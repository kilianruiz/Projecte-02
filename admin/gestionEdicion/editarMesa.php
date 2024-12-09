<?php
session_start();

// Verificar si el usuario tiene el rol de Administrador
if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

include_once('../../conexion/conexion.php');

// Verificar si el ID de la mesa está presente en la URL
if (!isset($_GET['table_id'])) {
    die("Error: No se especificó la mesa.");
}

$table_id = intval($_GET['table_id']);

// Consultar los detalles de la mesa
$sqlMesa = "
    SELECT tbl_tables.table_id, tbl_tables.room_id, tbl_tables.table_number, tbl_tables.capacity, tbl_tables.status, tbl_rooms.name_rooms, tbl_rooms.image_url 
    FROM tbl_tables
    INNER JOIN tbl_rooms ON tbl_tables.room_id = tbl_rooms.room_id
    WHERE tbl_tables.table_id = :table_id
";
$stmtMesa = $conexion->prepare($sqlMesa);
$stmtMesa->execute([':table_id' => $table_id]);

// Si la mesa no existe, redirigir
if ($stmtMesa->rowCount() == 0) {
    die("Error: Mesa no encontrada.");
}

$mesa = $stmtMesa->fetch();

// Obtener el stock de sillas disponible
$sqlStockSillas = "SELECT chairs_in_warehouse FROM tbl_chairs_stock LIMIT 1";
$stmtStockSillas = $conexion->query($sqlStockSillas);
$stockSillas = $stmtStockSillas->fetchColumn();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Mesa - Restaurante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        /* Tu CSS aquí */
    </style>
</head>
<body>
    <div class="container mt-5">
        <h3>Editar Mesa: <?= htmlspecialchars($mesa['table_number']); ?></h3>
        <form method="POST" action="procesarEdicionMesa.php">
            <input type="hidden" name="table_id" value="<?= htmlspecialchars($mesa['table_id']); ?>">

            <div class="mb-3">
                <label for="room_id" class="form-label">Sala</label>
                <select name="room_id" id="room_id" class="form-select">
                    <option value="<?= htmlspecialchars($mesa['room_id']); ?>" selected><?= htmlspecialchars($mesa['name_rooms']); ?></option>
                    <?php
                    $stmtSalas = $conexion->query("SELECT * FROM tbl_rooms");
                    while ($sala = $stmtSalas->fetch()) { ?>
                        <option value="<?= htmlspecialchars($sala['room_id']); ?>">
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
                <input type="number" name="capacity" id="capacity" class="form-control" value="<?= htmlspecialchars($mesa['capacity']); ?>" required min="1" max="<?= $stockSillas ?>">
                <small>Stock de sillas disponibles: <?= $stockSillas ?></small>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Estado</label>
                <select name="status" id="status" class="form-select">
                    <option value="free" <?= $mesa['status'] === 'free' ? 'selected' : ''; ?>>Libre</option>
                    <option value="occupied" <?= $mesa['status'] === 'occupied' ? 'selected' : ''; ?>>Ocupada</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="image_url" class="form-label">Imagen de Sala</label>
                <input type="text" name="image_url" id="image_url" class="form-control" value="<?= htmlspecialchars($mesa['image_url']); ?>" placeholder="URL de la imagen">
            </div>

            <button type="submit" class="btn btn-primary">Actualizar Mesa</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
