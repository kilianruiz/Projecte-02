<?php
if (isset($_POST['edit'])) {
    $user_id = intval($_POST['user_id']);

    include_once('../conexion/conexion.php');

    if (!isset($conexion)) {
        die("Error: La conexión a la base de datos no se estableció.");
    }

    $sql = "SELECT * FROM tbl_users WHERE user_id = $user_id";
    $result = mysqli_query($conexion, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Actualizar Usuario</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body {
                    background-color: #d9a875;
                }
                .card-header {
                    background-color: #8A5021;
                    color: white;
                }
                .btn-primary {
                    background-color: #8A5021;
                    border-color: #8A5021;
                }
                .btn-primary:hover {
                    background-color: #6e3e19;
                    border-color: #6e3e19;
                }
            </style>
        </head>
        <body>
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-header text-center">
                                <h3>Actualizar Usuario</h3>
                            </div>
                            <div class="card-body">
                                <form action="update_user.php" method="POST">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($row['user_id']); ?>">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Nombre de Usuario:</label>
                                        <input 
                                            type="text" 
                                            name="username" 
                                            id="username" 
                                            class="form-control" 
                                            value="<?php echo htmlspecialchars($row['username']); ?>" 
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Nueva Contraseña (dejar en blanco para no cambiar):</label>
                                        <input 
                                            type="password" 
                                            name="password" 
                                            id="password" 
                                            class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Rol:</label>
                                        <select name="role" id="role" class="form-select" required>
                                            <option value="1" <?php echo $row['role_id'] == 1 ? 'selected' : ''; ?>>Camarero</option>
                                            <option value="2" <?php echo $row['role_id'] == 2 ? 'selected' : ''; ?>>Administrador</option>
                                        </select>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="update" class="btn btn-primary">Actualizar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
        <?php
    } else {
        echo "<div class='text-center mt-5'><h4 class='text-danger'>Usuario no encontrado.</h4></div>";
    }

    mysqli_close($conexion);
}
?>
