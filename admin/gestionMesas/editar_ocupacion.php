<?php
session_start();

// Verificar si el usuario está autenticado y tiene rol de administrador
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

try {
    // Verificar conexión a la base de datos
    if (!isset($conexion)) {
        throw new Exception("Error: La conexión a la base de datos no se estableció.");
    }

    // Verificar que el ID de la mesa fue proporcionado
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: ../historial.php?error=id_no_proporcionado");
        exit();
    }

    $tableId = intval($_GET['id']);

    // Obtener información de la ocupación
    $sql = "
        SELECT 
            tbl_tables.table_id,
            tbl_tables.table_number,
            tbl_rooms.name_rooms AS room_name,
            tbl_tables.status,
            tbl_occupations.start_time,
            tbl_occupations.end_time,
            tbl_users.username,
            tbl_users.user_id
        FROM 
            tbl_tables
        INNER JOIN 
            tbl_rooms ON tbl_tables.room_id = tbl_rooms.room_id
        LEFT JOIN 
            tbl_occupations ON tbl_tables.table_id = tbl_occupations.table_id
        LEFT JOIN 
            tbl_users ON tbl_occupations.user_id = tbl_users.user_id
        WHERE 
            tbl_tables.table_id = ? 
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(1, $tableId, PDO::PARAM_INT);
    $stmt->execute();
    $occupacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$occupacion) {
        throw new Exception("No se encontró la ocupación asociada a esta mesa.");
    }

    // Procesar la solicitud POST para actualizar la ocupación
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nuevoEstado = $_POST['estado'];
        $nuevoUsuarioId = intval($_POST['usuario']);
        $nuevaFechaInicio = $_POST['start_time'];
        $nuevaFechaFin = $_POST['end_time'];

        // Validar datos obligatorios
        if (empty($nuevoEstado) || empty($nuevoUsuarioId) || empty($nuevaFechaInicio)) {
            throw new Exception("Todos los campos son obligatorios.");
        }

        // Actualizar ocupación
        $updateSql = "
            UPDATE tbl_occupations
            SET start_time = ?, end_time = ?, user_id = ?
            WHERE table_id = ?
        ";

        $stmtUpdate = $conexion->prepare($updateSql);
        $stmtUpdate->bindParam(1, $nuevaFechaInicio, PDO::PARAM_STR);
        $stmtUpdate->bindParam(2, $nuevaFechaFin, PDO::PARAM_STR);
        $stmtUpdate->bindParam(3, $nuevoUsuarioId, PDO::PARAM_INT);
        $stmtUpdate->bindParam(4, $tableId, PDO::PARAM_INT);

        if ($stmtUpdate->execute()) {
            // Actualizar estado de la mesa
            $estadoSql = "UPDATE tbl_tables SET status = ? WHERE table_id = ?";
            $stmtEstado = $conexion->prepare($estadoSql);
            $stmtEstado->bindParam(1, $nuevoEstado, PDO::PARAM_STR);
            $stmtEstado->bindParam(2, $tableId, PDO::PARAM_INT);
            $stmtEstado->execute();

            header("Location: ../historial.php?msg=ocupacion_actualizada");
            exit();
        } else {
            throw new Exception("Error al actualizar la ocupación.");
        }
    }

    // Obtener lista de usuarios, excluyendo al usuario actual de esta mesa
    $usuariosSql = "SELECT user_id, username FROM tbl_users WHERE user_id != ?";
    $usuariosStmt = $conexion->prepare($usuariosSql);
    $usuariosStmt->bindParam(1, $occupacion['user_id'], PDO::PARAM_INT);
    $usuariosStmt->execute();
    $usuarios = $usuariosStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Ocupación</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #a67c52; /* Color de fondo principal */
            color: white;
        }

        .container {
            background-color: #6c3e18; /* Fondo más oscuro para la sección */
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
        }

        h1.text-center {
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-control, .form-select {
            background-color: #a67c52;
            border: 2px solid #6c3e18;
            color: white;
        }

        .form-control:focus, .form-select:focus {
            background-color: #a67c52;
            border-color: white;
            box-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
        }

        .form-control[readonly] {
            background-color: #a67c52; 
            color: white;
            cursor: not-allowed;
        }

        .btn-primary {
            background-color: #6c3e18;
            border-color: white;
            color: white;
        }

        .btn-primary:hover {
            background-color: #8A5021;
            border-color: white;
        }

        .btn-secondary {
            background-color: #d1b07b;
            border-color: #d1b07b;
            color: #8A5021;
        }

        .btn-secondary:hover {
            background-color: #a67c52;
            border-color: #a67c52;
        }

        .texto-historial {
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="text-center texto-historial">Editar Ocupación de Mesa</h1>
        
        <form method="POST">
            <div class="form-group">
                <label for="table_number" class="texto-historial">Número de Mesa</label>
                <input type="text" class="form-control" id="table_number" 
                       value="<?= htmlspecialchars($occupacion['table_number']) ?>" readonly>
            </div>
            
            <div class="form-group">
                <label for="room_name" class="texto-historial">Sala</label>
                <input type="text" class="form-control" id="room_name" 
                       value="<?= htmlspecialchars($occupacion['room_name']) ?>" readonly>
            </div>
            
            <div class="form-group">
                <label for="estado" class="texto-historial">Estado</label>
                <select class="form-control" id="estado" name="estado" required>
                    <option value="occupied" <?= $occupacion['status'] === 'occupied' ? 'selected' : '' ?>>Ocupada</option>
                    <option value="free" <?= $occupacion['status'] === 'free' ? 'selected' : '' ?>>Libre</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="usuario" class="texto-historial">Usuario</label>
                <select class="form-control" id="usuario" name="usuario" required>
                    <!-- Usuario actual (no editable, solo muestra) -->
                    <option value="<?= $occupacion['user_id'] ?>" selected>
                        <?= htmlspecialchars($occupacion['username']) ?>
                    </option>
                    <!-- Usuarios disponibles para asignar -->
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?= $usuario['user_id'] ?>">
                            <?= htmlspecialchars($usuario['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="start_time" class="texto-historial">Fecha de Ocupación</label>
                <input type="datetime-local" class="form-control" id="start_time" name="start_time" 
                       value="<?= $occupacion['start_time'] ? date('Y-m-d\TH:i', strtotime($occupacion['start_time'])) : '' ?>" required>
            </div>

            <div class="form-group">
                <label for="end_time" class="texto-historial">Fecha de Liberación</label>
                <input type="datetime-local" class="form-control" id="end_time" name="end_time" 
                       value="<?= $occupacion['end_time'] ? date('Y-m-d\TH:i', strtotime($occupacion['end_time'])) : '' ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="../historial.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>
