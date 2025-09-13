<?php
require_once 'config.php';

if (!isAuthenticated() || !hasRole('administrador')) {
    redirect('login.php');
}

// Consulta para obtener usuarios
$stmt = $conn->query("SELECT id, nombre, email, rol, activo, ultimo_login FROM usuarios ORDER BY nombre");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario para editar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_usuario'])) {
    $id = intval($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $rol = $_POST['rol'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    try {
        // Verificar si el email ya existe en otro usuario
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "El email ya está registrado por otro usuario";
        } else {
            // Actualizar usuario
            $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ?, activo = ? WHERE id = ?");
            $stmt->execute([$nombre, $email, $rol, $activo, $id]);
            
            $_SESSION['success'] = "Usuario actualizado con éxito";
        }
        
        redirect('usuarios.php');
    } catch (PDOException $e) {
        $error = "Error al actualizar el usuario: " . $e->getMessage();
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    $id = intval($_POST['id']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->execute([$password, $id]);
        
        $_SESSION['success'] = "Contraseña actualizada con éxito";
        redirect('usuarios.php');
    } catch (PDOException $e) {
        $error = "Error al actualizar la contraseña: " . $e->getMessage();
    }
}

// Activar/desactivar usuario
if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    
    try {
        $stmt = $conn->prepare("UPDATE usuarios SET activo = NOT activo WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Estado del usuario actualizado";
        redirect('usuarios.php');
    } catch (PDOException $e) {
        $error = "Error al cambiar el estado del usuario: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Usuarios</h2>
            <a href="register.php" class="btn btn-primary">
                <i class="bi bi-plus"></i> Nuevo Usuario
            </a>
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
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Último Login</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo ucfirst($usuario['rol']); ?></td>
                                    <td>
                                        <?php if ($usuario['ultimo_login']): ?>
                                            <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Nunca</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $usuario['activo'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editarUsuarioModal"
                                                data-id="<?php echo $usuario['id']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                                data-email="<?php echo htmlspecialchars($usuario['email']); ?>"
                                                data-rol="<?php echo $usuario['rol']; ?>"
                                                data-activo="<?php echo $usuario['activo']; ?>">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#cambiarPasswordModal"
                                                data-id="<?php echo $usuario['id']; ?>">
                                            <i class="bi bi-key"></i> Contraseña
                                        </button>
                                        <a href="usuarios.php?toggle_status=<?php echo $usuario['id']; ?>" class="btn btn-sm <?php echo $usuario['activo'] ? 'btn-outline-secondary' : 'btn-outline-success'; ?>">
                                            <i class="bi bi-power"></i> <?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?>
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
    
    <!-- Modal para editar usuario -->
    <div class="modal fade" id="editarUsuarioModal" tabindex="-1" aria-labelledby="editarUsuarioModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarUsuarioModalLabel">Editar Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="usuario_id">
                        <input type="hidden" name="editar_usuario" value="1">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="rol" class="form-label">Rol</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="administrador">Administrador</option>
                                <option value="empleado">Empleado</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="activo" name="activo">
                            <label class="form-check-label" for="activo">Usuario activo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal para cambiar contraseña -->
    <div class="modal fade" id="cambiarPasswordModal" tabindex="-1" aria-labelledby="cambiarPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cambiarPasswordModalLabel">Cambiar Contraseña</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="pass_usuario_id">
                        <input type="hidden" name="cambiar_password" value="1">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configurar modal de edición
        document.getElementById('editarUsuarioModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modal = this;
            
            modal.querySelector('#usuario_id').value = button.getAttribute('data-id');
            modal.querySelector('#nombre').value = button.getAttribute('data-nombre');
            modal.querySelector('#email').value = button.getAttribute('data-email');
            modal.querySelector('#rol').value = button.getAttribute('data-rol');
            modal.querySelector('#activo').checked = button.getAttribute('data-activo') === '1';
        });
        
        // Configurar modal de cambio de contraseña
        document.getElementById('cambiarPasswordModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            this.querySelector('#pass_usuario_id').value = button.getAttribute('data-id');
        });
        
        // Validar que las contraseñas coincidan
        document.querySelector('#cambiarPasswordModal form').addEventListener('submit', function(e) {
            const password = this.querySelector('#password').value;
            const confirmPassword = this.querySelector('#confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Las contraseñas no coinciden');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>