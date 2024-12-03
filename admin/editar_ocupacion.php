<?php
session_start();

if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

include_once('../conexion/conexion.php');

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
    $stmt->bind_param("i", $tableId);
    $stmt->execute();
    $result = $stmt->get_result();
    $occupacion = $result->fetch_assoc();

    if (!$occupacion) {
        echo "No se encontró la ocupación.";
        exit();
    }

    $stmt->close();
} else {
    header("Location: historial.php?error=id_no_proporcionado");
    exit();
}

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoEstado = $_POST['estado'];
    $nuevoUsuarioId = intval($_POST['usuario']);
    $nuevaFechaInicio = $_POST['start_time'];
    $nuevaFechaFin = $_POST['end_time'];

    $updateSql = "
        UPDATE tbl_occupations
        SET start_time = ?, end_time = ?, user_id = ?
        WHERE table_id = ?
    ";
    
    if ($stmtUpdate = $conexion->prepare($updateSql)) {
        $stmtUpdate->bind_param("ssii", $nuevaFechaInicio, $nuevaFechaFin, $nuevoUsuarioId, $tableId);
        
        if ($stmtUpdate->execute()) {
            $estadoSql = "UPDATE tbl_tables SET status = ? WHERE table_id = ?";
            if ($stmtEstado = $conexion->prepare($estadoSql)) {
                $stmtEstado->bind_param("si", $nuevoEstado, $tableId);
                $stmtEstado->execute();
                $stmtEstado->close();
            }
            header("Location: historial.php?msg=ocupacion_actualizada");
        } else {
            echo "Error al actualizar la ocupación.";
        }
        $stmtUpdate->close();
    } else {
        echo "Error en la preparación de la consulta.";
    }
}

$usuariosSql = "SELECT user_id, username FROM tbl_users";
$usuariosResult = $conexion->query($usuariosSql);
$usuarios = [];
if ($usuariosResult->num_rows > 0) {
    while ($row = $usuariosResult->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Ocupación</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../styles.css">
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
            <a href="historial.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>
