<?php
session_start();

if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

// Verificar que los datos estén presentes
if (!isset($_POST['table_id'], $_POST['capacity'], $_POST['room_id'], $_POST['status'])) {
    die("Error: Datos incompletos.");
}

$table_id = intval($_POST['table_id']);
$new_capacity = intval($_POST['capacity']);
$room_id = intval($_POST['room_id']);
$status = $_POST['status'];
$image_url = !empty($_POST['image_url']) ? $_POST['image_url'] : NULL;

// Obtener el stock de sillas disponible antes de la actualización
$sqlStockSillas = "SELECT chairs_in_warehouse FROM tbl_chairs_stock LIMIT 1";
$stmtStockSillas = $conexion->query($sqlStockSillas);
$stockSillas = $stmtStockSillas->fetchColumn();

// Consultar la capacidad original de la mesa antes de actualizar
$sqlMesa = "
    SELECT capacity
    FROM tbl_tables
    WHERE table_id = :table_id
";
$stmtMesa = $conexion->prepare($sqlMesa);
$stmtMesa->execute([':table_id' => $table_id]);
$mesa = $stmtMesa->fetch();

// Verificar si la nueva capacidad es válida
if ($new_capacity > $stockSillas) {
    header("Location: editarMesa.php?table_id=$table_id&error=No hay suficiente stock de sillas.");
    exit();
}

// Actualizar el stock de sillas: 
// Restar las sillas de la capacidad original y agregar las nuevas sillas
$capacity_difference = $new_capacity - $mesa['capacity'];
$new_stock = $stockSillas - $capacity_difference;

// Si el stock es suficiente, actualizar la mesa
if ($new_stock >= 0) {
    // Actualizar la mesa en la base de datos
    $sqlUpdate = "
        UPDATE tbl_tables
        SET room_id = :room_id, table_number = :table_number, capacity = :capacity, status = :status
        WHERE table_id = :table_id
    ";
    $stmtUpdate = $conexion->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':room_id' => $room_id,
        ':table_number' => $_POST['table_number'],
        ':capacity' => $new_capacity,
        ':status' => $status,
        ':table_id' => $table_id
    ]);

    // Actualizar el stock de sillas
    $sqlUpdateStock = "
        UPDATE tbl_chairs_stock
        SET chairs_in_warehouse = :new_stock
        WHERE stock_id = 1
    ";
    $stmtUpdateStock = $conexion->prepare($sqlUpdateStock);
    $stmtUpdateStock->execute([
        ':new_stock' => $new_stock
    ]);

    // Si la imagen fue modificada, actualizarla también
    if ($image_url) {
        $sqlUpdateImage = "
            UPDATE tbl_rooms
            SET image_url = :image_url
            WHERE room_id = :room_id
        ";
        $stmtUpdateImage = $conexion->prepare($sqlUpdateImage);
        $stmtUpdateImage->execute([
            ':image_url' => $image_url,
            ':room_id' => $room_id
        ]);
    }

    header("Location: crudMesas.php?success");
    exit();
} else {
    header("Location: editarMesa.php?table_id=$table_id&error=StockInsuficiente");
    exit();
}
