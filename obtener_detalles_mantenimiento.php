<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'ID no proporcionado']);
    exit;
}

$mantenimiento_id = intval($_GET['id']);

// Verificar que el cliente tiene permiso para ver estos detalles
$stmt = $conn->prepare("
    SELECT m.id 
    FROM mantenimientos m
    JOIN motos mo ON m.moto_id = mo.id
    WHERE m.id = ? AND mo.cliente_id = ?
");
$stmt->execute([$mantenimiento_id, $_SESSION['cliente_id']]);
$permiso = $stmt->fetch();

if (!$permiso) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'No tiene permiso para ver estos detalles']);
    exit;
}

// Primero verificar qué tablas existen en la base de datos
$tablas = ['mantenimiento_repuestos', 'mantenimiento_detalles', 'mantenimiento_ventas'];
$tabla_valida = null;

foreach ($tablas as $tabla) {
    $stmt = $conn->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$tabla]);
    if ($stmt->fetch()) {
        $tabla_valida = $tabla;
        break;
    }
}

if (!$tabla_valida) {
    echo json_encode([]);
    exit;
}

// Obtener detalles según la tabla existente
switch ($tabla_valida) {
    case 'mantenimiento_repuestos':
        $stmt = $conn->prepare("
            SELECT 
                producto_nombre as nombre,
                producto_codigo as codigo,
                cantidad,
                precio_unitario as precio_venta,
                subtotal
            FROM mantenimiento_repuestos 
            WHERE mantenimiento_id = ? AND eliminado = FALSE
        ");
        break;
        
    case 'mantenimiento_detalles':
        $stmt = $conn->prepare("
            SELECT 
                p.nombre,
                p.codigo,
                md.cantidad,
                md.precio_unitario as precio_venta,
                md.subtotal
            FROM mantenimiento_detalles md
            JOIN productos p ON md.producto_id = p.id
            WHERE md.mantenimiento_id = ?
        ");
        break;
        
    case 'mantenimiento_ventas':
        $stmt = $conn->prepare("
            SELECT 
                p.nombre,
                p.codigo,
                vd.cantidad,
                vd.precio_unitario as precio_venta,
                vd.subtotal
            FROM mantenimiento_ventas mv
            JOIN ventas v ON mv.venta_id = v.id
            JOIN venta_detalles vd ON v.id = vd.venta_id
            JOIN productos p ON vd.producto_id = p.id
            WHERE mv.mantenimiento_id = ?
        ");
        break;
}

$stmt->execute([$mantenimiento_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($detalles);