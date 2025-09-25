<?php
require_once 'JsonHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    echo "ID inválido.";
    exit;
}

try {
    $jsonHelper = new JsonHelper('./data/');
    $result = $jsonHelper->delete('postresitos', $id);
    
    if ($result) {
        echo "Postre eliminado exitosamente.";
    } else {
        echo "Error al eliminar el postre.";
    }
} catch (Exception $e) {
    echo "Error en el servidor.";
}