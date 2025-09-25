<?php
// ofertas_logic.php
require_once 'JsonHelper.php';

// 1. Conectar y leer todos los postres
$jsonHelper = new JsonHelper('./data/');
$postres = $jsonHelper->getAll('postresitos');

if (empty($postres)) {
    throw new Exception("Error al leer postresitos");
}

// 2. Si hay al menos uno, barajar y quedarse con hasta 3
shuffle($postres);
$ofertas = array_slice($postres, 0, min(3, count($postres)));

// 3. Aplicar un 20% de descuento
foreach ($ofertas as &$p) {
    $p['old_price'] = $p['precio'];
    $p['new_price'] = round($p['precio'] * 0.8, 2);
}
unset($p);

// Ahora tenemos disponible en este archivo el array $ofertas