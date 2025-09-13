<?php
require_once 'config.php';

if (!isClienteAuthenticated()) {
    redirect('login_cliente.php');
}

// Obtener información del cliente
$stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$_SESSION['cliente_id']]);
$cliente = $stmt->fetch();

// Obtener información de las motos del cliente
$stmt = $conn->prepare("
    SELECT m.*, 
           COALESCE(
               (SELECT estado 
                FROM mantenimientos 
                WHERE moto_id = m.id 
                ORDER BY fecha_ingreso DESC 
                LIMIT 1
           ), 'sin_mantenimientos') as ultimo_estado
    FROM motos m
    WHERE m.cliente_id = ?
    ORDER BY m.fecha_registro DESC
");
$stmt->execute([$_SESSION['cliente_id']]);
$motos = $stmt->fetchAll();

// Obtener todos los mantenimientos del cliente para el historial
$stmt = $conn->prepare("
    SELECT m.*, mo.marca, mo.modelo, mo.placa, mo.color, mo.kilometraje as kilometraje_actual,
           c.nombres as cliente_nombre, c.cedula, c.telefono, c.email,
           u.nombre as responsable
    FROM mantenimientos m
    JOIN motos mo ON m.moto_id = mo.id
    JOIN clientes c ON mo.cliente_id = c.id
    LEFT JOIN usuarios u ON m.responsable_id = u.id
    WHERE mo.cliente_id = ?
    ORDER BY m.fecha_ingreso DESC
");
$stmt->execute([$_SESSION['cliente_id']]);
$todos_mantenimientos = $stmt->fetchAll();

// Obtener repuestos asociados a los mantenimientos
$repuestos = [];
if (!empty($todos_mantenimientos)) {
    $mantenimiento_ids = array_column($todos_mantenimientos, 'id');
    $placeholders = implode(',', array_fill(0, count($mantenimiento_ids), '?'));
    
    // Primero intentar con mantenimiento_repuestos
    $stmt = $conn->prepare("SHOW TABLES LIKE 'mantenimiento_repuestos'");
    $stmt->execute();
    $tabla_repuestos_existe = $stmt->fetch();
    
    if ($tabla_repuestos_existe) {
        $query = "SELECT mr.* 
                  FROM mantenimiento_repuestos mr
                  WHERE mr.mantenimiento_id IN ($placeholders) AND mr.eliminado = FALSE
                  ORDER BY mr.fecha_registro DESC";
    } else {
        // Si no existe, intentar con mantenimiento_detalles
        $query = "SELECT md.*, p.nombre as producto_nombre, p.codigo as producto_codigo
                  FROM mantenimiento_detalles md
                  JOIN productos p ON md.producto_id = p.id
                  WHERE md.mantenimiento_id IN ($placeholders)
                  ORDER BY md.fecha_registro DESC";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($mantenimiento_ids);
    $repuestos_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar repuestos por mantenimiento
    foreach ($repuestos_result as $repuesto) {
        $repuestos[$repuesto['mantenimiento_id']][] = $repuesto;
    }
}

// Procesar actualización de kilometraje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_kilometraje'])) {
    $moto_id = intval($_POST['moto_id']);
    $kilometraje = intval($_POST['kilometraje']);
    
    try {
        $stmt = $conn->prepare("UPDATE motos SET kilometraje = ? WHERE id = ? AND cliente_id = ?");
        $stmt->execute([$kilometraje, $moto_id, $_SESSION['cliente_id']]);
        
        $_SESSION['success'] = "Kilometraje actualizado con éxito";
        redirect('dashboard_cliente.php');
    } catch (PDOException $e) {
        $error = "Error al actualizar el kilometraje: " . $e->getMessage();
    }
}

// Procesar solicitud de mantenimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_mantenimiento'])) {
    $moto_id = intval($_POST['moto_id']);
    $novedades = trim($_POST['novedades']);
    
    try {
        $stmt = $conn->prepare("INSERT INTO mantenimientos (moto_id, novedades, estado) VALUES (?, ?, 'solicitado')");
        $stmt->execute([$moto_id, $novedades]);
        
        // Notificar al administrador
        $stmt = $conn->prepare("
            INSERT INTO notificaciones (usuario_id, titulo, mensaje) 
            SELECT id, 'Nueva solicitud de mantenimiento', ? 
            FROM usuarios WHERE rol = 'administrador'
        ");
        $mensaje = "El cliente " . $_SESSION['cliente_nombre'] . " ha solicitado un mantenimiento para su moto.";
        $stmt->execute([$mensaje]);
        
        $_SESSION['success'] = "Solicitud de mantenimiento enviada con éxito";
        redirect('dashboard_cliente.php');
    } catch (PDOException $e) {
        $error = "Error al enviar la solicitud: " . $e->getMessage();
    }
}

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombres = trim($_POST['nombres']);
    $cedula = trim($_POST['cedula']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    
    try {
        $stmt = $conn->prepare("UPDATE clientes SET nombres = ?, cedula = ?, telefono = ?, email = ? WHERE id = ?");
        $stmt->execute([$nombres, $cedula, $telefono, $email, $_SESSION['cliente_id']]);
        
        // Actualizar también la sesión
        $_SESSION['cliente_nombre'] = $nombres;
        
        $_SESSION['success'] = "Perfil actualizado con éxito";
        redirect('dashboard_cliente.php');
    } catch (PDOException $e) {
        $error = "Error al actualizar el perfil: " . $e->getMessage();
    }
}

// Procesar registro de nueva moto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_moto'])) {
    $marca = trim($_POST['marca']);
    $modelo = trim($_POST['modelo']);
    $placa = trim($_POST['placa']);
    $serie = trim($_POST['serie']);
    $color = trim($_POST['color']);
    $kilometraje = intval($_POST['kilometraje']);
    
    try {
        $stmt = $conn->prepare("INSERT INTO motos (cliente_id, marca, modelo, placa, serie, color, kilometraje) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['cliente_id'], $marca, $modelo, $placa, $serie, $color, $kilometraje]);
        
        $_SESSION['success'] = "Moto registrada con éxito";
        redirect('dashboard_cliente.php');
    } catch (PDOException $e) {
        $error = "Error al registrar la moto: " . $e->getMessage();
    }
}

// Función para obtener la clase CSS según el estado
function getEstadoClass($estado) {
    switch($estado) {
        case 'recibido': return 'bg-secondary';
        case 'en_proceso': return 'bg-warning text-dark';
        case 'terminado': return 'bg-primary';
        case 'entregado': return 'bg-success';
        case 'solicitado': return 'bg-info';
        default: return 'bg-light text-dark';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Cliente - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .moto-card {
            transition: transform 0.2s;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
        }
        .moto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        .badge {
            font-size: 0.8rem;
            padding: 0.4em 0.6em;
        }
        .btn-action {
            margin: 2px;
            border-radius: 5px;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 15px;
            width: 2px;
            background-color: #dee2e6;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            top: 15px;
            left: -30px;
            width: 12px;
            height: 12px;
            background-color: #0d6efd;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #0d6efd;
        }
        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #dee2e6;
        }
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
            background: transparent;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }
        .repuesto-eliminado {
            text-decoration: line-through;
            color: #6c757d;
            opacity: 0.7;
        }
        .accordion-button:not(.collapsed) {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
        .whatsapp-btn {
            background-color: #25D366;
            color: white;
            border: none;
        }
        .whatsapp-btn:hover {
            background-color: #128C7E;
            color: white;
        }
        .profile-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .nav-pills .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar Original (No fijo) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard_cliente.php">
                <i class="bi bi-bicycle"></i> Portal Cliente
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard_cliente.php">
                            <i class="bi bi-house"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#historial">
                            <i class="bi bi-clock-history"></i> Historial
                        </a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['cliente_nombre']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#perfilModal">
                                <i class="bi bi-person"></i> Mi Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#agregarMotoModal">
                                <i class="bi bi-plus-circle"></i> Agregar Moto
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Alertas -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Sección de Mis Motos -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="bi bi-bicycle"></i> Mis Motos</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($motos)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-bicycle" style="font-size: 3rem; color: #6c757d;"></i>
                                <h5 class="mt-3">No tienes motos registradas</h5>
                                <p class="text-muted">Agrega tu primera moto para comenzar</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#agregarMotoModal">
                                    <i class="bi bi-plus-circle"></i> Agregar Mi Primera Moto
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                                <?php foreach ($motos as $moto): ?>
                                    <div class="col">
                                        <div class="card h-100 moto-card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <h5 class="card-title">
                                                        <?php echo htmlspecialchars($moto['marca'] . ' ' . $moto['modelo']); ?>
                                                    </h5>
                                                    <span class="badge <?php echo getEstadoClass($moto['ultimo_estado']); ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $moto['ultimo_estado'])); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <h6 class="card-subtitle text-muted">
                                                        <i class="bi bi-tag"></i> Placa: <?php echo htmlspecialchars($moto['placa']); ?>
                                                    </h6>
                                                    <small class="text-muted">Color: <?php echo htmlspecialchars($moto['color']); ?></small>
                                                    <?php if (!empty($moto['serie'])): ?>
                                                        <small class="text-muted d-block">Serie: <?php echo htmlspecialchars($moto['serie']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <p class="card-text">
                                                        <strong><i class="bi bi-speedometer2"></i> Kilometraje actual:</strong>
                                                        <?php echo number_format($moto['kilometraje'], 0); ?> km
                                                    </p>
                                                    
                                                    <!-- Formulario para actualizar kilometraje -->
                                                    <form method="POST" class="mb-3">
                                                        <input type="hidden" name="moto_id" value="<?php echo $moto['id']; ?>">
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" class="form-control" name="kilometraje" 
                                                                   placeholder="Nuevo kilometraje" min="0" required>
                                                            <button type="submit" name="actualizar_kilometraje" 
                                                                    class="btn btn-outline-primary">
                                                                <i class="bi bi-arrow-up"></i> Actualizar
                                                            </button>
                                                        </div>
                                                        <small class="text-muted">Ingrese el kilometraje actual de su moto</small>
                                                    </form>
                                                    
                                                    <?php if (!empty($moto['proximo_cambio_aceite'])): ?>
                                                        <div class="mb-3">
                                                            <small class="text-muted d-block mb-1">Próximo cambio de aceite:</small>
                                                            <div class="progress">
                                                                <?php
                                                                $porcentaje = min(100, ($moto['kilometraje'] / $moto['proximo_cambio_aceite']) * 100);
                                                                $colorBarra = $porcentaje >= 90 ? 'bg-danger' : ($porcentaje >= 75 ? 'bg-warning' : 'bg-success');
                                                                ?>
                                                                <div class="progress-bar <?php echo $colorBarra; ?>" 
                                                                     role="progressbar" 
                                                                     style="width: <?php echo $porcentaje; ?>%"
                                                                     aria-valuenow="<?php echo $porcentaje; ?>" 
                                                                     aria-valuemin="0" 
                                                                     aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                            <small class="text-muted">
                                                                Recomendado: <?php echo number_format($moto['proximo_cambio_aceite'], 0); ?> km
                                                                (<?php echo number_format($moto['proximo_cambio_aceite'] - $moto['kilometraje'], 0); ?> km restantes)
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <button class="btn btn-sm btn-primary w-100" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#solicitudModal"
                                                        data-moto-id="<?php echo $moto['id']; ?>"
                                                        data-moto-info="<?php echo htmlspecialchars($moto['marca'] . ' ' . $moto['modelo'] . ' - ' . $moto['placa']); ?>">
                                                    <i class="bi bi-tools"></i> Solicitar Mantenimiento
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Historial Completo -->
        <div id="historial" class="row mb-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="bi bi-clock-history"></i> Historial Completo de Mantenimientos</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($todos_mantenimientos)): ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle"></i> No hay mantenimientos registrados en el historial.
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="historialAccordion">
                                <?php foreach ($todos_mantenimientos as $index => $mant): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                            <button class="accordion-button <?php echo $index !== 0 ? 'collapsed' : ''; ?>" 
                                                    type="button" data-bs-toggle="collapse" 
                                                    data-bs-target="#collapse<?php echo $index; ?>" 
                                                    aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                                    aria-controls="collapse<?php echo $index; ?>">
                                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                    <div>
                                                        <span class="badge <?php echo getEstadoClass($mant['estado']); ?> me-2">
                                                            <?php echo ucfirst(str_replace('_', ' ', $mant['estado'])); ?>
                                                        </span>
                                                        <?php echo date('d/m/Y', strtotime($mant['fecha_ingreso'])); ?>
                                                        - <?php echo htmlspecialchars($mant['marca'] . ' ' . $mant['modelo']); ?>
                                                    </div>
                                                    <div class="text-muted">
                                                        <small>Placa: <?php echo htmlspecialchars($mant['placa']); ?></small>
                                                    </div>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $index; ?>" 
                                             class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                             aria-labelledby="heading<?php echo $index; ?>" 
                                             data-bs-parent="#historialAccordion">
                                            <div class="accordion-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <h6>Información del Servicio</h6>
                                                        <p><strong>Fecha de ingreso:</strong> <?php echo date('d/m/Y H:i', strtotime($mant['fecha_ingreso'])); ?></p>
                                                        <?php if ($mant['fecha_entrega_estimada']): ?>
                                                            <p><strong>Entrega estimada:</strong> <?php echo date('d/m/Y', strtotime($mant['fecha_entrega_estimada'])); ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($mant['fecha_entrega_real']): ?>
                                                            <p><strong>Entregado el:</strong> <?php echo date('d/m/Y H:i', strtotime($mant['fecha_entrega_real'])); ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($mant['kilometraje_actual']): ?>
                                                            <p><strong>Kilometraje:</strong> <?php echo number_format($mant['kilometraje_actual'], 0); ?> km</p>
                                                        <?php endif; ?>
                                                        <p><strong>Responsable:</strong> <?php echo htmlspecialchars($mant['responsable'] ?? 'No asignado'); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6>Detalles del Trabajo</h6>
                                                        <p><strong>Novedades:</strong></p>
                                                        <div class="border-start border-3 border-primary ps-3 mb-3">
                                                            <?php echo nl2br(htmlspecialchars($mant['novedades'])); ?>
                                                        </div>
                                                        <?php if (!empty($mant['costo'])): ?>
                                                            <p><strong>Costo total:</strong> $<?php echo number_format($mant['costo'], 2); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- Repuestos utilizados -->
                                                <?php if (!empty($repuestos[$mant['id']])): ?>
                                                    <div class="mt-4">
                                                        <h6 class="border-bottom pb-2">Repuestos y Servicios</h6>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-hover">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Producto</th>
                                                                        <th>Código</th>
                                                                        <th>Cantidad</th>
                                                                        <th>Precio Unitario</th>
                                                                        <th>Subtotal</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php 
                                                                    $total_mantenimiento = 0;
                                                                    foreach ($repuestos[$mant['id']] as $repuesto): 
                                                                        $producto_nombre = $repuesto['producto_nombre'] ?? $repuesto['nombre'] ?? 'Producto no disponible';
                                                                        $codigo = $repuesto['producto_codigo'] ?? $repuesto['codigo'] ?? 'N/A';
                                                                        $cantidad = $repuesto['cantidad'] ?? 0;
                                                                        $precio = $repuesto['precio_unitario'] ?? $repuesto['precio_venta'] ?? 0;
                                                                        $subtotal = $repuesto['subtotal'] ?? ($cantidad * $precio);
                                                                        $total_mantenimiento += $subtotal;
                                                                    ?>
                                                                        <tr class="<?php echo ($repuesto['eliminado'] ?? false) ? 'repuesto-eliminado' : ''; ?>">
                                                                            <td><?php echo htmlspecialchars($producto_nombre); ?></td>
                                                                            <td><?php echo htmlspecialchars($codigo); ?></td>
                                                                            <td><?php echo $cantidad; ?></td>
                                                                            <td>$<?php echo number_format($precio, 2); ?></td>
                                                                            <td>$<?php echo number_format($subtotal, 2); ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                                <tfoot class="table-primary">
                                                                    <tr>
                                                                        <th colspan="4" class="text-end">Total:</th>
                                                                        <th>$<?php echo number_format($total_mantenimiento, 2); ?></th>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-info mt-3">
                                                        <i class="bi bi-info-circle"></i> No se registraron repuestos para este mantenimiento.
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Botones de acción -->
                                                <div class="d-flex justify-content-end mt-3 gap-2">
                                                    <?php if ($mant['estado'] == 'terminado'): ?>
                                                        <button class="btn btn-sm btn-success"
                                                                onclick="confirmarRecepcion(<?php echo $mant['id']; ?>)">
                                                            <i class="bi bi-check-lg"></i> Confirmar Recepción
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (($mant['estado'] == 'solicitado' || $mant['estado'] == 'recibido') && empty($repuestos[$mant['id']])): ?>
                                                        <button class="btn btn-sm btn-warning"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editarSolicitudModal"
                                                                data-mantenimiento-id="<?php echo $mant['id']; ?>"
                                                                data-novedades="<?php echo htmlspecialchars($mant['novedades']); ?>">
                                                            <i class="bi bi-pencil"></i> Editar Solicitud
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($cliente['telefono'])): ?>
                                                        <?php
                                                        $mensaje = "Estimado cliente,\n\n";
                                                        $mensaje .= "Detalles del mantenimiento realizado el " . date('d/m/Y', strtotime($mant['fecha_ingreso'])) . ":\n";
                                                        $mensaje .= "Moto: " . $mant['marca'] . " " . $mant['modelo'] . " (Placa: " . $mant['placa'] . ")\n";
                                                        if (!empty($mant['kilometraje_actual'])) {
                                                            $mensaje .= "Kilometraje: " . number_format($mant['kilometraje_actual'], 0) . " km\n";
                                                        }
                                                        $mensaje .= "Trabajos realizados:\n" . $mant['novedades'] . "\n\n";
                                                        
                                                        if (!empty($repuestos[$mant['id']])) {
                                                            $mensaje .= "Repuestos/servicios:\n";
                                                            foreach ($repuestos[$mant['id']] as $repuesto) {
                                                                if (!($repuesto['eliminado'] ?? false)) {
                                                                    $producto_nombre = $repuesto['producto_nombre'] ?? $repuesto['nombre'] ?? 'Producto';
                                                                    $cantidad = $repuesto['cantidad'] ?? 0;
                                                                    $subtotal = $repuesto['subtotal'] ?? 0;
                                                                    $mensaje .= "- " . $producto_nombre . " (x" . $cantidad . "): $" . number_format($subtotal, 2) . "\n";
                                                                }
                                                            }
                                                            $mensaje .= "\nTotal: $" . number_format($total_mantenimiento, 2) . "\n";
                                                        }
                                                        
                                                        $mensaje .= "\nGracias por confiar en nuestro taller.";
                                                        $telefono = preg_replace('/[^0-9]/', '', $cliente['telefono']);
                                                        if (strpos($telefono, '0') === 0) {
                                                            $telefono = '+593' . substr($telefono, 1);
                                                        }
                                                        $url_whatsapp = "https://wa.me/$telefono?text=" . urlencode($mensaje);
                                                        ?>
                                                        <a href="<?php echo $url_whatsapp; ?>" class="btn btn-sm whatsapp-btn" target="_blank">
                                                            <i class="bi bi-whatsapp"></i> Compartir por WhatsApp
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para solicitar mantenimiento -->
    <div class="modal fade" id="solicitudModal" tabindex="-1" aria-labelledby="solicitudModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="solicitudModalLabel">Solicitar Mantenimiento</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="moto_id" name="moto_id">
                        <div class="mb-3">
                            <label class="form-label">Moto seleccionada:</label>
                            <p class="form-control-static fw-bold" id="moto-info"></p>
                        </div>
                        <div class="mb-3">
                            <label for="novedades" class="form-label">¿Qué necesita tu moto?</label>
                            <textarea class="form-control" id="novedades" name="novedades" rows="4" 
                                      placeholder="Describe los problemas, ruidos, o mantenimientos que necesitas realizar..." required></textarea>
                            <div class="form-text">Sé lo más específico posible para que podamos ayudarte mejor.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="solicitar_mantenimiento" class="btn btn-primary">Enviar Solicitud</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar solicitud de mantenimiento -->
    <div class="modal fade" id="editarSolicitudModal" tabindex="-1" aria-labelledby="editarSolicitudModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="editar_solicitud.php">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="editarSolicitudModalLabel">Editar Solicitud de Mantenimiento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editar_mantenimiento_id" name="mantenimiento_id">
                        <div class="mb-3">
                            <label for="editar_novedades" class="form-label">Novedades:</label>
                            <textarea class="form-control" id="editar_novedades" name="novedades" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar perfil -->
    <div class="modal fade" id="perfilModal" tabindex="-1" aria-labelledby="perfilModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="perfilModalLabel">Mi Perfil</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="actualizar_perfil" value="1">
                        <div class="mb-3">
                            <label for="nombres" class="form-label">Nombres Completos</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" 
                                   value="<?php echo htmlspecialchars($cliente['nombres']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="cedula" class="form-label">Cédula</label>
                            <input type="text" class="form-control" id="cedula" name="cedula" 
                                   value="<?php echo htmlspecialchars($cliente['cedula']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   value="<?php echo htmlspecialchars($cliente['telefono']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($cliente['email']); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para agregar moto -->
    <div class="modal fade" id="agregarMotoModal" tabindex="-1" aria-labelledby="agregarMotoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="agregarMotoModalLabel">Agregar Nueva Moto</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="agregar_moto" value="1">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="marca" class="form-label">Marca</label>
                                <input type="text" class="form-control" id="marca" name="marca" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modelo" class="form-label">Modelo</label>
                                <input type="text" class="form-control" id="modelo" name="modelo" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="placa" class="form-label">Placa</label>
                                <input type="text" class="form-control" id="placa" name="placa" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="serie" class="form-label">Número de Serie</label>
                                <input type="text" class="form-control" id="serie" name="serie">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="text" class="form-control" id="color" name="color">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="kilometraje" class="form-label">Kilometraje Actual</label>
                                <input type="number" class="form-control" id="kilometraje" name="kilometraje" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Registrar Moto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configurar modal de solicitud de mantenimiento
        const solicitudModal = document.getElementById('solicitudModal');
        solicitudModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const motoId = button.getAttribute('data-moto-id');
            const motoInfo = button.getAttribute('data-moto-info');
            
            document.getElementById('moto_id').value = motoId;
            document.getElementById('moto-info').textContent = motoInfo;
        });
        
        // Configurar modal de edición de solicitud
        const editarSolicitudModal = document.getElementById('editarSolicitudModal');
        editarSolicitudModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const mantenimientoId = button.getAttribute('data-mantenimiento-id');
            const novedades = button.getAttribute('data-novedades');
            
            document.getElementById('editar_mantenimiento_id').value = mantenimientoId;
            document.getElementById('editar_novedades').value = novedades;
        });
        
        // Función para confirmar recepción de moto
        function confirmarRecepcion(mantenimientoId) {
            if (confirm('¿Confirmas que has recibido tu moto? Esta acción no se puede deshacer.')) {
                fetch('confirmar_recepcion.php?id=' + mantenimientoId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Recepción confirmada con éxito. Gracias por tu confianza.');
                            location.reload();
                        } else {
                            alert('Error: ' . data.error);
                        }
                    })
                    .catch(error => {
                        alert('Error al confirmar la recepción. Por favor, intenta nuevamente.');
                    });
            }
        }
        
        // Scroll suave a la sección de historial
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('a[href^="#"]');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId === '#historial') {
                        const targetElement = document.querySelector(targetId);
                        if (targetElement) {
                            targetElement.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>