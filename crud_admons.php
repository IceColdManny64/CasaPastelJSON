<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
require_once 'crud_auditoria.php';
requierePersonal();
header('Content-Type: application/json');

$jh     = new JsonHelper('./data/');
$method = $_SERVER['REQUEST_METHOD'];
$data   = json_decode(file_get_contents('php://input'), true) ?? [];

// ROLES ACTUALIZADOS SEGÚN LA MATRIZ DE SEGURIDAD (Sección 10.2)
$rolesValidos = ['admin','gerente','vendedor'];

if ($method === 'GET') {
    $admons = $jh->getAll('admons');
    // No devolver contraseñas
    $admons = array_map(fn($a) => array_diff_key($a, ['passw'=>1]), $admons);
    echo json_encode(array_values($admons));

} elseif ($method === 'POST') {
    if (empty($data['usuario']) || empty($data['passw']) || empty($data['rol'])) {
        http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Usuario, contraseña y rol requeridos']); exit;
    }
    if (!in_array($data['rol'], $rolesValidos)) {
        http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Rol no válido']); exit;
    }
    // Verificar duplicado
    $todos = $jh->getAll('admons');
    foreach ($todos as $a) {
        if ($a['usuario'] === $data['usuario']) {
            echo json_encode(['ok'=>false,'msg'=>'Ese usuario ya existe']); exit;
        }
    }
    $nuevo = [
        'usuario' => $data['usuario'],
        'passw'   => $data['passw'],  // En producción: password_hash()
        'nombre'  => $data['nombre'] ?? $data['usuario'],
        'rol'     => $data['rol']
    ];
    $creado = $jh->create('admons', $nuevo);
    registrarAuditoria('Admin','admin.crear',"Usuario del panel creado: {$data['usuario']} (rol: {$data['rol']})");
    echo json_encode(['ok'=>(bool)$creado]);

} elseif ($method === 'PUT') {
    if (empty($data['id'])) { http_response_code(400); echo json_encode(['ok'=>false]); exit; }
    $campos = [];
    if (!empty($data['nombre'])) $campos['nombre'] = $data['nombre'];
    if (!empty($data['rol']) && in_array($data['rol'], $rolesValidos)) $campos['rol'] = $data['rol'];
    if (!empty($data['passw'])) $campos['passw'] = $data['passw'];
    $ok = $jh->update('admons', intval($data['id']), $campos);
    registrarAuditoria('Admin','admin.editar',"Usuario panel editado ID:{$data['id']}", $campos);
    echo json_encode(['ok'=>$ok]);

} elseif ($method === 'DELETE') {
    if (empty($data['id'])) { http_response_code(400); echo json_encode(['ok'=>false]); exit; }
    // No permitir eliminar al admin con id=1
    if (intval($data['id']) === 1) {
        echo json_encode(['ok'=>false,'msg'=>'No se puede eliminar el administrador principal']); exit;
    }
    $a  = $jh->findById('admons', intval($data['id']));
    $ok = $jh->delete('admons', intval($data['id']));
    registrarAuditoria('Admin','admin.eliminar',"Usuario panel eliminado: ".($a['usuario']??''));
    echo json_encode(['ok'=>$ok]);
}