<?php
session_start();

if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no se estableció.");
}

if (isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($conexion, $_POST['username']);
    $password = mysqli_real_escape_string($conexion, $_POST['password']);
    $role = intval($_POST['role']); 

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO tbl_users (username, pwd, role_id) VALUES ('$username', '$hashed_password', $role)";

    if (mysqli_query($conexion, $sql)) {
        header('Location: ./usuarios.php');
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conexion);
    }

    mysqli_close($conexion);
} else {
    echo "Acceso no autorizado.";
}
?>
