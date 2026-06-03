<?php
require_once 'JsonHelper.php';
require_once 'crud_auditoria.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

$titulo        = trim($_POST['titulo'] ?? '');
$descripcion   = trim($_POST['descripcion'] ?? '');
$precio        = floatval($_POST['precio'] ?? 0);
$categoria     = trim($_POST['categoria'] ?? '');
$tamanio       = trim($_POST['tamanio'] ?? '');
$sabor         = trim($_POST['sabor'] ?? '');
$imagen_url    = trim($_POST['imagen_url'] ?? '');
$stock         = intval($_POST['stock'] ?? 0);
$stock_minimo  = intval($_POST['stock_minimo'] ?? 5);
$activo        = !isset($_POST['activo']) || !in_array((string) ($_POST['activo'] ?? '1'), ['0', 'false'], true);

if (!$titulo || !$descripcion) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Título y descripción son obligatorios']);
    exit;
}

try {
    $jsonHelper = new JsonHelper('./data/');

    $newPostre = [
        'titulo'        => $titulo,
        'descripcion'   => $descripcion,
        'precio'        => $precio,
        'categoria'     => $categoria,
        'tamanio'       => $tamanio,
        'sabor'         => $sabor,
        'imagen_url'    => $imagen_url,
        'stock'         => $stock,
        'stock_minimo'  => $stock_minimo,
        'activo'        => $activo,
    ];

    $result = $jsonHelper->create('postresitos', $newPostre);

    if ($result) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        registrarAuditoria('Inventario', 'postre.crear', "Nuevo postre creado: {$titulo}", $newPostre);
        echo json_encode(['ok' => true, 'msg' => 'Postre creado exitosamente', 'postre' => $result]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'Error al crear el postre']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error en el servidor: ' . $e->getMessage()]);
}

