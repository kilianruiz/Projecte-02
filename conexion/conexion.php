<?php
$dbServer = "localhost";
$dbUser = "root";
$dbPsswd = "";
$dbName = "db_mokadictos";

try {
    $conexion = new PDO('mysql:host=localhost;dbname=db_mokadictos', 'root', '');
}catch(Exception $e){
    echo "Error de conexión —-----> $e";
}
