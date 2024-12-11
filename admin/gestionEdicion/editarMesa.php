<?php
session_start();

// Verificar si el usuario es administrador
if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');
if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no se estableció correctamente.");
}

// Obtener ID de la mesa
$tableId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($tableId <= 0) {
    header('Location: ../index.php?error=3');
    exit();
}

// Consultar datos de la mesa
$sqlMesa = "SELECT * FROM tbl_tables WHERE table_id = :table_id";
$stmtMesa = $conexion->prepare($sqlMesa);
$stmtMesa->execute([':table_id' => $tableId]);
$mesa = $stmtMesa->fetch(PDO::FETCH_ASSOC);
if (!$mesa) {
    header('Location: ../index.php?error=4');
    exit();
}

// Obtener las salas
$sqlSalas = "SELECT room_id, name_rooms FROM tbl_rooms";
$stmtSalas = $conexion->query($sqlSalas);

// Obtener el stock actual de sillas
$sqlSillas = "SELECT chairs_in_warehouse FROM tbl_chairs_stock WHERE stock_id = 1";
$stmtSillas = $conexion->query($sqlSillas);
$sillasStock = $stmtSillas->fetch(PDO::FETCH_ASSOC);

// Actualizar mesa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = intval($_POST['room_id']);
    $tableNumber = intval($_POST['table_number']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status'];
    $imagePath = $mesa['image_path']; // Mantener la imagen existente si no se sube una nueva

    // Validar si el número de mesa ya existe en la misma sala
    $sqlValidarMesa = "SELECT COUNT(*) FROM tbl_tables WHERE room_id = :room_id AND table_number = :table_number AND table_id != :table_id";
    $stmtValidarMesa = $conexion->prepare($sqlValidarMesa);
    $stmtValidarMesa->execute([
        ':room_id' => $roomId,
        ':table_number' => $tableNumber,
        ':table_id' => $tableId
    ]);
    $mesaExistente = $stmtValidarMesa->fetchColumn();

    if ($mesaExistente > 0) {
        // Si existe una mesa con el mismo número en la misma sala
        header('Location: crudMesas.php?error=6'); // Error: Mesa ya existe en esta sala
        exit();
    }

    // Procesar nueva imagen
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../../img/terrazas/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $imageName = basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = 'img/terrazas/' . $imageName;
        }
    }

    // Actualizar base de datos
    $sqlUpdate = "
        UPDATE tbl_tables
        SET room_id = :room_id,
            table_number = :table_number,
            capacity = :capacity,
            status = :status,
            image_path = :image_path
        WHERE table_id = :table_id
    ";
    $stmtUpdate = $conexion->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':room_id' => $roomId,
        ':table_number' => $tableNumber,
        ':capacity' => $capacity,
        ':status' => $status,
        ':image_path' => $imagePath,
        ':table_id' => $tableId
    ]);

    // Actualizar el stock de sillas
    $stockSillas = $sillasStock['chairs_in_warehouse'];
    $cantidadSillasMesa = $capacity;

    // Calcular diferencia de sillas y actualizar stock
    $diferenciaSillas = $cantidadSillasMesa - $mesa['capacity'];
    $nuevoStockSillas = $stockSillas - $diferenciaSillas;

    if ($nuevoStockSillas >= 0) {
        // Actualizar el stock de sillas en el almacén
        $sqlUpdateSillas = "
            UPDATE tbl_chairs_stock
            SET chairs_in_warehouse = :nuevo_stock
            WHERE stock_id = 1
        ";
        $stmtUpdateSillas = $conexion->prepare($sqlUpdateSillas);
        $stmtUpdateSillas->execute([':nuevo_stock' => $nuevoStockSillas]);

        // Redirigir al CRUD con éxito
        header("Location: crudMesas.php?success=1");
        exit();
    } else {
        // Si el stock de sillas es insuficiente
        header("Location: crudMesas.php?error=5");
        exit();
    }
}
?>

<!-- HTML con el mensaje de error si existe un conflicto en el número de mesa -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Mesa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #a67c52; }
        .top-bar { background-color: #8A5021; padding: 20px; margin-bottom: 20px; text-align: center; color: white; font-size: 1.5rem; font-weight: bold; }
        .container { padding: 30px; margin-top: 20px; background-color: #8A5021; border-radius: 10px; color: white; }
        .error-message { color: red; font-size: 0.9rem; }
        .form-control, .form-select {
            background-color: #a67c52;
            border: 2px solid #6c3e18;
            color: white;
        }

        .form-control:focus, .form-select:focus {
            background-color: #a67c52;
            border-color: white;
            box-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
        }

        .form-control[readonly] {
            background-color: #a67c52; 
            color: white;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Editar Mesa</h1>
        <!-- Mensaje de error si el número de mesa ya existe -->
        <?php if (isset($_GET['error']) && $_GET['error'] == 6): ?>
            <div class="alert alert-danger mt-3" role="alert">
                El número de mesa que estás intentando asignar ya existe en esta sala. Por favor, elige otro número.
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" onsubmit="return validarFormulario()">
            <div class="mb-3">
                <label for="room_id" class="form-label">Sala</label>
                <select name="room_id" id="room_id" class="form-select" required>
                    <?php while ($sala = $stmtSalas->fetch()) { ?>
                        <option value="<?= $sala['room_id']; ?>" <?= $mesa['room_id'] == $sala['room_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($sala['name_rooms']); ?>
                        </option>
                    <?php } ?>
                </select>
                <div id="room_id_error" class="error-message"></div>
            </div>
            <div class="mb-3">
                <label for="table_number" class="form-label">Número de Mesa</label>
                <input type="number" name="table_number" id="table_number" class="form-control" value="<?= htmlspecialchars($mesa['table_number']); ?>" >
                <div id="table_number_error" class="error-message"></div>
            </div>
            <div class="mb-3">
                <label for="capacity" class="form-label">Capacidad</label>
                <input type="number" name="capacity" id="capacity" class="form-control" value="<?= htmlspecialchars($mesa['capacity']); ?>" >
                <div id="capacity_error" class="error-message"></div>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Estado</label>
                <select name="status" id="status" class="form-select">
                    <option value="free" <?= $mesa['status'] === 'free' ? 'selected' : ''; ?>>Libre</option>
                    <option value="occupied" <?= $mesa['status'] === 'occupied' ? 'selected' : ''; ?>>Ocupada</option>
                </select>
                <div id="status_error" class="error-message"></div>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Imagen</label>
                <input type="file" name="image" id="image" class="form-control">
                <?php if (!empty($mesa['image_path'])): ?>
                    <img src="../../<?= htmlspecialchars($mesa['image_path']); ?>" alt="Mesa" class="mt-3 img-thumbnail" style="max-width: 200px;">
                <?php endif; ?>
                <div id="image_path_error" class="error-message"></div>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="crudMesas.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
    <script src="../../validaciones/validaciones.js"></script>
</body>
</html>
