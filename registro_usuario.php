<?php
require_once 'JsonHelper.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo   = trim($_POST["correo"] ?? "");
    $password = trim($_POST["password"] ?? "");

    // Validar campos obligatorios
    if (empty($correo) || empty($password)) {
        echo "<script>
                alert('Correo y contraseña son obligatorios.');
                window.history.back();
              </script>";
        exit;
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo "<script>
                alert('Ingresa un correo electrónico válido.');
                window.history.back();
              </script>";
        exit;
    }

    $jsonHelper = new JsonHelper('./data/');

    // Verificar si el correo ya existe
    if ($jsonHelper->emailExists($correo)) {
        echo "<script>
                alert('Este correo ya está registrado.');
                window.history.back();
              </script>";
        exit;
    }

    // Insertar usuario nuevo
    $newUser = [
        'correo' => $correo,
        'pass' => $password
    ];

    $result = $jsonHelper->create('usuarios', $newUser);

    if ($result) {
        echo "<script>
                alert('Registro exitoso. Ahora puedes iniciar sesión.');
                window.location.href='loginUsuario.html';
              </script>";
    } else {
        echo "<script>
                alert('Error al registrar el usuario.');
                window.history.back();
              </script>";
    }

    exit;
}