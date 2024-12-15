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

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $room_id = $_GET['id'];

    try {
        // Iniciar transacción
        $conexion->beginTransaction();

        // 1. Eliminar todas las ocupaciones asociadas a las mesas de la sala
        $sqlOccupations = "DELETE FROM tbl_occupations WHERE table_id IN (SELECT table_id FROM tbl_tables WHERE room_id = :room_id)";
        $stmtOccupations = $conexion->prepare($sqlOccupations);
        $stmtOccupations->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmtOccupations->execute();

        // 2. Eliminar todas las mesas de la sala
        $sqlTables = "DELETE FROM tbl_tables WHERE room_id = :room_id";
        $stmtTables = $conexion->prepare($sqlTables);
        $stmtTables->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmtTables->execute();

        // 3. Eliminar la sala de la tabla tbl_rooms
        $sqlDeleteRoom = "DELETE FROM tbl_rooms WHERE room_id = :room_id";
        $stmtDeleteRoom = $conexion->prepare($sqlDeleteRoom);
        $stmtDeleteRoom->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmtDeleteRoom->execute();

        // Confirmar la transacción
        $conexion->commit();

        // Redirigir con mensaje de éxito
        header('Location: crudSalas.php');
        exit();
    } catch (Exception $e) {
        // Si ocurre algún error, revertir la transacción
        $conexion->rollBack();
        // Redirigir con mensaje de error
        header('Location: crudSalas.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si no se ha enviado un room_id válido
    header('Location: crudSalas.php?error=ID inválido');
    exit();
}
