<?php
// Lista real de usuarios, con búsqueda y acceso al alta y edición.
session_start();
require_once '../includes/sesion.php';
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) { header('Location: ../index.php'); exit; }
$buscar = trim($_GET['buscar'] ?? '');
$rol = (int)($_GET['rol'] ?? 0);
$patron = '%' . $buscar . '%';
$stmt = $conexion->prepare('SELECT u.id_usuario,u.nombre,u.apellido,u.correo,u.estado,r.nombre_rol FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE (CONCAT_WS(" ",u.nombre,u.apellido) LIKE ? OR u.correo LIKE ?) AND (?=0 OR u.id_rol=?) ORDER BY u.id_usuario');
$stmt->bind_param('ssii',$patron,$patron,$rol,$rol); $stmt->execute(); $usuarios=$stmt->get_result();
$resumen=$conexion->query('SELECT COUNT(*) total, SUM(id_rol=1) administradores, SUM(id_rol=2) tecnicos FROM usuarios')->fetch_assoc();
cabecera_admin('Gestión de usuarios','usuarios');
?>
<!-- Cifras y filtros obtenidos de la misma base de usuarios del login. -->
<a href="usuario.php" class="btn-principal">+ Nuevo usuario</a>
<div class="tarjetas-dashboard"><?php foreach(['total'=>'Total de usuarios','administradores'=>'Administradores','tecnicos'=>'Técnicos'] as $campo=>$etiqueta) { ?><div class="tarjeta"><h3><?= $etiqueta ?></h3><p><?= (int)$resumen[$campo] ?></p></div><?php } ?></div>
<?php if (isset($_GET['guardado'])) { ?><p role="status">Usuario guardado correctamente.</p><?php } ?>
<?php if (isset($_GET['eliminado'])) { ?><p role="status">Usuario eliminado correctamente.</p><?php } ?>
<form class="filtros" method="get"><input name="buscar" aria-label="Nombre o correo" placeholder="Buscar nombre o correo" value="<?= escapar($buscar) ?>"><select name="rol" aria-label="Rol"><option value="0">Todos los roles</option><?php foreach($conexion->query('SELECT * FROM roles') as $fila) { ?><option value="<?= (int)$fila['id_rol'] ?>" <?= $rol==$fila['id_rol']?'selected':'' ?>><?= escapar($fila['nombre_rol']) ?></option><?php } ?></select><button>Buscar</button><a href="usuarios.php" class="btn-limpiar">Limpiar</a></form>
<div class="tabla-contenedor"><table><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
<?php while($fila=$usuarios->fetch_assoc()) { ?><tr><td><?= escapar($fila['nombre'].' '.$fila['apellido']) ?></td><td><?= escapar($fila['correo']) ?></td><td><?= escapar($fila['nombre_rol']) ?></td><td><?= $fila['estado']?'Activo':'Inactivo' ?></td><td><a class="btn" href="usuario.php?id=<?= (int)$fila['id_usuario'] ?>">Editar</a> <?php if ((int)$fila['id_usuario'] !== (int)$_SESSION['id_usuario']) { ?><a class="btn" href="eliminar_usuario.php?id=<?= (int)$fila['id_usuario'] ?>">Eliminar</a><?php } ?></td></tr><?php } if(!$usuarios->num_rows) { ?><tr><td colspan="5">No hay usuarios que coincidan.</td></tr><?php } ?></tbody></table></div>
<?php pie_admin(); ?>


