<?php
session_start();

// Verificar si el usuario es administrador
if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

// Verificar si la conexión a la base de datos fue exitosa
if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no se estableció correctamente.");
}

// Configuración de paginación
$recordsPerPage = isset($_POST['recordsPerPage']) ? intval($_POST['recordsPerPage']) : (isset($_SESSION['recordsPerPage']) ? intval($_SESSION['recordsPerPage']) : 10);
$_SESSION['recordsPerPage'] = $recordsPerPage; // Guardar en sesión
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;  // Verifica que la página sea un número mayor a 1

// Consultar salas
$sqlSalas = "SELECT room_id, name_rooms FROM tbl_rooms";
$stmtSalas = $conexion->query($sqlSalas);

// Construir consulta de filtro para mesas
$params = [];
$whereSql = '';

if (!empty($_POST['name_rooms'])) {
    $whereSql .= " AND tbl_rooms.name_rooms LIKE :name_rooms";
    $params[':name_rooms'] = '%' . $_POST['name_rooms'] . '%';
}

// Eliminar el primer "AND" si existe
$whereSql = ltrim($whereSql, " AND");
$whereSql = $whereSql ? "WHERE " . $whereSql : "";

// Consultar el total de mesas con los filtros aplicados
$sqlCount = "
    SELECT COUNT(*) 
    FROM tbl_tables 
    INNER JOIN tbl_rooms ON tbl_tables.room_id = tbl_rooms.room_id 
    $whereSql
";
$stmtCount = $conexion->prepare($sqlCount);
$stmtCount->execute($params);
$totalRecords = $stmtCount->fetchColumn();
$totalPages = ceil($totalRecords / $recordsPerPage);

// Consultar mesas con los filtros aplicados y paginación
$offset = ($currentPage - 1) * $recordsPerPage;

$sqlMesas = "
    SELECT tbl_rooms.room_id,
           tbl_rooms.name_rooms AS room_name, 
           tbl_rooms.capacity, 
           tbl_rooms.image_path,
           tbl_rooms.description
    FROM tbl_rooms 
    $whereSql
    LIMIT :limit OFFSET :offset
";  
$stmtMesas = $conexion->prepare($sqlMesas);

// Vincular los parámetros para paginación
$stmtMesas->bindParam(':limit', $recordsPerPage, PDO::PARAM_INT);
$stmtMesas->bindParam(':offset', $offset, PDO::PARAM_INT);

// Vincular otros parámetros de filtro si existen
foreach ($params as $key => $value) {
    $stmtMesas->bindValue($key, $value);
}

