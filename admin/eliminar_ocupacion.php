<?php
session_start();

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

include_once('../conexion/conexion.php');

if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no se estableció.");
}

// Verificar si se pasó un ID de mesa en la URL
if (isset($_GET['id'])) {
    $tableId = intval($_GET['id']); // Sanitización básica

    // Iniciar una transacción para garantizar la consistencia
    $conexion->begin_transaction();
    try {   
        // Eliminar las ocupaciones relacionadas con la mesa
        $sqlOcupaciones = "DELETE FROM tbl_occupations WHERE table_id = ?";
        $stmtOcupaciones = $conexion->prepare($sqlOcupaciones);
        $stmtOcupaciones->bind_param("i", $tableId);
        $stmtOcupaciones->execute();
        $stmtOcupaciones->close();

        // Actualizar el estado de la mesa a 'free'
        $sqlActualizarMesa = "UPDATE tbl_tables SET status = 'free' WHERE table_id = ?";
        $stmtActualizarMesa = $conexion->prepare($sqlActualizarMesa);
        $stmtActualizarMesa->bind_param("i", $tableId);
        $stmtActualizarMesa->execute();
        $stmtActualizarMesa->close();

        // Confirmar transacción
        $conexion->commit();

        // Redirigir con mensaje de éxito
        header("Location: historial.php?msg=mesa_liberada");
    } catch (Exception $e) {
        // Revertir cambios en caso de error
        $conexion->rollback();
        header("Location: historial.php?error=operacion_fallida");
    }
} else {
    // Redirigir con mensaje de error si no se pasa un ID
    header("Location: historial.php?error=id_no_proporcionado");
}

// Cerrar la conexión
$conexion->close();
?>
