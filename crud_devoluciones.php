<?php
require_once 'JsonHelper.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$jh     = new JsonHelper('./data/');
$method = $_SERVER['REQUEST_METHOD'];
$data   = json_decode(file_get_contents('php://input'), true) ?? [];

// GET: listar solicitudes
if ($method === 'GET') {
    $rol = $_SESSION['rol'] ?? '';
    $uid = $_SESSION['usuario_id'] ?? null;

    $solicitudes = $jh->getAll('solicitudes_devolucion');

    if ($rol === 'cliente' && $uid) {
        // El cliente solo ve las suyas
        $solicitudes = array_values(array_filter($solicitudes, fn($s) => (int)($s['usuario_id']??0) === (int)$uid));
    } elseif (!in_array($rol, ['admin','crm','gerente'])) {
        http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'Sin acceso']); exit;
    }
    echo json_encode($solicitudes); exit;
}

// POST: el cliente solicita devolución o cancelación
if ($method === 'POST') {
    $uid = $_SESSION['usuario_id'] ?? null;
    $rol = $_SESSION['rol'] ?? '';
    if (!$uid || $rol !== 'cliente') {
        http_response_code(401); echo json_encode(['ok'=>false,'msg'=>'Sesión requerida']); exit;
    }

    $folio  = trim($data['folio'] ?? '');
    $tipo   = $data['tipo'] ?? 'devolucion'; // 'devolucion' | 'cancelacion'
    $motivo = trim($data['motivo'] ?? '');

    if (!$folio) { echo json_encode(['ok'=>false,'msg'=>'Folio requerido']); exit; }

    // Verificar que el pedido pertenezca al cliente
    $pedidos = $jh->getAll('pedidos');
    $pedido  = null;
    foreach ($pedidos as $p) {
        if (($p['folio'] ?? '') === $folio || ($p['numero_pedido'] ?? '') === $folio) {
            $pedido = $p; break;
        }
    }
    if (!$pedido) { echo json_encode(['ok'=>false,'msg'=>'Pedido no encontrado']); exit; }
    if ((int)($pedido['usuario_id'] ?? 0) !== (int)$uid) {
        echo json_encode(['ok'=>false,'msg'=>'No puedes solicitar devolución de un pedido ajeno']); exit;
    }
    if ($pedido['estado'] === 'Cancelado') {
        echo json_encode(['ok'=>false,'msg'=>'El pedido ya fue cancelado']); exit;
    }

    // Verificar que no haya una solicitud pendiente para el mismo folio
    $existentes = $jh->getAll('solicitudes_devolucion');
    foreach ($existentes as $s) {
        if ($s['folio'] === $folio && in_array($s['estado_solicitud'] ?? '', ['pendiente','en_revision'])) {
            echo json_encode(['ok'=>false,'msg'=>'Ya existe una solicitud pendiente para este pedido']); exit;
        }
    }

    $nueva = [
        'folio'           => $folio,
        'usuario_id'      => (int)$uid,
        'cliente'         => $pedido['cliente'] ?? '',
        'correo'          => $pedido['correo'] ?? '',
        'tipo'            => $tipo,
        'motivo'          => $motivo ?: 'Sin motivo especificado',
        'monto_pedido'    => floatval($pedido['total'] ?? 0),
        'estado_solicitud'=> 'pendiente',
        'fecha_solicitud' => date('Y-m-d H:i:s'),
        'fecha_resolucion'=> null,
        'notas_admin'     => '',
    ];
    $creada = $jh->create('solicitudes_devolucion', $nueva);
    echo json_encode(['ok'=>(bool)$creada,'msg'=>'Solicitud enviada. Revisaremos en breve.']);
    exit;
}

// PUT: admin confirma o deniega
if ($method === 'PUT') {
    $rol = $_SESSION['rol'] ?? '';
    if (!in_array($rol, ['admin','crm','gerente'])) {
        http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'Sin acceso']); exit;
    }

    $id       = intval($data['id'] ?? 0);
    $decision = $data['decision'] ?? ''; // 'aprobada' | 'denegada'
    $notas    = trim($data['notas_admin'] ?? '');

    if (!$id || !in_array($decision, ['aprobada','denegada'])) {
        echo json_encode(['ok'=>false,'msg'=>'Datos inválidos']); exit;
    }

    $solicitud = $jh->findById('solicitudes_devolucion', $id);
    if (!$solicitud) { echo json_encode(['ok'=>false,'msg'=>'Solicitud no encontrada']); exit; }

    $jh->update('solicitudes_devolucion', $id, [
        'estado_solicitud' => $decision,
        'fecha_resolucion' => date('Y-m-d H:i:s'),
        'notas_admin'      => $notas,
    ]);

    if ($decision === 'aprobada') {
        $folio  = $solicitud['folio'];
        $monto  = floatval($solicitud['monto_pedido'] ?? 0);
        $tipo   = $solicitud['tipo'] ?? 'devolucion';

        // Cancelar/devolver el pedido
        $pedidos = $jh->getAll('pedidos');
        foreach ($pedidos as $p) {
            if (($p['folio'] ?? '') === $folio || ($p['numero_pedido'] ?? '') === $folio) {
                $jh->update('pedidos', $p['id'], ['estado' => 'Cancelado']);
                break;
            }
        }

        // Registrar egreso en finanzas
        $jh->create('finanzas', [
            'fecha'           => date('Y-m-d H:i:s'),
            'tipo'            => 'egreso',
            'concepto'        => ucfirst($tipo) . ' aprobada: ' . $folio,
            'referencia_id'   => $folio,
            'referencia_tipo' => 'devolucion',
            'monto'           => $monto,
            'metodo_pago'     => 'reembolso',
        ]);
    }

    echo json_encode(['ok'=>true,'msg'=>'Solicitud '.($decision==='aprobada'?'aprobada':'denegada').'.']);
    exit;
}

http_response_code(405);
echo json_encode(['ok'=>false,'msg'=>'Método no permitido']);