<?php
require_once 'config.php';

if (!isAuthenticated()) {
    redirect('login.php');
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Consulta con paginación
$query = "SELECT SQL_CALC_FOUND_ROWS * FROM clientes";
$params = [];

if ($search) {
    $query .= " WHERE nombres LIKE ? OR cedula LIKE ? OR telefono LIKE ?";
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term];
}

$query .= " ORDER BY nombres LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener total para paginación
$totalClientes = $conn->query("SELECT FOUND_ROWS()")->fetchColumn();
$totalPages = ceil($totalClientes / $limit);


// Procesar formulario para agregar/editar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nombres = trim($_POST['nombres']);
    $cedula = trim($_POST['cedula']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);
    $password = trim($_POST['password'] ?? '');

    try {
        if ($id > 0) {
            // Actualizar cliente existente
            if (!empty($password)) {
                // Si se proporcionó una nueva contraseña, actualizarla
                $stmt = $conn->prepare("UPDATE clientes SET nombres = ?, cedula = ?, telefono = ?, email = ?, direccion = ?, password = ? WHERE id = ?");
                $stmt->execute([$nombres, $cedula, $telefono, $email, $direccion, password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                // Si no hay nueva contraseña, mantener la existente
                $stmt = $conn->prepare("UPDATE clientes SET nombres = ?, cedula = ?, telefono = ?, email = ?, direccion = ? WHERE id = ?");
                $stmt->execute([$nombres, $cedula, $telefono, $email, $direccion, $id]);
            }
            $_SESSION['success'] = "Cliente actualizado con éxito";
        } else {
            // Insertar nuevo cliente con contraseña predeterminada si no se proporciona
            $defaultPassword = !empty($password) ? $password : $cedula; // Usa la cédula como contraseña por defecto
            $stmt = $conn->prepare("INSERT INTO clientes (nombres, cedula, telefono, email, direccion, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombres, $cedula, $telefono, $email, $direccion, password_hash($defaultPassword, PASSWORD_DEFAULT)]);

            $_SESSION['success'] = "Cliente agregado con éxito. Contraseña inicial: " . $defaultPassword;
        }

        redirect('clientes.php');
    } catch (PDOException $e) {
        $error = "Error al guardar el cliente: " . $e->getMessage();
    }
}

// Eliminar cliente
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    try {
        // Verificar si el cliente tiene motos registradas
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM motos WHERE cliente_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            $_SESSION['error'] = "No se puede eliminar el cliente porque tiene motos registradas";
        } else {
            $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['success'] = "Cliente eliminado con éxito";
        }

        redirect('clientes.php');
    } catch (PDOException $e) {
        $error = "Error al eliminar el cliente: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Clientes</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clienteModal">
                <i class="bi bi-plus"></i> Nuevo Cliente
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Buscar clientes..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombres</th>
                                <th>Cédula</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cliente['nombres']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($cliente['fecha_registro'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-btn"
                                            data-id="<?php echo $cliente['id']; ?>"
                                            data-nombres="<?php echo htmlspecialchars($cliente['nombres']); ?>"
                                            data-cedula="<?php echo htmlspecialchars($cliente['cedula']); ?>"
                                            data-telefono="<?php echo htmlspecialchars($cliente['telefono']); ?>"
                                            data-email="<?php echo htmlspecialchars($cliente['email']); ?>"
                                            data-direccion="<?php echo htmlspecialchars($cliente['direccion']); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="clientes.php?delete=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Estás seguro de eliminar este cliente?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <a href="motos.php?cliente=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-outline-info" title="Ver motos">
                                            <i class="bi bi-bicycle"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
    <!-- Modal para agregar/editar cliente -->
    <div class="modal fade" id="clienteModal" tabindex="-1" aria-labelledby="clienteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="clienteModalLabel">Nuevo Cliente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="cliente_id" name="id" value="0">
                        <div class="mb-3">
                            <label for="nombres" class="form-label">Nombres Completos</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required>
                        </div>
                        <div class="mb-3">
                            <label for="cedula" class="form-label">Cédula</label>
                            <input type="text" class="form-control" id="cedula" name="cedula" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña <?php echo isset($_POST['id']) ? '(dejar vacío para mantener actual)' : '(opcional)'; ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Si se deja vacío, se usará la cédula como contraseña por defecto</small>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Editar cliente
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('clienteModal'));

                document.getElementById('cliente_id').value = this.getAttribute('data-id');
                document.getElementById('nombres').value = this.getAttribute('data-nombres');
                document.getElementById('cedula').value = this.getAttribute('data-cedula');
                document.getElementById('telefono').value = this.getAttribute('data-telefono');
                document.getElementById('email').value = this.getAttribute('data-email');
                document.getElementById('direccion').value = this.getAttribute('data-direccion');

                document.getElementById('clienteModalLabel').textContent = 'Editar Cliente';

                modal.show();
            });
        });

        // Resetear modal al cerrar
        document.getElementById('clienteModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('clienteModalLabel').textContent = 'Nuevo Cliente';
            document.getElementById('cliente_id').value = '0';
            this.querySelector('form').reset();
        });

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            
            // Cambiar el ícono del botón
            const icon = event.currentTarget.querySelector('i');
            icon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        }
    </script>
</body>

</html>