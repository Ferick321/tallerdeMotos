<?php
require_once 'config.php';

if (!isAuthenticated()) {
    redirect('login.php');
}

// Iniciar el temporizador para medir el rendimiento (solo en desarrollo)
$start_time = microtime(true);

/**
 * Función para ejecutar consultas preparadas con caché simple
 */
function getCachedQueryResult($conn, $query, $params = [], $cache_time = 60) {
    static $cache = [];
    $cache_key = md5($query . serialize($params));
    
    // Si tenemos un caché válido, lo retornamos
    if (isset($cache[$cache_key]) && 
        (time() - $cache[$cache_key]['time']) < $cache_time) {
        return $cache[$cache_key]['data'];
    }
    
    // Ejecutar la consulta
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Almacenar en caché
        $cache[$cache_key] = [
            'time' => time(),
            'data' => $result
        ];
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error en consulta: " . $e->getMessage());
        return [];
    }
}

// Obtener estadísticas básicas con caché de 5 minutos
$stats = [];
$queries = [
    'productos_bajo_stock' => "SELECT COUNT(*) as count FROM productos WHERE stock < stock_minimo AND activo = 1",
    'mantenimientos_pendientes' => "SELECT COUNT(*) as count FROM mantenimientos WHERE estado != 'entregado'",
    'ventas_hoy' => "SELECT COUNT(*) as count FROM ventas WHERE DATE(fecha) = CURDATE() AND estado = 'completada'",
    'clientes_registrados' => "SELECT COUNT(*) as count FROM clientes",
    'cambios_aceite_pendientes' => "SELECT COUNT(*) as count FROM motos WHERE proximo_cambio_aceite IS NOT NULL AND kilometraje >= proximo_cambio_aceite"
];

foreach ($queries as $key => $query) {
    $result = getCachedQueryResult($conn, $query, [], 300); // 5 minutos de caché
    $stats[$key] = $result[0]['count'] ?? 0;
}

// Obtener últimas notificaciones (sin caché para que sean siempre actuales)
$notificaciones = getCachedQueryResult($conn, 
    "SELECT id, titulo, mensaje, fecha, leida FROM notificaciones 
     WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 5", 
    [$_SESSION['user_id']], 
    0 // Sin caché
);

// Obtener últimos mantenimientos (excluyendo los entregados)
$ultimos_mantenimientos = getCachedQueryResult($conn,
    "SELECT m.id, m.fecha_ingreso, m.estado, 
            mo.marca, mo.modelo, mo.placa, 
            c.nombres as cliente_nombre, c.cedula, c.telefono
     FROM mantenimientos m
     JOIN motos mo ON m.moto_id = mo.id
     JOIN clientes c ON mo.cliente_id = c.id
     WHERE m.estado != 'entregado'
     ORDER BY m.fecha_ingreso DESC LIMIT 5",
    [],
    60 // 1 minuto de caché
);

