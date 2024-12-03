<?php
session_start();
include_once('../conexion/conexion.php');

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no se estableció.");
}

// Recibir los datos del formulario
$numeroMesa = $_POST['numeroMesa'];
$sala = $_POST['sala'];
$estadoSala = $_POST['estadoSala'];
$usuario = $_POST['usuario'];
$fechaOcupacion = $_POST['fechaOcupacion'];
$fechaLiberacion = $_POST['fechaLiberacion'];

// Validar si la fecha de liberación fue proporcionada
if (empty($fechaLiberacion) && $estadoSala == 'Ocupado') {
    $fechaLiberacion = null;  // Estará "ocupado actualmente" sin una fecha de liberación
}

try {
    // Obtener el `user_id` del usuario seleccionado
    $usuarioQuery = $conexion->prepare("SELECT user_id FROM tbl_users WHERE username = ?");
    $usuarioQuery->bind_param('s', $usuario);
    $usuarioQuery->execute();
    $usuarioResult = $usuarioQuery->get_result();
    $user_id = $usuarioResult->fetch_assoc()['user_id'];

    // Obtener el `room_id` y `table_id` de la mesa y sala seleccionadas
    $mesaQuery = $conexion->prepare("
        SELECT tbl_tables.table_id, tbl_rooms.room_id, tbl_tables.status 
        FROM tbl_tables 
        INNER JOIN tbl_rooms ON tbl_tables.room_id = tbl_rooms.room_id 
        WHERE tbl_tables.table_number = ? AND tbl_rooms.name = ?");
    $mesaQuery->bind_param('is', $numeroMesa, $sala);
    $mesaQuery->execute();
    $mesaResult = $mesaQuery->get_result();
    $mesaData = $mesaResult->fetch_assoc();
    $table_id = $mesaData['table_id'];
    $room_id = $mesaData['room_id'];
    $mesaStatus = $mesaData['status'];

    // Verificar si la mesa ya está ocupada
    if ($mesaStatus == 'occupied') {
        header('Location: crear_ocupacion.php?error=ocupada');
        exit();
    }

    // Insertar la ocupación en `tbl_occupations` con la fecha de liberación opcional
    $insercionOcupacion = $conexion->prepare("
        INSERT INTO tbl_occupations (table_id, user_id, start_time, end_time) 
        VALUES (?, ?, ?, ?)");
    $insercionOcupacion->bind_param('iiss', $table_id, $user_id, $fechaOcupacion, $fechaLiberacion);
    $insercionOcupacion->execute();

    // Actualizar el estado de la mesa en `tbl_tables` a "occupied"
    $actualizarMesa = $conexion->prepare("
        UPDATE tbl_tables SET status = 'occupied' 
        WHERE table_id = ?");
    $actualizarMesa->bind_param('i', $table_id);
    $actualizarMesa->execute();

    // Redireccionar con mensaje de éxito
    header('Location: crear_ocupacion.php?success=1');
} catch (Exception $e) {
    echo "Error al crear la ocupación: " . $e->getMessage();
}

?>
