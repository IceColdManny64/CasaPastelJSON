<?php
// ofertas_logic.php
require_once 'JsonHelper.php';

$ofertas = [];

try {
    $jsonHelper = new JsonHelper('./data/');
    $postres    = $jsonHelper->getAll('postresitos');
    $proms      = $jsonHelper->getAll('promociones');
    $hoy        = date('Y-m-d');

    // Promociones activas y vigentes
    $promsActivas = array_filter($proms, function($pr) use ($hoy) {
        if (!($pr['activa'] ?? false)) return false;
        if (!empty($pr['fecha_inicio']) && $hoy < $pr['fecha_inicio']) return false;
        if (!empty($pr['fecha_fin'])    && $hoy > $pr['fecha_fin'])    return false;
        return true;
    });

    // Función para calcular el precio con promociones
    function aplicarPromocion($postre, $promsActivas) {
        $precio = (float)$postre['precio'];
        $best   = 0; // mayor descuento porcentual encontrado

        foreach ($promsActivas as $pr) {
            $aplica = false;
            if ($pr['aplica_a'] === 'todos') {
                $aplica = true;
            } elseif ($pr['aplica_a'] === 'categoria') {
                $aplica = strcasecmp($postre['categoria'] ?? '', $pr['referencia'] ?? '') === 0;
            } elseif ($pr['aplica_a'] === 'producto') {
                // referencia puede ser ID o título
                $aplica = ((string)($postre['id'] ?? '') === (string)($pr['referencia'] ?? ''))
                       || strcasecmp($postre['titulo'] ?? '', $pr['referencia'] ?? '') === 0;
            }
            if (!$aplica) continue;

            if ($pr['tipo'] === 'descuento_porcentaje') {
                $pct = (float)$pr['valor'];
                if ($pct > $best) $best = $pct;
            } elseif ($pr['tipo'] === 'precio_especial') {
                $pct = (1 - (float)$pr['valor'] / $precio) * 100;
                if ($pct > $best) $best = $pct;
            }
        }
        return $best;
    }

    // Productos FIJOS de oferta: IDs 1, 2, 3
    $idsOferta = [1, 2, 3];
    foreach ($postres as $p) {
        if (!in_array((int)$p['id'], $idsOferta, true)) continue;

        $pct = aplicarPromocion($p, $promsActivas);
        if ($pct <= 0) $pct = 20; // descuento base 20% si no hay promo activa

        $p['old_price']       = $p['precio'];
        $p['new_price']       = round($p['precio'] * (1 - $pct / 100), 2);
        $p['descuento_pct']   = $pct;
        $ofertas[] = $p;

        if (count($ofertas) === 3) break;
    }
} catch (Exception $e) {
    $ofertas = [];
}
?>