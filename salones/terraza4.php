<?php
session_start();
include '../conexion/conexion.php';

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

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header('Location: ../index.php?error=1');   
    exit;
}

$usuario = $_SESSION['usuario'];

// Obtener ID del usuario basado en el nombre de usuario
$sqlGetUserId = "SELECT user_id FROM tbl_users WHERE username = ?";
$stmtGetUserId = $conexion->prepare($sqlGetUserId);
$stmtGetUserId->execute([$usuario]);
$userId = $stmtGetUserId->fetchColumn();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['tableId'])) {
    $tableId = intval($_POST['tableId']);
    $action = $_POST['action'];

    if ($action === 'occupy') {
        // Ocupa la mesa y registra el tiempo actual en 'occupied_since'
        $sqlUpdateTable = "UPDATE tbl_tables 
                           SET status = 'occupied', 
                               occupied_since = NOW() 
                           WHERE table_id = ? AND status = 'free'";
        $stmtUpdateTable = $conexion->prepare($sqlUpdateTable);
        $stmtUpdateTable->execute([$tableId]);

        // Verifica si la mesa se ocupó correctamente
        if ($stmtUpdateTable->rowCount() > 0) {
            // Si la mesa fue ocupada, registrar la ocupación en tbl_occupations
            $sqlInsertOccupation = "INSERT INTO tbl_occupations (table_id, user_id, start_time) 
                                    VALUES (?, ?, NOW())";
            $stmtInsertOccupation = $conexion->prepare($sqlInsertOccupation);
            $stmtInsertOccupation->execute([$tableId, $userId]);
        } else {
            // Si no se actualizó, significa que la mesa ya estaba ocupada
            echo "La mesa ya está ocupada.";
        }
    } elseif ($action === 'free') {
        // Libera la mesa y resetea la columna occupied_since
        $sqlUpdateTable = "UPDATE tbl_tables 
                           SET status = 'free', 
                               occupied_since = NULL 
                           WHERE table_id = ?";
        $stmtUpdateTable = $conexion->prepare($sqlUpdateTable);
        $stmtUpdateTable->execute([$tableId]);

        // Actualiza la tabla de ocupaciones (si la mesa estaba ocupada)
        $sqlEndOccupation = "UPDATE tbl_occupations 
                             SET end_time = NOW() 
                             WHERE table_id = ? AND end_time IS NULL";
        $stmtEndOccupation = $conexion->prepare($sqlEndOccupation);
        $stmtEndOccupation->execute([$tableId]);
    }

    // Redirigir para evitar reenvío de formularios
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Liberar mesas automáticamente si han estado ocupadas por más de 2 horas
$sqlCheckTables = "SELECT table_id, occupied_since FROM tbl_tables WHERE status = 'occupied'";
$stmtCheckTables = $conexion->query($sqlCheckTables);
$mesasOcupadas = $stmtCheckTables->fetchAll(PDO::FETCH_ASSOC);

foreach ($mesasOcupadas as $mesa) {
    $tableId = $mesa['table_id'];
    $occupiedSince = new DateTime($mesa['occupied_since']);
    $currentTime = new DateTime();
    $interval = $currentTime->diff($occupiedSince);

    // Si han pasado más de 2 horas, liberar la mesa
    if ($interval->h >= 2) {
        // Liberar la mesa
        $sqlUpdateTable = "UPDATE tbl_tables SET status = 'free', occupied_since = NULL WHERE table_id = ?";
        $stmtUpdateTable = $conexion->prepare($sqlUpdateTable);
        $stmtUpdateTable->execute([$tableId]);

        // Actualizar el historial de ocupación
        $sqlEndOccupation = "UPDATE tbl_occupations SET end_time = CURRENT_TIMESTAMP WHERE table_id = ? AND end_time IS NULL";
        $stmtEndOccupation = $conexion->prepare($sqlEndOccupation);
        $stmtEndOccupation->execute([$tableId]);
    }
}

// Obtener las mesas de la base de datos
$sql = "SELECT table_id, status FROM tbl_tables WHERE table_id BETWEEN 21 AND 30";
$stmt = $conexion->query($sql);
$mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terraza II</title> 
    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sancreek&display=swap" rel="stylesheet">
</head>
<body>
    <div><img src="./../img/logo.webp" alt="Logo de la página" class="superpuesta"><br></div>
    <div class="container2">
        <div class="header">
            <h1>S A L Ó N    I</h1> 
        </div>
        <div class="grid2">
            <?php
            // Generar HTML para cada mesa
            foreach ($mesas as $mesa) {
                $tableId = $mesa['table_id'];
                $status = $mesa['status'];
                $romanTableId = romanNumerals($tableId); // Convertimos a números romanos
                $imgSrc = ($status === 'occupied') ? '../img/salonRoja.webp' : '../img/salonVerde.webp';

                echo "
                <div class='table' id='mesa$tableId' onclick='openTableOptions($tableId, \"$status\", \"$romanTableId\")'>
                    <img id='imgMesa$tableId' src='$imgSrc' alt='Mesa $tableId'>
                    <p>Mesa $romanTableId</p>
                </div>

                <form id='formMesa$tableId' method='POST' style='display: none;'>
                    <input type='hidden' name='tableId' value='$tableId'>
                    <input type='hidden' name='action' id='action$tableId'>
                    <input type='hidden' name='newRoomId' id='newRoomId$tableId'>
                </form>
                ";
            }
            ?>
        </div>

        <button class="logout-button" onclick="logout()">Cerrar Sesión</button>
        <form action="../paginaPrincipal.php">
            <button class="logout">Volver</button>
        </form>
    </div>

    <script src="../validaciones/funcionesSalones.js"></script>
    <script src="../validaciones/funciones.js"></script>
</body>
</html>
