<?php
session_start();
require_once 'JsonHelper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];

    $jsonHelper = new JsonHelper('./data/');
    $user = $jsonHelper->authenticateUser('usuarios', 'correo', 'pass', $correo, $contrasena);

    if ($user) {
        $_SESSION['rol'] = 'cliente';
        $_SESSION['usuario'] = $correo;
        header("Location: PantallaPrincipal.html");
        exit;
    }

    // --- CAMBIO CLAVE AQUÍ ---
    // En lugar de imprimir un script con alert(), redirigimos con un parámetro de error.
    header("Location: login.html?error=1");
    exit;
}
?>