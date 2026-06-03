<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
require_once 'crud_auditoria.php';

// Según la Tabla 6 de permisos, solo el Admin puede pagar nómina
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requiereAdmin(); 
} else {
    requierePersonal(); // Para que el Gerente pueda ver el historial si es necesario
}

header('Content-Type: application/json');

$jh     = new JsonHelper('./data/');
$method = $_SERVER['REQUEST_METHOD'];
$data   = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'GET') {
    echo json_encode($jh->getAll('nomina'));

} elseif ($method === 'POST') {
    if (empty($data['empleado_id']) || empty($data['total']) || empty($data['periodo'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Faltan datos requeridos para el pago.']);
        exit;
    }

    $total = floatval($data['total']);
    if ($total <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'El monto total debe ser mayor a $0.00']);
        exit;
    }

    // 🔴 NUEVO: VALIDACIÓN PARA EVITAR PAGO DOBLE
    $todasLasNominas = $jh->getAll('nomina');
    foreach ($todasLasNominas as $nom) {
        if ($nom['empleado_id'] == $data['empleado_id'] && $nom['periodo'] === $data['periodo']) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'msg' => "La nómina de este empleado ya fue pagada en el período {$data['periodo']}."]);
            exit;
        }
    }

    // 1. Crear el registro en Nómina
    $nomina = [
        'empleado_id'  => intval($data['empleado_id']),
        'empleado'     => $data['empleado'] ?? 'Desconocido',
        'periodo'      => $data['periodo'],
        'salario_base' => floatval($data['salario_base'] ?? 0),
        'bono'         => floatval($data['bono'] ?? 0),
        'total'        => $total,
        'fecha'        => date('Y-m-d H:i:s')
    ];
    
    $nominaCreada = $jh->create('nomina', $nomina);

    if ($nominaCreada) {
        $nominaId = $nominaCreada['id'] ?? uniqid();

        // 2. Reflejar automáticamente en FINANZAS como un Egreso
        $jh->create('finanzas', [
            'fecha'            => date('Y-m-d H:i:s'),
            'tipo'             => 'egreso',
            'concepto'         => "Nómina - " . $nomina['empleado'] . " (" . $nomina['periodo'] . ")",
            'referencia_id'    => (string)$nominaId,
            'referencia_tipo'  => 'nomina',
            'monto'            => $total,
            'metodo_pago'      => 'transferencia'
        ]);

        // 3. Registrar el movimiento en AUDITORÍA
        registrarAuditoria(
            'RR.HH.', 
            'nomina.pagar', 
            "Pago de nómina registrado para {$nomina['empleado']} por $" . number_format($total, 2),
            ['empleado_id' => $nomina['empleado_id'], 'periodo' => $nomina['periodo']]
        );

        echo json_encode(['ok' => true, 'msg' => 'Nómina pagada y reflejada en Finanzas.']);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'Error al guardar la nómina.']);
    }
}