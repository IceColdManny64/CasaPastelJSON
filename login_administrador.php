<?php
session_start();
require_once 'JsonHelper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    $jsonHelper = new JsonHelper('./data/');
    // Asegúrate de que 'admons' sea el nombre correcto de tu archivo/tabla en el JSON
    $admin = $jsonHelper->authenticateUser('admons', 'usuario', 'passw', $usuario, $contrasena);

    if ($admin) {
        $_SESSION['rol'] = 'admin';
        $_SESSION['usuario'] = $usuario;
        header("Location: panel.html");
        exit;
    }

    // --- CAMBIO CLAVE AQUÍ ---
    // En lugar de imprimir un script con alert(), redirigimos con un parámetro de error.
    header("Location: login_administrador.html?error=1");
    exit;
}
?>