<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
require_once 'crud_auditoria.php';
requiereAdmin();
header('Content-Type: application/json');

$jh   = new JsonHelper('./data/');
$data = json_decode(file_get_contents('php://input'), true) ?? [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['ok'=>false]); exit;
}

if (empty($data['receta_id']) || empty($data['lotes'])) {
    http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Receta y número de lotes requeridos']); exit;
}

$receta = $jh->findById('recetas', intval($data['receta_id']));
if (!$receta) { http_response_code(404); echo json_encode(['ok'=>false,'msg'=>'Receta no encontrada']); exit; }

$lotes = intval($data['lotes']);

// 1. Verificar que hay suficiente de cada insumo
foreach ($receta['ingredientes'] as $ing) {
    $insumo    = $jh->findById('insumos', intval($ing['insumo_id']));
    $necesario = floatval($ing['cantidad']) * $lotes;
    if (!$insumo || floatval($insumo['stock']) < $necesario) {
        echo json_encode([
            'ok'  => false,
            'msg' => "Stock insuficiente de \"{$ing['insumo_nombre']}\". Necesario: {$necesario} {$ing['unidad']}, disponible: ".($insumo['stock']??0)." {$ing['unidad']}"
        ]);
        exit;
    }
}

// 2. Descontar insumos
foreach ($receta['ingredientes'] as $ing) {
    $insumo    = $jh->findById('insumos', intval($ing['insumo_id']));
    $necesario = floatval($ing['cantidad']) * $lotes;
    $nuevo     = round(floatval($insumo['stock']) - $necesario, 4);
    $jh->update('insumos', intval($ing['insumo_id']), ['stock' => $nuevo]);
    $jh->create('movimientos', [
        'fecha'      => date('Y-m-d H:i:s'),
        'tipo'       => 'salida',
        'producto_id'=> 0,
        'producto'   => $ing['insumo_nombre'],
        'cantidad'   => $necesario,
        'referencia' => 'produccion',
        'motivo'     => "Producción: {$receta['nombre']} x{$lotes} lotes"
    ]);
}

// 3. Sumar stock al postre producido
$producido = intval($receta['rinde']) * $lotes;
$postre    = $jh->findById('postresitos', intval($receta['postre_id']));
if ($postre) {
    $jh->update('postresitos', intval($receta['postre_id']), [
        'stock' => intval($postre['stock']) + $producido
    ]);
    $jh->create('movimientos', [
        'fecha'      => date('Y-m-d H:i:s'),
        'tipo'       => 'entrada',
        'producto_id'=> intval($receta['postre_id']),
        'producto'   => $postre['titulo'],
        'cantidad'   => $producido,
        'referencia' => 'produccion',
        'motivo'     => "Producción x{$lotes} lotes de \"{$receta['nombre']}\""
    ]);
}

registrarAuditoria('SCM','produccion.ejecutar',
    "Producción: {$receta['nombre']} x{$lotes} lotes → +{$producido} unidades de ".($postre['titulo']??''),
    ['receta_id'=>$data['receta_id'], 'lotes'=>$lotes, 'producido'=>$producido]
);

echo json_encode(['ok'=>true, 'producido'=>$producido, 'postre'=>$postre['titulo']??'']);