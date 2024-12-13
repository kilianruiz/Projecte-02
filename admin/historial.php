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
$numeroMesaFiltro = isset($_POST['numero_mesa']) ? $_POST['numero_mesa'] : '';
$horaReservaFiltro = isset($_POST['hora_reserva']) ? $_POST['hora_reserva'] : '';

// Consulta dinámica con filtros
$sql = "
    SELECT 
        tbl_reservations.reservation_id,
        tbl_reservations.table_id,
        tbl_tables.table_number,
        tbl_rooms.name_rooms AS room_name,
        tbl_reservations.customer_name,
        tbl_reservations.reservation_date,
        tbl_reservations.reservation_time,
        tbl_reservations.people_count,
        tbl_tables.status
    FROM 
        tbl_reservations
    INNER JOIN 
        tbl_tables ON tbl_reservations.table_id = tbl_tables.table_id
    INNER JOIN 
        tbl_rooms ON tbl_tables.room_id = tbl_rooms.room_id
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
    $sql .= " AND tbl_reservations.customer_name LIKE :usuario";
    $params[':usuario'] = "%$usuarioFiltro%";
}
if ($fechaFiltro != '') {
    $sql .= " AND tbl_reservations.reservation_date = :fecha";
    $params[':fecha'] = $fechaFiltro;
}
if ($numeroMesaFiltro != '') {
    $sql .= " AND tbl_tables.table_number LIKE :numero_mesa";
    $params[':numero_mesa'] = "%$numeroMesaFiltro%";
}
if ($horaReservaFiltro != '') {
    $sql .= " AND tbl_reservations.reservation_time LIKE :hora_reserva";
    $params[':hora_reserva'] = "%$horaReservaFiltro%";
}

$sql .= " ORDER BY tbl_reservations.reservation_date DESC, tbl_reservations.reservation_time";

