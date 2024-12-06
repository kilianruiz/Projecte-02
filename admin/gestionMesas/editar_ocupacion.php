<?php
session_start();

if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

try {
    if (!isset($conexion)) {
        die("Error: La conexión a la base de datos no se estableció.");
    }

    if (isset($_GET['id'])) {
        $tableId = intval($_GET['id']); 

        $sql = "
            SELECT 
                tbl_tables.table_id,
                tbl_tables.table_number,
                tbl_rooms.name AS room_name,
                tbl_tables.status,
                tbl_occupations.start_time,
                tbl_occupations.end_time,
                tbl_users.username,
                tbl_users.user_id
            FROM 
                tbl_tables
            INNER JOIN 
                tbl_rooms ON tbl_tables.room_id = tbl_rooms.room_id
            INNER JOIN 
                tbl_occupations ON tbl_tables.table_id = tbl_occupations.table_id
            INNER JOIN 
                tbl_users ON tbl_occupations.user_id = tbl_users.user_id
            WHERE 
                tbl_tables.table_id = ?
        ";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(1, $tableId, PDO::PARAM_INT);
        $stmt->execute();
        $occupacion = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$occupacion) {
            echo "No se encontró la ocupación.";
            exit();
        }

        // Procesar el formulario de actualización
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nuevoEstado = $_POST['estado'];
            $nuevoUsuarioId = intval($_POST['usuario']);
            $nuevaFechaInicio = $_POST['start_time'];
            $nuevaFechaFin = $_POST['end_time'];

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
            } else {
                echo "Error al actualizar la ocupación.";
            }
        }

        // Obtener usuarios para el selector
        $usuariosSql = "SELECT user_id, username FROM tbl_users";
        $usuariosStmt = $conexion->prepare($usuariosSql);
        $usuariosStmt->execute();
        $usuarios = $usuariosStmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        header("Location: ../historial.php?error=id_no_proporcionado");
        exit();
    }

    // Cerrar la conexión a la base de datos
    $conexion = null;

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Ocupación</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../styles.css">
</head>
<body>
    <div class="container my-5">
        <h1 class="text-center" class="texto-historial">Editar Ocupación de Mesa</h2>
        
        <form method="POST">
            <div class="form-group">
                <label for="table_number" class="texto-historial">Número de Mesa</label>
                <input type="text" class="form-control" id="table_number" value="<?= htmlspecialchars($occupacion['table_number']) ?>" disabled>
            </div>
            
            <div class="form-group">
                <label for="room_name" class="texto-historial">Sala</label>
                <input type="text" class="form-control" id="room_name" value="<?= htmlspecialchars($occupacion['room_name']) ?>" disabled>
            </div>
            
            <div class="form-group">
                <label for="estado" class="texto-historial">Estado</label>
                <select class="form-control" id="estado" name="estado">
                    <option value="occupied" <?= $occupacion['status'] === 'occupied' ? 'selected' : '' ?>>Ocupada</option>
                    <option value="free" <?= $occupacion['status'] === 'free' ? 'selected' : '' ?>>Libre</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="usuario" class="texto-historial">Usuario</label>
                <select class="form-control" id="usuario" name="usuario">
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?= $usuario['user_id'] ?>" <?= $usuario['user_id'] == $occupacion['user_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($usuario['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="start_time" class="texto-historial">Fecha de Ocupación</label>
                <input type="datetime-local" class="form-control" id="start_time" name="start_time" 
                       value="<?= $occupacion['start_time'] ? date('Y-m-d\TH:i', strtotime($occupacion['start_time'])) : '' ?>">
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
