<?php
// Conectar a la base de datos
include_once('../conexion/conexion.php');

try {
    // Datos del usuario a insertar
    $username = 'Pol';
    $password = 'qweQWE123';
    $role_id = 2;

    // Hash de la contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Consulta para insertar el usuario
    $sql = "INSERT INTO tbl_users (username, pwd, role_id) VALUES (:username, :password, :role_id)";
    $stmt = $conexion->prepare($sql);

    // Enlazar parámetros
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        echo "Usuario insertado con éxito.";
    } else {
        echo "Error al insertar el usuario.";
    }
} catch (PDOException $e) {
    echo "Error en la base de datos: " . $e->getMessage();
}
?>
