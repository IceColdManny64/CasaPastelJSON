<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Timeout de sesión: 2 horas de inactividad
if (!empty($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > 7200)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['ultima_actividad'] = time();

function requiereAdmin(): void {
    if (($_SESSION['rol'] ?? '') !== 'admin') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Acceso restringido a administradores.']);
        exit;
    }
}

// ESTA ES LA FUNCIÓN CLAVE ACTUALIZADA: Deja pasar a todo el staff válido
function requierePersonal(): void {
    $rol = $_SESSION['rol'] ?? '';
    if (!in_array($rol, ['admin', 'gerente', 'vendedor'])) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Sesión no válida o sin permisos.']);
        exit;
    }
}

function requiereVendedorOAdmin(): void {
    $rol = $_SESSION['rol'] ?? '';
    if (!in_array($rol, ['admin', 'vendedor'])) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Acceso solo para Ventas o Admin.']);
        exit;
    }
}

function requiereGerenteOAdmin(): void {
    $rol = $_SESSION['rol'] ?? '';
    if (!in_array($rol, ['admin', 'gerente'])) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Acceso solo para Gerencia o Admin.']);
        exit;
    }
}

function getRol(): string {
    return $_SESSION['rol'] ?? '';
}

/** API solo para usuarios del e-commerce con sesión válida */
function requiereCliente(): void {
    if (($_SESSION['rol'] ?? '') !== 'cliente' || empty($_SESSION['usuario_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Debes iniciar sesión como cliente.']);
        exit;
    }
}
?>