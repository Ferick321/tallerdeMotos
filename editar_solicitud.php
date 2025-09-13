<?php
require_once 'config.php';

if (!isClienteAuthenticated()) {
    redirect('login_cliente.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mantenimiento_id = intval($_POST['mantenimiento_id']);
    $novedades = trim($_POST['novedades']);
    
    // Verificar que el cliente tiene permiso para editar esta solicitud
    $stmt = $conn->prepare("
        SELECT m.id 
        FROM mantenimientos m
        JOIN motos mo ON m.moto_id = mo.id
        WHERE m.id = ? AND mo.cliente_id = ? AND (m.estado = 'solicitado' OR m.estado = 'recibido')
    ");
    $stmt->execute([$mantenimiento_id, $_SESSION['cliente_id']]);
    $permiso = $stmt->fetch();
    
    if (!$permiso) {
        $_SESSION['error'] = "No tiene permiso para editar esta solicitud";
        redirect('dashboard_cliente.php');
    }
    
    try {
        $stmt = $conn->prepare("UPDATE mantenimientos SET novedades = ? WHERE id = ?");
        $stmt->execute([$novedades, $mantenimiento_id]);
        
        $_SESSION['success'] = "Solicitud actualizada con éxito";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al actualizar la solicitud: " . $e->getMessage();
    }
}

redirect('dashboard_cliente.php');