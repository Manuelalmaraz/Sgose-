<?php
// INICIA LA SESIÓN DEL USUARIO.
session_start();

// REDIRIGE SI NO HAY UNA SESIÓN ACTIVA.
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../index.php");
    exit;
}

// PERMITE EL ACCESO ÚNICAMENTE AL ADMINISTRADOR.
if ($_SESSION["id_rol"] != 1) {
    header("Location: ../tecnico/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <!-- CONFIGURA LA PÁGINA Y CARGA LOS ESTILOS -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SGOSE - Usuarios</title>

    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>

<!-- MENÚ LATERAL Y NAVEGACIÓN DEL ADMINISTRADOR -->
<div class="sidebar">

    <h2>⚡ SGOSE</h2>

    <a href="dashboard.php">Dashboard</a>
    <a href="clientes.php">Clientes</a>
    <a href="ordenes.php">Órdenes</a>
    <a href="usuarios.php" class="activo">Usuarios</a>
    <a href="historial.php">Historial</a>

    <a href="../logout.php">Cerrar sesión</a>

</div>

<div class="contenido">

    <!-- TÍTULO Y ACCESO PARA REGISTRAR UN USUARIO -->
    <div class="encabezado">

        <div>
            <h1>Gestión de usuarios</h1>
            <p>Administra los usuarios internos del sistema.</p>
        </div>

        <a href="nuevo_cliente.php" class="btn-principal">
             + Nuevo cliente
        </a>

    </div>

    <!-- RESUMEN DE USUARIOS POR TIPO -->
    <div class="tarjetas-dashboard">

        <!-- CANTIDAD TOTAL DE USUARIOS -->
        <div class="tarjeta">
            <h3>Total de usuarios</h3>
            <p>3</p>
        </div>

        <!-- CANTIDAD DE ADMINISTRADORES -->
        <div class="tarjeta">
            <h3>Administradores</h3>
            <p>1</p>
        </div>

        <!-- CANTIDAD DE TÉCNICOS -->
        <div class="tarjeta">
            <h3>Técnicos</h3>
            <p>2</p>
        </div>

    </div>

    <!-- FILTROS DE BÚSQUEDA POR NOMBRE, CORREO Y ROL -->
    <div class="filtros">

        <input
            type="text"
            placeholder="Buscar usuario por nombre o correo"
        >

        <select>
            <option>Todos los roles</option>
            <option>Administrador</option>
            <option>Técnico</option>
        </select>

        <button>Buscar</button>

    </div>

    <!-- TABLA CON LA INFORMACIÓN Y ACCIONES DE LOS USUARIOS -->
    <div class="tabla-contenedor">

        <table>

            <!-- ENCABEZADOS DE LAS COLUMNAS -->
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <!-- REGISTROS DE USUARIOS -->
            <tbody>

                <!-- DATOS DEL ADMINISTRADOR -->
                <tr>
                    <td>Manuel Almaraz</td>
                    <td>admin@sgose.com</td>
                    <td>Administrador</td>
                    <td>
                        <span class="estado finalizada">
                            Activo
                        </span>
                    </td>
                    <td>
                        <a href="#" class="btn">Editar</a>
                    </td>
                </tr>

                <!-- DATOS DEL PRIMER TÉCNICO -->
                <tr>
                    <td>Estuardo Vásquez</td>
                    <td>estuardo@sgose.com</td>
                    <td>Técnico</td>
                    <td>
                        <span class="estado finalizada">
                            Activo
                        </span>
                    </td>
                    <td>
                        <a href="#" class="btn">Editar</a>
                    </td>
                </tr>

                <!-- DATOS DEL SEGUNDO TÉCNICO -->
                <tr>
                    <td>Fran González</td>
                    <td>fran@sgose.com</td>
                    <td>Técnico</td>
                    <td>
                        <span class="estado finalizada">
                            Activo
                        </span>
                    </td>
                    <td>
                        <a href="#" class="btn">Editar</a>
                    </td>
                </tr>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>