// Obtener cambios de aceite pendientes
$cambios_aceite_pendientes = getCachedQueryResult($conn,
    "SELECT m.*, c.nombres as cliente_nombre, c.telefono, c.email
     FROM motos m
     JOIN clientes c ON m.cliente_id = c.id
     WHERE m.proximo_cambio_aceite IS NOT NULL AND m.kilometraje >= m.proximo_cambio_aceite
     ORDER BY m.proximo_cambio_aceite - m.kilometraje ASC LIMIT 5",
    [],
    300 // 5 minutos de caché
);

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
// Tiempo de carga (solo para desarrollo)
$load_time = round((microtime(true) - $start_time) * 1000, 2);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    
    <style>
        :root {
            --primary-color: #3498db;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --info-color: #1abc9c;
        }
        
        /* Mejoras para móviles */
        body {
            font-size: 14px;
        }
        
        .card-icon { 
            font-size: 2.5rem;
            margin-bottom: 0.5rem; 
            opacity: 0.8;
        }

        .stat-card { 
            transition: all 0.3s; 
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }
        
        .stat-card .card-body {
            padding: 1rem;
        }
        
        .stat-card h6 {
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card h2 {
            font-size: 1.5rem;
            margin-bottom: 0;
        }
        
        .notification-item { 
            cursor: pointer; 
            transition: background-color 0.2s;
            padding: 0.75rem 1rem;
        }
        
        .notification-item.unread {
            background-color: rgba(13, 110, 253, 0.05);
            border-left: 3px solid var(--primary-color);
        }
        
        .badge-estado {
            min-width: 70px;
            font-size: 0.7rem;
            padding: 0.35em 0.5em;
        }
        
        .progress-thin {
            height: 4px;
        }
        
        .whatsapp-btn {
            background-color: #25D366;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table th, .table td {
            padding: 0.5rem;
            white-space: nowrap;
        }
        
        /* Ajustes específicos para pantallas pequeñas */
        @media (max-width: 767.98px) {
            body {
                font-size: 13px;
            }
            
            .stat-card h2 {
                font-size: 1.3rem;
            }
            
            .card-icon {
                font-size: 2rem;
            }
            
            .navbar-brand {
                font-size: 1rem;
            }
            
            .table th, .table td {
                padding: 0.3rem;
                font-size: 0.8rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.7rem;
            }
            
            .list-group-item {
                padding: 0.5rem;
            }
        }
        
        /* Ajustes para pantallas muy pequeñas (menos de 400px) */
        @media (max-width: 399.98px) {
            .stat-card h6 {
                font-size: 0.7rem;
            }
            
            .stat-card h2 {
                font-size: 1.1rem;
            }
            
            .card-icon {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid mt-3">
        <!-- Fila de estadísticas rápidas - ahora en columnas apiladas en móviles -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-2">
            <!-- Productos bajo stock -->
            <div class="col">
                <div class="card stat-card text-white bg-primary h-100">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1">Bajo Stock</h6>
                                <h2 class="mb-0"><?php echo $stats['productos_bajo_stock']; ?></h2>
                                <a href="productos.php?filter=low_stock" class="text-white small d-block mt-1">
                                    Ver detalles
                                </a>
                            </div>
                            <div class="card-icon">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mantenimientos pendientes -->
            <div class="col">
                <div class="card stat-card text-white bg-warning h-100">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1">Mantenimientos</h6>
                                <h2 class="mb-0"><?php echo $stats['mantenimientos_pendientes']; ?></h2>
                                <a href="mantenimientos.php" class="text-white small d-block mt-1">
                                    Ver detalles
                                </a>
                            </div>
                            <div class="card-icon">
                                <i class="bi bi-tools"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ventas hoy -->
            <div class="col">
                <div class="card stat-card text-white bg-success h-100">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1">Ventas hoy</h6>
                                <h2 class="mb-0"><?php echo $stats['ventas_hoy']; ?></h2>
                                <a href="ventas.php" class="text-white small d-block mt-1">
                                    Ver detalles
                                </a>
                            </div>
                            <div class="card-icon">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cambios de aceite -->
            <div class="col">
                <div class="card stat-card text-white bg-danger h-100">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1">Cambio Aceite</h6>
                                <h2 class="mb-0"><?php echo $stats['cambios_aceite_pendientes']; ?></h2>
                                <a href="#cambios-aceite" class="text-white small d-block mt-1">
                                    Ver detalles
                                </a>
                            </div>
                            <div class="card-icon">
                                <i class="bi bi-fuel-pump"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Segunda fila: Contenido principal -->
        <div class="row mt-3 g-2">
            <!-- Columna principal (Mantenimientos) -->
            <div class="col-lg-8">
                <!-- Tarjeta de últimos mantenimientos -->
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <h5 class="mb-0 fs-6">Últimos Mantenimientos</h5>
                        <div class="d-flex gap-1">
                            <a href="mantenimientos.php" class="btn btn-sm btn-outline-primary py-0 px-1">
                                <i class="bi bi-list-ul"></i>
                            </a>
                            <a href="agregar_moto.php" class="btn btn-sm btn-primary py-0 px-1">
                                <i class="bi bi-plus"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-1">
                        <?php if (!empty($ultimos_mantenimientos)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Cliente</th>
                                            <th>Moto</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ultimos_mantenimientos as $mant): ?>
                                            <tr>
                                                <td>
                                                    <small><?php echo date('d/m H:i', strtotime($mant['fecha_ingreso'])); ?></small>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold small"><?php echo htmlspecialchars(shortenText($mant['cliente_nombre'], 15)); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars(shortenText($mant['cedula'], 8)); ?></small>
                                                </td>
                                                <td>
                                                    <div class="small"><?php echo htmlspecialchars(shortenText($mant['marca'] . ' ' . $mant['modelo'], 12)); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($mant['placa']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-estado <?php echo getEstadoClass($mant['estado']); ?>">
                                                        <?php echo shortenEstado($mant['estado']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="ver_mantenimiento.php?id=<?php echo $mant['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary py-0 px-1" 
                                                           title="Ver detalles">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <?php if ($mant['telefono']): ?>
                                                        <a href="https://wa.me/593<?php echo preg_replace('/[^0-9]/', '', substr($mant['telefono'], -9)); ?>?text=Estimado%20cliente,%20le%20escribimos%20del%20taller%20sobre%20el%20mantenimiento%20de%20su%20moto%20<?php echo urlencode($mant['marca'] . ' ' . $mant['modelo'] . ' (Placa: ' . $mant['placa'] . ')'); ?>%20que%20se%20encuentra%20en%20estado%20<?php echo urlencode($mant['estado']); ?>." 
                                                           class="btn btn-sm whatsapp-btn py-0 px-1" 
                                                           target="_blank"
                                                           title="Contactar por WhatsApp">
                                                            <i class="bi bi-whatsapp"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0 p-2 text-center">
                                <i class="bi bi-info-circle"></i> No hay mantenimientos pendientes
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Columna secundaria (Notificaciones y cambios de aceite) -->
            <div class="col-lg-4">
                <!-- Tarjeta de notificaciones -->
                <div class="card h-100">
                    <div class="card-header py-2">
                        <h5 class="mb-0 fs-6">Notificaciones</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($notificaciones)): ?>
                            <div class="p-2 text-center text-muted">
                                <i class="bi bi-bell-slash"></i>
                                <p class="mt-1 mb-0 small">No hay notificaciones</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush small">
                                <?php foreach ($notificaciones as $notif): ?>
                                    <div class="list-group-item notification-item py-2 px-2 <?php echo !$notif['leida'] ? 'unread' : ''; ?>" 
                                         data-id="<?php echo $notif['id']; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars(shortenText($notif['titulo'], 20)); ?></h6>
                                                <p class="mb-1"><?php echo htmlspecialchars(shortenText($notif['mensaje'], 30)); ?></p>
                                                <small class="text-muted">
                                                    <?php echo date('d/m H:i', strtotime($notif['fecha'])); ?>
                                                </small>
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger delete-notification py-0 px-1" 
                                                    data-id="<?php echo $notif['id']; ?>"
                                                    title="Eliminar notificación">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tarjeta de cambios de aceite pendientes -->
                <div class="card mt-2" id="cambios-aceite">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <h5 class="mb-0 fs-6">Cambios de Aceite</h5>
                        <span class="badge bg-danger"><?php echo $stats['cambios_aceite_pendientes']; ?></span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($cambios_aceite_pendientes)): ?>
                            <div class="p-2 text-center text-muted">
                                <i class="bi bi-check-circle"></i>
                                <p class="mt-1 mb-0 small">No hay cambios pendientes</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush small">
                                <?php foreach ($cambios_aceite_pendientes as $moto): ?>
                                    <div class="list-group-item py-2 px-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars(shortenText($moto['marca'] . ' ' . $moto['modelo'], 15)); ?></h6>
                                                <p class="mb-1"><?php echo htmlspecialchars(shortenText($moto['cliente_nombre'], 15)); ?></p>
                                                <small class="text-muted">Placa: <?php echo htmlspecialchars($moto['placa']); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-danger">
                                                    <?php echo number_format($moto['kilometraje'], 0); ?> km
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="progress progress-thin mt-1 mb-1">
                                            <div class="progress-bar bg-danger" 
                                                 role="progressbar" 
                                                 style="width: <?php echo min(100, ($moto['kilometraje'] / $moto['proximo_cambio_aceite']) * 100); ?>%"
                                                 aria-valuenow="<?php echo $moto['kilometraje']; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="<?php echo $moto['proximo_cambio_aceite']; ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex gap-1 mt-1">
                                            <a href="agregar_mantenimiento.php?moto_id=<?php echo $moto['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary flex-grow-1 py-0">
                                                <i class="bi bi-tools"></i>
                                            </a>
                                            <?php if ($moto['telefono']): ?>
                                            <a href="https://wa.me/593<?php echo preg_replace('/[^0-9]/', '', substr($moto['telefono'], -9)); ?>?text=Estimado%20cliente,%20su%20moto%20<?php echo urlencode($moto['marca'] . ' ' . $moto['modelo'] . ' (Placa: ' . $moto['placa'] . ')'); ?>%20tiene%20pendiente%20el%20cambio%20de%20aceite.%20Por%20favor%20contactarnos%20para%20agendar%20una%20cita." 
                                               class="btn btn-sm whatsapp-btn flex-grow-1 py-0"
                                               target="_blank">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                            <?php endif; ?>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para marcar notificación como leída
        function markNotificationAsRead(notifId) {
            return fetch('marcar_notificacion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + notifId
            });
        }

        // Función para eliminar notificación
        function deleteNotification(notifId) {
            return fetch('marcar_notificacion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'delete=' + notifId
            });
        }

        // Marcar como leída al hacer clic en una notificación
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notifId = this.getAttribute('data-id');
                
                markNotificationAsRead(notifId).then(() => {
                    this.classList.remove('unread');
                });
            });
        });

        // Eliminar notificación individual
        document.querySelectorAll('.delete-notification').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const notifId = this.getAttribute('data-id');
                
                if (confirm('¿Eliminar esta notificación?')) {
                    deleteNotification(notifId).then(() => {
                        this.closest('.notification-item').remove();
                    });
                }
            });
        });
    </script>
    
    <!-- Mostrar tiempo de carga solo en desarrollo -->
    <?php if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false): ?>
        <div class="position-fixed bottom-0 end-0 p-1 bg-dark text-white rounded-start small">
            <?php echo $load_time; ?> ms
        </div>
    <?php endif; ?>
</body>
</html>

<?php
// Función para acortar texto
function shortenText($text, $length) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}

// Función para acortar estado
function shortenEstado($estado) {
    $map = [
        'recibido' => 'Recibido',
        'en_proceso' => 'Proceso',
        'terminado' => 'Terminado',
        'entregado' => 'Entregado'
    ];
    return $map[$estado] ?? $estado;
}
?>