<?php
// INICIA LA SESIÓN Y VALIDA EL ACCESO DEL ADMINISTRADOR.
session_start();
require_once __DIR__ . '/../includes/sesion.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SESSION['id_rol'] != 1) {
    header('Location: ../tecnico/dashboard.php');
    exit();
}

// CONEXIÓN A LA BASE DE DATOS
require_once '../config/conexion.php';

// CONSULTA DE ÓRDENES
$sql = "SELECT
            o.id_orden, o.id_estado,
            o.numero_orden,
            o.tipo_solicitud,
            o.servicio_solicitado,
            o.fecha_solicitud,
            o.fecha_programada,

            c.nombre AS cliente_nombre,
            c.apellido AS cliente_apellido,

            cs.nombre_categoria,

            u.nombre AS tecnico_nombre,
            u.apellido AS tecnico_apellido,

            e.nombre_estado
             FROM orden_servicio o
             INNER JOIN clientes c
            ON o.id_cliente = c.id_cliente
             INNER JOIN categorias_servicio cs
            ON o.id_categoria = cs.id_categoria
             LEFT JOIN usuarios u
            ON o.id_tecnico = u.id_usuario
             INNER JOIN estados_orden e
            ON o.id_estado = e.id_estado
             ORDER BY o.id_orden DESC";

require __DIR__ . '/../includes/filtrar_ordenes.php';
require_once __DIR__ . '/../includes/resumen.php';
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>SGOSE - Órdenes</title>

        <link rel="stylesheet" href="../css/admin.css?v=2" />
    </head>

    <body>
        <!-- MENÚ LATERAL DE ADMINISTRACIÓN -->
        <div class="sidebar">
            <h2>⚡ SGOSE</h2>

            <a href="dashboard.php">Dashboard</a>
            <a href="clientes.php">Clientes</a>
            <a href="ordenes.php" class="activo">Órdenes</a>
            <a href="usuarios.php">Usuarios</a>
            <a href="historial.php">Historial</a>

            <a href="../logout.php">Cerrar sesión</a>
        </div>

        <div class="contenido">
            <!-- ENCABEZADO Y ACCESO PARA CREAR ÓRDENES -->
            <div class="encabezado">
                <div>
                    <h1>Órdenes de servicio</h1>
                    <p>Consulta y administra las órdenes registradas.</p>
                </div>

                <!-- BOTÓN PARA UNA NUEVA ORDEN -->
                <a href="nueva_orden.php" class="btn-nuevo">+ Nueva orden</a>
            </div>

            <!-- RESUMEN DE ÓRDENES POR ESTADO -->
            <div class="tarjetas-dashboard">
                <div class="tarjeta">
                    <h3>Pendientes</h3>
                    <p><?= $resumen['pendientes'] ?></p>
                </div>

                <div class="tarjeta">
                    <h3>En proceso</h3>
                    <p><?= $resumen['proceso'] ?></p>
                </div>

                <div class="tarjeta">
                    <h3>Finalizadas</h3>
                    <p><?= $resumen['finalizadas'] ?></p>
                </div>

                <div class="tarjeta">
                    <h3>Canceladas</h3>
                    <p><?= $resumen['canceladas'] ?></p>
                </div>
            </div>

            <?php require __DIR__ . '/../includes/formulario_filtros.php'; ?>
            <!-- TABLA DE ÓRDENES Y ACCIONES -->
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>N° Orden</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Categoría</th>
                            <th>Servicio</th>
                            <th>Técnico</th>
                            <th>Estado</th>
                            <th>Fecha programada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

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
                                <?php echo htmlspecialchars($orden['tipo_solicitud']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($orden['nombre_categoria']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($orden['servicio_solicitado']); ?>
                            </td>

                            <td>
                                <?php if (!empty($orden['tecnico_nombre'])) {
                    echo htmlspecialchars(
                        $orden['tecnico_nombre'] . ' ' . $orden['tecnico_apellido']
                    );
                } else {
                    echo 'Sin asignar';
                } ?>
                            </td>

                            <td>
                                <?php echo etiqueta_estado($orden['nombre_estado']); ?>
                            </td>

                            <td>
                                <?php if (!empty($orden['fecha_programada'])) {
                    echo date('d/m/Y h:i A', strtotime($orden['fecha_programada']));
                } else {
                    echo 'Sin programar';
                } ?>
                            </td>

                            <td>
                                <a class="btn" href="ver_orden.php?id=<?php echo $orden['id_orden']; ?>">Ver</a>
                                <?php if ( !in_array((int) $orden['id_estado'], [7, 8], true) ) { ?>
                                <a class="btn" href="editar_orden.php?id=<?= (int) $orden['id_orden'] ?>">Editar</a>
                                <?php } ?>
                            </td>
                        </tr>

                        <?php } ?>

                        <?php } else { ?>

                        <tr>
                            <td colspan="9">No hay órdenes registradas.</td>
                        </tr>

                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>
