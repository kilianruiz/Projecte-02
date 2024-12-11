<?php
session_start();

// Verificar si el usuario está autenticado y es administrador
if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

include_once('../conexion/conexion.php');

// Verificar si la conexión a la base de datos está establecida
if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no se estableció.");
}

// Filtros de búsqueda
$salaFiltro = isset($_POST['sala']) ? $_POST['sala'] : '';
$estadoFiltro = isset($_POST['estado']) ? $_POST['estado'] : '';
$usuarioFiltro = isset($_POST['usuario']) ? $_POST['usuario'] : '';
$fechaFiltro = isset($_POST['fecha_ocupacion']) ? $_POST['fecha_ocupacion'] : '';

// Consulta dinámica con filtros
$sql = "
    SELECT 
        tbl_tables.table_id,
        tbl_tables.table_number,
        tbl_rooms.name_rooms AS room_name,  
        tbl_tables.status,
        tbl_occupations.start_time,
        tbl_occupations.end_time,
        tbl_users.username
    FROM 
        tbl_tables
    INNER JOIN 
        tbl_rooms ON tbl_tables.room_id = tbl_rooms.room_id
    INNER JOIN 
        tbl_occupations ON tbl_tables.table_id = tbl_occupations.table_id
    INNER JOIN 
        tbl_users ON tbl_occupations.user_id = tbl_users.user_id
    WHERE 
        1=1";

// Construcción de los filtros con PDO
$params = [];
if ($salaFiltro != '') {
    $sql .= " AND tbl_rooms.name_rooms LIKE :sala"; 
    $params[':sala'] = "%$salaFiltro%";
}
if ($estadoFiltro != '') {
    $sql .= " AND tbl_tables.status = :estado";
    $params[':estado'] = $estadoFiltro;
}
if ($usuarioFiltro != '') {
    $sql .= " AND tbl_users.username LIKE :usuario";
    $params[':usuario'] = "%$usuarioFiltro%";
}
if ($fechaFiltro != '') {
    $sql .= " AND DATE(tbl_occupations.start_time) = :fecha";
    $params[':fecha'] = $fechaFiltro;
}

$sql .= " ORDER BY tbl_occupations.start_time DESC";

// Ejecución de la consulta
$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener los usuarios de la base de datos
$usuariosSql = "SELECT username FROM tbl_users";
$usuariosStmt = $conexion->query($usuariosSql);
$usuarios = $usuariosStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <div class="containerHistorial">
    <h1 class="text-center">Panel de Administración - Historial y Estado de Salas</h1>
        <div class="text-right mb-3">
            <a href="./gestionUsuarios/usuarios.php" class="btn btn-info">Gestionar Usuarios</a>
        </div>
        <!-- Filtros de Búsqueda -->
        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <label for="sala" class="texto-historial">Sala</label>
                    <select name="sala" id="sala" class="form-control">
                        <option value="">Filtrar por sala</option>
                        <option value="Terraza" <?= $salaFiltro == 'Terraza' ? 'selected' : '' ?>>Terraza</option>
                        <option value="Salón" <?= $salaFiltro == 'Salón' ? 'selected' : '' ?>>Salón</option>
                        <option value="VIP" <?= $salaFiltro == 'VIP' ? 'selected' : '' ?>>VIP</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="estado" class="texto-historial">Estado</label>
                    <select name="estado" id="estado" class="form-control">
                        <option value="">Filtrar por estado</option>
                        <option value="occupied" <?= $estadoFiltro == 'occupied' ? 'selected' : '' ?>>Ocupada</option>
                        <option value="free" <?= $estadoFiltro == 'free' ? 'selected' : '' ?>>Libre</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="usuario" class="texto-historial">Usuario</label>
                    <select name="usuario" id="usuario" class="form-control">
                        <option value="">Filtrar por Usuario</option>
                        <?php
                        foreach ($usuarios as $usuario) {
                            echo "<option value='" . htmlspecialchars($usuario) . "' " . ($usuarioFiltro == $usuario ? 'selected' : '') . ">" . htmlspecialchars($usuario) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fecha_ocupacion" class="texto-historial">Fecha de Ocupación</label>
                    <input type="date" class="form-control" name="fecha_ocupacion" id="fecha_ocupacion" value="<?= htmlspecialchars($fechaFiltro) ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Filtrar</button>
        </form>

        <!-- Tabla de Resultados -->
        <?php
        if (count($result) > 0) {
            echo "<table class='tabla tabla-bordered tabla-striped'>";
            echo "<thead class='thead-dark'>
                    <tr>
                        <th>Número de Mesa</th>
                        <th>Sala</th>
                        <th>Estado</th>
                        <th>Fecha Ocupación</th>
                        <th>Fecha Liberación</th>
                        <th>Usuario</th>
                        <th>Acciones</th>
                    </tr>
                  </thead>";
            echo "<tbody>";
            
            foreach ($result as $row) {
                $estadoClase = $row["status"] == "occupied" ? "table-danger" : "table-success";
                echo "<tr class='{$estadoClase}'>
                        <td>" . htmlspecialchars($row["table_number"]) . "</td>
                        <td>" . htmlspecialchars($row["room_name"]) . "</td>
                        <td>" . ucfirst(htmlspecialchars($row["status"])) . "</td>
                        <td>" . ($row["start_time"] ?: "N/A") . "</td>
                        <td>" . ($row["end_time"] ? $row["end_time"] : "Ocupada actualmente") . "</td>
                        <td>" . htmlspecialchars($row["username"]) . "</td>
                        <td>
                            <a href='./gestionMesas/editar_ocupacion.php?id=" . htmlspecialchars($row["table_id"]) . "' class='btn btn-warning btn-sm'>Editar</a>
                            <button class='btn btn-danger btn-sm' onclick='confirmarEliminacion(" . htmlspecialchars($row["table_id"]) . ")'>Eliminar</button>
                        </td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p class='text-center text-warning'>No hay resultados que coincidan con los filtros aplicados.</p>";
        }
        ?>
        <br>
        <div class="text-right mb-3">
            <form id="eliminarHistorialForm" action="eliminar_historial.php" method="post">
                <button type="button" class="btn btn-danger" onclick="confirmarEliminacion()">Eliminar Historial de Ocupaciones</button>
            </form>
        </div>
        <button class="logout-button" onclick="logout()">Cerrar Sesión</button>
        <form action="./principalAdmin.php">
            <button class="logout-button">Volver</button>
        </form>

        <!-- Script para la confirmación con SweetAlert -->
        <script>
        function confirmarEliminacion(tableId) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción eliminará la ocupación de la mesa y no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirigir a eliminar_ocupacion.php con el ID de la mesa
                    window.location.href = './gestionMesas/eliminar_ocupacion.php?id=' + tableId;
                }
            });
        }

        function logout() {
            window.location.href = "../cerrarSesion/logout.php";
        }
        </script>

    </div>
</body>
</html>
