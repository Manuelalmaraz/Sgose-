<?php

// Zona horaria y excepciones para guardar movimientos de forma consistente.
date_default_timezone_set('America/Guatemala');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('SGOSE_DB_HOST') ?: 'localhost';
$usuario = getenv('SGOSE_DB_USER') ?: 'root';
$password = getenv('SGOSE_DB_PASSWORD') ?: '';
$base_datos = getenv('SGOSE_DB_NAME') ?: 'sgose';

$conexion = new mysqli(
    $host,
    $usuario,
    $password,
    $base_datos,
    (int) (getenv('SGOSE_DB_PORT') ?: 3306)
);

if ($conexion->connect_error) {
    die('Error de conexión a la base de datos.');
}

$conexion->set_charset('utf8mb4');
$conexion->query("SET time_zone = '-06:00'");
?>


