<?php
require_once 'JsonHelper.php';

$id = intval($_POST['id'] ?? 0);
$correo = $_POST['correo'] ?? '';
$pass   = $_POST['pass']   ?? '';

$jsonHelper = new JsonHelper('./data/');

$updateData = [
    'correo' => $correo,
    'pass' => $pass
];

$result = $jsonHelper->update('usuarios', $id, $updateData);

if ($result) {
  $resp = ['ok'=>true, 'msg'=>'Usuario actualizado correctamente.'];
} else {
  $resp = ['ok'=>false,'msg'=>'Error al actualizar usuario.'];
}

header('Content-Type: application/json');
echo json_encode($resp);