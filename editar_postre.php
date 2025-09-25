<?php
require_once 'JsonHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id          = intval($_POST['id'] ?? 0);
$titulo      = $_POST['titulo']      ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$precio      = floatval($_POST['precio'] ?? 0);
$categoria   = $_POST['categoria']   ?? '';
$tamanio     = $_POST['tamanio']     ?? '';
$sabor       = $_POST['sabor']       ?? '';
$imagen_url  = $_POST['imagen_url']  ?? '';
$stock       = intval($_POST['stock'] ?? 0);

if (!$id || !$titulo || !$descripcion) {
    echo "Faltan datos para actualizar.";
    exit;
}

try {
    $jsonHelper = new JsonHelper('./data/');
    
    $updateData = [
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'precio' => $precio,
        'categoria' => $categoria,
        'tamanio' => $tamanio,
        'sabor' => $sabor,
        'imagen_url' => $imagen_url,
        'stock' => $stock
    ];

    $result = $jsonHelper->update('postresitos', $id, $updateData);
    
    if ($result) {
        echo "Postre actualizado exitosamente.";
    } else {
        echo "Error al actualizar el postre.";
    }
} catch (Exception $e) {
    echo "Error en el servidor.";
}