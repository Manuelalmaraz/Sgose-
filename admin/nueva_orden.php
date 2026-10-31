<?php

session_start();
require_once __DIR__ . '/../includes/sesion.php';

// PERMITE EL ACCESO ÚNICAMENTE A USUARIOS ADMINISTRADORES.
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/conexion.php';

// CONSULTAS PARA CARGAR EN EL FORMULARIO LOS CLIENTES, CATEGORÍAS Y TÉCNICOS DISPONIBLES.
$sql_clientes = "SELECT id_cliente, nombre, apellido 
                 FROM clientes 
                 ORDER BY nombre ASC";
$clientes = $conexion->query($sql_clientes);

$sql_categorias = "SELECT id_categoria, nombre_categoria
             FROM categorias_servicio
             ORDER BY nombre_categoria ASC";
$categorias = $conexion->query($sql_categorias);

$sql_tecnicos = "SELECT id_usuario, nombre, apellido
             FROM usuarios
             WHERE id_rol = 2 AND estado = 1
             ORDER BY nombre ASC";

$tecnicos = $conexion->query($sql_tecnicos);

$error = '';

// Guarda con validación compartida y una transacción.
require __DIR__ . '/../includes/crear_orden.php';

$id_cliente_seleccionado = (int) ($_POST['id_cliente'] ?? 0);

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' &&
    isset($_GET['id_cliente']) &&
    is_numeric($_GET['id_cliente'])
) {
    $id_cliente_seleccionado = (int) $_GET['id_cliente'];
}
?>

<!-- INTERFAZ DEL ADMINISTRADOR PARA CREAR UNA ORDEN DE SERVICIO. -->
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>Nueva orden - SGOSE</title>

        <link rel="stylesheet" href="../css/admin.css?v=2" />
    </head>

    <body>
        <div class="contenedor-admin">
            <aside class="sidebar">
                <h2>SGOSE</h2>

                <a href="dashboard.php">Dashboard</a>
                <a href="clientes.php">Clientes</a>
                <a href="ordenes.php">Órdenes</a>
                <a href="usuarios.php">Usuarios</a>
                <a href="historial.php">Historial</a>
                <a href="../logout.php">Cerrar sesión</a>
            </aside>

            <main class="contenido">
                <!-- TÍTULO Y DESCRIPCIÓN DE LA PÁGINA. -->
                <div class="encabezado-pagina">
                    <div>
                        <h1>Nueva orden</h1>
                        <p>Registrar una nueva solicitud de servicio.</p>
                    </div>
                </div>

                <div class="formulario-contenedor">
                    <!-- MUESTRA LOS ERRORES DE VALIDACIÓN O GUARDADO, SI EXISTEN. -->
                    <?php if (!empty($error)) { ?>

                    <div class="mensaje-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>

                    <?php } ?>

                    <form method="POST">
                        <input type="hidden" name="csrf" value="<?= escapar( token_formulario() ) ?>" />

                        <!-- EL CLIENTE SE SELECCIONA DE LOS REGISTROS OBTENIDOS EN LA BASE DE DATOS. -->
                        <div class="grupo-formulario">
                            <label>Cliente *</label>

                            <select name="id_cliente" required>
                                <option value="">Seleccione un cliente</option>

                                <?php while ($cliente = $clientes->fetch_assoc()) { ?>

                                <option value="<?php echo $cliente['id_cliente']; ?>" <?php if ($cliente['id_cliente'] == $id_cliente_seleccionado) {
                                    echo 'selected';
                                } ?>>
                                    <?php echo htmlspecialchars(
                                    $cliente['nombre'] . ' ' . $cliente['apellido']
                                ); ?>
                                </option>

                                <?php } ?>
                            </select>
                            <a href="nuevo_cliente.php?desde=orden" class="btn-secundario">
                                + Nuevo cliente
                            </a>
                        </div>

                        <!-- TIPO DE SOLICITUD QUE SE REGISTRARÁ EN LA ORDEN. -->
                        <div class="grupo-formulario">
                            <label>Tipo de solicitud *</label>

                            <select name="tipo_solicitud" required>
                                <option value="Servicio">Servicio</option>
                                <option value="Presupuesto" <?= ($_POST['tipo_solicitud'] ?? '') === 'Presupuesto' ? 'selected' : '' ?>>Presupuesto</option>
                            </select>
                        </div>

                        <!-- CATEGORÍA DEL SERVICIO, CARGADA DESDE LA BASE DE DATOS. -->
                        <div class="grupo-formulario">
                            <label>Categoría *</label>

                            <select name="id_categoria" required>
                                <option value="">Seleccione una categoría</option>

                                <?php while ($categoria = $categorias->fetch_assoc()) { ?>

                                <option value="<?php echo $categoria['id_categoria']; ?>" <?= ($_POST[ 'id_categoria' ] ?? 0) == $categoria['id_categoria'] ? 'selected' : '' ?>>
                                    <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
                                </option>

                                <?php } ?>
                            </select>
                        </div>

                        <!-- DESCRIPCIÓN DEL TRABAJO O SERVICIO QUE SOLICITA EL CLIENTE. -->
                        <div class="grupo-formulario">
                            <label>Servicio solicitado *</label>

                            <textarea name="servicio_solicitado" rows="4" required>
