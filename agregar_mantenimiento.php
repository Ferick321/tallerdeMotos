<?php
require_once 'config.php';

if (!isAuthenticated()) {
    redirect('login.php');
}

// Obtener datos de la moto si se proporciona ID
$moto_id = isset($_GET['moto_id']) ? intval($_GET['moto_id']) : 0;
$moto = null;
$cliente = null;

if ($moto_id > 0) {
    $stmt = $conn->prepare("SELECT m.*, c.nombres, c.cedula, c.telefono, c.email 
                           FROM motos m
                           JOIN clientes c ON m.cliente_id = c.id
                           WHERE m.id = ?");
    $stmt->execute([$moto_id]);
    $moto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$moto) {
        $_SESSION['error'] = "Moto no encontrada";
        redirect('motos.php');
    }
}

// Procesar formulario de mantenimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moto_id = intval($_POST['moto_id']);
    $kilometraje_actual = intval($_POST['kilometraje_actual']);
    $tipo_mantenimiento = $_POST['tipo_mantenimiento'];
    $novedades = trim($_POST['novedades']);
    $proximo_cambio_aceite = isset($_POST['proximo_cambio_aceite']) ? intval($_POST['proximo_cambio_aceite']) : null;

    try {
        // Registrar el mantenimiento
        $stmt = $conn->prepare("INSERT INTO mantenimientos 
                              (moto_id, tipo, kilometraje_actual, novedades, responsable_id)
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $moto_id,
            $tipo_mantenimiento,
            $kilometraje_actual,
            $novedades,
            $_SESSION['user_id']
        ]);

        // Actualizar kilometraje y próximo cambio de aceite si es aplicable
        if ($tipo_mantenimiento === 'cambio_aceite') {
            $stmt = $conn->prepare("UPDATE motos SET 
                                  kilometraje = ?,
                                  proximo_cambio_aceite = ?,
                                  fecha_ultimo_cambio = CURDATE()
                                  WHERE id = ?");
            $stmt->execute([
                $kilometraje_actual,
                $proximo_cambio_aceite,
                $moto_id
            ]);

            // Notificar al cliente por WhatsApp si tiene teléfono
            if ($moto['telefono'] && filter_var($proximo_cambio_aceite, FILTER_VALIDATE_INT)) {
                $mensaje_whatsapp = "Estimado cliente, le informamos que se ha realizado el cambio de aceite a su moto " . 
                                    $moto['marca'] . " " . $moto['modelo'] . " (Placa: " . $moto['placa'] . ").\n\n" .
                                    "Kilometraje actual: " . number_format($kilometraje_actual, 0) . " km\n" .
                                    "Próximo cambio recomendado: " . number_format($proximo_cambio_aceite, 0) . " km\n\n" .
                                    "Gracias por confiar en nuestro taller.";

                $_SESSION['whatsapp_message'] = $mensaje_whatsapp;
                $_SESSION['whatsapp_number'] = $moto['telefono'];
            }
        } else {
            $stmt = $conn->prepare("UPDATE motos SET kilometraje = ? WHERE id = ?");
            $stmt->execute([$kilometraje_actual, $moto_id]);
        }

        $_SESSION['success'] = "Mantenimiento registrado con éxito";
        redirect('mantenimientos.php');
    } catch (PDOException $e) {
        $error = "Error al registrar el mantenimiento: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Mantenimiento - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid mt-4">
        <h2>Registrar Mantenimiento</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="moto_id" value="<?php echo $moto_id; ?>">

                    <?php if ($moto): ?>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Información del Cliente</h5>
                                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($moto['nombres']); ?></p>
                                <p><strong>Cédula:</strong> <?php echo htmlspecialchars($moto['cedula']); ?></p>
                                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($moto['telefono']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Información de la Moto</h5>
                                <p><strong>Marca/Modelo:</strong> <?php echo htmlspecialchars($moto['marca'] . ' ' . $moto['modelo']); ?></p>
                                <p><strong>Placa:</strong> <?php echo htmlspecialchars($moto['placa']); ?></p>
                                <p><strong>Kilometraje actual:</strong> <?php echo number_format($moto['kilometraje'], 0); ?> km</p>
                                <?php if ($moto['proximo_cambio_aceite']): ?>
                                    <p><strong>Próx. cambio aceite:</strong> <?php echo number_format($moto['proximo_cambio_aceite'], 0); ?> km</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">No se ha seleccionado una moto. <a href="motos.php">Seleccionar moto</a></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="kilometraje_actual" class="form-label">Kilometraje Actual</label>
                            <input type="number" class="form-control" id="kilometraje_actual" name="kilometraje_actual" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tipo_mantenimiento" class="form-label">Tipo de Mantenimiento</label>
                            <select class="form-select" id="tipo_mantenimiento" name="tipo_mantenimiento" required>
                                <option value="general">General</option>
                                <option value="cambio_aceite">Cambio de Aceite</option>
                            </select>
                        </div>
                    </div>

                    <div class="row" id="cambio-aceite-fields" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="proximo_cambio_aceite" class="form-label">Próximo Cambio de Aceite (km)</label>
                            <input type="number" class="form-control" id="proximo_cambio_aceite" name="proximo_cambio_aceite">
                            <small class="text-muted">Dejar en blanco para no registrar</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="novedades" class="form-label">Novedades / Trabajos Realizados</label>
                        <textarea class="form-control" id="novedades" name="novedades" rows="3" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Registrar Mantenimiento</button>
                    <a href="motos.php" class="btn btn-secondary">Cancelar</a>

                    <?php if (isset($_SESSION['whatsapp_message']) && isset($_SESSION['whatsapp_number'])): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $_SESSION['whatsapp_number']); ?>?text=<?php echo urlencode($_SESSION['whatsapp_message']); ?>" 
                           class="btn btn-success float-end" target="_blank">
                            <i class="bi bi-whatsapp"></i> Enviar por WhatsApp
                        </a>
                        <?php 
                        unset($_SESSION['whatsapp_message']);
                        unset($_SESSION['whatsapp_number']);
                        ?>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tipoMantenimiento = document.getElementById('tipo_mantenimiento');
            const cambioAceiteFields = document.getElementById('cambio-aceite-fields');

            tipoMantenimiento.addEventListener('change', function() {
                if (this.value === 'cambio_aceite') {
                    cambioAceiteFields.style.display = 'block';
                    document.getElementById('proximo_cambio_aceite').required = true;
                } else {
                    cambioAceiteFields.style.display = 'none';
                    document.getElementById('proximo_cambio_aceite').required = false;
                }
            });

            if (document.querySelector('.alert-danger') && tipoMantenimiento.value === 'cambio_aceite') {
                cambioAceiteFields.style.display = 'block';
            }

            const kilometrajeActual = document.getElementById('kilometraje_actual');
            kilometrajeActual.value = <?php echo ($moto ? $moto['kilometraje'] + 1 : 0); ?>;
        });
    </script>
</body>
</html>
