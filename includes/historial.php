<?php
// Esta vista recibe únicamente órdenes cuyo acceso ya fue autorizado.
$stmt_historial = $conexion->prepare('SELECT h.*, u.nombre, u.apellido, a.nombre_estado AS anterior, n.nombre_estado AS nuevo FROM historial_orden h JOIN usuarios u ON u.id_usuario=h.id_usuario LEFT JOIN estados_orden a ON a.id_estado=h.id_estado_anterior JOIN estados_orden n ON n.id_estado=h.id_estado_nuevo WHERE h.id_orden=? ORDER BY h.id_historial DESC');
$stmt_historial->bind_param('i', $id_orden);
$stmt_historial->execute();
$movimientos = $stmt_historial->get_result();
?>
<div class="detalle-grupo tabla-contenedor">
<h3>Historial de la orden</h3>
<table><thead><tr><th>Fecha</th><th>Usuario</th><th>Cambio de estado</th><th>Observación</th></tr></thead><tbody>
<?php while ($movimiento = $movimientos->fetch_assoc()) { ?>
<tr><td><?= htmlspecialchars($movimiento['fecha_cambio']) ?></td><td><?= htmlspecialchars($movimiento['nombre'].' '.$movimiento['apellido']) ?></td><td><?= htmlspecialchars(($movimiento['anterior'] ?? 'Creación').' → '.$movimiento['nuevo']) ?></td><td><?= nl2br(htmlspecialchars($movimiento['observacion'] ?? '')) ?></td></tr>
<?php } ?>
<?php if ($movimientos->num_rows === 0) { ?><tr><td colspan="4">Sin movimientos registrados.</td></tr><?php } ?>
</tbody></table></div>
