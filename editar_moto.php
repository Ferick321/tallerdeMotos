<?php
require_once 'config.php';

if (!isAuthenticated()) {
    redirect('login.php');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    $_SESSION['error'] = "Moto no especificada";
    redirect('motos.php');
}

// Obtener datos de la moto
$stmt = $conn->prepare("SELECT m.*, c.nombres, c.cedula, c.telefono, c.email 
                       FROM motos m
                       JOIN clientes c ON m.cliente_id = c.id
                       WHERE m.id = ?");
$stmt->execute([$id]);
$moto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$moto) {
    $_SESSION['error'] = "Moto no encontrada";
    redirect('motos.php');
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marca = trim($_POST['marca']);
    $modelo = trim($_POST['modelo']);
    $placa = trim($_POST['placa']);
    $serie = trim($_POST['serie']);
    $color = trim($_POST['color']);
    $kilometraje = intval($_POST['kilometraje']);
    $proximo_cambio_aceite = !empty($_POST['proximo_cambio_aceite']) ? intval($_POST['proximo_cambio_aceite']) : null;
    
    try {
        $stmt = $conn->prepare("UPDATE motos SET 
                              marca = ?, modelo = ?, placa = ?, serie = ?, color = ?,
                              kilometraje = ?, proximo_cambio_aceite = ?
                              WHERE id = ?");
        $stmt->execute([
            $marca, $modelo, $placa, $serie, $color,
            $kilometraje, $proximo_cambio_aceite, $id
        ]);
        
        $_SESSION['success'] = "Moto actualizada con éxito";
        redirect('motos.php');
    } catch (PDOException $e) {
        $error = "Error al actualizar la moto: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Moto - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <h2>Editar Moto</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Información del Cliente</h5>
                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($moto['nombres']); ?></p>
                            <p><strong>Cédula:</strong> <?php echo htmlspecialchars($moto['cedula']); ?></p>
                            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($moto['telefono']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="marca" class="form-label">Marca</label>
                            <input type="text" class="form-control" id="marca" name="marca" value="<?php echo htmlspecialchars($moto['marca']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modelo" class="form-label">Modelo</label>
                            <input type="text" class="form-control" id="modelo" name="modelo" value="<?php echo htmlspecialchars($moto['modelo']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="placa" class="form-label">Placa</label>
                            <input type="text" class="form-control" id="placa" name="placa" value="<?php echo htmlspecialchars($moto['placa']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="serie" class="form-label">Número de Serie</label>
                            <input type="text" class="form-control" id="serie" name="serie" value="<?php echo htmlspecialchars($moto['serie']); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="color" name="color" value="<?php echo htmlspecialchars($moto['color']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="kilometraje" class="form-label">Kilometraje</label>
                            <input type="number" class="form-control" id="kilometraje" name="kilometraje" value="<?php echo $moto['kilometraje']; ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="proximo_cambio_aceite" class="form-label">Próximo Cambio de Aceite (km)</label>
                            <input type="number" class="form-control" id="proximo_cambio_aceite" name="proximo_cambio_aceite" value="<?php echo $moto['proximo_cambio_aceite']; ?>">
                            <small class="text-muted">Dejar en blanco para no registrar</small>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <a href="motos.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>