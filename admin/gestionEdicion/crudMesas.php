<?php
session_start();

if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no se estableció correctamente.");
}

// Construir consulta de filtro para mesas
$params = [];
$whereSql = '';

if (!empty($_POST['sala'])) {
    $whereSql .= " AND tbl_tables.room_id = :room_id";
    $params[':room_id'] = intval($_POST['sala']);
}
if (!empty($_POST['estado'])) {
    $whereSql .= " AND tbl_tables.status = :status";
    $params[':status'] = $_POST['estado'];
}

// Eliminar el primer "AND" si existe
$whereSql = ltrim($whereSql, " AND");
$whereSql = $whereSql ? "WHERE " . $whereSql : "";

// Consultar salas
$sqlSalas = "SELECT room_id, name_rooms FROM tbl_rooms";
$stmtSalas = $conexion->query($sqlSalas);

// Consultar mesas (con la URL de la imagen de la sala)
$sqlMesas = "
    SELECT tbl_tables.table_id, 
           tbl_tables.table_number, 
           tbl_rooms.name_rooms AS room_name, 
           tbl_tables.capacity, 
           tbl_tables.status, 
           tbl_rooms.image_url  -- Agregamos la URL de la imagen
    FROM tbl_tables 
    INNER JOIN tbl_rooms ON tbl_tables.room_id = tbl_rooms.room_id
    $whereSql
";
$stmtMesas = $conexion->prepare($sqlMesas);
$stmtMesas->execute($params);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Mesas - Restaurante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #a67c52;
        }
        .top-bar {
            background-color: #8A5021;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .container {
            padding: 30px;
            margin-top: 20px;
            background-color: #8A5021;
            border-radius: 10px;
            color: white;
        }
        h1, h3 {
            text-align: center;
        }
        .form-inline {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            flex-wrap: nowrap;
            gap: 10px;
        }
        .btn-primary, .btn-warning {
            background-color: #8A5021;
            color: white;
            border: 2px solid white;
        }
        .btn-primary:hover, .btn-warning:hover {
            background-color: #6c3e18;
            border-color: white;
        }
        .table {
            margin-top: 30px;
            border-radius: 8px;
            border: 3px solid #8A5021;
            overflow: hidden;
        }
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
            color: #8A5021;
            border: 3px solid #8A5021;
        }
        .table thead {
            background-color: #6c3e18;
            color: white;
        }
        .table tbody {
            background-color: #8A5021;
        }
        .table tbody tr:hover {
            background-color: #6c3e18;
            color: white;
        }
        .table img {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        Administración de Mesas
        <a href="../principalAdmin.php" class="btn btn-primary">Volver</a>
    </div>
    <div class="container">
        <!-- Mensajes de éxito o error -->
        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success">Mesa añadida exitosamente.</div>
        <?php } elseif (isset($_GET['errors'])) { ?>
            <div class="alert alert-danger"><?= nl2br(htmlspecialchars($_GET['errors'])); ?></div>
        <?php } ?>

        <!-- Añadir Mesa -->
        <div>
            <h3>Añadir Mesa</h3>
            <form class="form-inline" method="POST" action="crearMesa.php">
                <input type="hidden" name="action" value="add">
                <select name="room_id" class="form-select">
                    <option value="" disabled selected>Seleccionar Sala</option>
                    <?php while ($sala = $stmtSalas->fetch()) { ?>
                        <option value="<?= htmlspecialchars($sala['room_id']); ?>">
                            <?= htmlspecialchars($sala['name_rooms']); ?>
                        </option>
                    <?php } ?>
                </select>
                <input type="number" name="table_number" class="form-control" placeholder="Número de Mesa">
                <input type="number" name="capacity" class="form-control" placeholder="Capacidad">
                <select name="status" class="form-select">
                    <option value="free">Libre</option>
                    <option value="occupied">Ocupada</option>
                </select>
                <button type="submit" class="btn btn-primary">Añadir</button>
            </form>
        </div>

        <!-- Filtrar Mesas -->
        <div class="mt-4">
            <h3>Filtrar Mesas</h3>
            <form class="form-inline" method="POST">
                <select name="sala" class="form-select">
                    <option value="">Todas las Salas</option>
                    <?php
                    $stmtSalas->execute(); // Volver a ejecutar para el segundo uso
                    while ($sala = $stmtSalas->fetch()) { ?>
                        <option value="<?= htmlspecialchars($sala['room_id']); ?>">
                            <?= htmlspecialchars($sala['name_rooms']); ?>
                        </option>
                    <?php } ?>
                </select>
                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="free">Libre</option>
                    <option value="occupied">Ocupada</option>
                </select>
                <button type="submit" class="btn btn-warning">Filtrar</button>
            </form>
        </div>

        <!-- Tabla de Mesas -->
        <div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Número de Mesa</th>
                        <th>Sala</th>
                        <th>Capacidad</th>
                        <th>Estado</th>
                        <th>Imagen Sala</th> <!-- Columna para la imagen de la sala -->
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stmtMesas->rowCount() > 0) { 
                        while ($mesa = $stmtMesas->fetch()) { ?>
                            <tr>
                                <td><?= htmlspecialchars($mesa['table_number']); ?></td>
                                <td><?= htmlspecialchars($mesa['room_name']); ?></td>
                                <td><?= htmlspecialchars($mesa['capacity']); ?></td>
                                <td><?= $mesa['status'] === 'free' ? 'Libre' : 'Ocupada'; ?></td>
                                <td>
                                    <!-- Mostrar la imagen de la sala -->
                                    <?php if (!empty($mesa['image_url'])) { ?>
                                        <img src="<?= htmlspecialchars($mesa['image_url']); ?>" alt="Imagen de la sala">
                                    <?php } else { ?>
                                        <span>No disponible</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 10px; justify-content: center;">
                                        <a href="editarMesa.php?table_id=<?= htmlspecialchars($mesa['table_id']); ?>" class="btn btn-warning">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <form action="eliminarMesa.php" method="POST">
                                            <input type="hidden" name="table_id" value="<?= htmlspecialchars($mesa['table_id']); ?>">
                                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php } 
                    } else { ?>
                        <tr><td colspan="6">No se encontraron mesas.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.querySelectorAll('form[action="eliminarMesa.php"]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault(); // Evita el envío inmediato del formulario
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción eliminará la mesa definitivamente.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>

</body>
</html>
