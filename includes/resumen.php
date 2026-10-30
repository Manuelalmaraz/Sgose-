<?php
// Resumen real; el panel técnico solo cuenta sus propias órdenes.
require_once __DIR__ . '/../config/conexion.php';
$tecnico_panel = $_SESSION['id_rol'] == 2 ? (int)$_SESSION['id_usuario'] : 0;
$stmt_resumen = $conexion->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(id_estado IN (1,2)),0) AS pendientes, COALESCE(SUM(id_estado IN (3,5,6)),0) AS proceso, COALESCE(SUM(id_estado=7),0) AS finalizadas, COALESCE(SUM(id_estado=8),0) AS canceladas FROM orden_servicio WHERE (?=0 OR id_tecnico=?)');
$stmt_resumen->bind_param('ii', $tecnico_panel, $tecnico_panel);
$stmt_resumen->execute();
$resumen = $stmt_resumen->get_result()->fetch_assoc();
$total_clientes = $conexion->query('SELECT COUNT(*) AS total FROM clientes')->fetch_assoc()['total'];
