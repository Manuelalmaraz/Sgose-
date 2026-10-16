<?php
// INICIA LA SESIÓN Y VALIDA EL ACCESO DEL ADMINISTRADOR.
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../index.php");
    exit;
}

if ($_SESSION["id_rol"] != 1) {
    header("Location: ../tecnico/dashboard.php");
    exit;
}

// CONECTA CON LA BASE DE DATOS.
require_once "../config/conexion.php";

// PREPARA EL MENSAJE PARA MOSTRAR ERRORES.
$mensaje = "";

// PROCESA EL FORMULARIO ENVIADO.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // OBTIENE Y LIMPIA LOS DATOS DEL CLIENTE.
    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $telefono = trim($_POST["telefono"]);
    $correo = trim($_POST["correo"]);
    $direccion = trim($_POST["direccion"]);

    // VALIDA LOS CAMPOS OBLIGATORIOS.
    if ($nombre === "" || $telefono === "") {
        $mensaje = "El nombre y teléfono son obligatorios.";
    } else {

        // PREPARA Y EJECUTA EL REGISTRO DEL CLIENTE.
        $sql = "INSERT INTO clientes
                (nombre, apellido, telefono, correo, direccion)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        $stmt->bind_param(
            "sssss",
            $nombre,
            $apellido,
            $telefono,
            $correo,
            $direccion
        );

        // REDIRIGE SI EL REGISTRO SE COMPLETA O MUESTRA UN ERROR.
        if ($stmt->execute()) {
            header("Location: clientes.php");
            exit;
        } else {
            $mensaje = "No se pudo registrar el cliente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <!-- CONFIGURA LA PÁGINA Y CARGA LOS ESTILOS -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SGOSE - Nuevo cliente</title>

    <link rel="stylesheet" href="../css/admin.css">
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

    <!-- ENCABEZADO DEL FORMULARIO -->
    <div class="encabezado">

        <div>
            <h1>Nuevo cliente</h1>
            <p>Registra la información del cliente.</p>
        </div>

    </div>

    <!-- ÁREA PRINCIPAL PARA INGRESAR LOS DATOS -->
    <!-- FORMULARIO PARA REGISTRAR UN CLIENTE -->
    <div class="formulario-contenedor">

        <!-- MUESTRA LOS ERRORES DE VALIDACIÓN O REGISTRO -->
        <?php if ($mensaje !== "") { ?>

            <div class="mensaje-error">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>

        <?php } ?>

        <form method="POST">

            <!-- CAMPO OBLIGATORIO: NOMBRE -->
            <div class="grupo-formulario">

                <label>Nombre *</label>

                <input
                    type="text"
                    name="nombre"
                    required
                >

            </div>

            <!-- CAMPO OPCIONAL: APELLIDO -->
            <div class="grupo-formulario">

                <label>Apellido</label>

                <input
                    type="text"
                    name="apellido"
                >

            </div>

            <!-- CAMPO OBLIGATORIO: TELÉFONO -->
            <div class="grupo-formulario">

                <label>Teléfono *</label>

                <input
                    type="text"
                    name="telefono"
                    required
                >

            </div>

            <!-- CAMPO OPCIONAL: CORREO -->
            <div class="grupo-formulario">

                <label>Correo</label>

                <input
                    type="email"
                    name="correo"
                >

            </div>

            <!-- CAMPO OPCIONAL: DIRECCIÓN -->
            <div class="grupo-formulario">

                <label>Dirección</label>

                <textarea
                    name="direccion"
                    rows="4"
                ></textarea>

            </div>

            <!-- BOTONES PARA GUARDAR O CANCELAR -->
            <div class="acciones-formulario">

                <button
                    type="submit"
                    class="btn-principal"
                >
                    Guardar cliente
                </button>

                <a
                    href="clientes.php"
                    class="btn secundario"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>