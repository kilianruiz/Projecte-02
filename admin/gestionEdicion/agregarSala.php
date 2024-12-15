<?php
session_start();

// Verificar si el usuario es administrador
if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../../index.php?error=2');
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

        // Obtener el ID de la sala recién creada
        $roomId = $conexion->lastInsertId();

        // Crear el nombre de archivo PHP para la nueva terraza
        $newPageName = strtolower(str_replace(' ', '', $name_rooms)) . ".php";
        $newPagePath = "../../salones/" . $newPageName;

        // Construir el contenido del archivo PHP con la plantilla
        $pageContent = "<?php
\$roomName = '$name_rooms';
\$roomId = $roomId;
include '../../salones/template.php'; // Incluimos la plantilla
?>";

        // Crear el archivo PHP
        file_put_contents($newPagePath, $pageContent);

        // Redirigir a una página de éxito
        header("Location: crudSalas.php");
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #a67c52; }
        .top-bar { background-color: #8A5021; padding: 20px; margin-bottom: 20px; text-align: center; color: white; font-size: 1.5rem; font-weight: bold; }
        .container { padding: 30px; margin-top: 20px; background-color: #8A5021; border-radius: 10px; color: white; }
        .error-message { color: red; font-size: 0.9rem; }
        .btn-primary, .btn-warning, .btn-danger { background-color: #6c3e18; color: white; border: 2px solid white; }
        .btn-primary:hover, .btn-warning:hover, .btn-danger:hover { background-color: #8A5021; border-color: white; }
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
<div class="top-bar">
        Añadir Sala
        <a href="crudSalas.php" class="btn btn-primary">Volver</a>
    </div>
    <div class="container mt-5">
        <h1>Crear Nueva Sala</h1>

        <!-- Formulario para crear sala -->
        <form method="POST" enctype="multipart/form-data" onsubmit="return validarFormulario()">
            <input type="hidden" name="action" value="create_room">
            
            <div class="mb-3">
                <label for="name_rooms" class="form-label">Nombre de Sala:</label>
                <input type="text" name="name_rooms" id="name_rooms" class="form-control" placeholder="Nombre de la Sala">
                <div id="name_rooms_error" class="error-message"></div>
            </div>

            <div class="mb-3">
                <label for="capacity" class="form-label">Capacidad:</label>
                <input type="number" name="capacity" id="capacity" class="form-control" placeholder="Capacidad">
                <div id="capacity_error" class="error-message"></div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descripción:</label>
                <textarea name="description" id="description" class="form-control" placeholder="Descripción de la Sala"></textarea>
                <div id="description_error" class="error-message"></div>
            </div>

            <div class="mb-3">
                <label for="roomtype" class="form-label">Tipo de Sala:</label>
                <select name="roomtype" id="roomtype" class="form-select" >
                    <?php foreach ($roomTypes as $type) { ?>
                        <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
                    <?php } ?>
                </select>
                <div id="roomtype_error" class="error-message"></div>
            </div>

            <div class="mb-3">
                <label for="image_file" class="form-label">Imagen de la Sala:</label>
                <input type="file" name="image_file" id="image_file" class="form-control">
                <small class="text-muted">Formatos permitidos: JPG, JPEG, PNG, GIF.</small>
                <div id="image_file_error" class="error-message"></div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">Crear Sala</button>
        </form>
    </div>

    <script>
        function validarFormulario() {
            let errors = false;

            // Verificar Nombre de la Sala
            const nameRooms = document.getElementById('name_rooms').value;
            if (nameRooms === "") {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor, ingrese el nombre de la sala.'
                });
                errors = true;
            }

            // Verificar Capacidad
            const capacity = document.getElementById('capacity').value;
            if (capacity === "") {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor, ingrese la capacidad de la sala.'
                });
                errors = true;
            }

            // Verificar Descripción
            const description = document.getElementById('description').value;
            if (description === "") {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor, ingrese una descripción de la sala.'
                });
                errors = true;
            }

            // Verificar Tipo de Sala
            const roomType = document.getElementById('roomtype').value;
            if (roomType === "") {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor, seleccione el tipo de sala.'
                });
                errors = true;
            }

            // Verificar Imagen
            const imageFile = document.getElementById('image_file').files.length;
            if (imageFile === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor, cargue una imagen para la sala.'
                });
                errors = true;
            }

            return !errors;
        }
    </script>

</body>
</html>
