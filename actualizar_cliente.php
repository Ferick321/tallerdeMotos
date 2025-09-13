<?php
require_once 'config.php';

if (!isClienteAuthenticated()) {
    redirect('login_cliente.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);
    
    try {
        $stmt = $conn->prepare("UPDATE clientes SET telefono = ?, email = ?, direccion = ? WHERE id = ?");
        $stmt->execute([$telefono, $email, $direccion, $_SESSION['cliente_id']]);
        
        $_SESSION['success'] = "Información actualizada con éxito";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al actualizar la información: " . $e->getMessage();
    }
}

redirect('dashboard_cliente.php');