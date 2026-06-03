<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
require_once 'crud_auditoria.php';
header('Content-Type: application/json');

$jh     = new JsonHelper('./data/');
$method = $_SERVER['REQUEST_METHOD'];
$data   = json_decode(file_get_contents('php://input'), true) ?? [];

// GET: obtener cupones (admin/crm ve todos; cliente ve los suyos)
if ($method === 'GET') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $rol = $_SESSION['rol'] ?? '';
    $uid = $_SESSION['usuario_id'] ?? null;
    $cupones = $jh->getAll('cupones');

    if ($rol === 'cliente' && $uid) {
        // El cliente solo ve cupones asignados a él y que no hayan expirado
        $hoy = date('Y-m-d');
        $cupones = array_values(array_filter($cupones, function($c) use ($uid, $hoy) {
            $esDelCliente = empty($c['usuario_id']) || (int)$c['usuario_id'] === (int)$uid;
            $vigente      = empty($c['fecha_expiracion']) || $c['fecha_expiracion'] >= $hoy;
            $disponible   = ($c['usado'] ?? false) == false;
            return $esDelCliente && $vigente && $disponible;
        }));
        echo json_encode($cupones); exit;
    }

    // Personal del panel: todos
    requierePersonal();
    echo json_encode($cupones);
    exit;
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'msg' => 'Debes iniciar sesión'
    ]);
    exit;
}

// POST: crear cupón (solo CRM/admin) para un cliente específico
if ($method === 'POST') {
    $cupones = $jh->getAll('cupones');

if (!is_array($cupones)) {
    $cupones = [];
}
$input = json_decode(file_get_contents('php://input'), true);

if (
    isset($input['accion']) &&
    $input['accion'] === 'rescate'
) {

    if (empty($_SESSION['usuario_id'])) {

        http_response_code(401);

        echo json_encode([
            'ok' => false,
            'msg' => 'Debes iniciar sesión'
        ]);

        exit;
    }

    $usuarioId = intval($_SESSION['usuario_id']);

    /*
    |--------------------------------------------------------------------------
    | EVITAR CUPONES DUPLICADOS ACTIVOS
    |--------------------------------------------------------------------------
    */

    foreach ($cupones as $c) {

        if (
            intval($c['usuario_id']) === $usuarioId &&
            ($c['motivo'] ?? '') === 'abandono_carrito' &&
            ($c['estado'] ?? '') === 'activo'
        ) {

            echo json_encode([
                'ok' => false,
                'msg' => 'Ya tienes un cupón activo'
            ]);

            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR CUPÓN
    |--------------------------------------------------------------------------
    */

    $codigo = 'PASTEL-' . strtoupper(substr(md5(uniqid()), 0, 6));

    $nuevo = [
        'id' => time(),
        'usuario_id' => $usuarioId,
        'codigo' => $codigo,
        'descripcion' => 'Cupón de recuperación de carrito',
        'tipo' => 'descuento_porcentaje',
        'valor' => 10,
        'fecha_expiracion' => date('Y-m-d', strtotime('+7 days')),
        'estado' => 'activo',
        'usos_restantes' => 1,
        'motivo' => 'abandono_carrito',
        'fecha_creacion' => date('Y-m-d H:i:s')
    ];

$cupones[] = $nuevo;

$jh->writeData('cupones', $cupones);

echo json_encode([
    'ok' => true,
    'codigo' => $codigo
]);

    exit;
}
    
}

// DELETE: eliminar cupón
if ($method === 'DELETE') {
    requiereCRM();
    if (empty($data['id'])) { http_response_code(400); echo json_encode(['ok'=>false]); exit; }
    $ok = $jh->delete('cupones', intval($data['id']));
    echo json_encode(['ok'=>$ok]);
    exit;
}

http_response_code(405);
echo json_encode(['ok'=>false,'msg'=>'Método no permitido']);