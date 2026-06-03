<?php

require_once 'JsonHelper.php';

header('Content-Type: application/json');

$jh = new JsonHelper('./data/');

$promos = $jh->getAll('promociones');

$hoy = date('Y-m-d');

$activas = array_values(array_filter($promos, function($p) use ($hoy) {

    if (!($p['activa'] ?? true)) {
        return false;
    }

    if (
        !empty($p['fecha_inicio']) &&
        $p['fecha_inicio'] > $hoy
    ) {
        return false;
    }

    if (
        !empty($p['fecha_fin']) &&
        $p['fecha_fin'] < $hoy
    ) {
        return false;
    }

    return true;
}));

echo json_encode($activas);