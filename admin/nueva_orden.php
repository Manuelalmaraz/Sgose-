<?php

session_start();

// PERMITE EL ACCESO ÚNICAMENTE A USUARIOS ADMINISTRADORES.
if (!isset($_SESSION["id_usuario"]) || $_SESSION["id_rol"] != 1) {
    header("Location: ../index.php");
    exit;
}

require_once "../config/conexion.php";

// CONSULTAS PARA CARGAR EN EL FORMULARIO LOS CLIENTES, CATEGORÍAS Y TÉCNICOS DISPONIBLES.
$sql_clientes = "SELECT id_cliente, nombre, apellido 
                 FROM clientes 
                 ORDER BY nombre ASC";
$clientes = $conexion->query($sql_clientes);
$clientes = $conexion->query($sql_clientes);

$sql_categorias = "SELECT id_categoria, nombre_categoria 
                   FROM categorias_servicio 
                   ORDER BY nombre_categoria ASC";
$categorias = $conexion->query($sql_categorias);
$categorias = $conexion->query($sql_categorias);

$sql_tecnicos = "SELECT id_usuario, nombre, apellido
                 FROM usuarios
                 WHERE id_rol = 2 AND estado = 1
                 ORDER BY nombre ASC";

$tecnicos = $conexion->query($sql_tecnicos);

$error = "";

// PROCESA LOS DATOS DEL FORMULARIO CUANDO SE ENVÍA MEDIANTE POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // RECUPERA LOS CAMPOS ENVIADOS; ELIMINA ESPACIOS INNECESARIOS EN LOS TEXTOS LIBRES.
    $id_cliente = $_POST["id_cliente"];
    $tipo_solicitud = $_POST["tipo_solicitud"];
    $id_categoria = $_POST["id_categoria"];
    $servicio_solicitado = trim($_POST["servicio_solicitado"]);
    $id_tecnico = $_POST["id_tecnico"];
    $fecha_programada = $_POST["fecha_programada"];
    $observaciones = trim($_POST["observaciones"]);

    // DEFINE EL ESTADO INICIAL DE LA ORDEN (2 CORRESPONDE A "ASIGNADA" EN LA BASE DE DATOS).
    $id_estado = 2;

    // GENERA UN NÚMERO DE ORDEN USANDO LA FECHA Y HORA ACTUALES.
    $numero_orden = "OS-" . date("YmdHis");

    // COMPRUEBA QUE ESTÉN COMPLETOS LOS CAMPOS OBLIGATORIOS ANTES DE GUARDAR.
    if (
        empty($id_cliente) ||
        empty($tipo_solicitud) ||
        empty($id_categoria) ||
        empty($servicio_solicitado) ||
        empty($id_tecnico)
    ) {
        $error = "Completa los campos obligatorios.";
    } else {

        // PREPARA LA INSERCIÓN DE LA ORDEN CON PARÁMETROS PARA EVITAR CONCATENAR VALORES EN EL SQL.
        $sql = "INSERT INTO orden_servicio
                (
                    numero_orden,
                    id_cliente,
                    id_categoria,
                    id_tecnico,
                    id_estado,
                    tipo_solicitud,
                    servicio_solicitado,
                    fecha_programada,
                    observaciones
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        $stmt->bind_param(
            "siiiissss",
            $numero_orden,
            $id_cliente,
            $id_categoria,
            $id_tecnico,
            $id_estado,
            $tipo_solicitud,
            $servicio_solicitado,
            $fecha_programada,
            $observaciones
        );

        if ($stmt->execute()) {

            // CONSERVA EL IDENTIFICADOR GENERADO PARA ASOCIARLO AL HISTORIAL DE LA ORDEN.
            $id_orden = $stmt->insert_id;

            // REGISTRA LA CREACIÓN COMO EL PRIMER MOVIMIENTO DEL HISTORIAL.
            $sql_historial = "INSERT INTO historial_orden
                              (
                                  id_orden,
                                  id_usuario,
                                  id_estado_anterior,
                                  id_estado_nuevo,
                                  observacion
                              )
                              VALUES (?, ?, NULL, ?, ?)";

            $stmt_historial = $conexion->prepare($sql_historial);

            $id_usuario = $_SESSION["id_usuario"];
            $observacion_historial = "Orden creada y asignada a técnico.";

            $stmt_historial->bind_param(
                "iiis",
                $id_orden,
                $id_usuario,
                $id_estado,
                $observacion_historial
            );

            $stmt_historial->execute();

            // AL COMPLETAR EL REGISTRO, REGRESA AL LISTADO CON UN MENSAJE DE CONFIRMACIÓN.
            header("Location: ordenes.php?mensaje=creada");
            exit;

        } else {
            $error = "No se pudo guardar la orden.";
        }
    }
}

