<?php
/**
 * Alias del listado de auditoría (el panel solicita este nombre de archivo).
 */
require_once __DIR__ . '/JsonHelper.php';
require_once __DIR__ . '/verificar_sesion.php';
requierePersonal();
header('Content-Type: application/json');
$jh = new JsonHelper('./data/');
echo json_encode($jh->getAll('auditoria'));
