<?php
// INICIA LA SESIÓN.
session_start();
require_once __DIR__ . '/../includes/sesion.php';

// VERIFICA QUE EL USUARIO HAYA INICIADO SESIÓN.
if (!isset($_SESSION['id_usuario'])) {
    // ENVÍA AL INICIO Y DETIENE EL CÓDIGO.
    header('Location: ../index.php');
    exit();
}

// PERMITE EL ACCESO SOLO AL TÉCNICO.
if ($_SESSION['id_rol'] != 2) {
    header('Location: ../admin/dashboard.php');
    exit();
}

require_once '../config/conexion.php';

$id_tecnico = $_SESSION['id_usuario'];

$sql = "SELECT
            o.id_orden, o.id_estado,
            o.numero_orden,
            o.tipo_solicitud,
            o.servicio_solicitado,
            o.fecha_programada,

            c.nombre AS cliente_nombre,
            c.apellido AS cliente_apellido,

            cs.nombre_categoria,

            e.nombre_estado
             FROM orden_servicio o
             INNER JOIN clientes c
            ON o.id_cliente = c.id_cliente
             INNER JOIN categorias_servicio cs
            ON o.id_categoria = cs.id_categoria
             INNER JOIN estados_orden e
            ON o.id_estado = e.id_estado
             WHERE o.id_tecnico = ?
             ORDER BY o.id_orden DESC";

require __DIR__ . '/../includes/filtrar_ordenes.php';
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <!-- VISTA EN OTROS DISPOSITIVOS . -->
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <!-- TÍTULO  -->
        <title>SGOSE - Mis órdenes</title>

        <!-- CARGA LOS ESTILOS CSS . -->
        <link rel="stylesheet" href="../css/tecnico.css" />
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
                    <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                </p>
            </div>

            <?php require __DIR__ . '/../includes/formulario_filtros.php'; ?>
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

                    <!-- DESCRIPCION DE LAS ORDENES  -->
                    <tbody>
                        <?php if ($resultado->num_rows > 0) { ?>

                        <?php while ($orden = $resultado->fetch_assoc()) { ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($orden['numero_orden']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars(
                            $orden['cliente_nombre'] . ' ' . $orden['cliente_apellido']
                        ); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($orden['servicio_solicitado']); ?>
                            </td>

                            <td>
                                <?php if (!empty($orden['fecha_programada'])) {
                            echo date('d/m/Y h:i A', strtotime($orden['fecha_programada']));
                        } else {
                            echo 'Sin programar';
                        } ?>
                            </td>

                            <td>
                                <span>
                                    <?php echo etiqueta_estado($orden['nombre_estado']); ?>
                                </span>
                            </td>

                            <td>
                                <a href="ver_orden.php?id=<?php echo $orden['id_orden']; ?>" class="btn">Ver detalle</a>
                                <!-- La edición utiliza el detalle autorizado y su historial existente. -->
                                <?php if (in_array((int) $orden['id_estado'], [3, 5, 6], true)) { ?>
                                <a class="btn" href="ver_orden.php?id=<?= (int) $orden[ 'id_orden' ] ?>#editar-orden">
                                    Editar
                                </a>
                                <?php } ?>
                            </td>
                        </tr>

                        <?php } ?>

                        <?php } else { ?>

                        <tr>
                            <td colspan="6">No tienes órdenes asignadas.</td>
                        </tr>

                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>
