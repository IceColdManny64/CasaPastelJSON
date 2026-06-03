<?php
require_once 'JsonHelper.php';

function registrarAuditoria(string $modulo, string $accion, string $descripcion, $detalle = null): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $jh = new JsonHelper('./data/');
    $jh->create('auditoria', [
        'fecha'     => date('Y-m-d H:i:s'),
        'usuario'   => $_SESSION['nombre']   ?? $_SESSION['usuario'] ?? 'Sistema',
        'rol'       => $_SESSION['rol']       ?? 'sistema',
        'modulo'    => $modulo,
        'accion'    => $accion,
        'descripcion'=> $descripcion,
        'detalle'   => $detalle ? json_encode($detalle, JSON_UNESCAPED_UNICODE) : null,
        'ip'        => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
}