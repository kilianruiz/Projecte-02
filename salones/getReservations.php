<?php
include('../conexion/conexion.php');

if (!isset($_GET['table_id']) || !is_numeric($_GET['table_id'])) {
    echo json_encode(['error' => 'ID de mesa inválido.']);
    exit();
}

$tableId = $_GET['table_id'];
try {
    $stmt = $conexion->prepare("SELECT customer_name, reservation_date, reservation_time 
                                FROM tbl_reservations 
                                WHERE table_id = :table_id");
    $stmt->bindParam(':table_id', $tableId, PDO::PARAM_INT);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($reservations);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
