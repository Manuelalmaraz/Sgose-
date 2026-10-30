<?php // Vista del historial de movimientos de órdenes para el administrador autorizado. ?>
<?php
session_start();
require_once __DIR__ . '/../includes/sesion.php';
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header('Location: ../index.php'); exit;
}
require_once '../config/conexion.php';
$id_orden = max(0, (int)($_GET['id'] ?? 0));
$stmt = $conexion->prepare('SELECT h.*, o.numero_orden, u.nombre, u.apellido, a.nombre_estado AS anterior, n.nombre_estado AS nuevo FROM historial_orden h JOIN orden_servicio o ON o.id_orden=h.id_orden JOIN usuarios u ON u.id_usuario=h.id_usuario LEFT JOIN estados_orden a ON a.id_estado=h.id_estado_anterior JOIN estados_orden n ON n.id_estado=h.id_estado_nuevo WHERE (?=0 OR h.id_orden=?) ORDER BY h.id_historial DESC LIMIT 200');
$stmt->bind_param('ii', $id_orden, $id_orden);
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Historial - SGOSE</title><link rel="stylesheet" href="../css/admin.css"></head><body>
<div class="sidebar"><h2>SGOSE</h2><a href="dashboard.php">Dashboard</a><a href="clientes.php">Clientes</a><a href="ordenes.php">Órdenes</a><a href="usuarios.php">Usuarios</a><a href="historial.php" class="activo">Historial</a><a href="../logout.php">Cerrar sesión</a></div>
<main class="contenido"><div class="encabezado"><h1>Historial de órdenes</h1><p>Últimos 200 movimientos. Abre una orden para consultar su historial completo.</p></div>
<div class="tabla-contenedor"><table><thead><tr><th>Orden</th><th>Fecha</th><th>Usuario</th><th>Estado anterior</th><th>Estado nuevo</th><th>Observación</th></tr></thead><tbody>
<?php while ($fila=$resultado->fetch_assoc()) { ?><tr><td><a href="ver_orden.php?id=<?= (int)$fila['id_orden'] ?>"><?= htmlspecialchars($fila['numero_orden']) ?></a></td><td><?= htmlspecialchars($fila['fecha_cambio']) ?></td><td><?= htmlspecialchars($fila['nombre'].' '.$fila['apellido']) ?></td><td><?= htmlspecialchars($fila['anterior'] ?? 'Creación') ?></td><td><?= htmlspecialchars($fila['nuevo']) ?></td><td><?= nl2br(htmlspecialchars($fila['observacion'] ?? '')) ?></td></tr><?php } ?>
<?php if ($resultado->num_rows === 0) { ?><tr><td colspan="6">Sin movimientos registrados.</td></tr><?php } ?>
</tbody></table></div></main></body></html>


