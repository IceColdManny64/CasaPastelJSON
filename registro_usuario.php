<?php
require_once 'JsonHelper.php';
require_once 'crud_auditoria.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$correo    = trim($_POST['correo'] ?? '');
$password  = trim($_POST['password'] ?? '');
$nombre    = trim($_POST['nombre'] ?? '');
$telefono  = trim($_POST['telefono'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');

if ($correo === '' || $password === '' || $nombre === '') {
    header('Location: registroUsuario.html?error=empty');
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header('Location: registroUsuario.html?error=invalid_email');
    exit;
}

$jh = new JsonHelper('./data/');

if ($jh->emailExists($correo)) {
    header('Location: registroUsuario.html?error=exists');
    exit;
}

// Usar password_hash para guardar la contraseña de forma segura
$newUser = [
    'correo'      => $correo,
    'pass'        => password_hash($password, PASSWORD_BCRYPT),
    'nombre'      => $nombre,
    'telefono'    => $telefono,
    'direccion'   => $direccion,
    'tipo_cuenta' => 'cliente',
];

$creado = $jh->create('usuarios', $newUser);
if (!$creado || empty($creado['id'])) {
    header('Location: registroUsuario.html?error=server');
    exit;
}

$uid = (int) $creado['id'];

$jh->create('crm_clientes', [
    'usuario_id'     => $uid,
    'email'          => $correo,
    'nombre'         => $nombre,
    'etiqueta'       => 'nuevo',
    'total_compras'  => 0,
    'ltv'            => 0,
    'fecha_registro' => date('Y-m-d H:i:s'),
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
registrarAuditoria('CRM', 'cliente.registro', "Nuevo cliente registrado: {$correo}", ['usuario_id' => $uid]);

header('Location: registroUsuario.html?registro=exito');
exit;
