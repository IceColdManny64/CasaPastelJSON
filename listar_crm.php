<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
requierePersonal();
header('Content-Type: application/json');

$jh       = new JsonHelper('./data/');
$usuarios = $jh->getAll('usuarios');
$pedidos  = $jh->getAll('pedidos');
$crm      = $jh->getAll('crm_clientes');
$cupones = $jh->getAll('cupones');

$resultado = array_map(function($u) use ($pedidos, $crm, $cupones) {
    $misP  = array_filter($pedidos, fn($p) =>
        ($p['correo'] ?? '') === $u['correo'] && ($p['estado'] ?? '') !== 'Cancelado'
    );
    $total = array_reduce(array_values($misP), fn($s,$p) => $s + floatval($p['total']??0), 0);
    $n     = count($misP);

    // Buscar registro CRM por correo o usuario_id
    $crmc = null;
    foreach ($crm as $c) {
        if (($c['email'] ?? '') === $u['correo'] || ($c['usuario_id'] ?? 0) == $u['id']) {
            $crmc = $c;
            break;
        }
    }

    // Etiqueta: primero desde CRM, si no calcular
    $etiq = $crmc['etiqueta'] ?? ($n >= 3 ? 'frecuente' : ($total > 500 ? 'vip' : 'nuevo'));

    // tipo_cuenta: SIEMPRE desde usuarios.json (es la fuente de verdad)
    $tipoCuenta = $u['tipo_cuenta'] ?? 'cliente';

    return [
        'id'           => $crmc['id'] ?? $u['id'],
        'crm_id'       => $crmc['id'] ?? null,
        'usuario_id'   => $u['id'],
        'correo'       => $u['correo'],
        'nombre'       => $u['nombre'] ?? '',
        'total_pedidos'=> $n,
        'ltv'          => round($total, 2),
        'etiqueta'     => $etiq,
        'tipo_cuenta'  => $tipoCuenta,
        'cupones' => array_values(array_filter($cupones, function($c) use ($u) {
    return intval($c['usuario_id']) === intval($u['id']);
})),
    ];
}, $usuarios);

echo json_encode(array_values($resultado));