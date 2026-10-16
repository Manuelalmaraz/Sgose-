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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SGOSE - Órdenes</title>

    <link rel="stylesheet" href="../css/admin.css">
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

        <button class="btn-principal">
            + Nueva orden
        </button>

    </div>

    <!-- RESUMEN DE ÓRDENES POR ESTADO -->
    <div class="tarjetas-dashboard">

        <div class="tarjeta">
            <h3>Solicitadas</h3>
            <p>4</p>
        </div>

        <div class="tarjeta">
            <h3>En proceso</h3>
            <p>3</p>
        </div>

        <div class="tarjeta">
            <h3>Finalizadas</h3>
            <p>6</p>
        </div>

        <div class="tarjeta">
            <h3>Canceladas</h3>
            <p>1</p>
        </div>

    </div>

    <!-- FILTROS PARA BUSCAR ÓRDENES -->
    <div class="filtros">

        <input
            type="text"
            placeholder="Buscar por número de orden o cliente"
        >

        <select>
            <option>Todos los estados</option>
            <option>Solicitada</option>
            <option>En proceso</option>
            <option>Finalizada</option>
            <option>Cancelada</option>
        </select>

        <select>
            <option>Todos los técnicos</option>
            <option>Estuardo Vásquez</option>
            <option>Fran González</option>
        </select>

        <button>Filtrar</button>

    </div>

    <!-- TABLA DE ÓRDENES Y ACCIONES -->
    <div class="tabla-contenedor">

        <table>

            <thead>
                <tr>
                    <th>N° Orden</th>
                    <th>Cliente</th>
                    <th>Técnico</th>
                    <th>Servicio</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Costo</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>

                <tr>
                    <td>OS-001</td>
                    <td>Carlos López</td>
                    <td>Estuardo Vásquez</td>
                    <td>Instalación de tomacorriente</td>
                    <td>03/10/2026</td>
                    <td>
                        <span class="estado pendiente">
                            Solicitada
                        </span>
                    </td>
                    <td>Q 250.00</td>
                    <td>
                        <a href="#" class="btn">Ver</a>
                        <a href="#" class="btn secundario">Editar</a>
                    </td>
                </tr>

                <tr>
                    <td>OS-002</td>
                    <td>Ana Pérez</td>
                    <td>Fran González</td>
                    <td>Cambio de breaker</td>
                    <td>04/10/2026</td>
                    <td>
                        <span class="estado proceso">
                            En proceso
                        </span>
                    </td>
                    <td>Q 375.00</td>
                    <td>
                        <a href="#" class="btn">Ver</a>
                        <a href="#" class="btn secundario">Editar</a>
                    </td>
                </tr>

                <tr>
                    <td>OS-003</td>
                    <td>José Martínez</td>
                    <td>Estuardo Vásquez</td>
                    <td>Revisión eléctrica</td>
                    <td>05/10/2026</td>
                    <td>
                        <span class="estado finalizada">
                            Finalizada
                        </span>
                    </td>
                    <td>Q 180.00</td>
                    <td>
                        <a href="#" class="btn">Ver</a>
                        <a href="#" class="btn secundario">Editar</a>
                    </td>
                </tr>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>