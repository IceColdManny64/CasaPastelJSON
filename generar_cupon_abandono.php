<?php

header('Content-Type: application/json');

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

$usuarioId = intval($_SESSION['usuario_id']);

$archivo = 'data/cupones.json';

$cupones = json_decode(file_get_contents($archivo), true);

if (!is_array($cupones)) {
    $cupones = [];
}

/*
|--------------------------------------------------------------------------
| EVITAR DUPLICADOS ACTIVOS
|--------------------------------------------------------------------------
*/

$yaExiste = false;

foreach ($cupones as $c) {

    if (
        intval($c['usuario_id']) === $usuarioId &&
        $c['motivo'] === 'abandono_carrito' &&
        $c['estado'] === 'activo'
    ) {
        $yaExiste = true;
        break;
    }
}

if ($yaExiste) {

    echo json_encode([
        'ok' => false,
        'msg' => 'Ya tienes un cupón activo'
    ]);

    exit;
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
    'descripcion' => 'Cupón por productos eliminados del carrito',
    'tipo' => 'porcentaje',
    'valor' => 10,
    'fecha_expiracion' => date('Y-m-d', strtotime('+7 days')),
    'estado' => 'activo',
    'usos_restantes' => 1,
    'motivo' => 'abandono_carrito',
    'fecha_creacion' => date('Y-m-d H:i:s')
];

$cupones[] = $nuevo;

$jh->save('cupones', $cupones);
echo json_encode([
    'ok' => true,
    'msg' => 'Cupón generado',
    'cupon' => $nuevo
]);