<?php

// INICIA LA SESIÓN Y VALIDA EL ACCESO DEL ADMINISTRADOR
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

// CONSULTA DE CLIENTES
$buscar = trim($_GET['buscar'] ?? '');
$patron = '%' . $buscar . '%';
$stmt = $conexion->prepare(
    'SELECT *
             FROM clientes
             WHERE CONCAT_WS(" ",nombre,apellido) LIKE ? OR telefono LIKE ?
             ORDER BY id_cliente DESC'
);
$stmt->bind_param('ss', $patron, $patron);
$stmt->execute();
$resultado = $stmt->get_result();

// TOTAL DE CLIENTES
$total_clientes = $conexion->query('SELECT COUNT(*) AS total FROM clientes')->fetch_assoc()[
    'total'
];
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>SGOSE - Clientes</title>

        <link rel="stylesheet" href="../css/admin.css" />
    </head>

    <body>
        <!-- MENÚ LATERAL DE ADMINISTRACIÓN -->
        <div class="sidebar">
            <h2>⚡ SGOSE</h2>

            <a href="dashboard.php">Dashboard</a>
            <a href="clientes.php" class="activo">Clientes</a>
            <a href="ordenes.php">Órdenes</a>
            <a href="usuarios.php">Usuarios</a>
            <a href="historial.php">Historial</a>

            <a href="../logout.php">Cerrar sesión</a>
        </div>

        <div class="contenido">
            <!-- ENCABEZADO Y ACCESO PARA CREAR CLIENTES -->
            <div class="encabezado">
                <div>
                    <h1>Gestión de clientes</h1>
                    <p>Consulta y administra los clientes registrados.</p>
                </div>

                <a href="nuevo_cliente.php" class="btn-principal">+ Nuevo cliente</a>
            </div>

            <!-- RESUMEN DEL TOTAL DE CLIENTES -->
            <div class="resumen">
                <div class="tarjeta">
                    <h3>Total de clientes</h3>
                    <p><?php echo $total_clientes; ?></p>
                </div>
            </div>

            <!-- BÚSQUEDA DE CLIENTES -->
            <form class="filtros" method="get">
                <input
                    type="text"
                    name="buscar"
                    value="<?= escapar($buscar) ?>"
                    aria-label="Nombre o teléfono"
                    placeholder="Buscar cliente por nombre o teléfono"
                />

                <button>Buscar</button>
            </form>

            <?php if ( isset($_GET['eliminado']) ) { ?>
            <p role="status">Cliente eliminado correctamente.</p>
            <?php } ?>
            <!-- LISTADO DE CLIENTES Y ACCIONES -->
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Dirección</th>
                            <th>Fecha de registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($cliente = $resultado->fetch_assoc()) { ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($cliente['telefono']); ?>
                            </td>

                            <td>
                                <?php echo escapar($cliente['correo']); ?>
                            </td>

                            <td>
                                <?php echo escapar($cliente['direccion']); ?>
                            </td>

                            <td>
                                <?php echo date('d/m/Y', strtotime($cliente['fecha_registro'])); ?>
                            </td>

                            <td>
                                <a href="editar_cliente.php?id=<?php echo $cliente['id_cliente']; ?>" class="btn">Editar</a>

                                <a href="ordenes.php?cliente=<?= (int) $cliente[ 'id_cliente' ] ?>" class="btn secundario">
                                    Historial
                                </a>
                                <a href="eliminar_cliente.php?id=<?= (int) $cliente[ 'id_cliente' ] ?>" class="btn">
                                    Eliminar
                                </a>
                            </td>
                        </tr>

                        <?php }
            if (
                !$resultado->num_rows
            ) { ?>
                        <tr>
                            <td colspan="6">No hay clientes que coincidan.</td>
                        </tr>
                        <?php }
            ?>
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>
