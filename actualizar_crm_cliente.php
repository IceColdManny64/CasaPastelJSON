<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
requiereCRM();
header('Content-Type: application/json');

$jh   = new JsonHelper('./data/');
$data = json_decode(file_get_contents('php://input'), true) ?? [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['ok'=>false]); exit;
}

if (empty($data['id'])) {
    echo json_encode(['ok'=>false,'msg'=>'ID requerido']); exit;
}

$crmId = intval($data['id']);

// Campos que se pueden actualizar en crm_clientes
$camposCRM = array_intersect_key($data, array_flip(['etiqueta','nombre','telefono']));
$ok = $jh->update('crm_clientes', $crmId, $camposCRM);

// Buscar usuario_id desde el registro CRM para actualizar usuarios.json
$crmReg = $jh->findById('crm_clientes', $crmId);
$usuarioId = $crmReg['usuario_id'] ?? null;

// Si viene tipo_cuenta, actualizar también en usuarios.json
if (!empty($data['tipo_cuenta']) && $usuarioId) {
    $jh->update('usuarios', intval($usuarioId), ['tipo_cuenta' => $data['tipo_cuenta']]);
}

// Si viene etiqueta, sincronizar tipo_cuenta equivalente en usuarios.json
if (!empty($data['etiqueta']) && $usuarioId) {
    $mapa = ['vip' => 'vip', 'frecuente' => 'frecuente', 'nuevo' => 'cliente'];
    $tipoCuenta = $mapa[$data['etiqueta']] ?? 'cliente';
    $jh->update('usuarios', intval($usuarioId), ['tipo_cuenta' => $tipoCuenta]);
}

echo json_encode(['ok' => $ok]);