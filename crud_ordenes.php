<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
require_once 'crud_auditoria.php';

// 1. PERMISO GENERAL DE LECTURA
// Dejamos pasar a Admin, Vendedor y Gerente para que el GET funcione
// y el Gerente pueda ver Finanzas sin que se rompa.
requierePersonal(); 
header('Content-Type: application/json');

$jh     = new JsonHelper('./data/');
$method = $_SERVER['REQUEST_METHOD'];
$data   = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'GET') {
    echo json_encode($jh->getAll('ordenes_compra'));

} elseif ($method === 'POST') {
    // 2. BLOQUEO ESTRICTO DE ESCRITURA
    // El Gerente no debe poder crear órdenes. (Según Tabla 6, esto incluso podría ser requiereAdmin() si solo él aprueba).
    requiereVendedorOAdmin();
    
    if (empty($data['proveedor']) || empty($data['producto'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Proveedor y descripción de artículo requeridos']);
        exit;
    }
    $tipoLinea = ($data['tipo_linea'] ?? 'producto') === 'insumo' ? 'insumo' : 'producto';
    $cant      = ($tipoLinea === 'insumo')
        ? (float) ($data['cantidad'] ?? 0)
        : intval($data['cantidad'] ?? 0);
    $costo     = floatval($data['costo_unitario'] ?? 0);
    if ($cant <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Cantidad inválida']);
        exit;
    }

    $orden = [
        'folio'           => 'OC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)),
        'fecha'           => date('Y-m-d H:i:s'),
        'proveedor'       => $data['proveedor'],
        'producto'        => $data['producto'],
        'tipo_linea'      => $tipoLinea,
        'producto_id'     => intval($data['producto_id'] ?? 0),
        'insumo_id'       => intval($data['insumo_id'] ?? 0),
        'cantidad'        => $cant,
        'costo_unitario'  => $costo,
        'total'           => round($cant * $costo, 2),
        'estado'          => 'Pendiente',
    ];
    $creada = $jh->create('ordenes_compra', $orden);
    registrarAuditoria('Compras', 'orden.crear', 'OC creada: ' . ($orden['folio'] ?? ''), $orden);
    echo json_encode(['ok' => (bool) $creada, 'orden' => $creada]);

} elseif ($method === 'PUT') {
    // BLOQUEO ESTRICTO: Solo Admin y Vendedor pueden recibir mercancía
    requiereVendedorOAdmin();
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['ok' => false]);
        exit;
    }
    $orden = $jh->findById('ordenes_compra', intval($data['id']));
    if (!$orden) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Orden no encontrada']);
        exit;
    }
    if ($orden['estado'] === 'Recibida') {
        echo json_encode(['ok' => false, 'msg' => 'Esta orden ya fue recibida']);
        exit;
    }

    $jh->update('ordenes_compra', intval($data['id']), ['estado' => 'Recibida']);

    $tipo = ($orden['tipo_linea'] ?? 'producto') === 'insumo' ? 'insumo' : 'producto';
    $ref  = $orden['folio'] ?? '';

    if ($tipo === 'insumo') {
        $iid = intval($orden['insumo_id'] ?? 0);
        if ($iid > 0) {
            $insumo = $jh->findById('insumos', $iid);
            if ($insumo) {
                $cant = floatval($orden['cantidad']);
                $nuevo = floatval($insumo['stock']) + $cant;
                $jh->update('insumos', $iid, ['stock' => $nuevo]);
                $jh->create('movimientos', [
                    'fecha'        => date('Y-m-d H:i:s'),
                    'tipo'         => 'entrada',
                    'producto_id'  => 0,
                    'insumo_id'    => $iid,
                    'producto'     => $insumo['nombre'] ?? '',
                    'cantidad'     => $cant,
                    'referencia'   => $ref,
                    'motivo'       => 'Compra a proveedor (insumo)',
                ]);
            }
        }
    } else {
        $pid = intval($orden['producto_id'] ?? 0);
        if ($pid > 0) {
            $postre = $jh->findById('postresitos', $pid);
            if ($postre) {
                $jh->update('postresitos', $pid, ['stock' => intval($postre['stock']) + intval($orden['cantidad'])]);
                $jh->create('movimientos', [
                    'fecha'        => date('Y-m-d H:i:s'),
                    'tipo'         => 'entrada',
                    'producto_id'  => $pid,
                    'insumo_id'    => 0,
                    'producto'     => $postre['titulo'] ?? '',
                    'cantidad'     => intval($orden['cantidad']),
                    'referencia'   => $ref,
                    'motivo'       => 'Compra a proveedor',
                ]);
            }
        }
    }

    if (floatval($orden['total'] ?? 0) > 0) {
        $jh->create('finanzas', [
            'fecha'            => date('Y-m-d H:i:s'),
            'tipo'             => 'egreso',
            'concepto'         => 'Orden de compra ' . ($orden['folio'] ?? ''),
            'referencia_id'    => $orden['folio'] ?? '',
            'referencia_tipo'  => 'orden_compra',
            'monto'            => floatval($orden['total']),
            'metodo_pago'      => 'transferencia',
        ]);
    }

    registrarAuditoria('Compras', 'orden.recibir', 'Recepción OC: ' . ($orden['folio'] ?? ''), ['id' => (int) $data['id']]);
    echo json_encode(['ok' => true]);

} elseif ($method === 'DELETE') {
    // BLOQUEO ESTRICTO: El Gerente no puede borrar órdenes
    requiereVendedorOAdmin();
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['ok' => false]);
        exit;
    }
    $orden = $jh->findById('ordenes_compra', intval($data['id']));
    if ($orden && $orden['estado'] === 'Recibida') {
        echo json_encode(['ok' => false, 'msg' => 'No se puede eliminar una orden ya recibida']);
        exit;
    }
    $ok = $jh->delete('ordenes_compra', intval($data['id']));
    echo json_encode(['ok' => $ok]);
}
