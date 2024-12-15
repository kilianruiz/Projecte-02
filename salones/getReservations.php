<?php
include('../conexion/conexion.php');

if (!isset($_GET['table_id']) || !is_numeric($_GET['table_id'])) {
    echo json_encode(['error' => 'ID de mesa inválido.']);
    exit();
}

$tableId = $_GET['table_id'];

try {
    // Obtener fecha y hora actual
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i');

    // Consultar solo reservas desde hoy en adelante
    $stmt = $conexion->prepare("
        SELECT customer_name, reservation_date, reservation_time 
        FROM tbl_reservations 
        WHERE table_id = :table_id
          AND (
              reservation_date > :current_date OR 
              (reservation_date = :current_date AND reservation_time >= :current_time)
          )
        ORDER BY reservation_date, reservation_time
    ");
    $stmt->bindParam(':table_id', $tableId, PDO::PARAM_INT);
    $stmt->bindParam(':current_date', $currentDate, PDO::PARAM_STR);
    $stmt->bindParam(':current_time', $currentTime, PDO::PARAM_STR);
    $stmt->execute();

    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($reservations);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
