<?php
session_start();

if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no se estableció correctamente.");
}
// Procesar formulario para añadir una mesa
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "add") {
    // Recoger los datos del formulario
    $room_id = $_POST["room_id"];
    $table_number = $_POST["table_number"];
    $capacity = $_POST["capacity"];
    $status = $_POST["status"];

    // Validar campos
    $errors = [];
    if (empty($room_id)) {
        $errors[] = "La sala es obligatoria.";
    }
    if (empty($table_number)) {
        $errors[] = "El número de mesa es obligatorio.";
    } elseif ($table_number < 1) {
        $errors[] = "El número de mesa no puede ser negativo o cero.";
    }
    if (empty($capacity)) {
        $errors[] = "La capacidad es obligatoria.";
    } elseif ($capacity < 1) {
        $errors[] = "La capacidad debe ser al menos 1.";
    }
    if (empty($status)) {
        $errors[] = "El estado de la mesa es obligatorio.";
    }

    // Verificar si hay errores
    if (count($errors) > 0) {
        $errorMessage = implode("\n", $errors);
        echo "<script>alert('$errorMessage');</script>";
    } else {
        // Comprobar si hay suficientes sillas en el stock
        $sqlCheckStock = "SELECT chairs_in_warehouse FROM tbl_chairs_stock LIMIT 1";
        $stmtCheckStock = $conexion->query($sqlCheckStock);
        $stock = $stmtCheckStock->fetchColumn();

        if ($capacity > $stock) {
            echo "<script>alert('No hay suficientes sillas disponibles en el almacén.');</script>";
        } else {
            // Verificar si la mesa ya existe en la sala
            $sqlCheckTable = "SELECT COUNT(*) FROM tbl_tables WHERE room_id = :room_id AND table_number = :table_number";
            $stmtCheckTable = $conexion->prepare($sqlCheckTable);
            $stmtCheckTable->execute([':room_id' => $room_id, ':table_number' => $table_number]);
            $existingTable = $stmtCheckTable->fetchColumn();

            if ($existingTable > 0) {
                echo "<script>alert('Ya existe una mesa con ese número en esta sala.');</script>";
            } else {
                // Insertar la nueva mesa
                $sqlInsertTable = "INSERT INTO tbl_tables (room_id, table_number, capacity, status) 
                                   VALUES (:room_id, :table_number, :capacity, :status)";
                $stmtInsertTable = $conexion->prepare($sqlInsertTable);
                $stmtInsertTable->execute([
                    ':room_id' => $room_id,
                    ':table_number' => $table_number,
                    ':capacity' => $capacity,
                    ':status' => $status
                ]);

                // Restar las sillas utilizadas del stock
                $sqlUpdateStock = "UPDATE tbl_chairs_stock SET chairs_in_warehouse = chairs_in_warehouse - :capacity";
                $stmtUpdateStock = $conexion->prepare($sqlUpdateStock);
                $stmtUpdateStock->execute([':capacity' => $capacity]);

                echo "<script>alert('Mesa añadida exitosamente.');</script>";
                header('Location: ./crudMesas.php');
            }
        }
    }
}
