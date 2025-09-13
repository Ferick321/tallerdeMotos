<?php
require_once 'config.php';

if (!isAuthenticated()) {
    redirect('login.php');
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pendientes';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construir la consulta base
$query = "SELECT m.id, m.fecha_ingreso, m.fecha_entrega_estimada, m.fecha_entrega_real, 
                 m.novedades, m.estado, m.tipo_mantenimiento as tipo, m.kilometraje_actual, m.costo,
                 mo.id as moto_id, mo.marca, mo.modelo, mo.placa, mo.color, mo.kilometraje,
                 c.nombres as cliente_nombre, c.cedula, c.telefono, c.email,
                 u.nombre as responsable
          FROM mantenimientos m
          JOIN motos mo ON m.moto_id = mo.id
          JOIN clientes c ON mo.cliente_id = c.id
          LEFT JOIN usuarios u ON m.responsable_id = u.id";

$params = [];

// Procesar eliminación de mantenimiento
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $conn->prepare("SELECT estado FROM mantenimientos WHERE id = ?");
        $stmt->execute([$id]);
        $mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($mantenimiento['estado'] !== 'terminado' && $mantenimiento['estado'] !== 'entregado') {
            $_SESSION['error'] = "Solo se pueden eliminar mantenimientos terminados o entregados";
        } else {
            // Verificar si hay ventas asociadas
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM mantenimiento_ventas WHERE mantenimiento_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $_SESSION['error'] = "No se puede eliminar porque tiene ventas asociadas";
            } else {
                $stmt = $conn->prepare("DELETE FROM mantenimientos WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = "Mantenimiento eliminado con éxito";
            }
        }
        redirect('mantenimientos.php');
    } catch (PDOException $e) {
        $error = "Error al eliminar el mantenimiento: " . $e->getMessage();
    }
}

