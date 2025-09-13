<?php
require_once 'config.php';

if (!isAuthenticated()) {
    redirect('login.php');
}

// Obtener datos del usuario actual
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    $_SESSION['error'] = "Usuario no encontrado";
    redirect('dashboard.php');
}

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validar campos obligatorios
    if (empty($nombre)) {
        $errors[] = "El nombre es obligatorio";
    }
    
    if (empty($email)) {
        $errors[] = "El email es obligatorio";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email no tiene un formato válido";
    }
    
    // Verificar si el email ya existe (excepto para el usuario actual)
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $errors[] = "El email ya está en uso por otro usuario";
    }
    
    // Validar cambio de contraseña si se proporcionó la actual
    if (!empty($current_password)) {
        if (!password_verify($current_password, $usuario['password'])) {
            $errors[] = "La contraseña actual es incorrecta";
        }
        
        if (empty($new_password)) {
            $errors[] = "Debes ingresar una nueva contraseña";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "La nueva contraseña debe tener al menos 8 caracteres";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Las contraseñas nuevas no coinciden";
        }
    }
    
    if (empty($errors)) {
        try {
            // Actualizar datos del usuario
            if (!empty($current_password) && !empty($new_password)) {
                // Actualizar con nueva contraseña
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $password_hash, $_SESSION['user_id']]);
                
                $_SESSION['success'] = "Perfil y contraseña actualizados correctamente";
            } else {
                // Actualizar sin cambiar contraseña
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $_SESSION['user_id']]);
                
                $_SESSION['success'] = "Perfil actualizado correctamente";
            }
            
            // Actualizar datos en sesión
            $_SESSION['user_name'] = $nombre;
            
            redirect('perfil.php');
        } catch (PDOException $e) {
            $errors[] = "Error al actualizar el perfil: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-card {
            max-width: 600px;
            margin: 0 auto;
        }
        .profile-header {
            background-color: #343a40;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
        }
        .password-toggle {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="card profile-card">
            <div class="profile-header text-center">
                <h3><i class="bi bi-person-circle"></i> Mi Perfil</h3>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol</label>
                        <input type="text" class="form-control" value="<?php echo ucfirst($usuario['rol']); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="registro" class="form-label">Fecha de Registro</label>
                        <input type="text" class="form-control" value="<?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>" readonly>
                    </div>
                    
                    <hr>
                    
                    <h5 class="mb-3">Cambiar Contraseña</h5>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Contraseña Actual</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password">
                            <span class="input-group-text password-toggle" onclick="togglePassword('current_password')">
                                <i class="bi bi-eye"></i>
                            </span>
                        </div>
                        <small class="text-muted">Dejar en blanco si no deseas cambiar la contraseña</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <span class="input-group-text password-toggle" onclick="togglePassword('new_password')">
                                <i class="bi bi-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            <span class="input-group-text password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="bi bi-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>