<?php
session_start();

// Verificar si el usuario es administrador
if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

// Incluir la conexión a la base de datos correctamente
include_once('../../conexion/conexion.php'); // Ajustar la ruta según sea necesario

// Verificar si la conexión fue exitosa
if (!$conexion) {
    die("Error al conectar con la base de datos.");
}

// Obtener el room_id de la URL
$roomId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($roomId > 0) {
    // Consultar la sala a editar
    $sqlSala = "SELECT room_id, name_rooms, image_path FROM tbl_rooms WHERE room_id = :room_id";
    $stmtSala = $conexion->prepare($sqlSala);
    $stmtSala->execute([':room_id' => $roomId]);
    $sala = $stmtSala->fetch(PDO::FETCH_ASSOC);

    if (!$sala) {
        die("Sala no encontrada.");
    }
} else {
    die("ID de sala no válida.");
}

// Obtener la lista de salas para el formulario de selección
$sqlSalas = "SELECT room_id, name_rooms FROM tbl_rooms"; 
$stmtSalas = $conexion->query($sqlSalas);
$salas = $stmtSalas->fetchAll(PDO::FETCH_ASSOC);

// Procesar el formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = intval($_POST['room_id']);
    $nameRooms = trim($_POST['name_rooms']);
    $imagePath = null; // Establecer la variable en null en caso de que no se cargue ninguna imagen

    // Procesar nueva imagen
    if (!empty($_FILES['image_file']['name'])) {
        $uploadDir = '../../img/terrazas/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $imageName = basename($_FILES['image_file']['name']);
        $targetFile = $uploadDir . $imageName;

        // Verificar si el archivo es una imagen válida
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileType, $allowedTypes)) {
            // Mover el archivo subido al directorio destino
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetFile)) {
                $imagePath = 'img/terrazas/' . $imageName;
            } else {
                $error = "Error al subir la imagen.";
            }
        } else {
            $error = "Formato de archivo no válido. Solo se permiten JPG, JPEG, PNG y GIF.";
        }
    } else {
        // Si no se carga nueva imagen, mantener la imagen actual
        $imagePath = $sala['image_path']; // Mantener la imagen existente si no se sube una nueva
    }

    // Actualizar los datos en la base de datos
    $sqlUpdate = "UPDATE tbl_rooms SET name_rooms = :name_rooms";
    $params = [':name_rooms' => $nameRooms, ':room_id' => $roomId];

    if ($imagePath) {
        $sqlUpdate .= ", image_path = :image_path"; 
        $params[':image_path'] = $imagePath;
    }

    $sqlUpdate .= " WHERE room_id = :room_id";
    $stmtUpdate = $conexion->prepare($sqlUpdate);

    if ($stmtUpdate->execute($params)) {
        header('Location: ./crudSalas.php?success=1');
        exit();
    } else {
        $error = "Error al actualizar la sala.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Sala</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Editar Sala</h1>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="room_id" class="form-label">Seleccionar Sala:</label>
                <select name="room_id" id="room_id" class="form-select" required>
                    <option value="">Seleccione una sala</option>
                    <?php foreach ($salas as $salaOption): ?>
                        <option value="<?= htmlspecialchars($salaOption['room_id']); ?>" 
                            <?= ($salaOption['room_id'] == $sala['room_id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($salaOption['name_rooms']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="name_rooms" class="form-label">Nombre de la Sala:</label>
                <input type="text" name="name_rooms" id="name_rooms" class="form-control" value="<?= htmlspecialchars($sala['name_rooms']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="image_file" class="form-label">Imagen de la Sala:</label>
                <input type="file" name="image_file" id="image_file" class="form-control">
                <small class="text-muted">Formatos permitidos: JPG, JPEG, PNG, GIF.</small>

                <!-- Mostrar la imagen actual si existe -->
                <?php if (!empty($sala['image_path'])): ?>
                    <div class="mt-3">
                        <strong>Imagen Actual:</strong><br>
                        <img src="../../<?= htmlspecialchars($sala['image_path']); ?>" alt="Imagen de la Sala" style="max-width: 300px;">
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
