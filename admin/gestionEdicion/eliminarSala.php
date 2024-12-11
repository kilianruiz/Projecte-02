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

if (isset($_POST['table_id']) && is_numeric($_POST['table_id'])) {
    $table_id = $_POST['table_id'];

    try {
        // Iniciar transacción
        $conexion->beginTransaction();

        // Eliminar registros de ocupaciones asociadas a la mesa
        $sqlOccupations = "DELETE FROM tbl_occupations WHERE table_id = :table_id";
        $stmtOccupations = $conexion->prepare($sqlOccupations);
        $stmtOccupations->bindParam(':table_id', $table_id, PDO::PARAM_INT);
        $stmtOccupations->execute();

        // Eliminar la mesa de la tabla tbl_tables
        $sqlTables = "DELETE FROM tbl_tables WHERE table_id = :table_id";
        $stmtTables = $conexion->prepare($sqlTables);
        $stmtTables->bindParam(':table_id', $table_id, PDO::PARAM_INT);
        $stmtTables->execute();

        // Eliminar la habitación en tbl_rooms si la mesa es la única en la habitación
        $sqlCheckRoom = "SELECT room_id FROM tbl_tables WHERE table_id = :table_id";
        $stmtCheckRoom = $conexion->prepare($sqlCheckRoom);
        $stmtCheckRoom->bindParam(':table_id', $table_id, PDO::PARAM_INT);
        $stmtCheckRoom->execute();

        $room = $stmtCheckRoom->fetch(PDO::FETCH_ASSOC);

        if ($room) {
            $room_id = $room['room_id'];
            // Verificar si la habitación tiene otras mesas asignadas
            $sqlCheckOtherTables = "SELECT COUNT(*) AS count FROM tbl_tables WHERE room_id = :room_id";
            $stmtCheckOtherTables = $conexion->prepare($sqlCheckOtherTables);
            $stmtCheckOtherTables->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmtCheckOtherTables->execute();
            $result = $stmtCheckOtherTables->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] == 0) {
                // Si no hay más mesas en la habitación, eliminar la habitación
                $sqlDeleteRoom = "DELETE FROM tbl_rooms WHERE room_id = :room_id";
                $stmtDeleteRoom = $conexion->prepare($sqlDeleteRoom);
                $stmtDeleteRoom->bindParam(':room_id', $room_id, PDO::PARAM_INT);
                $stmtDeleteRoom->execute();
            }
        }

        // Confirmar la transacción
        $conexion->commit();

        // Redirigir con mensaje de éxito
        header('Location: crudSalas.php?success=1');
        exit();
    } catch (Exception $e) {
        // Si ocurre algún error, revertir la transacción
        $conexion->rollBack();
        // Redirigir con mensaje de error
        header('Location: crudSalas.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si no se ha enviado un table_id válido, redirigir con error
    header('Location: crudSalas.php?error=ID inválido');
    exit();
}
