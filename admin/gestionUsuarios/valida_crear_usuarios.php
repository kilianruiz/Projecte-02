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
        $room = intval($_POST['room']);  // Obtención del ID de la sala

        // Encriptar la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Preparar la consulta SQL para insertar el nuevo usuario con sala asignada
        $sql = "INSERT INTO tbl_users (username, pwd, role_id, room_id) VALUES (:username, :pwd, :role_id, :room_id)";
        $stmt = $conexion->prepare($sql);

        // Vincular los parámetros
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':pwd', $hashed_password, PDO::PARAM_STR);
        $stmt->bindParam(':role_id', $role, PDO::PARAM_INT);
        $stmt->bindParam(':room_id', $room, PDO::PARAM_INT);  // Vincular el ID de la sala

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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Crear Nuevo Usuario</h1>

    <form action="crear_usuario.php" method="POST">
        <div class="form-group">
            <label for="username">Nombre de Usuario:</label>
            <input type="text" name="username" id="username" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="role">Rol:</label>
            <select name="role" id="role" class="form-control" required>
                <?php
                try {
                    // Obtener todos los roles disponibles
                    $sql_roles = "SELECT role_id, role_name FROM tbl_roles";
                    $stmt_roles = $conexion->query($sql_roles);
                    $roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($roles as $role) {
                        echo "<option value='" . $role['role_id'] . "'>" . $role['role_name'] . "</option>";
                    }
                } catch (PDOException $e) {
                    echo "<option disabled>Error al cargar los roles</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="room">Sala:</label>
            <select name="room" id="room" class="form-control" required>
                <?php
                try {
                    // Obtener todas las salas disponibles
                    $sql_rooms = "SELECT room_id, name_rooms FROM tbl_rooms"; 
                    $stmt_rooms = $conexion->query($sql_rooms);
                    $rooms = $stmt_rooms->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($rooms as $room) {
                        echo "<option value='" . $room['room_id'] . "'>" . $room['name_rooms'] . "</option>"; 
                    }
                } catch (PDOException $e) {
                    echo "<option disabled>Error al cargar las salas</option>";
                }
                ?>
            </select>
        </div>

        <button type="submit" name="submit" class="btn btn-primary">Crear Usuario</button>
        <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.10/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
