<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = $_POST['cedula'] ?? '';
    $nombres = $_POST['nombres'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    $errores = [];
    
    // Validaciones
    if (empty($cedula)) $errores[] = "La cédula es requerida";
    if (empty($nombres)) $errores[] = "El nombre es requerido";
    if (empty($email)) $errores[] = "El email es requerido";
    if (empty($password)) $errores[] = "La contraseña es requerida";
    if ($password !== $password_confirm) $errores[] = "Las contraseñas no coinciden";
    
    // Verificar si el cliente ya existe
    if (empty($errores)) {
        $stmt = $conn->prepare("SELECT id FROM clientes WHERE cedula = ?");
        $stmt->execute([$cedula]);
        if ($stmt->fetch()) {
            $errores[] = "Ya existe un cliente registrado con esta cédula";
        }
    }
    
    // Registrar cliente
    if (empty($errores)) {
        try {
            $conn->beginTransaction();
            
            // Insertar en la tabla clientes
            $stmt = $conn->prepare("INSERT INTO clientes (cedula, nombres, email, telefono, password, fecha_registro) 
                                  VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$cedula, $nombres, $email, $telefono, password_hash($password, PASSWORD_DEFAULT)]);
            
            $conn->commit();
            
            // Redireccionar al login
            $_SESSION['success_message'] = "Registro exitoso. Por favor inicia sesión.";
            redirect('login_cliente.php');
            
        } catch (Exception $e) {
            $conn->rollBack();
            $errores[] = "Error al registrar el cliente: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Cliente - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="mb-0">Registro de Cliente</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errores)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errores as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Cédula</label>
                                <input type="text" name="cedula" class="form-control" 
                                       value="<?php echo $_POST['cedula'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nombres Completos</label>
                                <input type="text" name="nombres" class="form-control" 
                                       value="<?php echo $_POST['nombres'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo $_POST['email'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" name="telefono" class="form-control" 
                                       value="<?php echo $_POST['telefono'] ?? ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirmar Contraseña</label>
                                <input type="password" name="password_confirm" class="form-control" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Registrarse</button>
                                <a href="login_cliente.php" class="btn btn-link">¿Ya tienes cuenta? Inicia sesión</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>