<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Soporta tanto form-data (panel admin) como JSON
$isJson  = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
if ($isJson) {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $id        = intval($data['id']   ?? 0);
    $correo    = $data['correo']      ?? '';
    $pass      = $data['pass']        ?? '';
    $tipoCuenta= $data['tipo_cuenta'] ?? '';
} else {
    $id        = intval($_POST['id']         ?? 0);
    $correo    = $_POST['correo']            ?? '';
    $pass      = $_POST['pass']              ?? '';
    $tipoCuenta= $_POST['tipo_cuenta']       ?? '';
}

$jh     = new JsonHelper('./data/');
$update = [];
if ($correo)    $update['correo']     = $correo;
if ($pass)      $update['pass']       = $pass;
if ($tipoCuenta && in_array($tipoCuenta, ['cliente','frecuente','vip'])) {
    $update['tipo_cuenta'] = $tipoCuenta;
}

if (!$id || empty($update)) {
    header('Content-Type: application/json');
    echo json_encode(['ok'=>false,'msg'=>'Datos insuficientes.']);
    exit;
}

$result = $jh->update('usuarios', $id, $update);

// Si se cambia tipo_cuenta, sincronizar etiqueta en crm_clientes
if ($result && $tipoCuenta) {
    $mapa   = ['vip'=>'vip','frecuente'=>'frecuente','cliente'=>'nuevo'];
    $crm    = $jh->getAll('crm_clientes');
    foreach ($crm as $c) {
        if ((int)($c['usuario_id'] ?? 0) === $id) {
            $jh->update('crm_clientes', $c['id'], ['etiqueta' => $mapa[$tipoCuenta] ?? 'nuevo']);
            break;
        }
    }
}

header('Content-Type: application/json');
if ($result) {
    echo json_encode(['ok'=>true,'msg'=>'Usuario actualizado correctamente.']);
} else {
    echo json_encode(['ok'=>false,'msg'=>'Error al actualizar usuario.']);
}