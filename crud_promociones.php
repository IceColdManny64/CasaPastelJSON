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
    echo json_encode($jh->getAll('promociones'));

} elseif ($method === 'POST') {
    if (empty($data['nombre']) || empty($data['tipo'])) {
        http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Nombre y tipo son obligatorios']); exit;
    }
    // tipos: descuento_porcentaje | precio_especial | 2x1
    $nueva = [
        'nombre'      => $data['nombre'],
        'tipo'        => $data['tipo'],
        'valor'       => floatval($data['valor'] ?? 0), // % o precio fijo
        'aplica_a'    => $data['aplica_a'] ?? 'todos',  // 'todos' | 'categoria' | 'producto'
        'referencia'  => $data['referencia'] ?? '',     // ID o nombre de categoría/producto
        'clientes'    => $data['clientes'] ?? 'todos',  // 'todos' | 'frecuente' | 'vip'
        'activa'      => true,
        'fecha_inicio'=> $data['fecha_inicio'] ?? date('Y-m-d'),
        'fecha_fin'   => $data['fecha_fin'] ?? null
    ];
    $creada = $jh->create('promociones', $nueva);
    registrarAuditoria('CRM','promo.crear',"Promoción creada: {$data['nombre']} (tipo: {$data['tipo']})", $nueva);
    echo json_encode(['ok'=>(bool)$creada, 'promo'=>$creada]);

} elseif ($method === 'PUT') {
    if (empty($data['id'])) { http_response_code(400); echo json_encode(['ok'=>false]); exit; }
    $campos = array_intersect_key($data, array_flip(['nombre','tipo','valor','aplica_a','referencia','clientes','activa','fecha_inicio','fecha_fin']));
    $ok = $jh->update('promociones', intval($data['id']), $campos);
    registrarAuditoria('CRM','promo.editar',"Promoción editada ID:{$data['id']}", $campos);
    echo json_encode(['ok'=>$ok]);

} elseif ($method === 'DELETE') {
    if (empty($data['id'])) { http_response_code(400); echo json_encode(['ok'=>false]); exit; }
    $pr = $jh->findById('promociones', intval($data['id']));
    $ok = $jh->delete('promociones', intval($data['id']));
    registrarAuditoria('CRM','promo.eliminar',"Promoción eliminada: ".($pr['nombre']??''));
    echo json_encode(['ok'=>$ok]);
}