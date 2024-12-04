<?php
session_start();

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['username']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

// Verificar que se recibió el ID del usuario
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: ID de usuario no proporcionado.");
}

$user_id = intval($_GET['id']);

try {
    // Obtener datos del usuario por ID
    $sql = "SELECT username, role_id FROM tbl_users WHERE user_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Error: Usuario no encontrado.");
    }

    // Obtener todos los roles
    $sql_roles = "SELECT role_id, role_name FROM tbl_roles";
    $stmt_roles = $conexion->query($sql_roles);
    $roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
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
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

        <div class="form-group">
            <label for="username">Nombre de Usuario:</label>
            <input type="text" name="username" id="username" class="form-control" 
                   value="<?php echo htmlspecialchars($user['username']); ?>">
        </div>

        <div class="form-group">
            <label for="role_id">Rol:</label>
            <select name="role_id" id="role_id" class="form-control">
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo htmlspecialchars($role['role_id']); ?>" 
                            <?php echo $role['role_id'] == $user['role_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($role['role_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
</body>
</html>
