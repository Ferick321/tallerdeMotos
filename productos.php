<?php
require_once 'config.php';

// Verificación de autenticación y permisos
if (!isAuthenticated()) {
    redirect('login.php');
}

class ProductoManager
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getProductos($filter = '', $search = '', $limit = 20, $offset = 0)
    {
        $query = "SELECT SQL_CALC_FOUND_ROWS p.*, 
                     CASE WHEN p.stock < p.stock_minimo THEN 1 ELSE 0 END as bajo_stock
              FROM productos p 
              WHERE p.activo = 1";

        $params = [];

        if ($filter === 'low_stock') {
            $query .= " AND p.stock < p.stock_minimo";
        } elseif ($search) {
            $query .= " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR p.marca LIKE ?)";
            $search_term = "%$search%";
            $params = [$search_term, $search_term, $search_term];
        }

        $query .= " ORDER BY bajo_stock DESC, p.nombre LIMIT $limit OFFSET $offset";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener total para paginación
        $total = $this->conn->query("SELECT FOUND_ROWS()")->fetchColumn();

        return ['productos' => $productos, 'total' => $total];
    }


    public function guardarProducto($data)
    {
        try {
            if ($data['id'] > 0) {
                $stmt = $this->conn->prepare("UPDATE productos SET 
                                          codigo = :codigo, nombre = :nombre, descripcion = :descripcion, 
                                          marca = :marca, categoria = :categoria, precio_compra = :precio_compra, 
                                          precio_venta = :precio_venta, stock = :stock, stock_minimo = :stock_minimo
                                          WHERE id = :id");
            } else {
                $stmt = $this->conn->prepare("INSERT INTO productos 
                                          (codigo, nombre, descripcion, marca, categoria, 
                                           precio_compra, precio_venta, stock, stock_minimo)
                                          VALUES (:codigo, :nombre, :descripcion, :marca, :categoria, 
                                                  :precio_compra, :precio_venta, :stock, :stock_minimo)");
            }

            $params = [
                ':codigo' => trim($data['codigo']),
                ':nombre' => trim($data['nombre']),
                ':descripcion' => trim($data['descripcion']),
                ':marca' => trim($data['marca']),
                ':categoria' => trim($data['categoria']),
                ':precio_compra' => (float)$data['precio_compra'],
                ':precio_venta' => (float)$data['precio_venta'],
                ':stock' => (int)$data['stock'],
                ':stock_minimo' => (int)$data['stock_minimo']
            ];

            if ($data['id'] > 0) {
                $params[':id'] = (int)$data['id'];
            }

            $stmt->execute($params);

            // Notificar stock bajo
            if ((int)$data['stock'] < (int)$data['stock_minimo']) {
                $this->notificarStockBajo($data['nombre'], $data['codigo'], $data['stock'], $data['stock_minimo']);
            }

            return true;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                throw new Exception("Ya existe un producto con ese código. Por favor, utiliza uno diferente.");
            }
            throw new Exception("Error al guardar el producto: " . $e->getMessage());
        }
    }

    public function eliminarProducto($id)
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM productos WHERE id = :id");
            $stmt->execute([':id' => (int)$id]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Error al eliminar el producto: " . $e->getMessage());
        }
    }

    private function notificarStockBajo($nombre, $codigo, $stock, $stock_minimo)
    {
        $mensaje = "El producto $nombre ($codigo) tiene stock bajo: $stock unidades (mínimo recomendado: $stock_minimo)";

        $stmt = $this->conn->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje) 
                                    SELECT id, 'Stock bajo', :mensaje FROM usuarios WHERE rol = 'administrador'");
        $stmt->execute([':mensaje' => $mensaje]);
    }
}

// Manejo de solicitudes
$productoManager = new ProductoManager($conn);
$filter = $_GET['filter'] ?? '';
$search = trim($_GET['search'] ?? '');

