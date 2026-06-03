<?php
require_once 'JsonHelper.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['items']) || !is_array($data['items'])) {
    http_response_code(400);
    echo json_encode(["ok" => false, "msg" => "Datos no válidos."]);
    exit;
}

$jh    = new JsonHelper('./data/');
$items = $data['items'];
$folio = 'CP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

// 1. Verificar stock antes de tocar nada
foreach ($items as $item) {
    $postre = $jh->findById('postresitos', intval($item['id']));
    if (!$postre || intval($postre['stock']) < intval($item['cantidad'])) {
        http_response_code(409);
        echo json_encode(["ok" => false, "msg" => "Stock insuficiente: " . ($item['titulo'] ?? 'producto')]);
        exit;
    }
}

// 2. Calcular subtotal base
$subtotal = array_reduce($items, fn($s, $i) => $s + floatval($i['precio']) * intval($i['cantidad']), 0);

// 3. Aplicar promociones automáticas (las del CRM)
$uid        = $_SESSION['usuario_id'] ?? null;
$tipoCuenta = 'cliente';
$etiqueta   = 'nuevo';
if ($uid) {
    $uData = $jh->findById('usuarios', (int)$uid);
    $tipoCuenta = $uData['tipo_cuenta'] ?? 'cliente';
    $crmList    = $jh->getAll('crm_clientes');
    foreach ($crmList as $c) {
        if ((int)($c['usuario_id'] ?? 0) === (int)$uid) {
            $etiqueta = $c['etiqueta'] ?? 'nuevo'; break;
        }
    }
}

$descuentoPromo = 0;
$hoy = date('Y-m-d');
$promociones = $jh->getAll('promociones');
foreach ($promociones as $pr) {
    if (!($pr['activa'] ?? false)) continue;
    if (!empty($pr['fecha_inicio']) && $pr['fecha_inicio'] > $hoy) continue;
    if (!empty($pr['fecha_fin'])   && $pr['fecha_fin']   < $hoy) continue;

    // Verificar segmento de cliente
    $segCliente = $pr['clientes'] ?? 'todos';
    if ($segCliente !== 'todos') {
        if ($segCliente === 'vip'       && $etiqueta !== 'vip')       continue;
        if ($segCliente === 'frecuente' && !in_array($etiqueta, ['frecuente','vip'])) continue;
    }

    // Calcular base del descuento según aplica_a
    $aplicaA  = $pr['aplica_a'] ?? 'todos';
    $baseDesc = 0;
    if ($aplicaA === 'todos') {
        $baseDesc = $subtotal;
    } elseif ($aplicaA === 'categoria') {
        $cat = strtolower($pr['referencia'] ?? '');
        foreach ($items as $it) {
            $postre = $jh->findById('postresitos', intval($it['id']));
            if ($postre && strtolower($postre['categoria'] ?? '') === $cat) {
                $baseDesc += floatval($it['precio']) * intval($it['cantidad']);
            }
        }
    } elseif ($aplicaA === 'producto') {
        $ref = trim($pr['referencia'] ?? '');
        foreach ($items as $it) {
            if ((string)$it['id'] === $ref || strtolower($it['titulo'] ?? '') === strtolower($ref)) {
                $baseDesc += floatval($it['precio']) * intval($it['cantidad']);
            }
        }
    }

    if ($pr['tipo'] === 'descuento_porcentaje') {
        $descuentoPromo += $baseDesc * (floatval($pr['valor']) / 100);
    } elseif ($pr['tipo'] === 'precio_especial') {
        // precio_especial: se aplica como descuento del subtotal de la base
        $descuentoPromo += max(0, $baseDesc - floatval($pr['valor']));
    }
    // 2x1: se implementa como 50% en la base
    elseif ($pr['tipo'] === '2x1') {
        $descuentoPromo += $baseDesc * 0.5;
    }
}
$descuentoPromo = min($descuentoPromo, $subtotal);

// 4. Aplicar cupón específico del cliente
$descuentoCupon = 0;
$cuponId        = null;
$codigoCupon    = strtoupper(trim($data['cupon_codigo'] ?? ''));
if ($codigoCupon && $uid) {
    $cupones = $jh->getAll('cupones');
    foreach ($cupones as $c) {
        if (strtoupper($c['codigo']) === $codigoCupon) {
            // Validar
            $esSuyo   = empty($c['usuario_id']) || (int)$c['usuario_id'] === (int)$uid;
            $vigente  = empty($c['fecha_expiracion']) || $c['fecha_expiracion'] >= $hoy;
            $noUsado  = !($c['usado'] ?? false);
            if ($esSuyo && $vigente && $noUsado) {
                $cuponId = $c['id'];
                if ($c['tipo'] === 'descuento_porcentaje') {
                    $descuentoCupon = ($subtotal - $descuentoPromo) * (floatval($c['valor']) / 100);
                } else {
                    $descuentoCupon = floatval($c['valor']);
                }
            }
            break;
        }
    }
}

