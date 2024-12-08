<?php
session_start();

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['username']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

try {
    // Verificar si se recibió el ID del usuario
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header('Location: usuarios.php?error=1'); // Redirección en caso de error
        exit();
    }

    $user_id = intval($_GET['id']);

    if ($user_id <= 0) {
        header('Location: usuarios.php?error=1'); // ID inválido
        exit();
    }

    // Eliminar el usuario de la base de datos
    $sql = "DELETE FROM tbl_users WHERE user_id = ?";
    $stmt = $conexion->prepare($sql);

    if ($stmt->execute([$user_id])) {
        header('Location: usuarios.php?success=1'); // Redirección en caso de éxito
        exit();
    } else {
        header('Location: usuarios.php?error=2'); // Redirección en caso de fallo
        exit();
    }
} catch (PDOException $e) {
    // Manejo de errores en caso de excepciones
    header('Location: usuarios.php?error=3&message=' . urlencode($e->getMessage()));
    exit();
}
?>
