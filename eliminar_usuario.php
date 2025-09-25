<?php
require_once 'JsonHelper.php';

$id = intval($_POST['id'] ?? 0);
$jsonHelper = new JsonHelper('./data/');

$result = $jsonHelper->delete('usuarios', $id);

if ($result) {
  $resp = ['ok'=>true, 'msg'=>'Usuario eliminado correctamente.'];
} else {
  $resp = ['ok'=>false,'msg'=>'Error al eliminar usuario.'];
}

header('Content-Type: application/json');
echo json_encode($resp);