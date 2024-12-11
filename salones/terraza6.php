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

if (!$userId) {
    echo "Usuario no encontrado.";
    exit;
}

// Actualizar la ocupación o desocupación de una mesa
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['tableId'])) {
    $tableId = intval($_POST['tableId']);
    $action = $_POST['action'];

    if ($action === 'occupy') {
        // Ocupa una mesa
        $sqlUpdateTable = "UPDATE tbl_tables SET status = 'occupied' WHERE table_id = ?";
        $stmtUpdateTable = $conexion->prepare($sqlUpdateTable);
        $stmtUpdateTable->execute([$tableId]);

        $sqlInsertOccupation = "INSERT INTO tbl_occupations (table_id, user_id, start_time) VALUES (?, ?, CURRENT_TIMESTAMP)";
        $stmtInsertOccupation = $conexion->prepare($sqlInsertOccupation);
        $stmtInsertOccupation->execute([$tableId, $userId]);
    } elseif ($action === 'free') {
        // Libera una mesa
        $sqlUpdateTable = "UPDATE tbl_tables SET status = 'free' WHERE table_id = ?";
        $stmtUpdateTable = $conexion->prepare($sqlUpdateTable);
        $stmtUpdateTable->execute([$tableId]);

        $sqlEndOccupation = "UPDATE tbl_occupations SET end_time = CURRENT_TIMESTAMP WHERE table_id = ? AND end_time IS NULL";
        $stmtEndOccupation = $conexion->prepare($sqlEndOccupation);
        $stmtEndOccupation->execute([$tableId]);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Consultar el estado actual de cada mesa en VIP I (mesas 46-49)
$sql = "SELECT table_id, status FROM tbl_tables WHERE table_id BETWEEN 46 AND 49";
$stmt = $conexion->query($sql);
$mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vip I</title>
    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sancreek&display=swap" rel="stylesheet">
</head>
<body>
    <div><img src="./../img/logo.webp" alt="Logo de la página" class="superpuesta"><br></div>
    <div class="container2">
        <div class="header">
            <h1>V I P    I</h1>
        </div>
        <div class="grid5">
            <?php
            // Generar HTML para cada mesa
            foreach ($mesas as $mesa) {
                $tableId = $mesa['table_id'];
                $status = $mesa['status'];
                $romanTableId = romanNumerals($tableId); // Convertimos a números romanos
                $imgSrc = ($status === 'occupied') ? '../img/sombrillaRoja.webp' : '../img/sombrilla.webp';

                echo "
                <div class='table1' id='mesa$tableId' onclick='openTableOptions($tableId, \"$status\", \"$romanTableId\")'>
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
