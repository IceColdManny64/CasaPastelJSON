<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
requiereGerenteOAdmin();

header('Content-Type: application/json');

$jh = new JsonHelper('./data/');
echo json_encode($jh->getAll('finanzas'));