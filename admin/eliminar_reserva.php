<?php
session_start();

// Verificar si el usuario está autenticado y es administrador
if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

include_once('../conexion/conexion.php');

// Verificar si la conexión a la base de datos está establecida
if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no se estableció.");
}

// Verificar si se ha enviado un ID de reserva y si es un valor válido
if (isset($_GET['reservation_id']) && is_numeric($_GET['reservation_id']) && $_GET['reservation_id'] > 0) {
    $reservationId = $_GET['reservation_id'];

    // Iniciar una transacción para asegurar que todas las eliminaciones sean consistentes
    $conexion->beginTransaction();

    try {
        // Eliminar las relaciones en las tablas relacionadas (como tbl_occupations)
        $sql = "DELETE FROM tbl_occupations WHERE table_id IN (SELECT table_id FROM tbl_reservations WHERE reservation_id = :reservation_id)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':reservation_id', $reservationId, PDO::PARAM_INT);
        $stmt->execute();

        // Eliminar la reserva en la tabla tbl_reservations
        $sql = "DELETE FROM tbl_reservations WHERE reservation_id = :reservation_id";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':reservation_id', $reservationId, PDO::PARAM_INT);
        $stmt->execute();

        // Confirmar la transacción
        $conexion->commit();

        // Redirigir con mensaje de éxito
        header("Location: historial.php?success=1");
        exit();
    } catch (Exception $e) {
        // Si ocurre un error, hacer rollback de la transacción
        $conexion->rollBack();
        echo "Error: " . $e->getMessage();
    }
} else {
    // Si no se ha enviado un ID válido de reserva, mostrar un mensaje adecuado
    echo "Error: No se ha enviado un ID de reserva válido.";

}
?>
