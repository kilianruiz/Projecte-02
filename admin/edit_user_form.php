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

$sql = "SELECT user_id, username FROM tbl_users";
$result = mysqli_query($conexion, $sql);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
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
                        <h3>Editar Usuario</h3>
                    </div>
                    <div class="card-body">
                        <form action="edit_user.php" method="POST">
                            <div class="mb-3">
                                <label for="user" class="form-label">Selecciona un usuario:</label>
                                <select name="user_id" id="user" class="form-select" required>
                                    <option value="">--Selecciona un usuario--</option>
                                    <?php
                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<option value='" . $row['user_id'] . "'>" . htmlspecialchars($row['username']) . "</option>";
                                        }
                                    } else {
                                        echo "<option value=''>No hay usuarios</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="edit" class="btn btn-primary">Editar</button>
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
mysqli_close($conexion);
?>
