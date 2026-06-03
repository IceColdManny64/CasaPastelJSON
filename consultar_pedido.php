<?php
require_once 'JsonHelper.php';
header('Content-Type: application/json; charset=UTF-8');

$folio = trim($_GET['folio'] ?? '');
if ($folio === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Folio requerido']);
    exit;
}

$jh      = new JsonHelper('./data/');
$pedidos = $jh->getAll('pedidos');
$found   = null;
foreach ($pedidos as $p) {
    $f = $p['folio'] ?? $p['numero_pedido'] ?? '';
    if ($f !== '' && strcasecmp($f, $folio) === 0) {
        $found = $p;
        break;
    }
}

if (!$found) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'msg' => 'Pedido no encontrado']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Requiere sesión de cliente autenticado
if (empty($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'cliente') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Inicia sesión para consultar tu pedido.']);
    exit;
}

// Solo puede ver sus propios pedidos
$uid = (int) $_SESSION['usuario_id'];
if (!empty($found['usuario_id']) && (int) $found['usuario_id'] !== $uid) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No tienes acceso a este pedido.']);
    exit;
}

echo json_encode(['ok' => true, 'pedido' => $found]);