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

// Variables para los filtros
$usernameFilter = $_GET['username'] ?? '';
$roleFilter = $_GET['role'] ?? '';

// Construir consulta con filtros
$sql = "
    SELECT u.user_id, u.username, u.lastname, r.role_name 
    FROM tbl_users u
    JOIN tbl_roles r ON u.role_id = r.role_id
    WHERE (u.username LIKE ? OR ? = '')
    AND (r.role_name LIKE ? OR ? = '')
";
$stmt = $conexion->prepare($sql);
$likeUsername = "%$usernameFilter%";
$likeRole = "%$roleFilter%";
$stmt->bind_param('ssss', $likeUsername, $usernameFilter, $likeRole, $roleFilter);
$stmt->execute();
$result = $stmt->get_result();

// Cerrar la conexión a la base de datos
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Usuarios</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet"> <!-- Font Awesome -->
    <style>
        body {
            background-color: #d9a875;
        }
        .container {
            background-color: #8A5021;
            padding: 30px;
            border-radius: 8px;
            margin-top: 50px;
        }
        table {
            background-color: #8A5021;
            color: white;
        }
        thead {
            background-color: #6c3e18;
            color: white;
        }
        th, td {
            text-align: center;
            vertical-align: middle;
            color: white;
        }
        .btn-primary {
            background-color: #8A5021;
            border-color: #8A5021;
            border-color: white;
        }
        .btn-primary:hover {
            background-color: #6c3e18;
            border-color: #6c3e18;
        }
        .btn-warning {
            background-color: #d9a875;
            border-color: #d9a875;
        }
        .btn-warning:hover {
            background-color: #c4873e;
            border-color: #c4873e;
        }
        .btn-danger {
            background-color: #c13d32;
            border-color: #c13d32;
            border-color: red;
        }
        .btn-danger:hover {
            background-color: #9e3026;
            border-color: #9e3026;
        }
        h1 {
            font-size: 30px;
            font-weight: bold;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.4);
            font-family: 'Arial Black', sans-serif;
            margin-bottom: 30px;
        }
        .fa-trash {
            color: red;
        }
        .fa-search{
            color: green;
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="text-center">Panel de Administración - Personalización de Usuarios</h1>

    <!-- Botón para Crear Usuario -->
    <div class="text-right mb-3">
        <a href="crear_usuario.php" class="btn btn-primary">Crear Usuario</a>
        <a href="../historial.php" class="btn btn-primary">Volver</a>
    </div>

    <!-- Formulario de Filtros -->
    <form method="GET" action="" class="mb-3">
        <div class="form-row">
            <div class="col-md-5">
                <input type="text" name="username" class="form-control" placeholder="Filtrar por Username"
                       value="<?php echo htmlspecialchars($usernameFilter); ?>">
            </div>
            <div class="col-md-5">
                <input type="text" name="role" class="form-control" placeholder="Filtrar por Rol"
                       value="<?php echo htmlspecialchars($roleFilter); ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-danger" style="background-color: #90EE90; border-color: green;">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <div class="col-md-1">
                <a href="?" class="btn btn-danger">
                    <i class="fas fa-trash"></i>
                </a>
            </div>
        </div>
    </form>

    <!-- Tabla de Usuarios -->
    <?php
    if ($result->num_rows > 0) {
        echo "<table class='table table-striped table-bordered'>";
        echo "<thead>
                <tr>
                    <th>Username</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
              </thead>";
        echo "<tbody>";

        while ($row = $result->fetch_assoc()) {
            $username = $row['username'] ?? '';
            $role_name = $row['role_name'] ?? '';

            echo "<tr>
                    <td>" . htmlspecialchars($username) . "</td>
                    <td>" . htmlspecialchars($role_name) . "</td>
                    <td>
                        <a href='edit_user_form.php?id=" . $row['user_id'] . "' class='btn btn-warning btn-sm'>Editar</a>
                        <a href='eliminar_usuario.php?id=" . $row['user_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Estás seguro de eliminar este usuario?\")'>Eliminar</a>
                    </td>
                  </tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<p class='text-center text-warning'>No hay usuarios registrados.</p>";
    }
    ?>
</div>

<script src="../validaciones/funciones.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.10/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
