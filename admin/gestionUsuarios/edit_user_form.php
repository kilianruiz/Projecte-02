<?php
session_start();

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['username']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

// Verificar que se recibió un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: usuarios.php?error=1'); // Redirigir en caso de error
    exit();
}

$user_id = intval($_GET['id']);

try {
    // Obtener datos del usuario por ID
    $sql = "SELECT username, role_id, room_id FROM tbl_users WHERE user_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: usuarios.php?error=2'); // Usuario no encontrado
        exit();
    }

    // Obtener todos los roles
    $sql_roles = "SELECT role_id, role_name FROM tbl_roles";
    $roles = $conexion->query($sql_roles)->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todas las salas
    $sql_rooms = "SELECT room_id, name_rooms FROM tbl_rooms";
    $rooms = $conexion->query($sql_rooms)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    header('Location: usuarios.php?error=3&message=' . urlencode($e->getMessage())); // Redirigir con mensaje de error
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Editar Usuario</h1>
    <form action="update_user.php" method="POST">
        <!-- ID del Usuario -->
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

        <!-- Nombre de Usuario -->
        <div class="form-group">
            <label for="username">Nombre de Usuario:</label>
            <input 
                type="text" 
                name="username" 
                id="username" 
                class="form-control" 
                value="<?php echo htmlspecialchars($user['username']); ?>" 
                required>
        </div>

        <!-- Rol del Usuario -->
        <div class="form-group">
            <label for="role_id">Rol:</label>
            <select name="role_id" id="role_id" class="form-control" required>
                <?php foreach ($roles as $role): ?>
                    <option 
                        value="<?php echo htmlspecialchars($role['role_id']); ?>" 
                        <?php echo $role['role_id'] == $user['role_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($role['role_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Botones de Acción -->
        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.10/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
