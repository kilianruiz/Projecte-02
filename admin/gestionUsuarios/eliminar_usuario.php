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

// Verificar si se recibió el ID del usuario
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: usuarios.php?error=1');
    exit();
}

$user_id = intval($_GET['id']);

// Eliminar usuario de la base de datos
$sql = "DELETE FROM tbl_users WHERE user_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $user_id);

if ($stmt->execute()) {
    header('Location: usuarios.php?success=1');
} else {
    header('Location: usuarios.php?error=2');
}

$stmt->close();
$conexion->close();
?>
