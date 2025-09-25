<?php
require_once 'JsonHelper.php';
header('Content-Type: application/json; charset=UTF-8');

try {
    $jsonHelper = new JsonHelper('./data/');
    $postres = $jsonHelper->getAll('postresitos');
    echo json_encode($postres);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al listar postres']);
}