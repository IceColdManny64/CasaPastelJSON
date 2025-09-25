<?php
require_once 'JsonHelper.php';

$jsonHelper = new JsonHelper('./data/');
$users = $jsonHelper->getAll('usuarios');

header('Content-Type: application/json');
echo json_encode($users);