// Procesar actualización de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $id = intval($_POST['mantenimiento_id']);
    $nuevo_estado = $_POST['estado'];
    
    try {
        if ($nuevo_estado === 'entregado') {
            $stmt = $conn->prepare("UPDATE mantenimientos SET 
                                  estado = :estado, 
                                  fecha_entrega_real = NOW() 
                                  WHERE id = :id");
        } else {
            $stmt = $conn->prepare("UPDATE mantenimientos SET 
                                  estado = :estado 
                                  WHERE id = :id");
        }
        
        $stmt->bindParam(':estado', $nuevo_estado);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Enviar notificación si se marcó como entregado
        if ($nuevo_estado === 'entregado') {
            // Obtener datos del cliente para notificación
            $stmt = $conn->prepare("SELECT c.email, c.nombres, mo.placa 
                                  FROM mantenimientos m
                                  JOIN motos mo ON m.moto_id = mo.id
                                  JOIN clientes c ON mo.cliente_id = c.id
                                  WHERE m.id = ?");
            $stmt->execute([$id]);
            $datos = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($datos && !empty($datos['email'])) {
                $asunto = "Su moto está lista para recoger";
                $mensaje = "Estimado/a {$datos['nombres']},<br><br>";
                $mensaje .= "Le informamos que el mantenimiento de su moto con placa {$datos['placa']} ";
                $mensaje .= "ha sido completado y está listo para ser recogido.<br><br>";
                $mensaje .= "Puede pasar a recogerla en nuestro taller en horario de atención.<br><br>";
                $mensaje .= "Atentamente,<br>El equipo del Taller de Motos";

                sendEmail($datos['email'], $asunto, $mensaje);
            }
        }

        $_SESSION['success'] = "Estado actualizado correctamente a " . ucfirst(str_replace('_', ' ', $nuevo_estado));
        redirect('mantenimientos.php');
        
    } catch (PDOException $e) {
        $error = "Error al actualizar estado: " . $e->getMessage();
        error_log("Error en mantenimientos.php: " . $e->getMessage());
    }
}

// Aplicar filtros
if ($filter === 'pendientes') {
    $query .= " WHERE m.estado != 'entregado'";
} elseif ($filter === 'historial') {
    $query .= " WHERE m.estado = 'entregado'";
} elseif ($search) {
    $query .= " WHERE (c.nombres LIKE ? OR mo.placa LIKE ? OR mo.marca LIKE ? OR mo.modelo LIKE ?)";
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term, $search_term];
}

$query .= " ORDER BY m.fecha_ingreso DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$mantenimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener ventas asociadas para cada mantenimiento
$ventas_por_mantenimiento = [];
$ventas_result = [];

if (isset($_GET['mantenimiento_id'])) {
    $mantenimiento_id = intval($_GET['mantenimiento_id']);

    try {
        $query = "SELECT v.id, 
                         DATE_FORMAT(v.fecha, '%d/%m/%Y') as fecha, 
                         GROUP_CONCAT(CONCAT(p.nombre, ' (', vd.cantidad, ' x $', FORMAT(vd.precio_unitario, 2), ')')) as productos, 
                         SUM(vd.subtotal) as total
                  FROM ventas v
                  JOIN mantenimiento_ventas mv ON v.id = mv.venta_id
                  JOIN venta_detalles vd ON v.id = vd.venta_id
                  JOIN productos p ON vd.producto_id = p.id
                  WHERE mv.mantenimiento_id = ?
                  GROUP BY v.id
                  ORDER BY v.fecha DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute([$mantenimiento_id]);
        $ventas_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener ventas: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimientos - Taller de Motos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .badge-entregado { background-color: #28a745; }
        .badge-terminado { background-color: #007bff; }
        .badge-enproceso { background-color: #ffc107; color: #212529; }
        .badge-recibido { background-color: #6c757d; }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }
        
        .accordion-button:not(.collapsed) {
            background-color: #f8f9fa;
        }
        
        .whatsapp-btn {
            background-color: #25D366;
            color: white;
        }
        .whatsapp-btn:hover {
            background-color: #128C7E;
            color: white;
        }
        
        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #0d6efd;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-tools"></i> Gestión de Mantenimientos</h2>

    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

        <div class="card-body">
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'pendientes' ? 'active' : ''; ?>" href="?filter=pendientes">Pendientes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'historial' ? 'active' : ''; ?>" href="?filter=historial">Historial</a>
                </li>
            </ul>
            
            <div class="table-responsive">
                <table id="mantenimientosTable" class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Acciones</th>
                            <th>Ingreso</th>
                            <th>Cliente</th>
                            <th>Moto</th>
                            <th>Placa</th>
                            <th>Estado</th>
                            <th>Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mantenimientos as $m): ?>
                            <tr>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <button class="btn btn-sm btn-outline-primary ver-detalle-btn" 
                                                data-id="<?php echo $m['id']; ?>"
                                                data-cliente="<?php echo htmlspecialchars($m['cliente_nombre']); ?>"
                                                data-estado="<?php echo $m['estado']; ?>"
                                                data-nombres="<?php echo htmlspecialchars($m['cliente_nombre']); ?>"
                                                data-cedula="<?php echo $m['cedula']; ?>"
                                                data-marca="<?php echo $m['marca']; ?>"
                                                data-modelo="<?php echo $m['modelo']; ?>"
                                                data-placa="<?php echo $m['placa']; ?>"
                                                data-color="<?php echo $m['color']; ?>"
                                                data-kilometraje="<?php echo $m['kilometraje_actual']; ?>"
                                                data-novedades="<?php echo htmlspecialchars($m['novedades']); ?>">
                                            <i class="bi bi-eye"></i> Detalle
                                        </button>
                                        
                                        <a href="historial_moto.php?id=<?php echo $m['moto_id']; ?>" class="btn btn-sm btn-outline-info" title="Ver historial">
                                            <i class="bi bi-clock-history"></i>
                                        </a>
                                        
                                        <?php
                                        $telefono = $m['telefono'];
                                        if (!empty($telefono)) {
                                            // Solo números
                                            $telefono = preg_replace('/[^0-9]/', '', $telefono);
                                            // Si empieza con 0, reemplaza por 593 (Ecuador)
                                            if (strpos($telefono, '0') === 0) {
                                                $telefono = '593' . substr($telefono, 1);
                                            }
                                            // Si el número tiene menos de 10 dígitos, no mostrar botón
                                            if (strlen($telefono) >= 10) {
                                                $mensaje = "*Hola* {$m['cliente_nombre']},\nSu moto ha sido registrada para mantenimiento.\n\n"
                                                         . "*Detalles del mantenimiento:*\n"
                                                         . "*Marca:* {$m['marca']}\n"
                                                         . "*Modelo:* {$m['modelo']}\n"
                                                         . "*Color:* {$m['color']}\n"
                                                         . "*Placa:* {$m['placa']}\n"
                                                         . "*Kilometraje:* {$m['kilometraje_actual']} km\n"
                                                         . "*Novedades:* {$m['novedades']}\n\n"
                                                         . "Si los datos son correctos por favor confirme con un *(OK)*\n"
                                                         . "*Fecha de ingreso:* " . date('d/m/Y', strtotime($m['fecha_ingreso']));
                                                
                                                $url_whatsapp = "https://wa.me/{$telefono}?text=" . rawurlencode($mensaje);
                                                ?>
                                                <a href="<?php echo $url_whatsapp; ?>" class="btn btn-sm whatsapp-btn" target="_blank" title="Enviar por WhatsApp">
                                                    <i class="bi bi-whatsapp"></i>
                                                </a>
                                                <?php
                                            }
                                        }
                                        
                                         if ($m['estado'] === 'terminado' || $m['estado'] === 'entregado'): ?>
                                            <a href="?delete=<?php echo $m['id']; ?>" 
                                               onclick="return confirm('¿Está seguro de eliminar este mantenimiento?')" 
                                               class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($m['fecha_ingreso'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($m['cliente_nombre']); ?>
                                    <small class="d-block text-muted"><?php echo $m['cedula']; ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($m['marca'] . ' ' . $m['modelo']); ?>
                                    <small class="d-block text-muted"><?php echo $m['color']; ?></small>
                                </td>
                                <td><?php echo $m['placa']; ?></td>
                                <td>
                                    <?php
                                    $badge_class = '';
                                    switch($m['estado']) {
                                        case 'entregado': $badge_class = 'badge-entregado'; break;
                                        case 'terminado': $badge_class = 'badge-terminado'; break;
                                        case 'en_proceso': $badge_class = 'badge-enproceso'; break;
                                        default: $badge_class = 'badge-recibido';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $m['estado'])); ?>
                                    </span>
                                    <?php if ($m['tipo'] === 'cambio_aceite'): ?>
                                        <span class="badge bg-info">Cambio aceite</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $m['responsable'] ?? 'Sin asignar'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles y actualización -->
<div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detalleModalLabel">Detalles del Mantenimiento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="mantenimiento_id" id="modalMantenimientoId">
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Información del Cliente</h5>
                        <p><strong>Nombre:</strong> <span id="modalClienteNombre"></span></p>
                        <p><strong>Cédula:</strong> <span id="modalCedula"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Información de la Moto</h5>
                        <p><strong>Marca/Modelo:</strong> <span id="modalMarca"></span> <span id="modalModelo"></span></p>
                        <p><strong>Placa/Color:</strong> <span id="modalPlaca"></span> / <span id="modalColor"></span></p>
                        <p><strong>Kilometraje:</strong> <span id="modalKilometraje"></span> km</p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="estado" class="form-label">Estado del Mantenimiento</label>
                    <select name="estado" id="modalEstado" class="form-select">
                        <option value="recibido">Recibido</option>
                        <option value="en_proceso">En Proceso</option>
                        <option value="terminado">Terminado</option>
                        <option value="entregado">Entregado</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Novedades/Trabajos Realizados</label>
                    <div class="border p-2 rounded bg-light" id="modalNovedades"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" name="actualizar_estado" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Inicializar DataTable con configuración en español
    $('#mantenimientosTable').DataTable({
        language: {
            "decimal": "",
            "emptyTable": "No hay datos disponibles en la tabla",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros coincidentes",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": activar para ordenar columna ascendente",
                "sortDescending": ": activar para ordenar columna descendente"
            }
        },
        order: [[1, 'desc']] // Ordenar por la segunda columna (Ingreso) de forma descendente
    });
    
    
    // Configurar modal de detalles
    $('#mantenimientosTable').on('click', '.ver-detalle-btn', function() {
        const id = $(this).data('id');
        const cliente = $(this).data('cliente');
        const cedula = $(this).data('cedula');
        const marca = $(this).data('marca');
        const modelo = $(this).data('modelo');
        const placa = $(this).data('placa');
        const color = $(this).data('color');
        const kilometraje = $(this).data('kilometraje');
        const novedades = $(this).data('novedades');
        const estado = $(this).data('estado');
        
        $('#modalMantenimientoId').val(id);
        $('#modalEstado').val(estado);
        $('#modalClienteNombre').text(cliente);
        $('#modalCedula').text(cedula);
        $('#modalMarca').text(marca);
        $('#modalModelo').text(modelo);
        $('#modalPlaca').text(placa);
        $('#modalColor').text(color);
        $('#modalKilometraje').text(kilometraje);
        $('#modalNovedades').text(novedades);
        
        
        // Cargar ventas asociadas via AJAX
        $.get('obtener_ventas_mantenimiento.php', { mantenimiento_id: id }, function(data) {
            if (data.length > 0) {
                let html = '<div class="table-responsive"><table class="table table-sm">';
                html += '<thead><tr><th>Fecha</th><th>Productos</th><th>Total</th></tr></thead><tbody>';
                
                data.forEach(function(venta) {
                    html += `<tr>
                        <td>${venta.fecha}</td>
                        <td>${venta.productos}</td>
                        <td>$${parseFloat(venta.total).toFixed(2)}</td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
                $('#ventasContainer').html(html);
            } else {
                $('#ventasContainer').html('<p class="text-muted">No hay repuestos/servicios registrados</p>');
            }
        }, 'json').fail(function() {
            $('#ventasContainer').html('<p class="text-danger">Error al cargar los repuestos</p>');
        });
        
        new bootstrap.Modal('#detalleModal').show();
    });
});
</script>
</body>
</html>