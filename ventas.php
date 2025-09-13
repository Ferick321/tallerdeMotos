<?php
require_once 'config.php';

if (!isAuthenticated()) {
    redirect('login.php');
}

// Configuración de paginación
$por_pagina = 20;
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina - 1) * $por_pagina;

// Parámetros de búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Consulta base para obtener ventas
$query = "SELECT SQL_CALC_FOUND_ROWS 
                 v.id, v.fecha, v.total, v.estado, 
                 u.nombre as vendedor,
                 c.nombres as cliente_nombre, c.cedula as cliente_cedula
          FROM ventas v
          JOIN usuarios u ON v.usuario_id = u.id
          LEFT JOIN clientes c ON v.cliente_id = c.id";

$where = [];
$params = [];

if ($search) {
    $where[] = "(c.nombres LIKE :search OR c.cedula LIKE :search OR u.nombre LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($fecha_inicio) {
    $where[] = "DATE(v.fecha) >= :fecha_inicio";
    $params[':fecha_inicio'] = $fecha_inicio;
}

if ($fecha_fin) {
    $where[] = "DATE(v.fecha) <= :fecha_fin";
    $params[':fecha_fin'] = $fecha_fin;
}

if ($estado && $estado !== 'todos') {
    $where[] = "v.estado = :estado";
    $params[':estado'] = $estado;
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY v.fecha DESC LIMIT :limit OFFSET :offset";

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($query);

// Vincular parámetros de búsqueda
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Vincular parámetros de paginación como enteros
$stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener el total de registros para paginación
$total_registros = $conn->query("SELECT FOUND_ROWS()")->fetchColumn();
$total_paginas = ceil($total_registros / $por_pagina);

// Procesar anulación/eliminación de venta
if (isset($_GET['accion'])) {
    $id = intval($_GET['id']);
    $accion = $_GET['accion'];
    
    try {
        if ($accion === 'anular') {
            // Obtener detalles de la venta para restablecer stock
            $stmt = $conn->prepare("SELECT producto_id, cantidad FROM venta_detalles WHERE venta_id = ?");
            $stmt->execute([$id]);
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Restablecer stock
            foreach ($detalles as $detalle) {
                $stmt = $conn->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
                $stmt->execute([$detalle['cantidad'], $detalle['producto_id']]);
            }
            
            // Marcar venta como cancelada
            $stmt = $conn->prepare("UPDATE ventas SET estado = 'cancelada' WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = "Venta anulada con éxito. Stock restablecido.";
        } elseif ($accion === 'eliminar' && hasRole('administrador')) {
            // Eliminar completamente la venta (solo administradores)
            $conn->beginTransaction();
            
            // Primero restablecer stock si la venta no estaba cancelada
            $stmt = $conn->prepare("SELECT estado FROM ventas WHERE id = ?");
            $stmt->execute([$id]);
            $venta_estado = $stmt->fetchColumn();
            
            if ($venta_estado !== 'cancelada') {
                $stmt = $conn->prepare("SELECT producto_id, cantidad FROM venta_detalles WHERE venta_id = ?");
                $stmt->execute([$id]);
                $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($detalles as $detalle) {
                    $stmt = $conn->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
                    $stmt->execute([$detalle['cantidad'], $detalle['producto_id']]);
                }
            }
            
            // Eliminar detalles de venta
            $stmt = $conn->prepare("DELETE FROM venta_detalles WHERE venta_id = ?");
            $stmt->execute([$id]);
            
            // Eliminar venta
            $stmt = $conn->prepare("DELETE FROM ventas WHERE id = ?");
            $stmt->execute([$id]);
            
            $conn->commit();
            
            $_SESSION['success'] = "Venta eliminada completamente del sistema.";
        }
        
        redirect('ventas.php');
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = "Error al procesar la venta: " . $e->getMessage();
    }
}

// Procesar eliminación masiva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_seleccionados']) && hasRole('administrador')) {
    $ids = isset($_POST['ids']) ? $_POST['ids'] : [];
    
    if (empty($ids)) {
        $_SESSION['error'] = "No se seleccionaron ventas para eliminar";
        redirect('ventas.php');
    }
    
    try {
        $conn->beginTransaction();
        
        // Convertir todos los IDs a enteros para seguridad
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // Primero restablecer stock de ventas no canceladas
        $stmt = $conn->prepare("SELECT vd.producto_id, vd.cantidad 
                              FROM venta_detalles vd
                              JOIN ventas v ON vd.venta_id = v.id
                              WHERE vd.venta_id IN ($placeholders) AND v.estado != 'cancelada'");
        $stmt->execute($ids);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($detalles as $detalle) {
            $stmt = $conn->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$detalle['cantidad'], $detalle['producto_id']]);
        }
        
        // Eliminar detalles de venta
        $stmt = $conn->prepare("DELETE FROM venta_detalles WHERE venta_id IN ($placeholders)");
        $stmt->execute($ids);
        
        // Eliminar ventas
        $stmt = $conn->prepare("DELETE FROM ventas WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        
        $conn->commit();
        
        $_SESSION['success'] = count($ids) . " ventas eliminadas completamente del sistema.";
        redirect('ventas.php');
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Error al eliminar ventas: " . $e->getMessage();
    }
}

// Función para generar enlaces de paginación manteniendo los parámetros de búsqueda
function getPaginationLink($pagina) {
    $params = $_GET;
    $params['pagina'] = $pagina;
    return 'ventas.php?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ventas - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .table-responsive {
            overflow-x: auto;
        }
        .table th {
            white-space: nowrap;
        }
        .form-check-input {
            margin-left: 0;
        }
        .badge {
            font-size: 0.9em;
        }
        .pagination {
            justify-content: center;
        }
        .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .page-link {
            color: #0d6efd;
        }
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 10px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Ventas</h2>
            <div>
                <a href="nueva_venta.php" class="btn btn-primary">
                    <i class="bi bi-plus"></i> Nueva Venta
                </a>
                <?php if (hasRole('administrador')): ?>
                    <a href="exportar_ventas.php" class="btn btn-success ms-2">
                        <i class="bi bi-file-earmark-excel"></i> Exportar
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <form method="GET" class="row g-3" id="filtroForm">
                    <input type="hidden" name="pagina" value="1">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Buscar por cliente o vendedor..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="fecha_inicio" class="form-control" placeholder="Fecha inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="fecha_fin" class="form-control" placeholder="Fecha fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="estado">
                            <option value="todos" <?php echo $estado === 'todos' || $estado === '' ? 'selected' : ''; ?>>Todos los estados</option>
                            <option value="completada" <?php echo $estado === 'completada' ? 'selected' : ''; ?>>Completadas</option>
                            <option value="cancelada" <?php echo $estado === 'cancelada' ? 'selected' : ''; ?>>Canceladas</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filtrar</button>
                        <a href="ventas.php" class="btn btn-outline-secondary w-100 mt-2">Limpiar</a>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <form method="POST" id="formEliminarMasivo">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="40px"></th>
                                    <th>Acciones</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Vendedor</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ventas)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No se encontraron ventas</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ventas as $venta): ?>
                                        <tr>
                                            <td>
                                                <?php if (hasRole('administrador')): ?>
                                                    <input type="checkbox" class="form-check-input" name="ids[]" value="<?php echo $venta['id']; ?>">
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary ver-detalle" 
                                                        data-id="<?php echo $venta['id']; ?>"
                                                        data-fecha="<?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?>"
                                                        data-cliente="<?php echo $venta['cliente_nombre'] ? htmlspecialchars($venta['cliente_nombre'] . ' (' . $venta['cliente_cedula'] . ')') : 'Sin cliente'; ?>"
                                                        data-vendedor="<?php echo htmlspecialchars($venta['vendedor']); ?>"
                                                        data-total="<?php echo number_format($venta['total'], 2); ?>"
                                                        data-estado="<?php echo $venta['estado']; ?>">
                                                    <i class="bi bi-eye"></i> Ver
                                                </button>
                                                <?php if ($venta['estado'] !== 'cancelada'): ?>
                                                    <a href="ventas.php?accion=anular&id=<?php echo $venta['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Estás seguro de anular esta venta? Se restablecerá el stock.')">
                                                        <i class="bi bi-x-circle"></i> Anular
                                                    </a>
                                                <?php elseif (hasRole('administrador')): ?>
                                                    <a href="ventas.php?accion=eliminar&id=<?php echo $venta['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Estás seguro de ELIMINAR COMPLETAMENTE esta venta? Esta acción no se puede deshacer.')">
                                                        <i class="bi bi-trash"></i> Eliminar
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></td>
                                            <td>
                                                <?php if ($venta['cliente_nombre']): ?>
                                                    <?php echo htmlspecialchars($venta['cliente_nombre']); ?>
                                                    <small class="text-muted d-block"><?php echo htmlspecialchars($venta['cliente_cedula']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin cliente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($venta['vendedor']); ?></td>
                                            <td><?php echo number_format($venta['total'], 2); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    switch($venta['estado']) {
                                                        case 'pendiente': echo 'bg-warning text-dark'; break;
                                                        case 'completada': echo 'bg-success'; break;
                                                        case 'cancelada': echo 'bg-danger'; break;
                                                        default: echo 'bg-light text-dark';
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst($venta['estado']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination mt-3">
                                <?php if ($pagina > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo getPaginationLink(1); ?>" aria-label="First">
                                            <span aria-hidden="true">&laquo;&laquo;</span>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo getPaginationLink($pagina - 1); ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php 
                                $inicio = max(1, $pagina - 2);
                                $fin = min($total_paginas, $pagina + 2);
                                
                                if ($inicio > 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                
                                for ($i = $inicio; $i <= $fin; $i++): ?>
                                    <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo getPaginationLink($i); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor;
                                
                                if ($fin < $total_paginas) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                ?>
                                
                                <?php if ($pagina < $total_paginas): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo getPaginationLink($pagina + 1); ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo getPaginationLink($total_paginas); ?>" aria-label="Last">
                                            <span aria-hidden="true">&raquo;&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    
                    <?php if (hasRole('administrador') && !empty($ventas)): ?>
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-secondary" id="seleccionarTodos">
                                <i class="bi bi-check-square"></i> Seleccionar todos
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="deseleccionarTodos">
                                <i class="bi bi-square"></i> Deseleccionar todos
                            </button>
                            <button type="submit" name="eliminar_seleccionados" class="btn btn-danger float-end" onclick="return confirm('¿Estás seguro de eliminar las ventas seleccionadas? Esta acción no restablecerá el stock y no se puede deshacer.')">
                                <i class="bi bi-trash"></i> Eliminar seleccionados
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal para ver detalles de venta -->
    <div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detalleModalLabel">Detalles de Venta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <h6>Fecha:</h6>
                            <p id="modal-fecha"></p>
                        </div>
                        <div class="col-md-4">
                            <h6>Cliente:</h6>
                            <p id="modal-cliente"></p>
                        </div>
                        <div class="col-md-4">
                            <h6>Vendedor:</h6>
                            <p id="modal-vendedor"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Estado:</h6>
                            <p>
                                <span class="badge" id="modal-estado-badge"></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Total:</h6>
                            <p id="modal-total"></p>
                        </div>
                    </div>
                    <h6>Productos:</h6>
                    <div class="loading-spinner" id="loadingSpinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p>Cargando detalles...</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm" id="modal-productos">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>P. Unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los productos se cargarán via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configurar modal de detalles
        document.addEventListener('DOMContentLoaded', function() {
            // Evento para los botones de ver detalle
            document.querySelectorAll('.ver-detalle').forEach(button => {
                button.addEventListener('click', function() {
                    const ventaId = this.getAttribute('data-id');
                    const fecha = this.getAttribute('data-fecha');
                    const cliente = this.getAttribute('data-cliente');
                    const vendedor = this.getAttribute('data-vendedor');
                    const total = this.getAttribute('data-total');
                    const estado = this.getAttribute('data-estado');
                    
                    // Configurar los datos estáticos
                    document.getElementById('modal-fecha').textContent = fecha;
                    document.getElementById('modal-cliente').textContent = cliente;
                    document.getElementById('modal-vendedor').textContent = vendedor;
                    document.getElementById('modal-total').textContent = total;
                    
                    // Configurar el badge de estado
                    const estadoBadge = document.getElementById('modal-estado-badge');
                    estadoBadge.textContent = estado.charAt(0).toUpperCase() + estado.slice(1);
                    
                    switch(estado) {
                        case 'pendiente': 
                            estadoBadge.className = 'badge bg-warning text-dark';
                            break;
                        case 'completada': 
                            estadoBadge.className = 'badge bg-success';
                            break;
                        case 'cancelada': 
                            estadoBadge.className = 'badge bg-danger';
                            break;
                        default: 
                            estadoBadge.className = 'badge bg-light text-dark';
                    }
                    
                    // Mostrar spinner y ocultar tabla temporalmente
                    document.getElementById('loadingSpinner').style.display = 'block';
                    document.querySelector('#modal-productos').style.display = 'none';
                    document.querySelector('#modal-productos tbody').innerHTML = '';
                    
                    // Cargar detalles de productos via AJAX
                    fetch('detalle_venta.php?id=' + ventaId)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Error al cargar los detalles');
                            }
                            return response.json();
                        })
                        .then(data => {
                            const tbody = document.querySelector('#modal-productos tbody');
                            tbody.innerHTML = '';
                            
                            if (data.length === 0) {
                                const tr = document.createElement('tr');
                                tr.innerHTML = '<td colspan="4" class="text-center">No hay productos registrados para esta venta</td>';
                                tbody.appendChild(tr);
                            } else {
                                data.forEach(producto => {
                                    const tr = document.createElement('tr');
                                    tr.innerHTML = `
                                        <td>${producto.nombre}</td>
                                        <td>${producto.cantidad}</td>
                                        <td>${producto.precio_unitario}</td>
                                        <td>${producto.subtotal}</td>
                                    `;
                                    tbody.appendChild(tr);
                                });
                            }
                            
                            // Ocultar spinner y mostrar tabla
                            document.getElementById('loadingSpinner').style.display = 'none';
                            document.querySelector('#modal-productos').style.display = 'table';
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            const tbody = document.querySelector('#modal-productos tbody');
                            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error al cargar los detalles</td></tr>';
                            
                            document.getElementById('loadingSpinner').style.display = 'none';
                            document.querySelector('#modal-productos').style.display = 'table';
                        });
                    
                    // Mostrar el modal
                    const modal = new bootstrap.Modal(document.getElementById('detalleModal'));
                    modal.show();
                });
            });

            // Seleccionar/Deseleccionar todos los checkboxes
            document.getElementById('seleccionarTodos').addEventListener('click', function() {
                document.querySelectorAll('.form-check-input').forEach(checkbox => {
                    checkbox.checked = true;
                });
            });

            document.getElementById('deseleccionarTodos').addEventListener('click', function() {
                document.querySelectorAll('.form-check-input').forEach(checkbox => {
                    checkbox.checked = false;
                });
            });
        });
    </script>
</body>
</html>