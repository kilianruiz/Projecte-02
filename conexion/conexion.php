<?php
$dbServer = "localhost";
$dbUser = "root";
$dbPsswd = "";
$dbName = "db_mokadictos";


try{
    $conexion = @mysqli_connect($dbServer, $dbUser, $dbPsswd, $dbName);
}
catch (Exception $e)
{
    echo "Error de conexion:" . $e->getMessage();
}
   