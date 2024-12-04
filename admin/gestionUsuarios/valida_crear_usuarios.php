<?php
session_start();

if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

try {
    if (isset($_POST['submit'])) {
        // Sanitizar y obtener datos
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = intval($_POST['role']);

        // Encriptar la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Preparar la consulta SQL
        $sql = "INSERT INTO tbl_users (username, pwd, role_id) VALUES (:username, :pwd, :role_id)";
        $stmt = $conexion->prepare($sql);

        // Vincular los parámetros
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':pwd', $hashed_password, PDO::PARAM_STR);
        $stmt->bindParam(':role_id', $role, PDO::PARAM_INT);

        // Ejecutar la consulta
        if ($stmt->execute()) {
            header('Location: ./usuarios.php');
        } else {
            echo "Error: No se pudo ejecutar la consulta.";
        }
    } else {
        echo "Acceso no autorizado.";
    }
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
