<?php
session_start();

// Verificar si el usuario es administrador
if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

// Verificar si la conexión a la base de datos fue exitosa
if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no se estableció correctamente.");
}

// Consultar salas
$sqlSalas = "SELECT room_id, name_rooms FROM tbl_rooms";
$stmtSalas = $conexion->query($sqlSalas);

// Consultar el stock actual de mesas y sillas
$sqlStockMesas = "SELECT tables_in_warehouse FROM tbl_tables_stock WHERE stock_id = 1";
$stmtStockMesas = $conexion->query($sqlStockMesas);
$stockMesas = $stmtStockMesas->fetch(PDO::FETCH_ASSOC)['tables_in_warehouse'];

$sqlStockSillas = "SELECT chairs_in_warehouse FROM tbl_chairs_stock WHERE stock_id = 1";
$stmtStockSillas = $conexion->query($sqlStockSillas);
$stockSillas = $stmtStockSillas->fetch(PDO::FETCH_ASSOC)['chairs_in_warehouse'];

// Procesar el formulario de añadir mesa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_table') {
    $room_id = intval($_POST['room_id']);
    $table_number = intval($_POST['table_number']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status'];
    $image_path = '';

    // Verificar si hay suficiente stock
    if ($stockMesas <= 0 || $stockSillas < $capacity) {
        echo "<script>alert('No hay suficiente stock de mesas o sillas para crear esta mesa.');</script>";
    } else {
        // Procesar la imagen si se ha subido
        if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
            $targetDir = "../../img/terrazas/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $imageName = uniqid() . "_" . basename($_FILES['image_path']['name']);
            $targetFile = $targetDir . $imageName;

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileMimeType = mime_content_type($_FILES['image_path']['tmp_name']);

            if (in_array($fileMimeType, $allowedMimeTypes)) {
                if (move_uploaded_file($_FILES['image_path']['tmp_name'], $targetFile)) {
                    $image_path = "img/terrazas/" . $imageName; // Ruta relativa
                } else {
                    echo "<script>alert('Error al subir la imagen. Por favor, inténtelo de nuevo.');</script>";
                }
            } else {
                echo "<script>alert('Formato de imagen no permitido. Solo se aceptan JPEG, PNG y GIF.');</script>";
            }
        }

        // Insertar mesa en la base de datos
        $sqlInsertMesa = "INSERT INTO tbl_tables (room_id, table_number, capacity, status, image_path) 
                          VALUES (:room_id, :table_number, :capacity, :status, :image_path)";
        $stmtInsertMesa = $conexion->prepare($sqlInsertMesa);
        $stmtInsertMesa->execute([
            ':room_id' => $room_id,
            ':table_number' => $table_number,
            ':capacity' => $capacity,
            ':status' => $status,
            ':image_path' => $image_path
        ]);

        // Descontar mesas y sillas del stock
        $newStockMesas = $stockMesas - 1;
        $newStockSillas = $stockSillas - $capacity;

        $sqlUpdateMesas = "UPDATE tbl_tables_stock SET tables_in_warehouse = :newStockMesas WHERE stock_id = 1";
        $stmtUpdateMesas = $conexion->prepare($sqlUpdateMesas);
        $stmtUpdateMesas->execute([':newStockMesas' => $newStockMesas]);

        $sqlUpdateSillas = "UPDATE tbl_chairs_stock SET chairs_in_warehouse = :newStockSillas WHERE stock_id = 1";
        $stmtUpdateSillas = $conexion->prepare($sqlUpdateSillas);
        $stmtUpdateSillas->execute([':newStockSillas' => $newStockSillas]);

        // Redirigir a la página de éxito
        header("Location: crudMesas.php?success_table=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Mesa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #a67c52; }
        .top-bar { background-color: #8A5021; padding: 20px; margin-bottom: 20px; text-align: center; color: white; font-size: 1.5rem; font-weight: bold; }
        .container { padding: 30px; margin-top: 20px; background-color: #8A5021; border-radius: 10px; color: white; }
    </style>
</head>
<body>
    <div class="top-bar">
        Añadir Mesa
        <a href="crudMesas.php" class="btn btn-primary">Volver</a>
    </div>
    <div class="container">
        <h3 class="text-center">Añadir Mesa</h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_table">
            <div class="mb-3">
                <label for="room_id" class="form-label">Sala</label>
                <select name="room_id" class="form-select" required>
                    <option value="">Seleccione una sala</option>
                    <?php while ($row = $stmtSalas->fetch(PDO::FETCH_ASSOC)) { ?>
                        <option value="<?= $row['room_id']; ?>"><?= $row['name_rooms']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="table_number" class="form-label">Número de Mesa</label>
                <input type="number" name="table_number" class="form-control" placeholder="Número de Mesa" required>
            </div>
            <div class="mb-3">
                <label for="capacity" class="form-label">Capacidad</label>
                <input type="number" name="capacity" class="form-control" placeholder="Capacidad de la Mesa" required>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Estado</label>
                <select name="status" class="form-select" required>
                    <option value="free">Libre</option>
                    <option value="occupied">Ocupada</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="image_path" class="form-label">Imagen de la Mesa</label>
                <input type="file" name="image_path" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary mt-2">Añadir Mesa</button>
        </form>
    </div>
</body>
</html>
