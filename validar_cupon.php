<?php
/**
 * Valida un cupón para el usuario en sesión y retorna el descuento.
 * NO marca como usado aquí; eso lo hace procesar_pago.php.
 */
require_once 'JsonHelper.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$codigo = strtoupper(trim($data['codigo'] ?? ''));
$uid    = $_SESSION['usuario_id'] ?? null;
$rol    = $_SESSION['rol'] ?? '';

if (!$uid || $rol !== 'cliente') {
    echo json_encode(['ok'=>false,'msg'=>'Debes iniciar sesión para usar cupones.']); exit;
}
if (!$codigo) {
    echo json_encode(['ok'=>false,'msg'=>'Código vacío.']); exit;
}

$jh = new JsonHelper('./data/');
$cupones = $jh->getAll('cupones');
$hoy = date('Y-m-d');

$encontrado = null;
foreach ($cupones as $c) {
    if (strtoupper($c['codigo']) === $codigo) {
        $encontrado = $c; break;
    }
}

if (!$encontrado) {
    echo json_encode(['ok'=>false,'msg'=>'Cupón no válido.']); exit;
}
if ($encontrado['usado'] ?? false) {
    echo json_encode(['ok'=>false,'msg'=>'Este cupón ya fue utilizado.']); exit;
}
if (!empty($encontrado['usuario_id']) && (int)$encontrado['usuario_id'] !== (int)$uid) {
    echo json_encode(['ok'=>false,'msg'=>'Este cupón no corresponde a tu cuenta.']); exit;
}
if (!empty($encontrado['fecha_expiracion']) && $encontrado['fecha_expiracion'] < $hoy) {
    echo json_encode(['ok'=>false,'msg'=>'Este cupón ha expirado.']); exit;
}

echo json_encode([
    'ok'          => true,
    'msg'         => 'Cupón válido: ' . ($encontrado['descripcion'] ?: $encontrado['codigo']),
    'tipo'        => $encontrado['tipo'],
    'valor'       => $encontrado['valor'],
    'codigo'      => $encontrado['codigo'],
    'id'          => $encontrado['id'],
]);