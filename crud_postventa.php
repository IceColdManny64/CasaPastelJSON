<?php
require_once 'JsonHelper.php';
require_once 'verificar_sesion.php';
require_once 'crud_auditoria.php';

header('Content-Type: application/json');

$jh = new JsonHelper('./data/');
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$pedidos = $jh->getAll('pedidos');
$solicitudes = $jh->getAll('solicitudes_devolucion');
if (!is_array($solicitudes)) $solicitudes = [];

function saveJsonHelper(JsonHelper $jh, string $name, array $data): void {
    $jh->writeData($name, $data);
}

function audit(JsonHelper $jh, string $accion, string $descripcion, array $detalle = [], string $modulo = 'Postventa'): void {
    $jh->create('auditoria', [
        'fecha' => date('Y-m-d H:i:s'),
        'modulo' => $modulo,
        'accion' => $accion,
        'usuario' => $_SESSION['nombre'] ?? 'Sistema',
        'rol' => $_SESSION['rol'] ?? '',
        'descripcion' => $descripcion,
        'detalle' => json_encode($detalle, JSON_UNESCAPED_UNICODE),
    ]);
}

function findPedidoIndex(array $pedidos, string $folio): ?int {
    foreach ($pedidos as $i => $p) {
        if (($p['folio'] ?? '') === $folio || ($p['numero_pedido'] ?? '') === $folio) {
            return $i;
        }
    }
    return null;
}

function findSolicitudIndex(array $solicitudes, string $folio): ?int {
    foreach ($solicitudes as $i => $s) {
        if (($s['folio'] ?? '') === $folio) {
            return $i;
        }
    }
    return null;
}

if ($method === 'GET') {
    requierePersonal();
    echo json_encode(array_values($solicitudes));
    exit;
}

if ($method === 'POST') {
    requiereCliente();

    $folio  = trim($input['folio'] ?? '');
    $tipo   = trim($input['tipo'] ?? '');
    $motivo = trim($input['motivo'] ?? '');

    if (!$folio || !in_array($tipo, ['cancelacion', 'devolucion'], true) || !$motivo) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
        exit;
    }

    $pedidoIndex = findPedidoIndex($pedidos, $folio);
    if ($pedidoIndex === null) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Pedido no encontrado.']);
        exit;
    }

    $pedido = $pedidos[$pedidoIndex];
    $uid = (int)($_SESSION['usuario_id'] ?? 0);

    $jhUsuario = $jh->findById('usuarios', $uid);
    $correoUsuario = $jhUsuario['correo'] ?? ($_SESSION['correo'] ?? '');

    $esPropio = (
        (!empty($pedido['usuario_id']) && (int)$pedido['usuario_id'] === $uid) ||
        (!empty($pedido['correo']) && !empty($correoUsuario) && $pedido['correo'] === $correoUsuario)
    );

    if (!$esPropio) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Ese pedido no pertenece a tu cuenta.']);
        exit;
    }

    $estadoPedido = $pedido['estado'] ?? 'Recibido';

    if ($tipo === 'cancelacion' && $estadoPedido === 'Entregado') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Ya no puedes cancelar un pedido entregado. Solicita devolución.']);
        exit;
    }

    if ($tipo === 'devolucion' && $estadoPedido !== 'Entregado') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'La devolución solo aplica para pedidos entregados.']);
        exit;
    }

    $estadoSolicitudActual = $pedido['solicitud_postventa']['estado'] ?? null;
    if (in_array($estadoSolicitudActual, ['pendiente', 'aprobada'], true)) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'Ya existe una solicitud activa o resuelta para este pedido.']);
        exit;
    }

    $solicitud = [
        'id' => time(),
        'folio' => $folio,
        'usuario_id' => $uid,
        'cliente' => $jhUsuario['nombre'] ?? ($_SESSION['nombre'] ?? ($pedido['cliente'] ?? 'Cliente')),
        'correo' => $correoUsuario,
        'tipo' => $tipo,
        'estado' => 'pendiente',
        'motivo' => $motivo,
        'monto' => floatval($pedido['total'] ?? 0),
        'metodo_pago' => $pedido['metodo_pago'] ?? ($pedido['metodo'] ?? 'otro'),
        'pedido_estado' => $estadoPedido,
        'fecha_solicitud' => date('Y-m-d H:i:s'),
        'fecha_resolucion' => null,
        'resuelto_por' => null,
        'resuelto_rol' => null,
    ];

    $solicitudes[] = $solicitud;
    $pedidos[$pedidoIndex]['solicitud_postventa'] = $solicitud;

    saveJsonHelper($jh, 'solicitudes_devolucion', $solicitudes);
    saveJsonHelper($jh, 'pedidos', $pedidos);

    audit($jh, 'solicitud.crear', 'Solicitud de postventa creada', $solicitud);

    echo json_encode(['ok' => true, 'msg' => 'Solicitud enviada.', 'solicitud' => $solicitud]);
    exit;
}

if ($method === 'PUT') {
    requiereCRM();

    $folio  = trim($input['folio'] ?? '');
    $accion = trim($input['accion'] ?? '');

    if (!$folio || !in_array($accion, ['aprobar', 'rechazar'], true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Datos inválidos.']);
        exit;
    }

    $solIndex = findSolicitudIndex($solicitudes, $folio);
    if ($solIndex === null) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'No existe la solicitud.']);
        exit;
    }

    if (($solicitudes[$solIndex]['estado'] ?? '') !== 'pendiente') {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'La solicitud ya fue procesada.']);
        exit;
    }

    $sol = $solicitudes[$solIndex];
    $nuevoEstado = $accion === 'aprobar' ? 'aprobada' : 'rechazada';

    $solicitudes[$solIndex]['estado'] = $nuevoEstado;
    $solicitudes[$solIndex]['fecha_resolucion'] = date('Y-m-d H:i:s');
    $solicitudes[$solIndex]['resuelto_por'] = $_SESSION['nombre'] ?? 'CRM';
    $solicitudes[$solIndex]['resuelto_rol'] = $_SESSION['rol'] ?? 'crm';

    $pedidoIndex = findPedidoIndex($pedidos, $folio);
    if ($pedidoIndex !== null) {
        $pedidos[$pedidoIndex]['solicitud_postventa'] = $solicitudes[$solIndex];
        if ($nuevoEstado === 'aprobada') {
            $pedidos[$pedidoIndex]['estado'] = 'Cancelado';
        }
    }

    if ($nuevoEstado === 'aprobada') {
        $jh->create('finanzas', [
            'fecha' => date('Y-m-d H:i:s'),
            'tipo' => 'egreso',
            'concepto' => 'Reembolso pedido ' . $folio,
            'referencia_id' => $folio,
            'referencia_tipo' => 'reembolso',
            'monto' => floatval($sol['monto'] ?? 0),
            'metodo_pago' => $sol['metodo_pago'] ?? 'otro',
        ]);
    }

    saveJsonHelper($jh, 'solicitudes_devolucion', $solicitudes);
    saveJsonHelper($jh, 'pedidos', $pedidos);

    audit($jh, 'solicitud.' . $accion, 'Solicitud de postventa ' . $nuevoEstado, $solicitudes[$solIndex]);

    echo json_encode(['ok' => true, 'msg' => 'Solicitud procesada.', 'estado' => $nuevoEstado]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'msg' => 'Método no permitido.']);