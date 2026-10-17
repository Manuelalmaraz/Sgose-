<?php

// INICIA LA SESIÓN.
session_start();

// VERIFICA QUE EL USUARIO HAYA INICIADO SESIÓN.
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../index.php");
    exit;
}

// PERMITE EL ACCESO SOLO AL TÉCNICO.
if ($_SESSION["id_rol"] != 2) {
    header("Location: ../admin/dashboard.php");
    exit;
}

// CONEXIÓN A LA BASE DE DATOS.
require_once "../config/conexion.php";

// VERIFICA QUE SE RECIBA EL ID DE LA ORDEN.
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: ordenes.php");
    exit;
}

$id_orden = (int) $_GET["id"];
$id_tecnico = $_SESSION["id_usuario"];

// BUSCA LA ORDEN.
// TAMBIÉN VERIFICA QUE PERTENEZCA AL TÉCNICO QUE INICIÓ SESIÓN.
$sql = "SELECT
            o.id_orden,
            o.numero_orden,
            o.tipo_solicitud,
            o.servicio_solicitado,
            o.fecha_solicitud,
            o.fecha_programada,
            o.diagnostico,
            o.trabajo_realizado,
            o.materiales_utilizados,
            o.observaciones,
            o.costo_servicio,
            o.fecha_finalizacion,

            c.nombre AS cliente_nombre,
            c.apellido AS cliente_apellido,
            c.telefono AS cliente_telefono,
            c.direccion AS cliente_direccion,

            cs.nombre_categoria,

            e.nombre_estado

        FROM orden_servicio o

        INNER JOIN clientes c
            ON o.id_cliente = c.id_cliente

        INNER JOIN categorias_servicio cs
            ON o.id_categoria = cs.id_categoria

        INNER JOIN estados_orden e
            ON o.id_estado = e.id_estado

        WHERE o.id_orden = ?
        AND o.id_tecnico = ?";

$stmt = $conexion->prepare($sql);

// ASOCIA EL ID DE LA ORDEN Y EL ID DEL TÉCNICO A LOS PARÁMETROS DE LA CONSULTA.
$stmt->bind_param(
    "ii",
    $id_orden,
    $id_tecnico
);

// EJECUTA LA CONSULTA Y OBTIENE EL RESULTADO.
$stmt->execute();

$resultado = $stmt->get_result();

// SI LA ORDEN NO EXISTE O NO PERTENECE AL TÉCNICO.
if ($resultado->num_rows == 0) {
    header("Location: ordenes.php");
    exit;
}

$orden = $resultado->fetch_assoc();

?>
<!-- MUESTRA LA INFORMACIÓN DE LA ORDEN ASIGNADA AL TÉCNICO. -->
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detalle de orden - SGOSE</title>

    <link rel="stylesheet" href="../css/tecnico.css">

</head>

<body>

<div class="contenedor-tecnico">

    <aside class="sidebar">

        <!-- MENÚ DE NAVEGACIÓN DEL TÉCNICO. -->
        <h2>SGOSE</h2>

        <a href="dashboard.php">Dashboard</a>
        <a href="ordenes.php">Mis órdenes</a>
        <a href="../logout.php">Cerrar sesión</a>

    </aside>

    <main class="contenido">

        <!-- ENCABEZADO CON EL NÚMERO DE ORDEN Y EL ACCESO AL LISTADO. -->
        <div class="encabezado-pagina">

            <div>

                <h1>
                    Orden
                    <?php echo htmlspecialchars($orden["numero_orden"]); ?>
                </h1>

                <p>Información de la orden asignada.</p>

            </div>

            <a href="ordenes.php" class="btn">
                Volver
            </a>

        </div>


        <div class="detalle-orden">

            <!-- DATOS DEL SERVICIO, SU CATEGORÍA, ESTADO Y DESCRIPCIÓN. -->
            <div class="detalle-grupo">

                <h3>Información del servicio</h3>

                <p>
                    <strong>Tipo de solicitud:</strong>
                    <?php echo htmlspecialchars($orden["tipo_solicitud"]); ?>
                </p>

                <p>
                    <strong>Categoría:</strong>
                    <?php echo htmlspecialchars($orden["nombre_categoria"]); ?>
                </p>

                <p>
                    <strong>Estado:</strong>
                    <?php echo htmlspecialchars($orden["nombre_estado"]); ?>
                </p>

                <p>
                    <strong>Servicio solicitado:</strong><br>

                    <?php
                    echo nl2br(
                        htmlspecialchars($orden["servicio_solicitado"])
                    );
                    ?>
                </p>

            </div>

            <!-- DATOS DE CONTACTO Y DOMICILIO DEL CLIENTE. -->
            <div class="detalle-grupo">

                <h3>Cliente</h3>

                <p>
                    <strong>Nombre:</strong>

                    <?php
                    echo htmlspecialchars(
                        $orden["cliente_nombre"] . " " .
                        $orden["cliente_apellido"]
                    );
                    ?>
                </p>

                <p>
                    <strong>Teléfono:</strong>
                    <?php echo htmlspecialchars($orden["cliente_telefono"]); ?>
                </p>

                <p>
                    <strong>Dirección:</strong>

                    <?php
                    echo !empty($orden["cliente_direccion"])
                        ? htmlspecialchars($orden["cliente_direccion"])
                        : "No registrada";
                    ?>
                </p>

            </div>

            <!-- FECHAS DE SOLICITUD Y PROGRAMACIÓN DEL SERVICIO. -->
            <div class="detalle-grupo">

                <h3>Programación</h3>

                <p>
                    <strong>Fecha de solicitud:</strong>

                    <?php
                    echo date(
                        "d/m/Y H:i",
                        strtotime($orden["fecha_solicitud"])
                    );
                    ?>
                </p>

                <p>
                    <strong>Fecha programada:</strong>

                    <?php
                    if (!empty($orden["fecha_programada"])) {

                        echo date(
                            "d/m/Y H:i",
                            strtotime($orden["fecha_programada"])
                        );

                    } else {

                        echo "Sin programar";

                    }
                    ?>
                </p>

            </div>

            <!-- OBSERVACIONES REGISTRADAS PARA LA ORDEN. -->
            <div class="detalle-grupo">

                <h3>Observaciones</h3>

                <p>

                    <?php
                    if (!empty($orden["observaciones"])) {

                        echo nl2br(
                            htmlspecialchars($orden["observaciones"])
                        );

                    } else {

                        echo "Sin observaciones.";

                    }
                    ?>

                </p>

            </div>

        </div>

    </main>

</div>

</body>
</html>
