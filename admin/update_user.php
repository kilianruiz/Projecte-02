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

if (isset($_POST['update'])) {
    $user_id = intval($_POST['user_id']);
    $username = mysqli_real_escape_string($conexion, $_POST['username']);
    $role = intval($_POST['role']);
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE tbl_users SET username = '$username', pwd = '$hashed_password', role_id = $role WHERE user_id = $user_id";
    } else {
        $sql = "UPDATE tbl_users SET username = '$username', role_id = $role WHERE user_id = $user_id";
    }

    if (mysqli_query($conexion, $sql)) {
        echo "Usuario actualizado exitosamente.";
    } else {
        echo "Error al actualizar: " . mysqli_error($conexion);
    }

    mysqli_close($conexion);
}
?>
