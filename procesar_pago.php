<?php
require_once 'JsonHelper.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !is_array($data)) {
    http_response_code(400);
    echo "Datos no válidos.";
    exit;
}

$jsonHelper = new JsonHelper('./data/');
$error = false;

foreach ($data as $item) {
    $id = intval($item['id']);
    $cantidad = intval($item['cantidad']);

    // Buscar el postre actual
    $postre = $jsonHelper->findById('postresitos', $id);
    
    if (!$postre) continue;

    $stockActual = intval($postre['stock']);

    if ($stockActual >= $cantidad) {
        $nuevoStock = $stockActual - $cantidad;
        // Actualizar el stock
        $jsonHelper->update('postresitos', $id, ['stock' => $nuevoStock]);
    } else {
        $error = true;
    }
}

if ($error) {
    echo "Pago realizado parcialmente. Algunos productos no tenían suficiente stock.";
} else {
    echo "¡Pago exitoso! Gracias por tu compra.";
}