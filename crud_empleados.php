<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
requierePersonal();
header('Content-Type: application/json');

$jh     = new JsonHelper('./data/');
$method = $_SERVER['REQUEST_METHOD'];
$data   = json_decode(file_get_contents("php://input"), true) ?? [];

if ($method === 'GET') {
    echo json_encode($jh->getAll('empleados'));

} elseif ($method === 'POST') {
    if (empty($data['nombre']) || empty($data['puesto'])) {
        http_response_code(400);
        echo json_encode(["ok" => false, "msg" => "Nombre y puesto son obligatorios"]);
        exit;
    }
    $nuevo = [
        "nombre"        => $data['nombre'],
        "puesto"        => $data['puesto'],
        "turno"         => $data['turno']         ?? "Mañana",
        "correo"        => $data['correo']         ?? "",
        "telefono"      => $data['telefono']       ?? "",
        "fecha_ingreso" => $data['fecha_ingreso']  ?? date('Y-m-d'),
        "activo"        => true,
        'salario_base'  => floatval($data['salario_base'] ?? 0) // <--- ¡AÑADE ESTA LÍNEA!
    ];
    $creado = $jh->create('empleados', $nuevo);
    echo json_encode(["ok" => (bool)$creado, "empleado" => $creado]);

} elseif ($method === 'PUT') {
    if (empty($data['id'])) {
        http_response_code(400); echo json_encode(["ok" => false]); exit;
    }
    $campos = array_intersect_key($data,
        array_flip(['nombre','puesto','turno','correo','telefono','fecha_ingreso','salario_base','activo'])
    );
    $ok = $jh->update('empleados', intval($data['id']), $campos);
    echo json_encode(["ok" => $ok]);

} elseif ($method === 'DELETE') {
    if (empty($data['id'])) {
        http_response_code(400); echo json_encode(["ok" => false]); exit;
    }
    // Baja lógica: marca como inactivo en lugar de borrar
    $ok = $jh->update('empleados', intval($data['id']), ['activo' => false]);
    echo json_encode(["ok" => $ok]);
}