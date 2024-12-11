<?php
session_start();

// Verificar si el ID de la sala está presente en la URL
if (isset($_GET['room_id'])) {
    $roomId = $_GET['room_id'];

    // Incluir el archivo de conexión
    include('../conexion/conexion.php');  // Ajusta la ruta si el archivo `conexion.php` está en una carpeta diferente

    // Verificar si la conexión a la base de datos es válida
    if (!$conexion) {
        echo "Error de conexión a la base de datos.";
        exit();
    }

    // Obtener la información de la sala seleccionada desde la base de datos
    $sql = "SELECT room_id, name_rooms FROM tbl_rooms WHERE room_id = :room_id";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':room_id', $roomId, PDO::PARAM_INT);
    $stmt->execute();
    $sala = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si no se encuentra la sala, mostrar un mensaje de error
    if (!$sala) {
        echo "Sala no encontrada.";
        exit();
    }

    // Obtener las mesas asociadas a la sala seleccionada
    $sqlTables = "SELECT table_id, status FROM tbl_tables WHERE room_id = :room_id";  // Ahora filtramos por `room_id`
    $stmtTables = $conexion->prepare($sqlTables);
    $stmtTables->bindParam(':room_id', $roomId, PDO::PARAM_INT);
    $stmtTables->execute();
    $tables = $stmtTables->fetchAll(PDO::FETCH_ASSOC);

} else {
    echo "No se seleccionó ninguna sala.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($sala['name_rooms']) ?></title>
    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sancreek&display=swap" rel="stylesheet">
    <style>
        .table {
            display: inline-block;
            margin: 10px;
            text-align: center;
            width: 100px; /* Ajuste el tamaño de cada mesa */
        }
        .table img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }
        .table p {
            margin-top: 5px;
            font-size: 1.1em;
        }
        .tables-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 15px;
        }
        /* Asegura que cada fila tenga solo 4 mesas */
        .tables-container > .table:nth-child(4n+1) {
            clear: both;
        }
    </style>
</head>
<body>
    <div><img src="../img/logo.webp" alt="Logo de la página" class="superpuesta"><br></div>
    <div class="container">
        <h1><?= htmlspecialchars($sala['name_rooms']) ?></h1>

        <div class="tables-container">
            <?php
            // Generar HTML para cada mesa en la sala seleccionada
            foreach ($tables as $row) {
                $tableId = $row['table_id'];
                $status = $row['status'];
                $romanTableId = romanNumerals($tableId); // Convertimos a números romanos
                $imgSrc = ($status === 'occupied') ? '../img/salonRoja.webp' : '../img/salonVerde.webp';

                echo "
                <div class='table' id='mesa$tableId' onclick='openTableOptions($tableId, \"$status\", \"$romanTableId\")'>
                    <img id='imgMesa$tableId' src='$imgSrc' alt='Mesa $tableId'>
                    <p>Mesa $romanTableId</p>
                </div>
                ";
            }
            ?>
        </div>

        <!-- Botón para volver a la página anterior -->
        <button class="logout-button" onclick="window.history.back()">Volver</button>
    </div>

    <script src="../validaciones/funciones.js"></script>
    <script src="../validaciones/funcionesPaginaPrincipal.js"></script>
    <script src="../validaciones/funcionesSalones.js"></script>
</body>
</html>

<?php
// Función para convertir un número entero a un número romano
function romanNumerals($number) {
    $map = [
        'M' => 1000,
        'CM' => 900,
        'D' => 500,
        'CD' => 400,
        'C' => 100,
        'XC' => 90,
        'L' => 50,
        'XL' => 40,
        'X' => 10,
        'IX' => 9,
        'V' => 5,
        'IV' => 4,
        'I' => 1
    ];
    $result = '';
    foreach ($map as $roman => $int) {
        while ($number >= $int) {
            $result .= $roman;
            $number -= $int;
        }
    }
    return $result;
}
?>
