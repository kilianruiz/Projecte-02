<?php
session_start();

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuarios</title>
    <!-- Incluir Bootstrap CSS -->
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
                        <h3>Registro de Usuarios</h3>
                    </div>
                    <div class="card-body">
                        <form action="valida_crear_usuarios.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nombre de Usuario:</label>
                                <input type="text" name="username" id="username" class="form-control" placeholder="Nombre de usuario" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña:</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Contraseña" required>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Rol:</label>
                                <select name="role" id="role" class="form-select" required>
                                    <option value="1">Camarero</option>
                                    <option value="2">Administrador</option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="submit" class="btn btn-primary">Registrar Usuario</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
