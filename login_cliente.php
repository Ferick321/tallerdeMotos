<?php
require_once 'config.php';

if (isClienteAuthenticated()) {
    redirect('dashboard_cliente.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = $_POST['cedula'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($cedula) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, cedula, nombres, password FROM clientes WHERE cedula = ?");
        $stmt->execute([$cedula]);
        $cliente = $stmt->fetch();
        
        // Verificar si el cliente existe y tiene contraseña
        if ($cliente && !empty($cliente['password'])) {
            if (password_verify($password, $cliente['password'])) {
                $_SESSION['cliente_id'] = $cliente['id'];
                $_SESSION['cliente_cedula'] = $cliente['cedula'];
                $_SESSION['cliente_nombre'] = $cliente['nombres'];
                redirect('dashboard_cliente.php');
            } else {
                $error = "Credenciales inválidas";
            }
        } else {
            // Si el cliente existe pero no tiene contraseña, verificar si usa la contraseña por defecto (cédula)
            if ($cliente && $password === $cliente['cedula']) {
                // Actualizar la contraseña con hash
                $stmt = $conn->prepare("UPDATE clientes SET password = ? WHERE id = ?");
                $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $cliente['id']]);
                
                // Iniciar sesión
                $_SESSION['cliente_id'] = $cliente['id'];
                $_SESSION['cliente_cedula'] = $cliente['cedula'];
                $_SESSION['cliente_nombre'] = $cliente['nombres'];
                
                $_SESSION['success_message'] = "Bienvenido! Por favor cambie su contraseña por seguridad.";
                redirect('dashboard_cliente.php');
            } else {
                $error = "Credenciales inválidas";
            }
        }
    } else {
        $error = "Por favor ingrese todos los campos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Cliente - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-image: url("imagenes/loginClientes.jpg"); /* Ruta de tu imagen */
            background-size: cover;       /* Hace que cubra toda la pantalla */
            background-repeat: no-repeat; /* No se repite */
            background-attachment: fixed; /* Fija la imagen al hacer scroll */
        }

        /* Para que la tarjeta sea semi-transparente y se vea bien sobre el fondo */
        .card {
            background-color: rgba(255, 255, 255, 0.9);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="mb-0">Acceso Clientes</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Cédula</label>
                                <input type="text" name="cedula" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Ingresar</button>
                                <a href="registro_cliente.php" class="btn btn-link">¿No tienes cuenta? Regístrate</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
