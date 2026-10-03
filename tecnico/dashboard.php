<?php
// INICIA LA SESIÓN.
session_start();

// VERIFICA QUE EL USUARIO INICIO SESIÓN.
if (!isset($_SESSION["id_usuario"])) {
    // ENVÍA AL INICIO Y DETIENE EL CÓDIGO.
    header("Location: ../index.php");
    exit;
}

// DA EL ACCESO SOLO AL TÉCNICO.
if ($_SESSION["id_rol"] != 2) {
    
    header("Location: ../admin/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- CONFIGURA LAS VISTAS EN DISTINTOS DISPOSITIVOS. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- LOS ESTILOS -->
    <title>SGOSE - Técnico</title>
    <link rel="stylesheet" href="../css/tecnico.css">


    
</head>

<body>

    <!-- MENÚ LATERAL. -->
    <div class="sidebar">

        <h2>⚡ SGOSE</h2>

        <a href="dashboard.php">Dashboard</a>

        <a href="ordenes.php">Mis órdenes</a>

        <!-- CERRAR LA SESIÓN. -->
        <a href="../logout.php">Cerrar sesión</a>

    </div>

    <!-- CONTENIDO PRINCIPAL DEL FORMULARIO . -->
    <div class="contenido">

        <!-- NOMBRE DEL USUARIO. -->
        <div class="encabezado">

            <h1>
                Bienvenido,
                <!-- MUESTRA EL NOMBRE SIN INTERPRETAR HTML. -->
                <?php echo htmlspecialchars($_SESSION["nombre"]); ?>
            </h1>

            <p>
                Panel del técnico
            </p>

        </div>

        <!-- RESUMEN DE ÓRDENES CON VALORES FIJOS PARA PRUEBA. -->
        <div class="tarjetas">

            <div class="tarjeta">
                <h3>Total asignadas</h3>
                <p>8</p>
            </div>

            <div class="tarjeta">
                <h3>Pendientes</h3>
                <p>3</p>
            </div>

            <div class="tarjeta">
                <h3>En proceso</h3>
                <p>2</p>
            </div>

            <div class="tarjeta">
                <h3>Finalizadas</h3>
                <p>3</p>
            </div>

        </div>

        <!-- TABLA DE ÓRDENES RECIENTES. -->
        <div class="tabla-contenedor">

            <h2>Órdenes asignadas recientemente</h2>

            <table>

                <!-- COLUMNAS . -->
                <thead>
                    <tr>
                        <th>N° Orden</th>
                        <th>Cliente</th>
                        <th>Servicio</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <!-- ÓRDENES DE EJEMPLO CON DATOS FIJOS PARA PRUEBA. -->
                <tbody>

                    <tr>
                        <td>OS-001</td>
                        <td>Carlos Vasquez</td>
                        <td>Instalación de tomacorriente</td>
                        <td>03/10/2026</td>
                        <td>
                            <span class="estado pendiente">
                                Pendiente
                            </span>
                        </td>
                        <td>
                            <a href="#" class="btn">
                                Ver
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td>OS-002</td>
                        <td>Ana De Leon</td>
                        <td>Cambio de breaker</td>
                        <td>04/10/2026</td>
                        <td>
                            <span class="estado proceso">
                                En proceso
                            </span>
                        </td>
                        <td>
                            <a href="#" class="btn">
                                Ver
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td>OS-003</td>
                        <td>José Vargas</td>
                        <td>Revisión eléctrica</td>
                        <td>05/10/2026</td>
                        <td>
                            <span class="estado finalizada">
                                Finalizada
                            </span>
                        </td>
                        <td>
                            <a href="#" class="btn">
                                Ver
                            </a>
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</body>
</html>
