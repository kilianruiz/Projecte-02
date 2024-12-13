<?php
session_start();

// Verificar si el usuario está autenticado y es administrador
if ($_SESSION['role_name'] !== 'Administrador') {
    header('Location: ../index.php?error=2');
    exit();
}

include_once('../conexion/conexion.php');

if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no se estableció.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selección de Salas</title>
    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sancreek&display=swap" rel="stylesheet">
    <style>

        .editar {
        width: 100px; /* Ajusta según el tamaño deseado */
        height: auto; /* Mantener la proporción */
        }
    </style>
</head>
<body>
    <div><img src="../img/logo.webp" alt="Logo de la página" class="superpuesta"><br></div>
    <div class="container">
        <h1>A D M I N I S T R A C I Ó N </h1>
        <div class="room-sections">
            <!-- Panel de control -->
            <div class="room-category">
                <form action="./historial.php">
                    <button><img src="../img/admin1.png" alt="Terrazas" onclick="mostrarEstadoTerrazas()"></button>
                </form>
            </div>

            <!-- Gestion de edicion -->
            <div class="room-category">
            <form action="./gestionEdicion/crudMesas.php">
                    <button><img class="editar" src="../img/editar1.png" alt="Terrazas" onclick="mostrarEstadoTerrazas()"></button>
                </form>
            </div>
            <div class="room-category">
            <form action="./gestionEdicion/crudSalas.php">
                    <button><img class="editar" src="../img/editar1.png" alt="Terrazas" onclick="mostrarEstadoTerrazas()"></button>
                </form>
            </div>
        </div>
        <button class="logout-button" onclick="logout()">Cerrar Sesión</button>
    </div>


    <script src="../validaciones/funciones.js"></script>
    <script src="./validaciones/funcionesPaginaPrincipal.js"></script>

</body>
</html>

