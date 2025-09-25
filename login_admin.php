<?php
session_start();
require_once 'JsonHelper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    $jsonHelper = new JsonHelper('./data/');
    $admin = $jsonHelper->authenticateUser('admins', 'usuario', 'passw', $usuario, $contrasena);

    if ($admin) {
        $_SESSION['rol'] = 'admin';
        $_SESSION['usuario'] = $usuario;
        header("Location: panel.html");
        exit;
    }

    echo "<script>alert('Usuario o contraseña incorrectos'); window.history.back();</script>";
}