// Ejecución de la consulta
$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener los usuarios de la base de datos (clientes)
$usuariosSql = "SELECT DISTINCT customer_name FROM tbl_reservations";
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
                body { background-color: #a67c52; }
        .top-bar { background-color: #8A5021; padding: 20px; margin-bottom: 20px; text-align: center; color: white; font-size: 1.5rem; font-weight: bold; }
        .container { padding: 30px; margin-top: 20px; background-color: #8A5021; border-radius: 10px; color: white; }
        h1, h3 { text-align: center; }
        .form-inline { display: flex; align-items: center; justify-content: flex-start; flex-wrap: nowrap; gap: 10px; }
        .btn-primary, .btn-warning, .btn-danger { background-color: #6c3e18; color: white; border: 2px solid white; }
        .btn-primary:hover, .btn-warning:hover, .btn-danger:hover { background-color: #8A5021; border-color: white; }
        .table { margin-top: 30px; border-radius: 8px; border: 3px solid #8A5021; overflow: hidden; }
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
        .table img { width: 100px; height: 70px; }

        /* Estilos para la paginación */
        .pagination {
            margin-top: 20px;
        }
        .pagination .page-item.active .page-link {
            background-color: #6c3e18;
            border-color: #6c3e18;
        }
        .pagination .page-item .page-link {
            background-color: #a67c52;
            border-color: #8A5021;
            color: white;
        }
        .pagination .page-item .page-link:hover {
            background-color: #8A5021;
            border-color: #6c3e18;
            color: white;
        }
        .form-select{
            background-color: #a67c52;
            color: white;
            border: 2px solid #8A5021;
        }
        /* Estilo para el selector de registros por página */
        .form-inline select, .form-inline input[type="number"] {
            background-color: #a67c52;
            color: white;
            border: 2px solid #8A5021;
        }
        .form-inline select:hover, .form-inline input[type="number"]:hover {
            background-color: #8A5021;
            border-color: #6c3e18;
        }

        /* Cambiar color del texto del placeholder a blanco */
        input::placeholder, select::placeholder {
            color: white;
        }
    </style>
</head>
<body>

    <div class="containerHistorial">
    <h1 class="text-center">Panel de Administración - Historial y Estado de Reservas</h1>
        <div class="text-right mb-3">
            <a href="./gestionUsuarios/usuarios.php" class="btn btn-primary mt-3">Gestionar Usuarios</a>
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
                    <label for="fecha_ocupacion" class="texto-historial">Fecha de Reserva</label>
                    <input type="date" class="form-control" name="fecha_ocupacion" id="fecha_ocupacion" value="<?= htmlspecialchars($fechaFiltro) ?>">
                </div>
                <div class="col-md-3">
                    <label for="numero_mesa" class="texto-historial">Número de Mesa</label>
                    <input type="number" class="form-control" name="numero_mesa" id="numero_mesa" placeholder="Número de mesa" value="<?= htmlspecialchars($numeroMesaFiltro) ?>">
                </div>
                <div class="col-md-3">
                    <label for="hora_reserva" class="texto-historial">Hora de Reserva</label>
                    <input type="time" class="form-control" name="hora_reserva" id="hora_reserva" value="<?= htmlspecialchars($horaReservaFiltro) ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Filtrar</button>
            <!-- Botón para Limpiar Filtros -->
            <button type="button" class="btn btn-primary mt-3" onclick="limpiarFiltros()">Limpiar Filtros</button>
        </form>

        <!-- Tabla de Resultados -->
        <?php
        if (count($result) > 0) {
            echo "<table class='tabla tabla-bordered tabla-striped'>";
            echo "<thead class='thead-dark'>
                    <tr>
                        <th>Número de Mesa</th>
                        <th>Sala</th>
                        <th>Nombre del Cliente</th>
                        <th>Fecha de Reserva</th>
                        <th>Hora de Reserva</th>
                        <th>Acciones</th>
                    </tr>
                  </thead>";
            echo "<tbody>";
            
            foreach ($result as $row) {
                // Determinamos la clase para el estado de la mesa
                echo "<tr>
                        <td>" . htmlspecialchars($row["table_number"] ?? '') . "</td>
                        <td>" . htmlspecialchars($row["room_name"] ?? '') . "</td>
                        <td>" . htmlspecialchars($row["customer_name"] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row["reservation_date"] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($row["reservation_time"] ?? 'N/A') . "</td>
                        <td>
                            <form id='eliminarReservaForm-". htmlspecialchars($row['reservation_id']) . "' action='eliminar_reserva.php' method='GET' style='display:inline;'>
                                <input type='hidden' name='reservation_id' value='" . htmlspecialchars($row['reservation_id']) . "'>
                                <button type='button' class='btn btn-danger' onclick='confirmarEliminacion(" . htmlspecialchars($row['reservation_id']) . ")'>
                                        <i class='fas fa-trash-alt'></i>
                                </button>
                            </form>
                        </td>
                      </tr>";   
            }
            echo "</tbody></table>";
        } else {
            echo "<p class='text-center text-warning'>No hay resultados que coincidan con los filtros aplicados.</p>";
        }
        ?>

        <br>

        <button class="btn btn-primary mt-3" onclick="logout()">Cerrar Sesión</button>
        <form action="./principalAdmin.php">
            <button class="btn btn-primary mt-3">Volver</button>
        </form>

        <!-- Script para la confirmación con SweetAlert -->
        <script>
        function confirmarEliminacion(reservationId) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción eliminará la reserva y todas sus relaciones.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Aquí enviamos el formulario de eliminación
                    document.getElementById('eliminarReservaForm-' + reservationId).submit();
                }
            });
        }

        function logout() {
            window.location.href = "../cerrarSesion/logout.php";
        }

        function limpiarFiltros() {
            // Recargar la página sin los filtros aplicados
            window.location.href = window.location.pathname;
        }
        </script>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../validaciones/funciones.js"></script>

</body>
</html>
