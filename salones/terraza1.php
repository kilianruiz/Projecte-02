<?php
session_start();
require_once '../conexion/conexion.php';

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
try {
    $sqlGetUserId = "SELECT user_id FROM tbl_users WHERE username = :username";
    $stmtGetUserId = $conexion->prepare($sqlGetUserId);
    $stmtGetUserId->bindParam(':username', $usuario, PDO::PARAM_STR);
    $stmtGetUserId->execute();
    $result = $stmtGetUserId->fetch(PDO::FETCH_ASSOC);
    $userId = ($result) ? $result['user_id'] : null;
} catch (PDOException $e) {
    die("Error al obtener el ID del usuario: " . $e->getMessage());
}

// Actualizar la ocupación o desocupación de una mesa
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['tableId'])) {
    $tableId = $_POST['tableId'];
    $action = $_POST['action'];

    try {
        if ($action === 'occupy') {
            $sqlUpdateTable = "UPDATE tbl_tables SET status = 'occupied' WHERE table_id = :table_id";
            $stmtUpdateTable = $conexion->prepare($sqlUpdateTable);
            $stmtUpdateTable->bindParam(':table_id', $tableId, PDO::PARAM_INT);
            $stmtUpdateTable->execute();

            $sqlInsertOccupation = "INSERT INTO tbl_occupations (table_id, user_id, start_time) VALUES (:table_id, :user_id, CURRENT_TIMESTAMP)";
            $stmtInsertOccupation = $conexion->prepare($sqlInsertOccupation);
            $stmtInsertOccupation->bindParam(':table_id', $tableId, PDO::PARAM_INT);
            $stmtInsertOccupation->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtInsertOccupation->execute();
        } elseif ($action === 'free') {
            $sqlUpdateTable = "UPDATE tbl_tables SET status = 'free' WHERE table_id = :table_id";
            $stmtUpdateTable = $conexion->prepare($sqlUpdateTable);
            $stmtUpdateTable->bindParam(':table_id', $tableId, PDO::PARAM_INT);
            $stmtUpdateTable->execute();

            $sqlEndOccupation = "UPDATE tbl_occupations SET end_time = CURRENT_TIMESTAMP WHERE table_id = :table_id AND end_time IS NULL";
            $stmtEndOccupation = $conexion->prepare($sqlEndOccupation);
            $stmtEndOccupation->bindParam(':table_id', $tableId, PDO::PARAM_INT);
            $stmtEndOccupation->execute();
        }
    } catch (PDOException $e) {
        die("Error al actualizar la mesa: " . $e->getMessage());
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Obtener las mesas para mostrar
try {
    $sql = "SELECT table_id, status FROM tbl_tables WHERE table_id BETWEEN 21 AND 30";
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener las mesas: " . $e->getMessage());
}
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
