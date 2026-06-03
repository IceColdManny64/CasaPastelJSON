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
    echo json_encode($jh->getAll('recetas'));

} elseif ($method === 'POST') {
    // Crear receta
    if (empty($data['nombre']) || empty($data['postre_id']) || empty($data['ingredientes'])) {
        http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Nombre, postre e ingredientes requeridos']); exit;
    }
    $nueva = [
        'nombre'       => $data['nombre'],
        'postre_id'    => intval($data['postre_id']),
        'postre_nombre'=> $data['postre_nombre'] ?? '',
        'rinde'        => intval($data['rinde'] ?? 1),
        'ingredientes' => $data['ingredientes'] // [{insumo_id, insumo_nombre, cantidad, unidad}]
    ];
    $creada = $jh->create('recetas', $nueva);
    registrarAuditoria('SCM','receta.crear',"Receta creada: {$data['nombre']}", $nueva);
    echo json_encode(['ok'=>(bool)$creada, 'receta'=>$creada]);

} elseif ($method === 'DELETE') {
    if (empty($data['id'])) { http_response_code(400); echo json_encode(['ok'=>false]); exit; }
    $rec = $jh->findById('recetas', intval($data['id']));
    $ok  = $jh->delete('recetas', intval($data['id']));
    registrarAuditoria('SCM','receta.eliminar',"Receta eliminada: ".($rec['nombre']??''));
    echo json_encode(['ok'=>$ok]);
}