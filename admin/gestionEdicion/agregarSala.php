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

// Obtener los tipos de sala desde la base de datos
$sqlRoomTypes = "SHOW COLUMNS FROM tbl_rooms LIKE 'room_type'";
$stmtRoomTypes = $conexion->query($sqlRoomTypes);
$rowRoomTypes = $stmtRoomTypes->fetch(PDO::FETCH_ASSOC);

// Extraer los valores ENUM de la columna room_type
preg_match("/^enum\('(.*)'\)$/", $rowRoomTypes['Type'], $matches);
$roomTypes = explode("','", $matches[1]);

// Crear sala
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_room') {
    $name_rooms = $_POST['name_rooms'];
    $capacity = intval($_POST['capacity']);
    $description = $_POST['description'];
    $roomtype = $_POST['roomtype']; // Nuevo campo para el tipo de sala
    $imagePath = null; // Establecer la variable de imagen como null por defecto

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
    }

    // Construir consulta SQL para insertar la sala
    $sqlInsertSala = "INSERT INTO tbl_rooms (name_rooms, capacity, description, room_type, image_path) 
                      VALUES (:name_rooms, :capacity, :description, :roomtype, :image_path)";
    
    $stmtInsertSala = $conexion->prepare($sqlInsertSala);

    // Preparar datos para la consulta
    $params = [
        ':name_rooms' => $name_rooms,
        ':capacity' => $capacity,
        ':description' => $description,
        ':roomtype' => $roomtype,
        ':image_path' => $imagePath
    ];

    // Ejecutar la consulta
    try {
        $stmtInsertSala->execute($params);
        header("Location: crudMesas.php?success=1");
        exit();
    } catch (Exception $e) {
        echo "<script>alert('Error al crear la sala: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Sala</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Crear Nueva Sala</h1>

        <!-- Mensajes de éxito -->
        <?php if (isset($_GET['success'])) { ?>
            <div class="alert alert-success">Sala añadida exitosamente.</div>
        <?php } ?>

        <!-- Formulario para crear sala -->
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_room">
            
            <div class="mb-3">
                <label for="name_rooms" class="form-label">Nombre de Sala:</label>
                <input type="text" name="name_rooms" id="name_rooms" class="form-control" placeholder="Nombre de la Sala" required>
            </div>

            <div class="mb-3">
                <label for="capacity" class="form-label">Capacidad:</label>
                <input type="number" name="capacity" id="capacity" class="form-control" placeholder="Capacidad" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descripción:</label>
                <textarea name="description" id="description" class="form-control" placeholder="Descripción de la Sala" required></textarea>
            </div>

            <div class="mb-3">
                <label for="roomtype" class="form-label">Tipo de Sala:</label>
                <select name="roomtype" id="roomtype" class="form-select" required>
                    <?php foreach ($roomTypes as $type) { ?>
                        <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="image_file" class="form-label">Imagen de la Sala:</label>
                <input type="file" name="image_file" id="image_file" class="form-control">
                <small class="text-muted">Formatos permitidos: JPG, JPEG, PNG, GIF.</small>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">Crear Sala</button>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
