<?php
require_once 'JsonHelper.php';

$id = intval($_GET['id'] ?? 0);
$jsonHelper = new JsonHelper('./data/');
$user = $jsonHelper->findById('usuarios', $id);

header('Content-Type: application/json');
echo json_encode($user ?: null);