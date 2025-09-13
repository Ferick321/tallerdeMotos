<?php
require_once 'config.php';

if (!isAuthenticated()) {
    redirect('login.php');
}

// Obtener lista de motos con información de cliente
$query = "SELECT m.*, c.nombres as cliente_nombre, c.cedula, c.telefono 
          FROM motos m
          JOIN clientes c ON m.cliente_id = c.id
          ORDER BY m.marca, m.modelo";

$stmt = $conn->query($query);
$motos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar eliminación de moto
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM mantenimientos WHERE moto_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $_SESSION['error'] = "No se puede eliminar la moto porque tiene mantenimientos asociados";
        } else {
            $stmt = $conn->prepare("DELETE FROM motos WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Moto eliminada con éxito";
        }
        
        redirect('motos.php');
    } catch (PDOException $e) {
        $error = "Error al eliminar la moto: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Motos - Taller de Motos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Motos Registradas</h2>
            <a href="agregar_moto.php" class="btn btn-primary">
                <i class="bi bi-plus"></i> Nueva Moto
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
                    <table id="motosTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Moto</th>
                                <th>Placa</th>
                                <th>Kilometraje</th>
                                <th>Próx. Cambio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($motos as $moto): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($moto['cliente_nombre']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($moto['cedula']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($moto['marca'] . ' ' . $moto['modelo']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($moto['color']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($moto['placa']); ?></td>
                                    <td><?php echo number_format($moto['kilometraje'], 0); ?> km</td>
                                    <td>
                                        <?php if ($moto['proximo_cambio_aceite']): ?>
                                            <?php echo number_format($moto['proximo_cambio_aceite'], 0); ?> km
                                            <?php if ($moto['kilometraje'] >= $moto['proximo_cambio_aceite']): ?>
                                                <span class="badge bg-danger">¡Cambio requerido!</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No registrado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="agregar_mantenimiento.php?moto_id=<?php echo $moto['id']; ?>" class="btn btn-sm btn-outline-primary" title="Agregar mantenimiento">
                                            <i class="bi bi-tools"></i>
                                        </a>
                                        <a href="editar_moto.php?id=<?php echo $moto['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="motos.php?delete=<?php echo $moto['id']; ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar esta moto?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php if ($moto['telefono']): ?>
                                            <?php
                                                $telefono_limpio = preg_replace('/[^0-9]/', '', $moto['telefono']);
                                                $telefono_con_codigo = (strpos($telefono_limpio, '0') === 0) ? '593' . substr($telefono_limpio, 1) : $telefono_limpio;
                                            ?>
                                            <a href="https://wa.me/<?php echo $telefono_con_codigo; ?>?text=Estimado%20cliente,%20le%20contactamos%20del%20Taller%20de%20Motos%20sobre%20su%20Motocicleta%20<?php echo urlencode($moto['marca'] . ' ' . $moto['modelo'] . ' placa ' . $moto['placa']); ?>" 
                                               class="btn btn-sm btn-outline-success" title="Contactar por WhatsApp" target="_blank">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>

        //definir idiomas del funcionamiento del sistema
        $(document).ready(function() {
            $('#motosTable').DataTable({
                language: {
                    "decimal":        "",
                    "emptyTable":     "No hay motos registradas",
                    "info":           "Mostrando _START_ a _END_ de _TOTAL_ motos",
                    "infoEmpty":      "Mostrando 0 a 0 de 0 motos",
                    "infoFiltered":   "(filtrado de _MAX_ motos en total)",
                    "infoPostFix":    "",
                    "thousands":      ",",
                    "lengthMenu":     "Mostrar _MENU_ motos por página",
                    "loadingRecords": "Cargando...",
                    "processing":     "Procesando...",
                    "search":         "Buscar: ",
                    "zeroRecords":    "No se encontraron motos coincidentes",
                    "paginate": {
                        "first":      "Primera",
                        "last":       "Última",
                        "next":       "Siguiente",
                        "previous":   "Anterior"
                    },
                    "aria": {
                        "sortAscending":  ": activar para ordenar la columna ascendente",
                        "sortDescending": ": activar para ordenar la columna descendente"
                    }
                }
            });
        });
    </script>
</body>
</html>