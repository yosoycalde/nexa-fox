<?php
session_start();
require_once 'config.php';

$conn = getConnection();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$sql = "SELECT * FROM contactos ORDER BY fecha_registro DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

$sqlCount = "SELECT COUNT(*) as total FROM contactos";
$resultCount = $conn->query($sqlCount);
$totalRecords = $resultCount->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $perPage);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Nexa-Fox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/style-admin.css">
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1>Panel de Administración</h1>
            <p>Gestión de Contactos - Nexa-Fox</p>
        </div>
    </div>

    <div class="container">
        <div class="row mb-4">
            <?php
            $stats = $conn->query("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN atendido = 1 THEN 1 ELSE 0 END) as atendidos,
                SUM(CASE WHEN atendido = 0 THEN 1 ELSE 0 END) as pendientes
                FROM contactos")->fetch_assoc();
            ?>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5>Total Contactos</h5>
                    <h2><?php echo $stats['total']; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5>Atendidos</h5>
                    <h2 class="text-success"><?php echo $stats['atendidos']; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5>Pendientes</h5>
                    <h2 class="text-warning"><?php echo $stats['pendientes']; ?></h2>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Listado de Contactos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Servicio</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                <td>
                                    <?php 
                                    $servicios = [
                                        'web' => 'Página Web',
                                        'ecommerce' => 'E-commerce',
                                        'mantenimiento' => 'Mantenimiento',
                                        'otro' => 'Otro'
                                    ];
                                    echo $servicios[$row['servicio']] ?? $row['servicio'];
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_registro'])); ?></td>
                                <td>
                                    <?php if ($row['atendido']): ?>
                                        <span class="badge badge-atendido">Atendido</span>
                                    <?php else: ?>
                                        <span class="badge badge-pendiente">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="verDetalle(<?php echo $row['id']; ?>)">
                                        Ver
                                    </button>
                                    <?php if (!$row['atendido']): ?>
                                    <button class="btn btn-sm btn-success" onclick="marcarAtendido(<?php echo $row['id']; ?>)">
                                        Marcar Atendido
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detalleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle del Contacto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalleContent">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalle(id) {
            fetch(`api/obtener_contacto.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const contacto = data.contacto;
                        document.getElementById('detalleContent').innerHTML = `
                            <p><strong>Nombre:</strong> ${contacto.nombre}</p>
                            <p><strong>Email:</strong> ${contacto.email}</p>
                            <p><strong>Teléfono:</strong> ${contacto.telefono || 'No proporcionado'}</p>
                            <p><strong>Servicio:</strong> ${contacto.servicio}</p>
                            <p><strong>Mensaje:</strong></p>
                            <p>${contacto.mensaje}</p>
                            <p><strong>Fecha:</strong> ${contacto.fecha_registro}</p>
                            <p><strong>IP:</strong> ${contacto.ip_address}</p>
                        `;
                        new bootstrap.Modal(document.getElementById('detalleModal')).show();
                    }
                });
        }

        function marcarAtendido(id) {
            if (confirm('¿Marcar este contacto como atendido?')) {
                fetch('api/actualizar_estado.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id, atendido: true})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al actualizar el estado');
                    }
                });
            }
        }
    </script>
</body>
</html>

<?php
$stmt->close();
closeConnection($conn);
?>