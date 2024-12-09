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

// Procesar solicitud de eliminación
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["table_id"])) {
    $table_id = intval($_POST["table_id"]);

    try {
        $conexion->beginTransaction();

        // Verificar si la mesa existe
        $sqlCheck = "SELECT table_id, capacity FROM tbl_tables WHERE table_id = :table_id";
        $stmtCheck = $conexion->prepare($sqlCheck);
        $stmtCheck->execute([':table_id' => $table_id]);

        if ($stmtCheck->rowCount() === 0) {
            $conexion->rollBack();
            header('Location: crudMesas.php?error=mesa_no_encontrada');
            exit();
        }

        $mesa = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $capacity = $mesa['capacity'];

        // Eliminar ocupaciones relacionadas
        $sqlDeleteOccupations = "DELETE FROM tbl_occupations WHERE table_id = :table_id";
        $stmtDeleteOccupations = $conexion->prepare($sqlDeleteOccupations);
        $stmtDeleteOccupations->execute([':table_id' => $table_id]);

        // Actualizar el stock de sillas
        $sqlUpdateChairs = "
            UPDATE tbl_chairs_stock 
            SET assigned_chairs = assigned_chairs - :capacity
            WHERE assigned_chairs >= :capacity";
        $stmtUpdateChairs = $conexion->prepare($sqlUpdateChairs);
        $stmtUpdateChairs->execute([':capacity' => $capacity]);

        if ($stmtUpdateChairs->rowCount() === 0) {
            throw new Exception('No hay suficientes sillas asignadas para esta operación.');
        }

        // Eliminar la mesa
        $sqlDeleteTable = "DELETE FROM tbl_tables WHERE table_id = :table_id";
        $stmtDeleteTable = $conexion->prepare($sqlDeleteTable);
        $stmtDeleteTable->execute([':table_id' => $table_id]);

        $conexion->commit();
        header('Location: crudMesas.php?mensaje=mesa_eliminada');
        exit();
    } catch (Exception $e) {
        $conexion->rollBack();
        header('Location: crudMesas.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si no se recibe un table_id en el POST
    header('Location: crudMesas.php?error=solicitud_invalida');
    exit();
}
