<?php
session_start();

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['username']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

try {
    // Verificar que se recibieron todos los datos
    if (!isset($_POST['user_id'], $_POST['username'], $_POST['role_id'])) {
        die("Error: Datos incompletos.");
    }

    $user_id = intval($_POST['user_id']);
    $username = trim($_POST['username']);
    $role_id = intval($_POST['role_id']);

    // Validar y actualizar datos del usuario
    $sql = "UPDATE tbl_users SET username = ?, role_id = ? WHERE user_id = ?";
    $stmt = $conexion->prepare($sql);

    // Ejecutar la consulta
    if ($stmt->execute([$username, $role_id, $user_id])) {
        header('Location: usuarios.php?success=1');
    } else {
        header('Location: edit_user_form.php?id=' . $user_id . '&error=1');
    }

} catch (PDOException $e) {
    // Manejo de errores en caso de excepciones
    header('Location: edit_user_form.php?id=' . $user_id . '&error=2&message=' . urlencode($e->getMessage()));
    exit();
}
?>
