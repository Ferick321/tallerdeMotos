<?php
require_once 'config.php';

if (!isAuthenticated()) {
    redirect('login.php');
}

// Configuración de paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina - 1) * $por_pagina;

$moto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($moto_id === 0) {
    $_SESSION['error'] = "Moto no especificada";
    redirect('motos.php');
}

// Obtener información básica de la moto y cliente
$stmt = $conn->prepare("SELECT m.*, c.nombres as cliente_nombre, c.cedula, c.telefono, c.email
                       FROM motos m
                       JOIN clientes c ON m.cliente_id = c.id
                       WHERE m.id = ?");
$stmt->execute([$moto_id]);
$moto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$moto) {
    $_SESSION['error'] = "Moto no encontrada";
    redirect('motos.php');
}

// Obtener historial de mantenimientos con paginación
$query = "SELECT SQL_CALC_FOUND_ROWS m.*, u.nombre as responsable
          FROM mantenimientos m
          LEFT JOIN usuarios u ON m.responsable_id = u.id
          WHERE m.moto_id = :moto_id
          ORDER BY m.fecha_ingreso DESC
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
$stmt->bindValue(':moto_id', $moto_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$mantenimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener el total de registros para paginación
$total_registros = $conn->query("SELECT FOUND_ROWS()")->fetchColumn();
$total_paginas = ceil($total_registros / $por_pagina);

// Obtener repuestos asociados a los mantenimientos (de la tabla mantenimiento_repuestos)
$repuestos = [];
if (!empty($mantenimientos)) {
    $mantenimiento_ids = array_column($mantenimientos, 'id');
    $placeholders = implode(',', array_fill(0, count($mantenimiento_ids), '?'));
    
    $query = "SELECT mr.*, u.nombre as usuario_nombre 
              FROM mantenimiento_repuestos mr
              LEFT JOIN usuarios u ON mr.usuario_eliminacion = u.id
              WHERE mr.mantenimiento_id IN ($placeholders)
              ORDER BY mr.fecha_registro DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($mantenimiento_ids);
    $repuestos_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar repuestos por mantenimiento
    foreach ($repuestos_result as $repuesto) {
        $repuestos[$repuesto['mantenimiento_id']][] = $repuesto;
    }
}

// Obtener productos para el formulario
$stmt = $conn->query("SELECT id, codigo, nombre, precio_venta FROM productos WHERE activo = 1 ORDER BY nombre");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar nueva venta asociada a mantenimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_venta'])) {
        $mantenimiento_id = intval($_POST['mantenimiento_id']);
        $producto_id = intval($_POST['producto_id']);
        $cantidad = intval($_POST['cantidad']);
        $precio_unitario = floatval($_POST['precio_unitario']);
        $subtotal = floatval($_POST['subtotal']);
        
        try {
            // Obtener información del producto para validar stock
            $stmt = $conn->prepare("SELECT id, codigo, nombre, precio_venta FROM productos WHERE id = ?");
            $stmt->execute([$producto_id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                throw new Exception("Producto no encontrado");
            }
            
            // Verificar stock disponible
            $stmt = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
            $stmt->execute([$producto_id]);
            $stock = $stmt->fetchColumn();
            
            if ($stock < $cantidad) {
                throw new Exception("Stock insuficiente. Disponible: " . $stock);
            }
            
            // Iniciar transacción
            $conn->beginTransaction();
            
            // 1. Crear venta
            $stmt = $conn->prepare("INSERT INTO ventas (usuario_id, total, estado) VALUES (?, ?, 'completada')");
            $stmt->execute([$_SESSION['user_id'], $subtotal]);
            $venta_id = $conn->lastInsertId();
            
            // 2. Agregar detalle de venta
            $stmt = $conn->prepare("INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$venta_id, $producto_id, $cantidad, $precio_unitario, $subtotal]);
            
            // 3. Actualizar stock
            $stmt = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$cantidad, $producto_id]);
            
            // 4. Relacionar venta con mantenimiento
            $stmt = $conn->prepare("INSERT INTO mantenimiento_ventas (mantenimiento_id, venta_id) VALUES (?, ?)");
            $stmt->execute([$mantenimiento_id, $venta_id]);
            
            // 5. Registrar repuesto en el historial permanente
            $stmt = $conn->prepare("INSERT INTO mantenimiento_repuestos 
                                  (mantenimiento_id, producto_id, producto_nombre, producto_codigo, 
                                   cantidad, precio_unitario, subtotal)
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $mantenimiento_id,
                $producto_id,
                $producto['nombre'],
                $producto['codigo'],
                $cantidad,
                $precio_unitario,
                $subtotal
            ]);
            
            // 6. Actualizar costo total del mantenimiento
            $stmt = $conn->prepare("UPDATE mantenimientos SET costo = IFNULL(costo, 0) + ? WHERE id = ?");
            $stmt->execute([$subtotal, $mantenimiento_id]);
            
            // Registrar en el historial de auditoría
            $stmt = $conn->prepare("INSERT INTO mantenimiento_historial 
                                  (mantenimiento_id, usuario_id, accion, descripcion)
                                  VALUES (?, ?, 'modificacion', ?)");
            $descripcion = "Se agregó repuesto: " . $producto['nombre'] . " (Cantidad: $cantidad, Total: $$subtotal)";
            $stmt->execute([$mantenimiento_id, $_SESSION['user_id'], $descripcion]);
            
            $conn->commit();
            
            $_SESSION['success'] = "Venta registrada y asociada al mantenimiento";
            redirect("historial_moto.php?id=$moto_id");
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error al registrar la venta: " . $e->getMessage();
        }
    }
    
    // Procesar eliminación de venta
    if (isset($_POST['eliminar_venta'])) {
        $venta_id = intval($_POST['venta_id']);
        $mantenimiento_id = intval($_POST['mantenimiento_id']);
        
        try {
            // Iniciar transacción
            $conn->beginTransaction();
            
            // 1. Obtener detalles de la venta para restablecer stock
            $stmt = $conn->prepare("SELECT producto_id, cantidad FROM venta_detalles WHERE venta_id = ?");
            $stmt->execute([$venta_id]);
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 2. Restablecer stock de productos
            foreach ($detalles as $detalle) {
                $stmt = $conn->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
                $stmt->execute([$detalle['cantidad'], $detalle['producto_id']]);
            }
            
            // 3. Obtener el total de la venta para actualizar el mantenimiento
            $stmt = $conn->prepare("SELECT total FROM ventas WHERE id = ?");
            $stmt->execute([$venta_id]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // 4. Marcar como eliminado en mantenimiento_repuestos (en lugar de borrar)
            $stmt = $conn->prepare("UPDATE mantenimiento_repuestos 
                                  SET eliminado = TRUE, fecha_eliminacion = NOW(), usuario_eliminacion = ?
                                  WHERE mantenimiento_id = ? AND producto_id IN (
                                      SELECT producto_id FROM venta_detalles WHERE venta_id = ?
                                  )");
            $stmt->execute([$_SESSION['user_id'], $mantenimiento_id, $venta_id]);
            
            // 5. Eliminar relación con mantenimiento
            $stmt = $conn->prepare("DELETE FROM mantenimiento_ventas WHERE venta_id = ? AND mantenimiento_id = ?");
            $stmt->execute([$venta_id, $mantenimiento_id]);
            
            // 6. Actualizar costo del mantenimiento
            $stmt = $conn->prepare("UPDATE mantenimientos SET costo = IFNULL(costo, 0) - ? WHERE id = ?");
            $stmt->execute([$venta['total'], $mantenimiento_id]);
            
            // 7. Eliminar detalles de venta
            $stmt = $conn->prepare("DELETE FROM venta_detalles WHERE venta_id = ?");
            $stmt->execute([$venta_id]);
            
            // 8. Eliminar venta
            $stmt = $conn->prepare("DELETE FROM ventas WHERE id = ?");
            $stmt->execute([$venta_id]);
            
            // Registrar en el historial de auditoría
            $stmt = $conn->prepare("INSERT INTO mantenimiento_historial 
                                  (mantenimiento_id, usuario_id, accion, descripcion)
                                  VALUES (?, ?, 'modificacion', ?)");
            $descripcion = "Se eliminó venta ID: $venta_id (Total: $" . $venta['total'] . ")";
            $stmt->execute([$mantenimiento_id, $_SESSION['user_id'], $descripcion]);
            
            $conn->commit();
            
            $_SESSION['success'] = "Venta eliminada correctamente";
            redirect("historial_moto.php?id=$moto_id");
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error al eliminar la venta: " . $e->getMessage();
        }
    }
    
    // Procesar cambio de estado a "entregado"
    if (isset($_POST['marcar_entregado'])) {
        $mantenimiento_id = intval($_POST['mantenimiento_id']);
        
        try {
            $stmt = $conn->prepare("UPDATE mantenimientos 
                                   SET estado = 'entregado', fecha_entrega_real = NOW()
                                   WHERE id = ?");
            $stmt->execute([$mantenimiento_id]);
            
            // Registrar en el historial de auditoría
            $stmt = $conn->prepare("INSERT INTO mantenimiento_historial 
                                  (mantenimiento_id, usuario_id, accion, descripcion)
                                  VALUES (?, ?, 'estado', 'Marcado como entregado')");
            $stmt->execute([$mantenimiento_id, $_SESSION['user_id']]);
            
            $_SESSION['success'] = "Mantenimiento marcado como entregado";
            redirect("historial_moto.php?id=$moto_id");
        } catch (Exception $e) {
            $error = "Error al actualizar el estado: " . $e->getMessage();
        }
    }
    
    // Procesar exportación del historial
    if (isset($_POST['exportar_historial'])) {
        $fecha_inicio = $_POST['fecha_inicio_export'] ?? null;
        $fecha_fin = $_POST['fecha_fin_export'] ?? null;
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=historial_moto_' . $moto['placa'] . '_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Escribir encabezados
        fputcsv($output, [
            'Fecha',
            'Estado',
            'Responsable',
            'Novedades',
            'Kilometraje',
            'Repuestos/Servicios',
            'Total',
            'Entregado'
        ]);
        
        // Consulta para obtener mantenimientos con filtros de fecha
        $query = "SELECT m.*, u.nombre as responsable
                  FROM mantenimientos m
                  LEFT JOIN usuarios u ON m.responsable_id = u.id
                  WHERE m.moto_id = ?";
        
        $params = [$moto_id];
        
        if ($fecha_inicio) {
            $query .= " AND DATE(m.fecha_ingreso) >= ?";
            $params[] = $fecha_inicio;
        }
        
        if ($fecha_fin) {
            $query .= " AND DATE(m.fecha_ingreso) <= ?";
            $params[] = $fecha_fin;
        }
        
        $query .= " ORDER BY m.fecha_ingreso DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $mantenimientos_export = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener repuestos para los mantenimientos exportados
        $repuestos_export = [];
        if (!empty($mantenimientos_export)) {
            $mantenimiento_ids_export = array_column($mantenimientos_export, 'id');
            $placeholders = implode(',', array_fill(0, count($mantenimiento_ids_export), '?'));
            
            $query = "SELECT mr.* 
                      FROM mantenimiento_repuestos mr
                      WHERE mr.mantenimiento_id IN ($placeholders) AND mr.eliminado = FALSE
                      ORDER BY mr.fecha_registro DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute($mantenimiento_ids_export);
            $repuestos_result_export = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($repuestos_result_export as $repuesto) {
                $repuestos_export[$repuesto['mantenimiento_id']][] = $repuesto;
            }
        }
        
        // Escribir datos
        foreach ($mantenimientos_export as $mantenimiento) {
            $productos_str = '';
            $total = 0;
            
            if (!empty($repuestos_export[$mantenimiento['id']])) {
                $productos = [];
                foreach ($repuestos_export[$mantenimiento['id']] as $repuesto) {
                    $productos[] = $repuesto['producto_nombre'] . " (x" . $repuesto['cantidad'] . ")";
                    $total += $repuesto['subtotal'];
                }
                $productos_str = implode('; ', $productos);
            }
            
            fputcsv($output, [
                date('d/m/Y', strtotime($mantenimiento['fecha_ingreso'])),
                ucfirst(str_replace('_', ' ', $mantenimiento['estado'])),
                $mantenimiento['responsable'] ?? 'No asignado',
                $mantenimiento['novedades'],
                $mantenimiento['kilometraje_actual'] ? number_format($mantenimiento['kilometraje_actual'], 0) : '',
                $productos_str,
                number_format($total, 2),
                $mantenimiento['fecha_entrega_real'] ? date('d/m/Y', strtotime($mantenimiento['fecha_entrega_real'])) : 'No'
            ]);
        }
        
        fclose($output);
        exit;
    }
}

// Función para generar enlaces de paginación manteniendo los parámetros
function getPaginationLink($pagina) {
    $params = $_GET;
    $params['pagina'] = $pagina;
    return 'historial_moto.php?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Moto - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            font-size: 14px;
        }
        
        .card-header {
            padding: 0.75rem 1rem;
        }
        
        .timeline {
            position: relative;
            padding-left: 20px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 10px;
            width: 2px;
            background-color: #dee2e6;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            top: 10px;
            left: -20px;
            width: 10px;
            height: 10px;
            background-color: #0d6efd;
            border-radius: 50%;
        }
        
        .badge-estado {
            min-width: 80px;
            font-size: 0.75rem;
        }
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .select2-container .select2-selection--single {
            height: 38px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        
        .pagination {
            justify-content: center;
        }
        
        .repuesto-eliminado {
            text-decoration: line-through;
            color: #6c757d;
        }
        
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 0.5rem auto;
                max-width: 95%;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4">Historial de Moto</h2>
            <div>
                <button class="btn btn-success btn-sm me-1" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="bi bi-file-earmark-excel"></i>
                </button>
                <a href="agregar_mantenimiento.php?moto_id=<?php echo $moto_id; ?>" class="btn btn-primary btn-sm me-1">
                    <i class="bi bi-plus-circle"></i>
                </a>
                <a href="motos.php" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success py-2"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger py-2"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Información de la moto y cliente -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white py-2">
                <h5 class="mb-0 h6">Información General</h5>
            </div>
            <div class="card-body p-2">
                <div class="row">
                    <div class="col-12 col-md-6 mb-2">
                        <h6 class="fw-bold">Datos del Cliente</h6>
                        <p class="mb-1"><small>Nombre:</small> <?php echo htmlspecialchars($moto['cliente_nombre']); ?></p>
                        <p class="mb-1"><small>Cédula:</small> <?php echo htmlspecialchars($moto['cedula']); ?></p>
                        <p class="mb-1"><small>Teléfono:</small> <?php echo htmlspecialchars($moto['telefono']); ?></p>
                        <p class="mb-1"><small>Email:</small> <?php echo htmlspecialchars($moto['email']); ?></p>
                    </div>
                    <div class="col-12 col-md-6">
                        <h6 class="fw-bold">Datos de la Moto</h6>
                        <p class="mb-1"><small>Marca/Modelo:</small> <?php echo htmlspecialchars($moto['marca'] . ' ' . $moto['modelo']); ?></p>
                        <p class="mb-1"><small>Placa:</small> <?php echo htmlspecialchars($moto['placa']); ?></p>
                        <p class="mb-1"><small>Color:</small> <?php echo htmlspecialchars($moto['color']); ?></p>
                        <p class="mb-1"><small>Kilometraje:</small> <?php echo number_format($moto['kilometraje'], 0); ?> km</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Historial de mantenimientos -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 h6">Historial de Mantenimientos</h5>
                    <span class="badge bg-light text-dark"><?php echo $total_registros; ?> registros</span>
                </div>
            </div>
            <div class="card-body p-2">
                <?php if (empty($mantenimientos)): ?>
                    <div class="alert alert-info py-2">No hay mantenimientos registrados</div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($mantenimientos as $mantenimiento): ?>
                            <div class="timeline-item mb-3">
                                <div class="card">
                                    <div class="card-header py-2" data-bs-toggle="collapse" data-bs-target="#mantenimiento-<?php echo $mantenimiento['id']; ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="fw-bold"><?php echo date('d/m/Y', strtotime($mantenimiento['fecha_ingreso'])); ?></small>
                                                <?php if ($mantenimiento['fecha_entrega_real']): ?>
                                                    <small class="text-success">(Entregado: <?php echo date('d/m/Y', strtotime($mantenimiento['fecha_entrega_real'])); ?>)</small>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <span class="badge badge-estado bg-<?php
                                                    echo ($mantenimiento['estado'] === 'entregado' ? 'success' :
                                                        ($mantenimiento['estado'] === 'terminado' ? 'primary' :
                                                            ($mantenimiento['estado'] === 'en_proceso' ? 'warning text-dark' : 'secondary')
                                                        ));
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $mantenimiento['estado'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="collapse show" id="mantenimiento-<?php echo $mantenimiento['id']; ?>">
                                        <div class="card-body p-2">
                                            <p class="mb-1"><small>Responsable:</small> <?php echo htmlspecialchars($mantenimiento['responsable'] ?? 'No asignado'); ?></p>
                                            <?php if ($mantenimiento['kilometraje_actual']): ?>
                                                <p class="mb-1"><small>Kilometraje:</small> <?php echo number_format($mantenimiento['kilometraje_actual'], 0); ?> km</p>
                                            <?php endif; ?>
                                            <p class="mb-1"><small>Novedades:</small></p>
                                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($mantenimiento['novedades'])); ?></p>
                                            
                                            <!-- Repuestos asociados -->
                                            <?php if (!empty($repuestos[$mantenimiento['id']])): ?>
                                                <div class="mt-2">
                                                    <h6 class="h6 fw-bold">Repuestos/Servicios:</h6>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-hover mb-2">
                                                            <thead>
                                                                <tr>
                                                                    <th><small>Producto</small></th>
                                                                    <th><small>Código</small></th>
                                                                    <th><small>Cantidad</small></th>
                                                                    <th><small>Precio Unitario</small></th>
                                                                    <th><small>Subtotal</small></th>
                                                                    
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($repuestos[$mantenimiento['id']] as $repuesto): ?>
                                                                    <tr class="<?php echo $repuesto['eliminado'] ? 'repuesto-eliminado' : ''; ?>">
                                                                        <td><small><?php echo htmlspecialchars($repuesto['producto_nombre']); ?></small></td>
                                                                        <td><small><?php echo htmlspecialchars($repuesto['producto_codigo']); ?></small></td>
                                                                        <td><small><?php echo $repuesto['cantidad']; ?></small></td>
                                                                        <td><small>$<?php echo number_format($repuesto['precio_unitario'], 2); ?></small></td>
                                                                        <td><small>$<?php echo number_format($repuesto['subtotal'], 2); ?></small></td>
                                                                        <td>
                                                                            <?php if (!$repuesto['eliminado'] && $mantenimiento['estado'] !== 'entregado'): ?>
                                                                                <form method="POST" style="display:inline;">
                                                                                    <input type="hidden" name="eliminar_venta" value="1">
                                                                                    <input type="hidden" name="venta_id" value="<?php echo $repuesto['id']; ?>">
                                                                                    <input type="hidden" name="mantenimiento_id" value="<?php echo $mantenimiento['id']; ?>">
                                                                                </form>
                                                                            <?php elseif ($repuesto['eliminado']): ?>
                                                                                <small class="text-muted">Eliminado por <?php echo htmlspecialchars($repuesto['usuario_nombre'] ?? 'Sistema'); ?></small>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <div>
                                                    <?php if (!empty($mantenimiento['costo'])): ?>
                                                        <small class="fw-bold">Total: $<?php echo number_format($mantenimiento['costo'], 2); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex">
                                                    <!-- Botón para agregar repuesto -->
                                                    <?php if ($mantenimiento['estado'] !== 'entregado'): ?>
                                                        <button class="btn btn-sm btn-outline-primary me-1" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#agregarVentaModal"
                                                                data-mantenimiento-id="<?php echo $mantenimiento['id']; ?>">
                                                            <i class="bi bi-plus"></i>
                                                        </button>
                                                        
                                                        <!-- Botón para marcar como entregado -->
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="marcar_entregado" value="1">
                                                            <input type="hidden" name="mantenimiento_id" value="<?php echo $mantenimiento['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-success me-1" onclick="return confirm('¿Marcar este mantenimiento como entregado?')">
                                                                <i class="bi bi-check-circle"></i> Entregado
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Botón WhatsApp -->
                                                    <?php if (!empty($moto['telefono'])): ?>
                                                        <?php
                                                        $mensaje = "Estimado cliente,\n\n";
                                                        $mensaje .= "Detalles del mantenimiento realizado el " . date('d/m/Y', strtotime($mantenimiento['fecha_ingreso'])) . ":\n";
                                                        $mensaje .= "Moto: " . $moto['marca'] . " " . $moto['modelo'] . " (Placa: " . $moto['placa'] . ")\n";
                                                        if (!empty($mantenimiento['kilometraje_actual'])) {
                                                            $mensaje .= "Kilometraje: " . number_format($mantenimiento['kilometraje_actual'], 0) . " km\n";
                                                        }
                                                        $mensaje .= "Trabajos realizados:\n" . $mantenimiento['novedades'] . "\n\n";
                                                        
                                                        if (!empty($repuestos[$mantenimiento['id']])) {
                                                            $mensaje .= "Repuestos/servicios:\n";
                                                            foreach ($repuestos[$mantenimiento['id']] as $repuesto) {
                                                                if (!$repuesto['eliminado']) {
                                                                    $mensaje .= "- " . $repuesto['producto_nombre'] . " (x" . $repuesto['cantidad'] . "): $" . number_format($repuesto['subtotal'], 2) . "\n";
                                                                }
                                                            }
                                                            $mensaje .= "\nTotal: $" . number_format($mantenimiento['costo'], 2) . "\n";
                                                        }
                                                        
                                                        $mensaje .= "\nGracias por confiar en nuestro taller.";
                                                        $telefono = preg_replace('/[^0-9]/', '', $moto['telefono']);
                                                        if (strpos($telefono, '0') === 0) {
                                                            $telefono = '+593' . substr($telefono, 1);
                                                        }
                                                        $url_whatsapp = "https://wa.me/$telefono?text=" . urlencode($mensaje);
                                                        ?>
                                                        <a href="<?php echo $url_whatsapp; ?>" class="btn btn-sm btn-success" target="_blank">
                                                            <i class="bi bi-whatsapp"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para agregar venta -->
    <div class="modal fade" id="agregarVentaModal" tabindex="-1" aria-labelledby="agregarVentaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title h6" id="agregarVentaModalLabel">Agregar Repuesto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-2">
                    <input type="hidden" name="mantenimiento_id" id="modalMantenimientoId">
                    <input type="hidden" name="agregar_venta" value="1">
                    
                    <div class="mb-2">
                        <label for="producto_id" class="form-label small">Buscar Producto</label>
                        <select class="form-select select2-productos" id="producto_id" name="producto_id" required>
                            <option value="">Buscar por nombre o código...</option>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?php echo $producto['id']; ?>" data-precio="<?php echo $producto['precio_venta']; ?>">
                                    <?php echo htmlspecialchars($producto['codigo'] . ' - ' . $producto['nombre']); ?> ($<?php echo number_format($producto['precio_venta'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="cantidad" class="form-label small">Cantidad</label>
                            <input type="number" class="form-control form-control-sm" id="cantidad" name="cantidad" value="1" min="1" required>
                        </div>
                        <div class="col-6">
                            <label for="precio_unitario" class="form-label small">Precio Unitario</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" id="precio_unitario" name="precio_unitario" value="" readonly>
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <label for="subtotal" class="form-label small">Subtotal</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" id="subtotal" name="subtotal" value="" readonly>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">Agregar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para exportar historial -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title h6" id="exportModalLabel">Exportar Historial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-2">
                    <input type="hidden" name="exportar_historial" value="1">
                    
                    <div class="mb-2">
                        <label class="form-label small">Rango de fechas (opcional)</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label for="fecha_inicio_export" class="form-label small">Desde</label>
                                <input type="date" class="form-control form-control-sm" id="fecha_inicio_export" name="fecha_inicio_export">
                            </div>
                            <div class="col-6">
                                <label for="fecha_fin_export" class="form-label small">Hasta</label>
                                <input type="date" class="form-control form-control-sm" id="fecha_fin_export" name="fecha_fin_export">
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info py-2">
                        <small>Se exportará todo el historial de mantenimientos y repuestos asociados a esta moto.</small>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success">Exportar a CSV</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Inicializar Select2 para búsqueda de productos
        $(document).ready(function() {
            $('.select2-productos').select2({
                placeholder: "Buscar por nombre o código...",
                allowClear: true,
                dropdownParent: $('#agregarVentaModal'),
                width: '100%',
                language: {
                    noResults: function() {
                        return "No se encontraron productos";
                    }
                }
            });
            
            // Ajustar para dispositivos móviles
            if ($(window).width() < 768) {
                $('.select2-container').css('width', '100%');
            }
        });
        
        // Configurar modal de agregar venta
        document.getElementById('agregarVentaModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modal = this;
            modal.querySelector('#modalMantenimientoId').value = button.getAttribute('data-mantenimiento-id');
            
            // Resetear formulario
            $('.select2-productos').val(null).trigger('change');
            modal.querySelector('#cantidad').value = 1;
            modal.querySelector('#precio_unitario').value = '';
            modal.querySelector('#subtotal').value = '';
        });
        
        // Calcular precios dinámicamente
        $(document).on('change', '#producto_id', function() {
            const selectedOption = $(this).find('option:selected');
            const precio = selectedOption.data('precio') || 0;
            $('#precio_unitario').val(precio);
            calcularSubtotal();
        });
        
        $(document).on('input', '#cantidad', calcularSubtotal);
        
        function calcularSubtotal() {
            const cantidad = parseFloat($('#cantidad').val()) || 0;
            const precio = parseFloat($('#precio_unitario').val()) || 0;
            const subtotal = (cantidad * precio).toFixed(2);
            $('#subtotal').val(subtotal);
        }
        
        // Mejorar experiencia en móviles
        document.addEventListener('DOMContentLoaded', function() {
            // Asegurar que los inputs numéricos muestren el teclado numérico en móviles
            const numberInputs = document.querySelectorAll('input[type="number"]');
            numberInputs.forEach(input => {
                input.setAttribute('pattern', '[0-9]*');
                input.setAttribute('inputmode', 'numeric');
            });
            
            // Inicializar cálculo al cargar
            const productoSelect = document.getElementById('producto_id');
            if (productoSelect) {
                productoSelect.dispatchEvent(new Event('change'));
            }
        });
        
        // Manejar redimensionamiento de pantalla
        window.addEventListener('resize', function() {
            if ($(window).width() < 768) {
                $('.select2-container').css('width', '100%');
            }
        });
    </script>
</body>
</html>