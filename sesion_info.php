<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/JsonHelper.php';

$modulosPorRol = [
    'admin'    => ['productos','agregar_postre','usuarios','metricas','pedidos','inventario','produccion','crm','compras','finanzas','rrhh','admins','auditoria'],
    'empleado' => ['productos','metricas','pedidos'],
    'crm'      => ['productos','pedidos','crm','auditoria'],
    'scm'      => ['productos','pedidos','inventario','produccion'],
    'gerente'  => ['productos','usuarios','metricas','pedidos','inventario','finanzas','auditoria'],
    'rrhh'     => ['rrhh'],
];

$out = [
    'rol'         => $_SESSION['rol'] ?? '',
    'nombre'      => $_SESSION['nombre'] ?? '',
    'activo'      => !empty($_SESSION['rol']),
    'autenticado' => !empty($_SESSION['rol']),
    'modulos'     => $modulosPorRol[$_SESSION['rol'] ?? ''] ?? [],
    'usuario_id'  => $_SESSION['usuario_id'] ?? null,
    'correo'      => '',
    'telefono'    => '',
    'direccion'   => '',
    'tipo_cuenta' => '',
    'etiqueta_crm'=> 'cliente',
];

if (!empty($_SESSION['usuario_id']) && ($_SESSION['rol'] ?? '') === 'cliente') {
    $jh = new JsonHelper('./data/');
    $u  = $jh->findById('usuarios', (int) $_SESSION['usuario_id']);
    if ($u) {
        $out['correo']       = $u['correo'] ?? '';
        $out['telefono']     = $u['telefono'] ?? '';
        $out['direccion']    = $u['direccion'] ?? '';
        $out['tipo_cuenta']  = $u['tipo_cuenta'] ?? 'cliente';
        $out['nombre']       = $u['nombre'] ?? ($_SESSION['nombre'] ?? $out['correo']);
        $crm                 = $jh->getAll('crm_clientes');
        foreach ($crm as $c) {
            if (isset($c['usuario_id']) && (int) $c['usuario_id'] === (int) $_SESSION['usuario_id']) {
                $out['etiqueta_crm'] = $c['etiqueta'] ?? 'nuevo';
                break;
            }
        }
    }
}

echo json_encode($out);
