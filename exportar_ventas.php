<?php
require_once 'config.php';

if (!isAuthenticated() || !hasRole('administrador')) {
    redirect('login.php');
}

// Configurar headers para descarga de archivo
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ventas_' . date('Y-m-d') . '.csv');

// Crear output stream
$output = fopen('php://output', 'w');

// Escribir encabezados
fputcsv($output, [
    'ID', 
    'Fecha', 
    'Cliente', 
    'Cédula', 
    'Vendedor', 
    'Total', 
    'Estado'
]);

// Consulta para obtener todas las ventas (sin paginación)
$query = "SELECT v.id, v.fecha, v.total, v.estado, 
                 u.nombre as vendedor,
                 c.nombres as cliente_nombre, c.cedula as cliente_cedula
          FROM ventas v
          JOIN usuarios u ON v.usuario_id = u.id
          LEFT JOIN clientes c ON v.cliente_id = c.id
          ORDER BY v.fecha DESC";

$stmt = $conn->query($query);

// Escribir datos
while ($venta = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $venta['id'],
        date('d/m/Y H:i', strtotime($venta['fecha'])),
        $venta['cliente_nombre'] ?: 'Sin cliente',
        $venta['cliente_cedula'] ?: '',
        $venta['vendedor'],
        number_format($venta['total'], 2),
        ucfirst($venta['estado'])
    ]);
}

fclose($output);
exit;
?>