<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
require_once 'crud_auditoria.php';
requierePersonal();
header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['id']) || !isset($data['estado'])) {
    http_response_code(400); echo json_encode(["ok" => false]); exit;
}
$estados = ["Recibido","En preparación","En camino","Entregado","Cancelado"];
if (!in_array($data['estado'], $estados)) {
    http_response_code(400); echo json_encode(["ok" => false, "msg" => "Estado inválido"]); exit;
}
$jh = new JsonHelper('./data/');
$pedido = $jh->findById('pedidos', intval($data['id']));
$ok = $jh->update('pedidos', intval($data['id']), ['estado' => $data['estado']]);
if ($ok && $pedido) {
    $folio = $pedido['folio'] ?? $pedido['numero_pedido'] ?? ('#' . $data['id']);
    registrarAuditoria('Ventas', 'pedido.estado', "Pedido {$folio} → {$data['estado']}", ['id' => (int) $data['id']]);
}
echo json_encode(["ok" => $ok]);