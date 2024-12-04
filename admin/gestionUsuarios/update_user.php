<?php
session_start();

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['username']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no se estableció.");
}

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
$stmt->bind_param('sii', $username, $role_id, $user_id);

if ($stmt->execute()) {
    header('Location: usuarios.php?success=1');
} else {
    header('Location: edit_user_form.php?id=' . $user_id . '&error=1');
}

$stmt->close();
$conexion->close();
?>
