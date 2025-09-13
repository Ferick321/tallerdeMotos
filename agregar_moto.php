<?php
require_once 'config.php';

if (!isAuthenticated()) {
    redirect('login.php');
}

$clientes = [];
$stmt = $conn->query("SELECT id, nombres, cedula, telefono FROM clientes ORDER BY nombres");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = null;  // Variable para almacenar errores
    
    // Verificar si es un nuevo cliente
    if ($_POST['tipo_cliente'] === 'nuevo') {
        // Verificar si la cédula ya existe
        $stmt = $conn->prepare("SELECT COUNT(*) FROM clientes WHERE cedula = ?");
        $stmt->execute([trim($_POST['cedula'])]);
        $count_cedula = $stmt->fetchColumn();
        
        // Verificar si el teléfono ya existe
        $stmt = $conn->prepare("SELECT COUNT(*) FROM clientes WHERE telefono = ?");
        $stmt->execute([trim($_POST['telefono'])]);
        $count_telefono = $stmt->fetchColumn();

        // Comprobar duplicados
        if ($count_cedula > 0) {
            $error = "La cédula ya está registrada en el sistema.";
        } elseif ($count_telefono > 0) {
            $error = "El número de teléfono (WhatsApp) ya está registrado.";
        }
        
        if ($error) {
            $_SESSION['error'] = $error;
            redirect('agregar_moto.php');
        } else {
            // Registrar nuevo cliente
           $stmt = $conn->prepare("INSERT INTO clientes (nombres, cedula, telefono, email, direccion) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
            trim($_POST['nombres']),
            trim($_POST['cedula']),
            trim($_POST['telefono']),
            trim($_POST['email']),
            trim($_POST['direccion'])
            ]);
            $cliente_id = $conn->lastInsertId();
            
            // Guardar teléfono para posible envío por WhatsApp
            $telefono_cliente = trim($_POST['telefono']);
        }
    } else {
        $cliente_id = $_POST['cliente_existente'];
        $tipo_mantenimiento = $_POST['tipo_mantenimiento'] ?? 'general';
        
        // Obtener teléfono del cliente existente
        foreach ($clientes as $cliente) {
            if ($cliente['id'] == $cliente_id) {
                $telefono_cliente = $cliente['telefono'];
                break;
            }
        }
    }
    
    // Registrar la moto
    // Verificar si la placa ya existe
    $stmt = $conn->prepare("SELECT COUNT(*) FROM motos WHERE placa = ?");
    $stmt->execute([trim($_POST['placa'])]);
    $count_placa = $stmt->fetchColumn();

    if ($count_placa > 0) {
        $_SESSION['error'] = "La placa ingresada ya está registrada en otra moto.";
        redirect('agregar_moto.php');
    } else {
        // Registrar la moto
        $stmt = $conn->prepare("INSERT INTO motos (cliente_id, marca, modelo, placa, serie, color, kilometraje) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $cliente_id,
            trim($_POST['marca']),
            trim($_POST['modelo']),
            trim($_POST['placa']),
            trim($_POST['serie']),
            trim($_POST['color']),
            trim($_POST['kilometraje'])
        ]);
        $moto_id = $conn->lastInsertId();
    }

    // Registrar el mantenimiento si hay novedades
    if (!empty($_POST['novedades'])) {
        $proximo_cambio_aceite = null;
        
        // Si es cambio de aceite, calcular próximo mantenimiento
        if ($tipo_mantenimiento === 'cambio_aceite' && !empty($_POST['kilometraje'])) {
            $proximo_cambio_aceite = intval($_POST['kilometraje']) + 1600; // 1600 km para próximo cambio
        }
        
        $stmt = $conn->prepare("INSERT INTO mantenimientos 
                              (moto_id, novedades, responsable_id, kilometraje_actual, tipo_mantenimiento) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $moto_id,
            trim($_POST['novedades']),
            $_SESSION['user_id'],
            trim($_POST['kilometraje']),
            $tipo_mantenimiento
        ]);
        
        // Actualizar datos de la moto si es cambio de aceite
        if ($tipo_mantenimiento === 'cambio_aceite' && $proximo_cambio_aceite) {
            $stmt = $conn->prepare("UPDATE motos SET 
                                  proximo_cambio_aceite = ?,
                                  fecha_ultimo_cambio = CURDATE()
                                  WHERE id = ?");
            $stmt->execute([$proximo_cambio_aceite, $moto_id]);
        }
        
        // Notificar al administrador
        $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje) 
                               SELECT id, 'Nuevo mantenimiento', ? FROM usuarios WHERE rol = 'administrador'");
        $stmt->execute(["Se ha registrado un nuevo mantenimiento para la moto placa: " . $_POST['placa']]);
        
        // Preparar mensaje para WhatsApp si hay teléfono
        if (!empty($telefono_cliente)) {
            $mensaje_whatsapp = " *📌 Registro de Moto - Taller de Motos📌* \n\n";
            $mensaje_whatsapp .= " *🛵 Datos de la Moto:*\n";
            $mensaje_whatsapp .= "• *Marca/Modelo:* " . $_POST['marca'] . " " . $_POST['modelo'] . "\n";
            $mensaje_whatsapp .= "• *Placa:* " . $_POST['placa'] . "\n";
            $mensaje_whatsapp .= "• *Color:* " . ($_POST['color'] ?: 'No especificado') . "\n";
            $mensaje_whatsapp .= "• *Kilometraje:* " . number_format($_POST['kilometraje'], 0) . " km\n";
            
            if ($tipo_mantenimiento === 'cambio_aceite') {
                $mensaje_whatsapp .= "• *Próximo cambio de aceite:* " . number_format($proximo_cambio_aceite, 0) . " km\n";
            }
            
            $mensaje_whatsapp .= "\n *🔧Trabajos a realizar:*\n" . $_POST['novedades'] . "\n\n";
            $mensaje_whatsapp .= "¡Gracias por confiar en nuestro taller! 🛠️";
            
            $_SESSION['whatsapp_message'] = $mensaje_whatsapp;
            $_SESSION['whatsapp_number'] = $telefono_cliente;
        }
    }
    
    $_SESSION['success'] = "Moto registrada con éxito";
    redirect('motos.php');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Moto - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .whatsapp-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .form-section {
            margin-bottom: 2rem;
        }
        .card-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        #cambio-aceite-fields {
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Registrar Nueva Moto</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="motoForm">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Datos del Cliente</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_cliente" id="nuevo_cliente" value="nuevo" checked>
                            <label class="form-check-label" for="nuevo_cliente">Nuevo Cliente</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_cliente" id="cliente_existente" value="existente">
                            <label class="form-check-label" for="cliente_existente">Cliente Existente</label>
                        </div>
                    </div>
                    
                    <div id="nuevo-cliente-fields">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombres" class="form-label">Nombres Completos <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombres" name="nombres" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cedula" class="form-label">Cédula <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cedula" name="cedula" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label">Teléfono (WhatsApp) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="telefono" name="telefono" required>
                                <small class="text-muted"></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion">
                            </div>
                        </div>
                    </div>
                    
                    <div id="cliente-existente-fields" style="display: none;">
                        <div class="mb-3">
                            <label for="cliente_existente" class="form-label">Seleccionar Cliente <span class="text-danger">*</span></label>
                            <select class="form-select" id="cliente_existente" name="cliente_existente">
                                <option value="">Seleccione un cliente...</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id']; ?>" data-telefono="<?php echo htmlspecialchars($cliente['telefono']); ?>">
                                        <?php echo htmlspecialchars($cliente['nombres'] . ' - ' . $cliente['cedula']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo_mantenimiento" class="form-label">Tipo de Mantenimiento</label>
                            <select class="form-select" id="tipo_mantenimiento" name="tipo_mantenimiento">
                                <option value="general">General</option>
                                <option value="cambio_aceite">Cambio de Aceite</option>
                            </select>
                        </div>
                        
                        <div id="cambio-aceite-fields">
                            <div class="mb-3">
                                <label for="proximo_cambio_aceite" class="form-label">Próximo Cambio de Aceite (km)</label>
                                <input type="number" class="form-control" id="proximo_cambio_aceite" name="proximo_cambio_aceite">
                                <small class="text-muted">Se calculará automáticamente (+3000 km al actual)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Resto del formulario (Datos de la Moto y Novedades) se mantiene igual -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Datos de la Moto</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="marca" class="form-label">Marca <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="marca" name="marca" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modelo" class="form-label">Modelo / Año <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modelo" name="modelo" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="placa" class="form-label">Placa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="placa" name="placa" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="serie" class="form-label">Número de Serie</label>
                            <input type="text" class="form-control" id="serie" name="serie">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="color" name="color">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="kilometraje" class="form-label">Kilometraje <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="kilometraje" name="kilometraje" min="0" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Novedades/Mantenimiento</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="novedades" class="form-label">Detalles del Mantenimiento</label>
                        <textarea class="form-control" id="novedades" name="novedades" rows="4" placeholder="Describa los trabajos a realizar..."></textarea>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary me-md-2">
                    <i class="bi bi-save"></i> Registrar Moto
                </button>
                <a href="motos.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar/ocultar campos según tipo de cliente
            document.querySelectorAll('input[name="tipo_cliente"]').forEach(input => {
                input.addEventListener('change', function() {
                    if (this.value === 'nuevo') {
                        document.getElementById('nuevo-cliente-fields').style.display = 'block';
                        document.getElementById('cliente-existente-fields').style.display = 'none';
                        
                        // Hacer campos obligatorios
                        document.getElementById('nombres').required = true;
                        document.getElementById('cedula').required = true;
                        document.getElementById('telefono').required = true;
                    } else {
                        document.getElementById('nuevo-cliente-fields').style.display = 'none';
                        document.getElementById('cliente-existente-fields').style.display = 'block';
                        
                        // Quitar requeridos
                        document.getElementById('nombres').required = false;
                        document.getElementById('cedula').required = false;
                        document.getElementById('telefono').required = false;
                    }
                });
            });
            
            // Mostrar campos de cambio de aceite cuando se selecciona esa opción
            document.getElementById('tipo_mantenimiento').addEventListener('change', function() {
                if (this.value === 'cambio_aceite') {
                    document.getElementById('cambio-aceite-fields').style.display = 'block';
                    
                    // Calcular automáticamente próximo cambio
                    const kilometraje = document.getElementById('kilometraje').value;
                    if (kilometraje) {
                        document.getElementById('proximo_cambio_aceite').value = parseInt(kilometraje) + 3000;
                    }
                } else {
                    document.getElementById('cambio-aceite-fields').style.display = 'none';
                }
            });
            
            // Calcular próximo cambio si se modifica el kilometraje
            document.getElementById('kilometraje').addEventListener('change', function() {
                if (document.getElementById('tipo_mantenimiento').value === 'cambio_aceite' && this.value) {
                    document.getElementById('proximo_cambio_aceite').value = parseInt(this.value) + 3000;
                }
            });
            
            // Validar formulario antes de enviar
            document.getElementById('motoForm').addEventListener('submit', function(e) {
                // Validar cliente existente si está seleccionado
                if (document.querySelector('input[name="tipo_cliente"]:checked').value === 'existente' && 
                    document.getElementById('cliente_existente').value === '') {
                    e.preventDefault();
                    alert('Por favor seleccione un cliente existente');
                    document.getElementById('cliente_existente').focus();
                }
            });
            
            // Formatear automáticamente el teléfono
            document.getElementById('telefono').addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9+]/g, '');
            });
            
            // Formatear automáticamente la cédula
            document.getElementById('cedula').addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });
    </script>
</body>
</html>