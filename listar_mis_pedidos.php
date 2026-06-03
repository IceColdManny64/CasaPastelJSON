<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
requiereCliente();
header('Content-Type: application/json; charset=UTF-8');

$jh  = new JsonHelper('./data/');
$uid = (int) $_SESSION['usuario_id'];
$all = $jh->getAll('pedidos');
$mine = array_values(array_filter($all, function ($p) use ($uid) {
    return isset($p['usuario_id']) && (int) $p['usuario_id'] === $uid;
}));
usort($mine, function ($a, $b) {
    return strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
});
echo json_encode($mine);
