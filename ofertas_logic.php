<?php
// ofertas_logic.php
require_once 'JsonHelper.php';

$ofertas = []; // Inicializamos array vacío por seguridad

try {
    // 1. Conectar y leer todos los postres
    $jsonHelper = new JsonHelper('./data/');
    $postres = $jsonHelper->getAll('postresitos');

    if (!empty($postres)) {
        // 2. Si hay al menos uno, barajar y quedarse con hasta 3
        shuffle($postres);
        $ofertas = array_slice($postres, 0, min(3, count($postres)));

        // 3. Aplicar un 20% de descuento
        foreach ($ofertas as &$p) {
            $p['old_price'] = $p['precio'];
            $p['new_price'] = round($p['precio'] * 0.8, 2);
        }
        unset($p);
    }
} catch (Exception $e) {
    // En lugar de romper la página con un error fatal, 
    // capturamos el error y dejamos el array de ofertas vacío.
    // La página mostrará "No hay ofertas disponibles" en lugar de un error de código.
    $ofertas = [];
}
?>