<?php
// INICIA LA SESIÓN.
session_start();
require_once __DIR__ . '/../includes/sesion.php';

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
require_once __DIR__ . '/../includes/resumen.php';
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
                <?php echo htmlspecialchars($_SESSION["nombre"]);
?>
            </h1>

            <p>
                Panel del técnico
            </p>

        </div>

        <!-- RESUMEN DE ÓRDENES CONSULTADO EN LA BASE DE DATOS. -->
        <div class="tarjetas">

            <div class="tarjeta">
                <h3>Total asignadas</h3>
                <p><?= $resumen["total"] ?></p>
            </div>

            <div class="tarjeta">
                <h3>Pendientes</h3>
                <p><?= $resumen["pendientes"] ?></p>
            </div>

            <div class="tarjeta">
                <h3>En proceso</h3>
                <p><?= $resumen["proceso"] ?></p>
            </div>

            <div class="tarjeta">
                <h3>Finalizadas</h3>
                <p><?= $resumen["finalizadas"] ?></p>
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

                <!-- ÓRDENES RECIENTES CONSULTADAS PARA EL TÉCNICO ACTUAL. -->
                <tbody>
<?php
$stmt = $conexion->prepare('SELECT o.*, c.nombre, c.apellido, e.nombre_estado FROM orden_servicio o JOIN clientes c ON c.id_cliente=o.id_cliente JOIN estados_orden e ON e.id_estado=o.id_estado WHERE o.id_tecnico=? ORDER BY o.id_orden DESC LIMIT 5');
$stmt->bind_param('i', $tecnico_panel); $stmt->execute(); $recientes=$stmt->get_result();
while ($fila=$recientes->fetch_assoc()) { ?>
<tr><td><?= htmlspecialchars($fila['numero_orden']) ?></td><td><?= htmlspecialchars($fila['nombre'].' '.$fila['apellido']) ?></td><td><?= htmlspecialchars($fila['servicio_solicitado']) ?></td><td><?= htmlspecialchars($fila['fecha_solicitud']) ?></td><td><?= htmlspecialchars($fila['nombre_estado']) ?></td><td><a class="btn" href="ver_orden.php?id=<?= (int)$fila['id_orden'] ?>">Ver</a></td></tr>
<?php } if ($recientes->num_rows === 0) { ?><tr><td colspan="6">No tienes órdenes asignadas.</td></tr><?php } ?>
</tbody>

            </table>

        </div>

    </div>

</body>
</html>




