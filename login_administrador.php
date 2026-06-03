<?php
session_start();
require_once 'JsonHelper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario    = trim($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    if (empty($usuario) || empty($contrasena)) {
        header("Location: login_administrador.html?error=1");
        exit;
    }

    $jh    = new JsonHelper('./data/');
    $admin = $jh->authenticateUser('admons', 'usuario', 'passw', $usuario, $contrasena);

    if ($admin) {
        $_SESSION['rol']     = $admin['rol'] ?? 'empleado';
        $_SESSION['usuario'] = $usuario;
        $_SESSION['nombre']  = $admin['nombre'] ?? $usuario;
        $_SESSION['usuario_id'] = $admin['id'];
        $_SESSION['admin_id']= $admin['id'];
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:01 GMT');
        header("Location: panel.html?t=" . time());
        exit;
    }

    header("Location: login_administrador.html?error=1");
    exit;
}
?>