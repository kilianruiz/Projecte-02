<?php
include('../conexion/conexion.php');

// Validar datos de entrada
if (
    empty($_POST['table_id']) || !is_numeric($_POST['table_id']) ||
    empty($_POST['customer_name']) || !is_string($_POST['customer_name']) ||
    empty($_POST['reservation_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['reservation_date']) ||
    empty($_POST['reservation_time']) || !preg_match('/^\d{2}:\d{2}$/', $_POST['reservation_time'])
) {
    echo json_encode([
        "status" => "error",
        "message" => "Datos de reserva inválidos."
    ]);
    exit();
}

$tableId = $_POST['table_id'];
$customerName = $_POST['customer_name'];
$reservationDate = $_POST['reservation_date'];
$reservationTime = $_POST['reservation_time'];

try {
    // Comprobar si la reserva es para hoy y la hora ya ha pasado
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i');
    
    if ($reservationDate === $currentDate && $reservationTime <= $currentTime) {
        echo json_encode([
            "status" => "error",
            "message" => "No puedes reservar en una hora que ya ha pasado."
        ]);
        exit();
    }

    // Verificar si ya existe una reserva en esa fecha y hora para la misma mesa
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM tbl_reservations 
                                WHERE table_id = :table_id 
                                AND reservation_date = :reservation_date 
                                AND reservation_time = :reservation_time");
    $stmt->bindParam(':table_id', $tableId, PDO::PARAM_INT);
    $stmt->bindParam(':reservation_date', $reservationDate, PDO::PARAM_STR);
    $stmt->bindParam(':reservation_time', $reservationTime, PDO::PARAM_STR);
    $stmt->execute();

    $existingReservations = $stmt->fetchColumn();

    if ($existingReservations > 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Ya existe una reserva para esa mesa en esa fecha y hora."
        ]);
        exit();
    }

    // Insertar la nueva reserva
    $stmt = $conexion->prepare("INSERT INTO tbl_reservations (table_id, customer_name, reservation_date, reservation_time) 
                                VALUES (:table_id, :customer_name, :reservation_date, :reservation_time)");
    $stmt->bindParam(':table_id', $tableId, PDO::PARAM_INT);
    $stmt->bindParam(':customer_name', $customerName, PDO::PARAM_STR);
    $stmt->bindParam(':reservation_date', $reservationDate, PDO::PARAM_STR);
    $stmt->bindParam(':reservation_time', $reservationTime, PDO::PARAM_STR);
    $stmt->execute();

    echo json_encode([
        "status" => "success",
        "message" => "Reserva guardada correctamente."
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error al guardar la reserva. " . $e->getMessage()
    ]);
}
?>
