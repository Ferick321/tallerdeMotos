<?php
require_once 'config.php';

if (!isClienteAuthenticated()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'ID no proporcionado']);
    exit;
}

$mantenimiento_id = intval($_GET['id']);

// Verificar que el cliente tiene permiso para confirmar este mantenimiento
$stmt = $conn->prepare("
    SELECT m.id 
    FROM mantenimientos m
    JOIN motos mo ON m.moto_id = mo.id
    WHERE m.id = ? AND mo.cliente_id = ? AND m.estado = 'terminado'
");
$stmt->execute([$mantenimiento_id, $_SESSION['cliente_id']]);
$permiso = $stmt->fetch();

if (!$permiso) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'No tiene permiso para confirmar este mantenimiento']);
    exit;
}

try {
    // Actualizar el estado a "entregado"
    $stmt = $conn->prepare("UPDATE mantenimientos SET estado = 'entregado', fecha_entrega_real = NOW() WHERE id = ?");
    $stmt->execute([$mantenimiento_id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Error al confirmar la recepción: ' . $e->getMessage()]);
}