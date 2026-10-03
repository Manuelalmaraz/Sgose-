<?php

// RECUPERA LA SESIÓN.
session_start();

// EXIGE INICIAR SESIÓN.
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../index.php");
    exit;
}

// SOLO PERMITE EL ACCESO AL ADMINISTRADOR.
if ($_SESSION["id_rol"] != 1) {
    header("Location: ../tecnico/dashboard.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <!-- PERMITE TILDES Y CARACTERES ESPECIALES. -->
    <meta charset="UTF-8">

    <!-- TÍTULO DE LA PESTAÑA. -->
    <title>SGOSE - Administrador</title>
    <link rel="stylesheet" href="../css/style.css">

</head>

<body>

   
    <h1>SGOSE</h1>

    <!-- MUESTRA EL NOMBRE SIN INTERPRETARLO COMO HTML. -->
    <h2>
        Bienvenido, <?php echo htmlspecialchars($_SESSION["nombre"]); ?>
    </h2>

    <!-- MENSAJE QUE MUESTRA EL ROL DEL USUARIO. -->
    <p>Rol: Administrador</p>

    <hr>

    <!-- OPCIONES DEL MENÚ QUE VERA EL ADMIN -->
    <h3>Menú principal</h3>

    <ul>
        <li>Dashboard</li>
        <li>Clientes</li>
        <li>Órdenes de servicio</li>
        <li>Usuarios</li>
        <li>Historial</li>
    </ul>

    <hr>

    <!-- RESUMEN DE ÓRDENES. -->
    <h3>Resumen</h3>

    <!-- VALORES FIJOS, AÚN SIN CONSULTAR LA BASE DE DATOS. -->
    <p>Órdenes pendientes: 0</p>
    <p>Órdenes en proceso: 0</p>
    <p>Órdenes finalizadas: 0</p>

    
    <br>

    <!-- ENLACE PARA CERRAR SESIÓN. -->
    <a href="../logout.php">Cerrar sesión</a>

</body>

</html>
