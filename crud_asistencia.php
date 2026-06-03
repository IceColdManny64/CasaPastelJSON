<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
requierePersonal();
header('Content-Type: application/json');

$jh   = new JsonHelper('./data/');
$data = json_decode(file_get_contents("php://input"), true) ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($data['empleado_id'])) {
        http_response_code(400);
        echo json_encode(["ok" => false]);
        exit;
    }
    $registro = [
        "empleado_id" => intval($data['empleado_id']),
        "empleado"    => $data['empleado'] ?? '',
        "fecha"       => $data['fecha']    ?? date('Y-m-d H:i:s')
    ];
    $jh->create('asistencia', $registro);
    echo json_encode(["ok" => true]);
} else {
    http_response_code(405);
    echo json_encode(["ok" => false]);
}