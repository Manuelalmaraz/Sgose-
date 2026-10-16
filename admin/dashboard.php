<?php
// INICIA LA SESIÓN Y VALIDA EL ACCESO DEL ADMINISTRADOR.
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../index.php");
    exit;
}

if ($_SESSION["id_rol"] != 1) {
    header("Location: ../tecnico/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SGOSE - Administrador</title>

    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>

<!-- MENÚ LATERAL DE ADMINISTRACIÓN -->
<div class="sidebar">

    <h2>⚡ SGOSE</h2>

    <a href="dashboard.php" class="activo">Dashboard</a>
    <a href="clientes.php">Clientes</a>
    <a href="ordenes.php">Órdenes</a>
    <a href="usuarios.php">Usuarios</a>
    <a href="historial.php">Historial</a>

    <a href="../logout.php">Cerrar sesión</a>

</div>

<div class="contenido">

    <!-- BIENVENIDA AL ADMINISTRADOR -->
    <div class="encabezado">

        <div>
            <h1>
                Bienvenido,
                <?php echo htmlspecialchars($_SESSION["nombre"]); ?>
            </h1>

            <p>Panel general del administrador</p>
        </div>

    </div>

    <!-- RESUMEN DE ÓRDENES Y CLIENTES -->
    <div class="tarjetas-dashboard">

        <div class="tarjeta">
            <h3>Órdenes pendientes</h3>
            <p>5</p>
        </div>

        <div class="tarjeta">
            <h3>En proceso</h3>
            <p>3</p>
        </div>

        <div class="tarjeta">
            <h3>Finalizadas</h3>
            <p>8</p>
        </div>

        <div class="tarjeta">
            <h3>Clientes</h3>
            <p>12</p>
        </div>

    </div>

    <!-- DESCRIPCIÓN GENERAL DEL SISTEMA -->
    <div class="tabla-contenedor">

        <h2>Resumen del sistema</h2>

        <p>
            Desde este panel puedes administrar clientes,
            órdenes de servicio, usuarios e historial.
        </p>

    </div>

</div>

</body>
</html>