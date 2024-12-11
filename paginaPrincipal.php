<?php
session_start();
include('./conexion/conexion.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ./index.php?error=1');
    exit();
}

// Función genérica para obtener el estado de mesas
function obtenerEstadoMesas($conexion, $roomId) {
    $sql = "SELECT 
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) AS ocupadas,
                SUM(CASE WHEN status = 'free' THEN 1 ELSE 0 END) AS libres
            FROM tbl_tables WHERE room_id = :room_id";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':room_id', $roomId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener las salas agrupadas por tipo
$sqlSalas = "SELECT room_id, name_rooms, room_type, image_path FROM tbl_rooms ORDER BY room_type, room_id";
$stmtSalas = $conexion->query($sqlSalas);
$salas = $stmtSalas->fetchAll(PDO::FETCH_ASSOC);

// Agrupar salas por tipo
$salasAgrupadas = [];
foreach ($salas as $sala) {
    $salasAgrupadas[$sala['room_type']][] = $sala;
}

// Función para generar el estado de mesas por cada sala
$estadosMesas = [];
foreach ($salas as $sala) {
    $estadosMesas[$sala['room_id']] = obtenerEstadoMesas($conexion, $sala['room_id']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selección de Salas</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sancreek&display=swap" rel="stylesheet">
    <style>
        .sala-image {
    width: 100%; /* Asegura que la imagen ocupe el 100% del contenedor */
    height: auto; /* Mantiene la proporción de la imagen */
    object-fit: cover; /* Hace que la imagen cubra el área disponible sin deformarse */
    border-radius: 10px; /* Bordes redondeados para un efecto visual más agradable */
    margin-bottom: 5px; /* Espacio debajo de la imagen */
}
    </style>
</head>
<body>
    <div><img src="./img/logo.webp" alt="Logo de la página" class="superpuesta"><br></div>
    <div class="container">
        <h1>S A L A S</h1>
        <div class="room-sections">
            <?php foreach ($salasAgrupadas as $tipo => $salasPorTipo): ?>
                <div class="room-category">
                    <h2><?= ucfirst($tipo) ?></h2>
                    <div class="buttons">
                        <?php foreach ($salasPorTipo as $sala): ?>
                            <form action="./salones/terraza<?= $sala['room_id'] ?>.php" method="get">
                                <div class="sala-item">
                                    <?php if (!empty($sala['image_path'])): ?>
                                        <img src="<?= htmlspecialchars($sala['image_path']) ?>" alt="Imagen de la Sala" class="sala-image">
                                    <?php else: ?>
                                        <p>No hay imagen disponible</p>
                                    <?php endif; ?>
                                    <button type="submit">
                                        <?= htmlspecialchars($sala['name_rooms']) ?>
                                    </button>
                                </div>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="logout-button" onclick="logout1()">Cerrar Sesión</button>
    </div>

    <script src="./validaciones/funciones.js"></script>
    <script src="./validaciones/funcionesPaginaPrincipal.js"></script>

</body>
</html>