try {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $resultado = $productoManager->getProductos($filter, $search, $limit, $offset);
    $productos = $resultado['productos'];
    $totalProductos = $resultado['total'];
    $totalPages = ceil($totalProductos / $limit);


    // Procesar formulario POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole('administrador')) {
        $productoManager->guardarProducto([
            'id' => $_POST['id'] ?? 0,
            'codigo' => $_POST['codigo'] ?? '',
            'nombre' => $_POST['nombre'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'marca' => $_POST['marca'] ?? '',
            'categoria' => $_POST['categoria'] ?? '',
            'precio_compra' => $_POST['precio_compra'] ?? 0,
            'precio_venta' => $_POST['precio_venta'] ?? 0,
            'stock' => $_POST['stock'] ?? 0,
            'stock_minimo' => $_POST['stock_minimo'] ?? 0
        ]);

        $_SESSION['success'] = $data['id'] > 0 ? "Producto actualizado con éxito" : "Producto agregado con éxito";
        redirect('productos.php');
    }

    // Procesar eliminación
    if (isset($_GET['delete']) && hasRole('administrador')) {
        $productoManager->eliminarProducto($_GET['delete']);
        $_SESSION['success'] = "Producto eliminado con éxito";
        redirect('productos.php');
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Productos</h2>
            <?php if (hasRole('administrador')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productoModal">
                    <i class="bi bi-plus"></i> Nuevo Producto
                </button>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link <?= $filter === '' ? 'active' : '' ?>" href="productos.php">Todos</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $filter === 'low_stock' ? 'active' : '' ?>"
                                    href="productos.php?filter=low_stock">Bajo Stock</a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="hidden" name="filter" value="<?= $filter ?>">
                            <input type="text" name="search" class="form-control me-2"
                                placeholder="Buscar..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Marca</th>
                                <th>Categoría</th>
                                <th>P. Compra</th>
                                <th>P. Venta</th>
                                <th>Stock</th>
                                <th>Mínimo</th>
                                <?php if (hasRole('administrador')): ?>
                                    <th>Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $prod): ?>
                                <tr class="<?= $prod['bajo_stock'] ? 'table-warning' : '' ?>">
                                    <td><?= htmlspecialchars($prod['codigo']) ?></td>
                                    <td><?= htmlspecialchars($prod['nombre']) ?></td>
                                    <td><?= htmlspecialchars($prod['marca']) ?></td>
                                    <td><?= htmlspecialchars($prod['categoria']) ?></td>
                                    <td><?= number_format($prod['precio_compra'], 2) ?></td>
                                    <td><?= number_format($prod['precio_venta'], 2) ?></td>
                                    <td><?= $prod['stock'] ?></td>
                                    <td><?= $prod['stock_minimo'] ?></td>
                                    <?php if (hasRole('administrador')): ?>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary edit-btn"
                                                data-id="<?= $prod['id'] ?>"
                                                data-codigo="<?= htmlspecialchars($prod['codigo']) ?>"
                                                data-nombre="<?= htmlspecialchars($prod['nombre']) ?>"
                                                data-descripcion="<?= htmlspecialchars($prod['descripcion']) ?>"
                                                data-marca="<?= htmlspecialchars($prod['marca']) ?>"
                                                data-categoria="<?= htmlspecialchars($prod['categoria']) ?>"
                                                data-precio-compra="<?= $prod['precio_compra'] ?>"
                                                data-precio-venta="<?= $prod['precio_venta'] ?>"
                                                data-stock="<?= $prod['stock'] ?>"
                                                data-stock-minimo="<?= $prod['stock_minimo'] ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="productos.php?delete=<?= $prod['id'] ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('¿Estás seguro de eliminar este producto?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if (hasRole('administrador')): ?>
        <!-- Modal para agregar/editar producto -->
        <div class="modal fade" id="productoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Nuevo Producto</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="producto_id" name="id" value="0">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="codigo" class="form-label">Código</label>
                                    <input type="text" class="form-control" id="codigo" name="codigo" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="marca" class="form-label">Marca</label>
                                    <input type="text" class="form-control" id="marca" name="marca">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="categoria" class="form-label">Categoría</label>
                                    <input type="text" class="form-control" id="categoria" name="categoria">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="precio_compra" class="form-label">Precio de Compra</label>
                                    <input type="number" step="0.01" class="form-control" id="precio_compra" name="precio_compra" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="precio_venta" class="form-label">Precio de Venta</label>
                                    <input type="number" step="0.01" class="form-control" id="precio_venta" name="precio_venta" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="stock" class="form-label">Stock Actual</label>
                                    <input type="number" class="form-control" id="stock" name="stock" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="stock_minimo" class="form-label">Stock Mínimo</label>
                                    <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Anterior</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Siguiente</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Manejo del modal de productos
            document.addEventListener('DOMContentLoaded', function() {
                const modal = new bootstrap.Modal(document.getElementById('productoModal'));
                const modalTitle = document.querySelector('#productoModal .modal-title');

                // Editar producto
                document.querySelectorAll('.edit-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        document.getElementById('producto_id').value = this.dataset.id;
                        document.getElementById('codigo').value = this.dataset.codigo;
                        document.getElementById('nombre').value = this.dataset.nombre;
                        document.getElementById('descripcion').value = this.dataset.descripcion;
                        document.getElementById('marca').value = this.dataset.marca;
                        document.getElementById('categoria').value = this.dataset.categoria;
                        document.getElementById('precio_compra').value = this.dataset.precioCompra;
                        document.getElementById('precio_venta').value = this.dataset.precioVenta;
                        document.getElementById('stock').value = this.dataset.stock;
                        document.getElementById('stock_minimo').value = this.dataset.stockMinimo;

                        modalTitle.textContent = 'Editar Producto';
                        modal.show();
                    });
                });

                // Resetear modal al cerrar
                document.getElementById('productoModal').addEventListener('hidden.bs.modal', function() {
                    modalTitle.textContent = 'Nuevo Producto';
                    this.querySelector('form').reset();
                });
            });
        </script>
    <?php endif; ?>
</body>

</html>