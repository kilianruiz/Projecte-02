<?php
// Conexión a la base de datos
include('../conexion/conexion.php');  // Ajusta la ruta si es necesario

// Obtener los datos enviados por POST
$tableId = $_POST['tableId'];

// Verificar el estado actual de la mesa
$sql = "SELECT * FROM tbl_tables WHERE table_id = :table_id";
$stmt = $conexion->prepare($sql);
$stmt->bindParam(':table_id', $tableId, PDO::PARAM_INT);
$stmt->execute();

$mesa = $stmt->fetch(PDO::FETCH_ASSOC);

if ($mesa) {
    if ($mesa['status'] == 'free') {
        // Actualizar el estado de la mesa a "ocupada"
        $updateTableSql = "UPDATE tbl_tables SET status = 'occupied', occupied_since = NOW() WHERE table_id = :table_id";
        $stmtUpdate = $conexion->prepare($updateTableSql);
        $stmtUpdate->bindParam(':table_id', $tableId, PDO::PARAM_INT);
        $stmtUpdate->execute();

        // Insertar en la tabla de ocupaciones
        $userId = 2; // Ajusta esto según cómo determines el ID del usuario que ocupa la mesa
        $insertOccupationSql = "INSERT INTO tbl_occupations (table_id, user_id, start_time) VALUES (:table_id, :user_id, NOW())";
        $stmtInsert = $conexion->prepare($insertOccupationSql);
        $stmtInsert->bindParam(':table_id', $tableId, PDO::PARAM_INT);
        $stmtInsert->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtInsert->execute();

        // Respuesta de éxito
        echo "success|occupy";
    } elseif ($mesa['status'] == 'occupied') {
        // Actualizar el estado de la mesa a "libre"
        $updateTableSql = "UPDATE tbl_tables SET status = 'free', occupied_since = NULL WHERE table_id = :table_id";
        $stmtUpdate = $conexion->prepare($updateTableSql);
        $stmtUpdate->bindParam(':table_id', $tableId, PDO::PARAM_INT);
        $stmtUpdate->execute();

        // Actualizar el historial de ocupaciones (registrar la liberación)
        $updateOccupationSql = "UPDATE tbl_occupations SET end_time = NOW() WHERE table_id = :table_id AND end_time IS NULL";
        $stmtUpdateOccupation = $conexion->prepare($updateOccupationSql);
        $stmtUpdateOccupation->bindParam(':table_id', $tableId, PDO::PARAM_INT);
        $stmtUpdateOccupation->execute();

        // Respuesta de éxito
        echo "success|vacate";
    } else {
        echo "error|Estado de mesa no reconocido";
    }
} else {
    echo "error|Mesa no encontrada";
}
?>
