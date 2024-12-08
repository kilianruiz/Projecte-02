<?php
session_start();

// Comprobar si el usuario tiene el rol de Administrador
if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

// Verificar que $pdo está definido
if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no se estableció correctamente.");
}

// Procesar formulario para añadir una mesa
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "add") {
    // Verificar si el campo room_id existe en el array $_POST
    if (isset($_POST["room_id"])) {
        // Recoger los datos del formulario
        $room_id = $_POST["room_id"];
        $table_number = $_POST["table_number"];
        $capacity = $_POST["capacity"];
        $status = $_POST["status"];

        // Validar que los campos no estén vacíos y que el número de mesa no sea negativo
        $errors = [];

        if (empty($room_id)) {
            $errors[] = "La sala es obligatoria.";
        }
        if (empty($table_number)) {
            $errors[] = "El número de mesa es obligatorio.";
        } elseif ($table_number < 1) {
            $errors[] = "El número de mesa no puede ser negativo o cero.";
        }
        if (empty($capacity)) {
            $errors[] = "La capacidad es obligatoria.";
        }
        if (empty($status)) {
            $errors[] = "El estado de la mesa es obligatorio.";
        }

        // Si hay errores, mostramos una única alerta con todos los errores
        if (count($errors) > 0) {
            // Convertir los errores en un solo mensaje
            $errorMessage = implode("\n", $errors);
            echo "<script>alert('$errorMessage');</script>";
        } else {
            // Verificar si la mesa con ese número ya existe en la sala seleccionada
            $sqlCheckTable = "SELECT COUNT(*) FROM tbl_tables WHERE room_id = :room_id AND table_number = :table_number";
            $stmtCheckTable = $conexion->prepare($sqlCheckTable);
            $stmtCheckTable->execute([':room_id' => $room_id, ':table_number' => $table_number]);
            $existingTable = $stmtCheckTable->fetchColumn();

            if ($existingTable > 0) {
                echo "<script>alert('Ya existe una mesa con ese número en esta sala.');</script>";
            } else {
                // Insertar la nueva mesa
                $sql = "INSERT INTO tbl_tables (room_id, table_number, capacity, status) 
                        VALUES (:room_id, :table_number, :capacity, :status)";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    ':room_id' => $room_id,
                    ':table_number' => $table_number,
                    ':capacity' => $capacity,
                    ':status' => $status
                ]);

                echo "<script>alert('Mesa añadida exitosamente.');</script>";
            }
        }
    } else {
        echo "<script>alert('No se seleccionó una sala.');</script>";
    }
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

// Consultar mesas
$sqlMesas = "
    SELECT tbl_tables.table_number, tbl_rooms.name_rooms AS room_name, tbl_tables.capacity, tbl_tables.status 
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
            background-color: #8A5021; /* Color marrón fuerte */
            border-radius: 10px;
            color: white; /* Para el texto en el contenedor */
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
            background-color: #8A5021; /* Fondo marrón para el botón de añadir */
            color: white; /* Texto blanco */
            border: 2px solid white; /* Borde blanco */
        }
        .btn-primary:hover, .btn-warning:hover {
            background-color: #6c3e18; /* Color marrón más oscuro cuando se pasa el mouse */
            border-color: white; /* Borde blanco en hover */
        }
        .table {
            margin-top: 30px;
            border-radius: 8px;
            border: 3px solid #8A5021; /* Bordes marrones más gruesos */
            overflow: hidden;
        }
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
            color: #8A5021; /* Letras marrones dentro de la tabla */
            border: 3px solid #8A5021; /* Bordes marrones más gruesos */
        }
        .table thead {
            background-color: #6c3e18; /* Color de fondo de la cabecera de la tabla */
            color: white;
        }
        .table tbody {
            background-color: #8A5021; /* Fondo de la tabla marrón fuerte */
        }
        .table tbody tr:hover {
            background-color: #6c3e18;
            color: white;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        Administración de Mesas
        <a href="../principalAdmin.php" class="btn btn-primary">Volver</a>
    </div>
    <div class="container">
        <!-- Añadir Mesa -->
        <div>
            <h3>Añadir Mesa</h3>
            <form class="form-inline" method="POST">
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
                    <option value="">Todos</option>
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
                                    <a href="#" class="btn btn-warning"><i class="fas fa-edit"></i> Editar</a>
                                    <a href="#" class="btn btn-danger"><i class="fas fa-trash"></i> Eliminar</a>
                                </td>
                            </tr>
                        <?php } 
                    } else { ?>
                        <tr><td colspan="5">No se encontraron mesas.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
