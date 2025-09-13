<?php
require_once 'config.php';

if (!isAuthenticated()) {
    redirect('login.php');
}

// Obtener clientes para el select
$clientes = $conn->query("SELECT id, nombres, cedula FROM clientes ORDER BY nombres")->fetchAll(PDO::FETCH_ASSOC);

// Buscar productos si se envió búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$productos = [];

if (!empty($search)) {
    $stmt = $conn->prepare("SELECT id, nombre, precio_venta, stock, codigo 
                          FROM productos 
                          WHERE (nombre LIKE ? OR codigo LIKE ?) 
                          AND stock > 0 AND activo = 1 
                          ORDER BY nombre LIMIT 50");
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Mostrar productos más vendidos o en stock por defecto
    $productos = $conn->query("SELECT id, nombre, precio_venta, stock, codigo 
                              FROM productos 
                              WHERE stock > 0 AND activo = 1 
                              ORDER BY nombre LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
}

// Procesar la venta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = !empty($_POST['cliente_id']) ? intval($_POST['cliente_id']) : null;
    $productos_venta = json_decode($_POST['productos_venta'], true);
    $total = 0;
    
    if (empty($productos_venta)) {
        $_SESSION['error'] = "Debe agregar al menos un producto a la venta";
    } else {
        try {
            $conn->beginTransaction();
            
            // Crear la venta
            $stmt = $conn->prepare("INSERT INTO ventas (cliente_id, usuario_id, total) VALUES (?, ?, ?)");
            $stmt->execute([$cliente_id, $_SESSION['user_id'], $total]);
            $venta_id = $conn->lastInsertId();
            
            // Procesar cada producto
            foreach ($productos_venta as $producto) {
                $producto_id = intval($producto['id']);
                $cantidad = intval($producto['cantidad']);
                $precio_unitario = floatval($producto['precio']);
                $subtotal = $cantidad * $precio_unitario;
                
                // Agregar detalle de venta
                $stmt = $conn->prepare("INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$venta_id, $producto_id, $cantidad, $precio_unitario, $subtotal]);
                
                // Actualizar stock
                $stmt = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$cantidad, $producto_id]);
                
                $total += $subtotal;
            }
            
            // Actualizar el total de la venta
            $conn->prepare("UPDATE ventas SET total = ? WHERE id = ?")->execute([$total, $venta_id]);
            
            $conn->commit();
            
            $_SESSION['success'] = "Venta registrada con éxito. Total: $" . number_format($total, 2);
            redirect('ventas.php');
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Error al registrar la venta: " . $e->getMessage();
        }
    }
}

