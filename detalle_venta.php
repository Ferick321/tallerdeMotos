<?php
require_once 'config.php';

if (!isAuthenticated()) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

// Consulta para obtener detalles de la venta
$query = "SELECT p.nombre, vd.cantidad, 
                 FORMAT(vd.precio_unitario, 2) as precio_unitario,
                 FORMAT(vd.subtotal, 2) as subtotal
          FROM venta_detalles vd
          JOIN productos p ON vd.producto_id = p.id
          WHERE vd.venta_id = ?";
          
$stmt = $conn->prepare($query);
$stmt->execute([$id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($detalles);
?>