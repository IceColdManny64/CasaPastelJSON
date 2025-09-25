<?php
require_once 'JsonHelper.php';
header('Content-Type: application/json; charset=UTF-8');

if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el parámetro id']);
    exit;
}

$id = intval($_GET['id']);

try {
    $jsonHelper = new JsonHelper('./data/');
    $postre = $jsonHelper->findById('postresitos', $id);

    if (!$postre) {
        http_response_code(404);
        echo json_encode(['error' => 'Postre no encontrado']);
    } else {
        echo json_encode($postre);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar el postre']);
}