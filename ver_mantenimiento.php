<?php
require_once 'config.php';

if (!isAuthenticated()) {
    redirect('login.php');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    $_SESSION['error'] = "Mantenimiento no especificado";
    redirect('mantenimientos.php');
}

// Obtener datos del mantenimiento
$stmt = $conn->prepare("SELECT m.*, 
                       mo.marca, mo.modelo, mo.placa, mo.color, mo.kilometraje,
                       c.nombres as cliente_nombre, c.cedula, c.telefono, c.email,
                       u.nombre as responsable
                       FROM mantenimientos m
                       JOIN motos mo ON m.moto_id = mo.id
                       JOIN clientes c ON mo.cliente_id = c.id
                       LEFT JOIN usuarios u ON m.responsable_id = u.id
                       WHERE m.id = ?");
$stmt->execute([$id]);
$mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mantenimiento) {
    $_SESSION['error'] = "Mantenimiento no encontrado";
    redirect('mantenimientos.php');
}

// Función para formatear el estado
function formatEstado($estado) {
    $estados = [
        'recibido' => 'Recibido',
        'en_proceso' => 'En Proceso',
        'terminado' => 'Terminado',
        'entregado' => 'Entregado'
    ];
    return $estados[$estado] ?? $estado;
}

// Función para obtener la clase CSS del estado
function getEstadoClass($estado) {
    switch($estado) {
        case 'recibido': return 'bg-secondary';
        case 'en_proceso': return 'bg-warning text-dark';
        case 'terminado': return 'bg-primary';
        case 'entregado': return 'bg-success';
        default: return 'bg-light text-dark';
    }
}

// Función para formatear el número de WhatsApp
function formatWhatsAppNumber($phone) {
    // Elimina todo excepto números
    $clean = preg_replace('/[^0-9]/', '', $phone);
    
    // Si empieza con 0, lo eliminamos
    if (strpos($clean, '0') === 0) {
        $clean = substr($clean, 1);
    }
    
    // Aseguramos que tenga 9 dígitos
    if (strlen($clean) === 9) {
        return '593' . $clean;
    }
    
    return false; // Número inválido
}

// Formatear número para WhatsApp
$whatsappNumber = $mantenimiento['telefono'] ? formatWhatsAppNumber($mantenimiento['telefono']) : false;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Mantenimiento - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <h2>Detalles del Mantenimiento</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Información General</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Fecha de Ingreso:</strong> <?php echo date('d/m/Y H:i', strtotime($mantenimiento['fecha_ingreso'])); ?></p>
                        <?php if ($mantenimiento['fecha_entrega_estimada']): ?>
                            <p><strong>Fecha Estimada de Entrega:</strong> <?php echo date('d/m/Y', strtotime($mantenimiento['fecha_entrega_estimada'])); ?></p>
                        <?php endif; ?>
                        <?php if ($mantenimiento['fecha_entrega_real']): ?>
                            <p><strong>Fecha Real de Entrega:</strong> <?php echo date('d/m/Y H:i', strtotime($mantenimiento['fecha_entrega_real'])); ?></p>
                        <?php endif; ?>
                        <p>
                            <strong>Estado:</strong> 
                            <span class="badge <?php echo getEstadoClass($mantenimiento['estado']); ?>">
                                <?php echo formatEstado($mantenimiento['estado']); ?>
                            </span>
                        </p>
                        <?php if ($mantenimiento['responsable']): ?>
                            <p><strong>Responsable:</strong> <?php echo htmlspecialchars($mantenimiento['responsable']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Información del Cliente</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($mantenimiento['cliente_nombre']); ?></p>
                        <p><strong>Cédula:</strong> <?php echo htmlspecialchars($mantenimiento['cedula']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($mantenimiento['telefono']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($mantenimiento['email']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Información de la Moto</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Marca/Modelo:</strong> <?php echo htmlspecialchars($mantenimiento['marca'] . ' ' . $mantenimiento['modelo']); ?></p>
                        <p><strong>Placa:</strong> <?php echo htmlspecialchars($mantenimiento['placa']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Color:</strong> <?php echo htmlspecialchars($mantenimiento['color']); ?></p>
                        <p><strong>Kilometraje:</strong> <?php echo number_format($mantenimiento['kilometraje'], 0); ?> km</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Novedades/Trabajos Realizados</h5>
            </div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($mantenimiento['novedades'])); ?></p>
            </div>
        </div>
        
        <div class="d-flex justify-content-between">
            <a href="mantenimientos.php" class="btn btn-secondary">Volver a Mantenimientos</a>
            <?php if ($whatsappNumber): ?>
                <a href="https://wa.me/<?php echo $whatsappNumber; ?>?text=Estimado%20cliente,%20consultamos%20sobre%20el%20estado%20del%20mantenimiento%20de%20su%20moto%20<?php echo urlencode($mantenimiento['marca'] . ' ' . $mantenimiento['modelo'] . ' (Placa: ' . $mantenimiento['placa'] . ')'); ?>%20en%20nuestro%20taller.%20*Estado%20actual: *%20<?php echo urlencode(formatEstado($mantenimiento['estado'])); ?>" 
                   class="btn btn-success" target="_blank">
                    <i class="bi bi-whatsapp"></i> Contactar al Cliente
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>