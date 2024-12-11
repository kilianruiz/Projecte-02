<?php
session_start();

// Verificar si el usuario es administrador
if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

// Incluir la conexión a la base de datos correctamente
include_once('../../conexion/conexion.php');

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

    // Validación: Verificar si ya existe una sala con el mismo nombre
    $sqlCheckName = "SELECT COUNT(*) FROM tbl_rooms WHERE name_rooms = :name_rooms AND room_id != :room_id";
    $stmtCheckName = $conexion->prepare($sqlCheckName);
    $stmtCheckName->execute([':name_rooms' => $nameRooms, ':room_id' => $roomId]);
    $nameCount = $stmtCheckName->fetchColumn();

    if ($nameCount > 0) {
        $error = "Ya existe una sala con ese nombre. Por favor, elige otro.";
    } else {
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

        // Si no hay errores, actualizar los datos en la base de datos
        if (!isset($error)) {
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

        .form-error {
            color: red;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div class="top-bar">
        Editar  Salas
        <a href="./crudSalas.php" class="btn btn-primary">Volver</a>
    </div>
    <div class="container mt-5">
        <h1>Editar Sala</h1>
        <form method="POST" enctype="multipart/form-data" onsubmit="return validaForm4()">
            <div class="mb-3">
                <label for="room_id" class="form-label">Seleccionar Sala:</label>
                <select name="room_id" id="room_id" class="form-select">
                    <option value="">Seleccione una sala</option>
                    <?php foreach ($salas as $salaOption): ?>
                        <option value="<?= htmlspecialchars($salaOption['room_id']); ?>" 
                            <?= ($salaOption['room_id'] == $sala['room_id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($salaOption['name_rooms']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="room_id_error" class="form-error"></div>
            </div>
            <div class="mb-3">
                <label for="name_rooms" class="form-label">Nombre de la Sala:</label>
                <input type="text" name="name_rooms" id="name_rooms" class="form-control" value="<?= htmlspecialchars($sala['name_rooms']); ?>">
                <div id="name_rooms_error" class="form-error">
                    <?php if (isset($error) && strpos($error, 'nombre') !== false): ?>
                        <?= htmlspecialchars($error); ?>
                    <?php endif; ?>
                </div>
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
            <?php if (isset($error) && strpos($error, 'Error') !== false): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../validaciones/validaciones.js"></script>

</body>
</html>