$stmtMesas->execute();
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
        body { background-color: #a67c52; }
        .top-bar { background-color: #8A5021; padding: 20px; margin-bottom: 20px; text-align: center; color: white; font-size: 1.5rem; font-weight: bold; }
        .container { padding: 30px; margin-top: 20px; background-color: #8A5021; border-radius: 10px; color: white; }
        h1, h3 { text-align: center; }
        .form-inline { display: flex; align-items: center; justify-content: flex-start; flex-wrap: nowrap; gap: 10px; }
        .btn-primary, .btn-warning, .btn-danger { background-color: #6c3e18; color: white; border: 2px solid white; }
        .btn-primary:hover, .btn-warning:hover, .btn-danger:hover { background-color: #8A5021; border-color: white; }
        .table { margin-top: 30px; border-radius: 8px; border: 3px solid #8A5021; overflow: hidden; }
        .table th, .table td { text-align: center; vertical-align: middle; color: #8A5021; border: 3px solid #8A5021; }
        .table thead { background-color: #6c3e18; color: white; }
        .table tbody { background-color: #8A5021; }
        .table tbody tr:hover { background-color: #6c3e18; color: white; }
        .table img { width: 100px; height: 70px; }

        /* Estilos para la paginación */
        .pagination {
            margin-top: 20px;
        }
        .pagination .page-item.active .page-link {
            background-color: #6c3e18;
            border-color: #6c3e18;
        }
        .pagination .page-item .page-link {
            background-color: #a67c52;
            border-color: #8A5021;
            color: white;
        }
        .pagination .page-item .page-link:hover {
            background-color: #8A5021;
            border-color: #6c3e18;
            color: white;
        }
        .form-select{
            background-color: #a67c52;
            color: white;
            border: 2px solid #8A5021;
        }
        /* Estilo para el selector de registros por página */
        .form-inline select, .form-inline input[type="number"] {
            background-color: #a67c52;
            color: white;
            border: 2px solid #8A5021;
        }
        .form-inline select:hover, .form-inline input[type="number"]:hover {
            background-color: #8A5021;
            border-color: #6c3e18;
        }

        /* Estilo para el botón eliminar */
        .btn-danger {
            background-color: #d33;
            border-color: #d33;
        }
        .btn-danger:hover {
            background-color: #a02a2a;
            border-color: #a02a2a;
        }

        /* Cambiar color del texto del placeholder a blanco */
        input::placeholder, select::placeholder {
            color: white;
        }

    </style>
</head>
<body>
    <div class="top-bar">
        Administración de Salas
        <a href="../principalAdmin.php" class="btn btn-primary">Volver</a>
    </div>
    <div class="container">
        <!-- Botones para agregar mesa, sala y editar sala -->
        <div class="d-flex justify-content-between mb-4">
            <a href="editarSala.php" class="btn btn-primary">
                <i class="fas fa-edit"></i> Editar Sala
            </a>
            <a href="agregarSala.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Agregar Sala
            </a>
        </div>

        <!-- Mensajes de éxito o error -->
        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success">Mesa añadida correctamente.</div>
        <?php } elseif (isset($_GET['errors'])) { ?>
            <div class="alert alert-danger"><?= nl2br(htmlspecialchars($_GET['errors'])); ?></div>
        <?php } ?>

        <!-- Filtro de Mesas -->
        <div class="mt-4">
            <h3>Filtrar Salas</h3>
            <form class="form-inline" method="POST">
                <input type="text" name="name_rooms" class="form-select" placeholder="Buscar por nombre de sala" value="<?= isset($_POST['name_rooms']) ? htmlspecialchars($_POST['name_rooms']) : ''; ?>">
                <button type="submit" class="btn btn-warning">Filtrar</button>
            </form>
        </div>

        <!-- Selección de registros por página -->
        <form method="POST" class="mb-3">
            <label for="recordsPerPage" class="me-2">Registros por página:</label>
            <select name="recordsPerPage" id="recordsPerPage" class="form-select w-auto d-inline" onchange="this.form.submit();">
                <option value="5" <?= $recordsPerPage == 5 ? 'selected' : ''; ?>>5</option>
                <option value="10" <?= $recordsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                <option value="20" <?= $recordsPerPage == 20 ? 'selected' : ''; ?>>20</option>
            </select>
        </form>

        <!-- Tabla de Mesas -->
        <div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Salas</th>
                        <th>Capacidad</th>
                        <th>Descripción</th>
                        <th>Imagen Sala</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stmtMesas->rowCount() > 0) { 
                        while ($mesa = $stmtMesas->fetch()) { ?>
                            <tr>
                                <td><?= htmlspecialchars($mesa['room_name']); ?></td>
                                <td><?= htmlspecialchars($mesa['capacity']); ?></td>
                                <td><?= htmlspecialchars($mesa['description']); ?></td>
                                <td>
                                    <?php if (!empty($mesa['image_path'])) { ?>
                                        <img src="../../<?= htmlspecialchars($mesa['image_path']); ?>" alt="Mesa" class="mt-3 img-thumbnail" style="max-width: 400px; max-height: 300px; object-fit: cover;">
                                    <?php } else { ?>
                                        Sin Imagen
                                    <?php } ?>
                                </td>
                                <td>
                                    <a href="editarSala.php?id=<?= $mesa['room_id']; ?>" class="btn btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form action="eliminarSala.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="room_id" value="<?= $mesa['room_id']; ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar esta sala?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>

                                </td>
                            </tr>
                        <?php } 
                    } else { ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay mesas disponibles.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <div class="pagination">
                <ul class="pagination">
                    <?php if ($currentPage > 1) { ?>
                        <li class="page-item"><a class="page-link" href="?page=1">Primera</a></li>
                        <li class="page-item"><a class="page-link" href="?page=<?= $currentPage - 1 ?>">Anterior</a></li>
                    <?php } ?>
                    <li class="page-item active"><span class="page-link"><?= $currentPage ?></span></li>
                    <?php if ($currentPage < $totalPages) { ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $currentPage + 1 ?>">Siguiente</a></li>
                        <li class="page-item"><a class="page-link" href="?page=<?= $totalPages ?>">Última</a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
