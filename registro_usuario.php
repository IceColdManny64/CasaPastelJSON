<?php
require_once 'JsonHelper.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo   = trim($_POST["correo"] ?? "");
    $password = trim($_POST["password"] ?? "");

    // Validar campos obligatorios
    if (empty($correo) || empty($password)) {
        header("Location: registroUsuario.html?error=empty");
        exit;
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        header("Location: registroUsuario.html?error=invalid_email");
        exit;
    }

    $jsonHelper = new JsonHelper('./data/');

    // Verificar si el correo ya existe
    if ($jsonHelper->emailExists($correo)) {
        header("Location: registroUsuario.html?error=exists");
        exit;
    }

    // Insertar usuario nuevo
    $newUser = [
        'correo' => $correo,
        'pass' => $password
    ];

    $result = $jsonHelper->create('usuarios', $newUser);

    if ($result) {
        // Éxito: Redirigir al login (index.html) con mensaje de éxito
        // Asumiendo que index.html también tiene sistema de modales para leer ?msg=registered
// Ejemplo en registro_usuario.php al finalizar con éxito:
header("Location: registroUsuario.html?registro=exito");
exit();
    } else {
        header("Location: registroUsuario.html?error=server");
    }

    exit;
}
?>