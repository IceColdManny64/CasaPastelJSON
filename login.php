<?php
session_start();
require_once 'JsonHelper.php';
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if (empty($correo) || empty($contrasena)) {
        header("Location: login.html?error=1");
        exit;
    }

    $jsonHelper = new JsonHelper('./data/');
    $usuarios = $jsonHelper->getAll('usuarios');
    
    // Buscar usuario por correo y verificar contraseña
    $user = null;
    foreach ($usuarios as $u) {
        if (($u['correo'] ?? '') === $correo) {
            // Verificar contraseña (hash o texto plano para compatibilidad)
            $passField = $u['pass'] ?? '';
            if (password_verify($contrasena, $passField)) {
                $user = $u;
                break;
            } elseif ($passField === $contrasena) {
                // Fallback: permitir texto plano (será convertido en siguiente login)
                $user = $u;
                break;
            }
        }
    }

    if ($user) {
        $_SESSION['rol']        = 'cliente';
        $_SESSION['usuario']    = $correo;
        $_SESSION['usuario_id'] = (int) ($user['id'] ?? 0);
        $_SESSION['nombre']     = $user['nombre'] ?? $correo;
        header("Location: PantallaPrincipal.html");
        exit;
    }

    header("Location: login.html?error=1");
    exit;
}
?>