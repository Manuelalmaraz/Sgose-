<?php

$host = "localhost";
$usuario = "root";
$password = "";
$base_datos = "sgose";

$conexion = new mysqli(
    $host,
    $usuario,
    $password,
    $base_datos
);

if ($conexion->connect_error) {
    die("Error de conexión a la base de datos.");
}

$conexion->set_charset("utf8mb4");

?>