// Mostrar errores de sesión si existen
$error = isset($_SESSION['error']) ? $_SESSION['error'] : (isset($error) ? $error : null);
if (isset($_SESSION['error'])) unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Venta - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
        }
        
        .product-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }
        
        .product-card .card-body {
            padding: 1rem;
        }
        
        #productos-carrito {
            max-height: 50vh;
            overflow-y: auto;
            scrollbar-width: thin;
        }
        
        #productos-carrito::-webkit-scrollbar {
            width: 5px;
        }
        
        #productos-carrito::-webkit-scrollbar-thumb {
            background-color: var(--primary-color);
            border-radius: 10px;
        }
        
        .search-container {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .search-container i {
            position: absolute;
            left: 10px;
            top: 10px;
            color: #6c757d;
        }
        
        .search-input {
            padding-left: 35px;
            border-radius: 20px;
        }
        
        .badge-stock {
            font-size: 0.75rem;
            background-color: var(--success-color);
        }
        
        .badge-code {
            font-size: 0.75rem;
            background-color: var(--secondary-color);
        }
        
        @media (max-width: 768px) {
            .product-card {
                margin-bottom: 1rem;
            }
            
            #productos-carrito {
                max-height: 30vh;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid mt-3">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-cart-plus"></i> Nueva Venta</h2>
                    <a href="ventas.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
          <!-- Columna de Productos -->
<div class="col-lg-8 mb-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0"><i class="bi bi-box-seam"></i> Productos Disponibles</h5>
                <form method="GET" class="d-flex mt-2 mt-lg-0 w-100 w-md-auto" style="max-width: 320px;">
                    <input type="text" name="search" class="form-control"
                        placeholder="Buscar por nombre o código..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-light ms-2">
                        <i class="bi bi-funnel"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="card-body px-2 px-sm-3" style="max-height: 600px; overflow-y: auto;">
            <?php if (empty($productos)): ?>
                <div class="alert alert-warning text-center">
                    <i class="bi bi-exclamation-triangle"></i> No se encontraron productos con ese criterio de búsqueda
                </div>
            <?php else: ?>
                <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3" id="contenedor-productos">
                    <?php
                    $limiteInicial = 0;
                    $limiteMostrar = 20;
                    $productosMostrados = array_slice($productos, $limiteInicial, $limiteMostrar);

                    foreach ($productosMostrados as $producto): ?>
                        <div class="col producto-item">
                            <div class="card h-100 product-card shadow-sm border-0"
                                data-id="<?= $producto['id'] ?>"
                                data-nombre="<?= htmlspecialchars($producto['nombre']) ?>"
                                data-precio="<?= $producto['precio_venta'] ?>"
                                data-stock="<?= $producto['stock'] ?>"
                                data-codigo="<?= htmlspecialchars($producto['codigo'] ?? '') ?>">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div>
                                        <h6 class="card-title text-truncate mb-1"><?= htmlspecialchars($producto['nombre']) ?></h6>
                                        <?php if (!empty($producto['codigo'])): ?>
                                            <span class="badge bg-secondary mb-2"><?= htmlspecialchars($producto['codigo']) ?></span>
                                        <?php endif; ?>
                                        <p class="mb-1 text-success fw-bold">$<?= number_format($producto['precio_venta'], 2) ?></p>
                                        <span class="badge bg-info text-dark">Stock: <?= $producto['stock'] ?></span>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary mt-3 w-100 agregar-carrito">
                                        <i class="bi bi-cart-plus"></i> Agregar
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($productos) > $limiteMostrar): ?>
                    <div class="text-center mt-3">
                        <button id="btn-cargar-mas" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-chevron-down"></i> Cargar más productos
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const todosProductos = <?= json_encode($productos) ?>;
    let cargados = <?= $limiteMostrar ?>;
    const total = todosProductos.length;

    document.getElementById('btn-cargar-mas')?.addEventListener('click', () => {
        const contenedor = document.getElementById('contenedor-productos');
        const siguiente = todosProductos.slice(cargados, cargados + 20);

        siguiente.forEach(producto => {
            const col = document.createElement('div');
            col.className = 'col producto-item';
            col.innerHTML = `
                <div class="card h-100 product-card shadow-sm border-0"
                    data-id="${producto.id}"
                    data-nombre="${producto.nombre}"
                    data-precio="${producto.precio_venta}"
                    data-stock="${producto.stock}"
                    data-codigo="${producto.codigo || ''}">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h6 class="card-title text-truncate mb-1">${producto.nombre}</h6>
                            ${producto.codigo ? `<span class="badge bg-secondary mb-2">${producto.codigo}</span>` : ''}
                            <p class="mb-1 text-success fw-bold">$${parseFloat(producto.precio_venta).toFixed(2)}</p>
                            <span class="badge bg-info text-dark">Stock: ${producto.stock}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-primary mt-3 w-100 agregar-carrito">
                            <i class="bi bi-cart-plus"></i> Agregar
                        </button>
                    </div>
                </div>`;
            contenedor.appendChild(col);
        });

        cargados += 20;
        if (cargados >= total) {
            document.getElementById('btn-cargar-mas').style.display = 'none';
        }
    });
</script>

            
            <!-- Columna del Carrito -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-cart-check"></i> Resumen de Venta</h5>
                    </div>
                    
                    <div class="card-body">
                        <form id="ventaForm" method="POST">
                            <div class="mb-3">
                                <label for="cliente_id" class="form-label">Cliente (opcional)</label>
                                <select class="form-select" id="cliente_id" name="cliente_id">
                                    <option value="">Seleccionar cliente...</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= $cliente['id'] ?>">
                                            <?= htmlspecialchars($cliente['nombres'] . ' - ' . $cliente['cedula']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <h6 class="mt-3 mb-2">Productos seleccionados:</h6>
                            <div id="productos-carrito" class="mb-3">
                                <div class="text-center text-muted py-3">
                                    <i class="bi bi-cart-x" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No hay productos agregados</p>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3 border-top pt-3">
                                <h4 class="mb-0">Total:</h4>
                                <h4 class="mb-0 text-success" id="total-venta">$0.00</h4>
                            </div>
                            
                            <input type="hidden" name="productos_venta" id="productos_venta">
                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                                <i class="bi bi-check-circle"></i> FINALIZAR VENTA
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para cantidad -->
    <div class="modal fade" id="cantidadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="producto-modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="producto_id">
                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" id="decrementar">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" class="form-control text-center" id="cantidad" min="1" value="1">
                            <button class="btn btn-outline-secondary" type="button" id="incrementar">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        <small class="text-muted">Disponible: <span id="stock-disponible" class="fw-bold"></span></small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Precio unitario:</span>
                        <strong id="precio-unitario"></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span>Subtotal:</span>
                        <strong class="text-success" id="subtotal-modal"></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="agregar-producto">
                        <i class="bi bi-cart-plus"></i> Agregar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        const productosSeleccionados = [];
        const cantidadModal = new bootstrap.Modal(document.getElementById('cantidadModal'));
        let productoActual = null;
        
        // Inicializar eventos
        document.addEventListener('DOMContentLoaded', function() {
            // Seleccionar producto
            document.querySelectorAll('.product-card').forEach(card => {
                card.addEventListener('click', function() {
                    productoActual = {
                        id: this.getAttribute('data-id'),
                        nombre: this.getAttribute('data-nombre'),
                        precio: parseFloat(this.getAttribute('data-precio')),
                        stock: parseInt(this.getAttribute('data-stock')),
                        codigo: this.getAttribute('data-codigo')
                    };
                    
                    document.getElementById('producto_id').value = productoActual.id;
                    document.getElementById('producto-modal-title').textContent = productoActual.nombre;
                    document.getElementById('stock-disponible').textContent = productoActual.stock;
                    document.getElementById('cantidad').max = productoActual.stock;
                    document.getElementById('cantidad').value = 1;
                    document.getElementById('precio-unitario').textContent = '$' + productoActual.precio.toFixed(2);
                    updateSubtotal();
                    
                    cantidadModal.show();
                });
            });
            
            // Eventos para incrementar/decrementar cantidad
            document.getElementById('incrementar').addEventListener('click', function() {
                const input = document.getElementById('cantidad');
                let value = parseInt(input.value);
                if (value < productoActual.stock) {
                    input.value = value + 1;
                    updateSubtotal();
                }
            });
            
            document.getElementById('decrementar').addEventListener('click', function() {
                const input = document.getElementById('cantidad');
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                    updateSubtotal();
                }
            });
            
            // Actualizar subtotal cuando cambia la cantidad
            document.getElementById('cantidad').addEventListener('input', function() {
                updateSubtotal();
            });
            
            // Agregar producto al carrito
            document.getElementById('agregar-producto').addEventListener('click', agregarProductoAlCarrito);
            
            // Validar formulario antes de enviar
            document.getElementById('ventaForm').addEventListener('submit', validarFormularioVenta);
            
            // Enfocar el buscador al cargar la página
            document.querySelector('.search-input')?.focus();
        });
        
        // Actualizar subtotal en el modal
        function updateSubtotal() {
            const cantidad = parseInt(document.getElementById('cantidad').value);
            const subtotal = cantidad * productoActual.precio;
            document.getElementById('subtotal-modal').textContent = '$' + subtotal.toFixed(2);
        }
        
        // Agregar producto al carrito
        function agregarProductoAlCarrito() {
            const cantidad = parseInt(document.getElementById('cantidad').value);
            
            if (cantidad < 1 || cantidad > productoActual.stock) {
                alert('Cantidad no válida');
                return;
            }
            
            // Verificar si el producto ya está en el carrito
            const index = productosSeleccionados.findIndex(p => p.id == productoActual.id);
            
            if (index >= 0) {
                // Actualizar cantidad
                productosSeleccionados[index].cantidad += cantidad;
            } else {
                // Agregar nuevo producto
                productosSeleccionados.push({
                    ...productoActual,
                    cantidad: cantidad
                });
            }
            
            actualizarCarrito();
            cantidadModal.hide();
            
            // Mostrar notificación
            showToast('Producto agregado', 'Se ha añadido ' + cantidad + ' unidad(es) de ' + productoActual.nombre);
        }
        
        // Actualizar vista del carrito
        function actualizarCarrito() {
            const carrito = document.getElementById('productos-carrito');
            const totalVenta = document.getElementById('total-venta');
            const inputProductos = document.getElementById('productos_venta');
            
            if (productosSeleccionados.length === 0) {
                carrito.innerHTML = `
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-cart-x" style="font-size: 2rem;"></i>
                        <p class="mt-2">No hay productos agregados</p>
                    </div>
                `;
                totalVenta.textContent = '$0.00';
                inputProductos.value = '';
                return;
            }
            
            let html = '';
            let total = 0;
            
            productosSeleccionados.forEach((producto, index) => {
                const subtotal = producto.precio * producto.cantidad;
                total += subtotal;
                
                html += `
                    <div class="card mb-2">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="flex: 1; min-width: 0;">
                                    <h6 class="mb-1 text-truncate">${producto.nombre}</h6>
                                    <small class="text-muted d-block">${producto.codigo || 'Sin código'}</small>
                                    <small class="d-block">$${producto.precio.toFixed(2)} x ${producto.cantidad} = $${subtotal.toFixed(2)}</small>
                                </div>
                                <button class="btn btn-sm btn-outline-danger eliminar-producto" data-index="${index}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            carrito.innerHTML = html;
            totalVenta.textContent = `$${total.toFixed(2)}`;
            inputProductos.value = JSON.stringify(productosSeleccionados);
            
            // Agregar eventos a los botones de eliminar
            document.querySelectorAll('.eliminar-producto').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    const productoEliminado = productosSeleccionados[index];
                    productosSeleccionados.splice(index, 1);
                    actualizarCarrito();
                    
                    // Mostrar notificación
                    showToast('Producto eliminado', 'Se ha quitado ' + productoEliminado.nombre + ' del carrito');
                });
            });
        }
        
        // Validar formulario antes de enviar
        function validarFormularioVenta(e) {
            if (productosSeleccionados.length === 0) {
                e.preventDefault();
                showToast('Error', 'Debe agregar al menos un producto a la venta', 'danger');
            }
        }
        
        // Mostrar notificación toast
        function showToast(title, message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `position-fixed bottom-0 end-0 p-3 toast-${type}`;
            toast.style.zIndex = '11';
            
            toast.innerHTML = `
                <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header bg-${type} text-white">
                        <strong class="me-auto">${title}</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Eliminar el toast después de 3 segundos
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
</body>
</html>