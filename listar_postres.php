<?php
require_once 'JsonHelper.php';
header('Content-Type: application/json; charset=UTF-8');

try {
    $jsonHelper = new JsonHelper('./data/');
    $postres    = $jsonHelper->getAll('postresitos');

    if (isset($_GET['tienda']) && $_GET['tienda'] === '1') {
        $postres = array_values(array_filter($postres, function ($p) {
            return !isset($p['activo']) || $p['activo'] === true || $p['activo'] === 1 || $p['activo'] === '1';
        }));
    }

    echo json_encode($postres);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al listar postres']);
}
