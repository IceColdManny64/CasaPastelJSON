<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
requierePersonal();
header('Content-Type: application/json');
$jh     = new JsonHelper('./data/');
$method = $_SERVER['REQUEST_METHOD'];
$data   = json_decode(file_get_contents("php://input"), true) ?? [];

if ($method === 'GET') {
    echo json_encode($jh->getAll('proveedores'));

} elseif ($method === 'POST') {
    if (empty($data['nombre'])) { http_response_code(400); echo json_encode(["ok"=>false,"msg"=>"Nombre requerido"]); exit; }
    $nuevo = [
        "nombre"          => $data['nombre'],
        "contacto"        => $data['contacto']        ?? "",
        "telefono"        => $data['telefono']        ?? "",
        "productos"       => $data['productos']       ?? "",
        "tiempo_entrega"  => $data['tiempo_entrega']  ?? "",
        "activo"          => true
    ];
    $creado = $jh->create('proveedores', $nuevo);
    echo json_encode(["ok" => (bool)$creado, "proveedor" => $creado]);

} elseif ($method === 'PUT') {
    if (empty($data['id'])) { http_response_code(400); echo json_encode(["ok"=>false]); exit; }
    $campos = array_intersect_key($data, array_flip(['nombre','contacto','telefono','productos','tiempo_entrega','activo']));
    $ok = $jh->update('proveedores', intval($data['id']), $campos);
    echo json_encode(["ok" => $ok]);

} elseif ($method === 'DELETE') {
    if (empty($data['id'])) { http_response_code(400); echo json_encode(["ok"=>false]); exit; }
    $ok = $jh->delete('proveedores', intval($data['id']));
    echo json_encode(["ok" => $ok]);
}