$id_cliente_seleccionado = 0;

if (isset($_GET["id_cliente"]) && is_numeric($_GET["id_cliente"])) {
    $id_cliente_seleccionado = (int) $_GET["id_cliente"];
}


?>

<!-- INTERFAZ DEL ADMINISTRADOR PARA CREAR UNA ORDEN DE SERVICIO. -->
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nueva orden - SGOSE</title>

    <link rel="stylesheet" href="../css/admin.css?v=2">

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

                <!-- EL CLIENTE SE SELECCIONA DE LOS REGISTROS OBTENIDOS EN LA BASE DE DATOS. -->
                <div class="grupo-formulario">

                    <label>Cliente *</label>

                    <select name="id_cliente" required>

                        <option value="">Seleccione un cliente</option>

                        <?php while ($cliente = $clientes->fetch_assoc()) { ?>

                            <option
                                value="<?php echo $cliente["id_cliente"]; ?>"

                                <?php
                                if ($cliente["id_cliente"] == $id_cliente_seleccionado) {
                                    echo "selected";
                                }
                                ?>
                            >
                                <?php
                                echo htmlspecialchars(
                                    $cliente["nombre"] . " " . $cliente["apellido"]
                                );
                                ?>

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
                        <option value="Presupuesto">Presupuesto</option>

                    </select>

                </div>

                <!-- CATEGORÍA DEL SERVICIO, CARGADA DESDE LA BASE DE DATOS. -->
                <div class="grupo-formulario">

                    <label>Categoría *</label>

                    <select name="id_categoria" required>

                        <option value="">Seleccione una categoría</option>

                        <?php while ($categoria = $categorias->fetch_assoc()) { ?>

                            <option value="<?php echo $categoria["id_categoria"]; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $categoria["nombre_categoria"]
                                );
                                ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>

                <!-- DESCRIPCIÓN DEL TRABAJO O SERVICIO QUE SOLICITA EL CLIENTE. -->
                <div class="grupo-formulario">

                    <label>Servicio solicitado *</label>

                    <textarea
                        name="servicio_solicitado"
                        rows="4"
                        required
                    ></textarea>

                </div>

                <!-- ASIGNA LA ORDEN A UN TÉCNICO ACTIVO. -->
                <div class="grupo-formulario">

                    <label>Técnico *</label>

                    <select name="id_tecnico" required>

                        <option value="">Seleccione un técnico</option>

                        <?php while ($tecnico = $tecnicos->fetch_assoc()) { ?>

                            <option value="<?php echo $tecnico["id_usuario"]; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $tecnico["nombre"] . " " . $tecnico["apellido"]
                                );
                                ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>

                <!-- CAMPOS OPCIONALES PARA PROGRAMAR EL SERVICIO Y AÑADIR DETALLES ADICIONALES. -->
                <div class="grupo-formulario">

                    <label>Fecha programada</label>

                    <input
                        type="datetime-local"
                        name="fecha_programada"
                    >

                </div>

                <div class="grupo-formulario">

                    <label>Observaciones</label>

                    <textarea
                        name="observaciones"
                        rows="3"
                    ></textarea>

                </div>

                <!-- PERMITE CANCELAR EL REGISTRO O ENVIAR EL FORMULARIO. -->
                <div class="acciones-formulario">

                    <a href="ordenes.php" class="btn-secundario">
                        Cancelar
                    </a>

                    <button type="submit" class="btn-guardar">
                        Guardar orden
                    </button>

                </div>

            </form>

        </div>

    </main>

</div>

</body>
</html>