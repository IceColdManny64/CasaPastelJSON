<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
require_once 'crud_auditoria.php';
requierePersonal();
header('Content-Type: application/json');

$jh     = new JsonHelper('./data/');
$method = $_SERVER['REQUEST_METHOD'];
$data   = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'GET') {
    echo json_encode($jh->getAll('insumos'));

} elseif ($method === 'POST') {
    if (empty($data['nombre']) || empty($data['unidad'])) {
        http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Nombre y unidad son obligatorios']); exit;
    }
    $nuevo = [
        'nombre'        => $data['nombre'],
        'unidad'        => $data['unidad'],
        'stock'         => floatval($data['stock'] ?? 0),
        'stock_minimo'  => floatval($data['stock_minimo'] ?? 5),
        'proveedor_id'  => !empty($data['proveedor_id']) ? intval($data['proveedor_id']) : null,
    ];
    $creado = $jh->create('insumos', $nuevo);
    registrarAuditoria('SCM','insumo.crear',"Insumo creado: {$data['nombre']}", $nuevo);
    echo json_encode(['ok'=>(bool)$creado, 'insumo'=>$creado]);

} elseif ($method === 'PUT') {
    if (empty($data['id'])) { http_response_code(400); echo json_encode(['ok'=>false]); exit; }
    $campos = array_intersect_key($data, array_flip(['nombre','unidad','stock','stock_minimo','proveedor_id']));
    // Ajuste manual de stock: registrar movimiento
    $insumo = $jh->findById('insumos', intval($data['id']));
    if ($insumo && isset($data['stock']) && $data['stock'] != $insumo['stock']) {
        $delta = floatval($data['stock']) - floatval($insumo['stock']);
        $tipo  = $delta > 0 ? 'entrada' : 'salida';
        $jh->create('movimientos', [
            'fecha'       => date('Y-m-d H:i:s'),
            'tipo'        => $tipo,
            'producto_id' => 0,
            'insumo_id'   => intval($data['id']),
            'producto'    => $insumo['nombre'],
            'cantidad'    => abs($delta),
            'referencia'  => 'ajuste_manual',
            'motivo'      => 'Ajuste manual de insumo'
        ]);
    }
    $ok = $jh->update('insumos', intval($data['id']), $campos);
    registrarAuditoria('SCM','insumo.editar',"Insumo editado ID:{$data['id']}", $campos);
    echo json_encode(['ok'=>$ok]);

} elseif ($method === 'DELETE') {
    if (empty($data['id'])) { http_response_code(400); echo json_encode(['ok'=>false]); exit; }
    $ins = $jh->findById('insumos', intval($data['id']));
    $ok  = $jh->delete('insumos', intval($data['id']));
    registrarAuditoria('SCM','insumo.eliminar',"Insumo eliminado: ".($ins['nombre']??''));
    echo json_encode(['ok'=>$ok]);
}