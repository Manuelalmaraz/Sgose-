<?php
// INICIA LA SESIÓN.
session_start();

// VERIFICA QUE EL USUARIO HAYA INICIADO SESIÓN.
if (!isset($_SESSION["id_usuario"])) {
    // ENVÍA AL INICIO Y DETIENE EL CÓDIGO.
    header("Location: ../index.php");
    exit;
}

// PERMITE EL ACCESO SOLO AL TÉCNICO.
if ($_SESSION["id_rol"] != 2) {

    header("Location: ../admin/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <!-- VISTA EN OTROS DISPOSITIVOS . -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- TÍTULO  -->
    <title>SGOSE - Mis órdenes</title>

    <!-- CARGA LOS ESTILOS CSS . -->
    <link rel="stylesheet" href="../css/tecnico.css">
</head>

<body>

    <!-- MENÚ LATERAL DEL FORMULARIO -->
    <div class="sidebar">

        <h2>⚡ SGOSE</h2>

        <a href="dashboard.php">Dashboard</a>

        <!-- RESALTA LA SECCIÓN ACTUAL DEL MOUSE. -->
        <a href="ordenes.php" class="activo">Mis órdenes</a>

        <!-- CIERRA LA SESIÓN. -->
        <a href="../logout.php">Cerrar sesión</a>

    </div>

    <!-- CONTENIDO PRINCIPAL DE LAS ÓRDENES. -->
    <div class="contenido">

        <!-- TÍTULO  -->
        <div class="encabezado">
            <h1>Mis órdenes asignadas</h1>

            <p>
                Técnico:
                <!-- MUESTRA EL NOMBRE SIN INTERPRETAR HTML. -->
                <?php echo htmlspecialchars($_SESSION["nombre"]); ?>
            </p>
        </div>

        <!-- CONTROL DE BÚSQUEDA AÚN SIN CONECTAR. -->
        <div class="filtros">

            <!-- CAMPO PARA INGRESAR CLIENTE O NÚMERO DE ORDEN. -->
            <input
                type="text"
                placeholder="Buscar por cliente o número de orden"
            >

            <!--ESTADO DE LA ORDEN. -->
            <select>
                <option>Todos los estados</option>
                <option>Pendiente</option>
                <option>En proceso</option>
                <option>Finalizada</option>
            </select>

            <button>Buscar</button>

        </div>

        <!-- TABLA DE ÓRDENES ASIGNADAS AL TECNICO. -->
        <div class="tabla-contenedor">

            <table>

                <!-- TÍTULOS DE LAS COLUMNAS. -->
                <thead>
                    <tr>
                        <th>N° Orden</th>
                        <th>Cliente</th>
                        <th>Servicio</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <!-- ÓRDENES DE EJEMPLO CON DATOS FIJOS PARA PRUEBA. -->
                <tbody>

                    <tr>
                        <td>OS-001</td>
                        <td>Carlos Torres</td>
                        <td>Instalación de tomacorriente</td>
                        <td>03/10/2026</td>
                        <td>
                            <span class="estado pendiente">
                                Pendiente
                            </span>
                        </td>

                        <td>
                            <!-- ENLACES DE ACCIÓN  -->
                            <a href="#" class="btn">
                                Ver detalle
                            </a>

                            <a href="#" class="btn secundario">
                                Actualizar
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td>OS-002</td>
                        <td>Ana Casasola</td>
                        <td>Cambio de breaker</td>
                        <td>04/10/2026</td>

                        <td>
                            <span class="estado proceso">
                                En proceso
                            </span>
                        </td>

                        <td>
                            <a href="#" class="btn">
                                Ver detalle
                            </a>

                            <a href="#" class="btn secundario">
                                Actualizar
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td>OS-003</td>
                        <td>José Franco</td>
                        <td>Revisión eléctrica</td>
                        <td>05/10/2026</td>

                        <td>
                            <span class="estado finalizada">
                                Finalizada
                            </span>
                        </td>

                        <td>
                            <a href="#" class="btn">
                                Ver detalle
                            </a>
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</body>

</html>