<?= escapar($_POST['servicio_solicitado'] ?? '') ?></textarea>
                        </div>

                        <!-- ASIGNA LA ORDEN A UN TÉCNICO ACTIVO. -->
                        <div class="grupo-formulario">
                            <label>Técnico *</label>

                            <select name="id_tecnico" required>
                                <option value="">Seleccione un técnico</option>

                                <?php while ($tecnico = $tecnicos->fetch_assoc()) { ?>

                                <option value="<?php echo $tecnico['id_usuario']; ?>" <?= ($_POST[ 'id_tecnico' ] ?? 0) == $tecnico['id_usuario'] ? 'selected' : '' ?>>
                                    <?php echo htmlspecialchars(
                                    $tecnico['nombre'] . ' ' . $tecnico['apellido']
                                ); ?>
                                </option>

                                <?php } ?>
                            </select>
                        </div>

                        <!-- CAMPOS OPCIONALES PARA PROGRAMAR EL SERVICIO Y AÑADIR DETALLES ADICIONALES. -->
                        <!-- Calendario, hora y minutos en cuadros alineados, sin el desplazamiento del selector combinado. -->
                        <div class="programacion-alineada">
                            <div class="grupo-formulario">
                                <label for="fecha_programada">Fecha programada</label>
                                <input
                                    type="date"
                                    id="fecha_programada"
                                    name="fecha_programada"
                                    value="<?= escapar($_POST['fecha_programada'] ?? '') ?>"
                                />
                            </div>
                            <!-- Hora escrita en un solo cuadro, en formato de 12 horas con AM/PM. -->
                            <div class="grupo-formulario">
                                <label for="hora_programada">Hora programada</label>
                                <input
                                    type="text"
                                    id="hora_programada"
                                    name="hora_programada"
                                    placeholder="02:30 PM"
                                    maxlength="8"
                                    autocomplete="off"
                                    value="<?= escapar($_POST['hora_programada'] ?? '') ?>"
                                />
                            </div>
                        </div>
                        <div class="grupo-formulario">
                            <label>Observaciones</label>

                            <textarea name="observaciones" rows="3"><?= escapar($_POST['observaciones'] ?? '') ?></textarea>
                        </div>

                        <!-- PERMITE CANCELAR EL REGISTRO O ENVIAR EL FORMULARIO. -->
                        <div class="acciones-formulario">
                            <a href="ordenes.php" class="btn-secundario">Cancelar</a>

                            <button type="submit" class="btn-guardar">Guardar orden</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </body>
</html>
