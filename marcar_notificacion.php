<?php
require_once 'config.php';

if (!isAuthenticated()) {
    die(json_encode(['success' => false, 'error' => 'No autorizado']));
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Marcar notificación como leída
        if (isset($_POST['id'])) {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            
            echo json_encode(['success' => true]);
            
        // Marcar todas como leídas
        } elseif (isset($_POST['mark_all_read'])) {
            $stmt = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            echo json_encode(['success' => true]);
            
        // Eliminar notificación
        } elseif (isset($_POST['delete'])) {
            $id = intval($_POST['delete']);
            $stmt = $conn->prepare("DELETE FROM notificaciones WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            
            echo json_encode(['success' => true]);
            
        // Eliminar todas las notificaciones
        } elseif (isset($_POST['delete_all'])) {
            $stmt = $conn->prepare("DELETE FROM notificaciones WHERE usuario_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            echo json_encode(['success' => true, 'reload' => true]);
        }
    }
} catch (PDOException $e) {
    error_log("Error en marcar_notificacion.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>