$totalDescuento = $descuentoPromo + $descuentoCupon;
$total          = max(0, $subtotal - $totalDescuento);

// 5. Descontar stock y registrar movimientos
foreach ($items as $item) {
    $postre     = $jh->findById('postresitos', intval($item['id']));
    $nuevoStock = intval($postre['stock']) - intval($item['cantidad']);
    $jh->update('postresitos', intval($item['id']), ['stock' => $nuevoStock]);
    $jh->create('movimientos', [
        "fecha"         => date('Y-m-d H:i:s'),
        "tipo"          => "salida",
        "producto_id"   => intval($item['id']),
        "producto"      => $item['titulo'] ?? '',
        "cantidad"      => intval($item['cantidad']),
        "referencia"    => $folio,
        "motivo"        => "Venta"
    ]);
}

// 6. Guardar pedido
$metodo = $data['metodo'] ?? 'tarjeta';
$pedido = [
    "folio"           => $folio,
    "numero_pedido"   => $folio,
    "fecha"           => date('Y-m-d H:i:s'),
    "usuario_id"      => $uid,
    "cliente"         => $data['cliente'] ?? 'Invitado',
    "correo"          => $data['correo']  ?? '',
    "direccion_envio" => $data['direccion'] ?? '',
    "metodo_pago"     => $metodo,
    "metodo"          => $metodo,
    "subtotal"        => round($subtotal, 2),
    "descuento_promo" => round($descuentoPromo, 2),
    "descuento_cupon" => round($descuentoCupon, 2),
    "cupon_codigo"    => $codigoCupon ?: null,
    "total"           => round($total, 2),
"estado" => (
    $metodo === 'atm'
        ? 'Esperando transferencia'
        : (
            $metodo === 'check'
                ? 'Cheque en validación'
                : 'Recibido'
        )
),
    "items"           => $items
];
$jh->create('pedidos', $pedido);

// 7. Registrar ingreso en finanzas
$jh->create('finanzas', [
    "fecha"           => date('Y-m-d H:i:s'),
    "tipo"            => "ingreso",
    "concepto"        => "Pedido " . $folio,
    "referencia_id"   => $folio,
    "referencia_tipo" => "pedido",
    "monto"           => round($total, 2),
    "metodo_pago"     => $metodo
]);

// 8. Marcar cupón como usado
if ($cuponId) {
    $jh->update('cupones', $cuponId, ['usado' => true, 'folio_pedido' => $folio]);
}

// 9. Sincronizar CRM
if ($uid && !empty($data['correo'])) {
    $existente = null;
    $crm = $jh->getAll('crm_clientes');
    foreach ($crm as $c) {
        $mailC = $c['email'] ?? $c['correo'] ?? '';
        if ($mailC === $data['correo'] || (int)($c['usuario_id'] ?? 0) === (int)$uid) {
            $existente = $c; break;
        }
    }
    if (!$existente) {
        $jh->create('crm_clientes', [
            "usuario_id"     => $uid,
            "email"          => $data['correo'],
            "nombre"         => $data['cliente'] ?? '',
            "etiqueta"       => "nuevo",
            "total_compras"  => 1,
            "ltv"            => round($total, 2),
            "fecha_registro" => date('Y-m-d H:i:s')
        ]);
    } else {
        $nuevasCompras = intval($existente['total_compras'] ?? 0) + 1;
        $nuevoLTV      = round(floatval($existente['ltv'] ?? 0) + $total, 2);
        $nuevaEtiqueta = $nuevasCompras >= 5 ? 'vip' : ($nuevasCompras >= 3 ? 'frecuente' : 'nuevo');
        $jh->update('crm_clientes', $existente['id'], [
            "total_compras" => $nuevasCompras,
            "ltv"           => $nuevoLTV,
            "etiqueta"      => $nuevaEtiqueta
        ]);
        // Sincronizar tipo_cuenta en usuarios si sube de nivel
        $mapa = ['vip'=>'vip','frecuente'=>'frecuente','nuevo'=>'cliente'];
        $jh->update('usuarios', (int)$uid, ['tipo_cuenta' => $mapa[$nuevaEtiqueta] ?? 'cliente']);
    }
}

require_once 'crud_auditoria.php';
registrarAuditoria('Ventas','pedido.confirmar',"Pedido: $folio \${$total} (desc promo:\${$descuentoPromo} cupón:\${$descuentoCupon})", ['folio'=>$folio,'items'=>count($items)]);

echo json_encode([
    "ok"              => true,
    "msg"             => "¡Pago exitoso!",
    "folio"           => $folio,
    "subtotal"        => round($subtotal, 2),
    "descuento_promo" => round($descuentoPromo, 2),
    "descuento_cupon" => round($descuentoCupon, 2),
    "total"           => round($total, 2),
]);