<?php
session_start();
// Guardar referencia de usuario antes de destruir
$uid = $_SESSION['usuario_id'] ?? null;

// Destruir sesión completamente
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Devolver el usuario_id para que el frontend limpie su carrito
header('Content-Type: application/json');
echo json_encode(['ok' => true, 'usuario_id' => $uid]);