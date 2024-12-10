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

// Actualizar mesa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = intval($_POST['room_id']);
    $tableNumber = intval($_POST['table_number']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status'];
    $imagePath = $mesa['image_path']; // Mantener la imagen existente si no se sube una nueva

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

    // Redirigir al CRUD con éxito
    header("Location: crudMesas.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Mesa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Editar Mesa</h1>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="room_id" class="form-label">Sala</label>
                <select name="room_id" id="room_id" class="form-select" required>
                    <?php while ($sala = $stmtSalas->fetch()) { ?>
                        <option value="<?= $sala['room_id']; ?>" <?= $mesa['room_id'] == $sala['room_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($sala['name_rooms']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="table_number" class="form-label">Número de Mesa</label>
                <input type="number" name="table_number" id="table_number" class="form-control" value="<?= htmlspecialchars($mesa['table_number']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="capacity" class="form-label">Capacidad</label>
                <input type="number" name="capacity" id="capacity" class="form-control" value="<?= htmlspecialchars($mesa['capacity']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Estado</label>
                <select name="status" id="status" class="form-select">
                    <option value="free" <?= $mesa['status'] === 'free' ? 'selected' : ''; ?>>Libre</option>
                    <option value="occupied" <?= $mesa['status'] === 'occupied' ? 'selected' : ''; ?>>Ocupada</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Imagen</label>
                <input type="file" name="image" id="image" class="form-control">
                <?php if (!empty($mesa['image_path'])): ?>
                    <img src="../../<?= htmlspecialchars($mesa['image_path']); ?>" alt="Mesa" class="mt-3 img-thumbnail" style="max-width: 200px;">
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="crudMesas.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>
