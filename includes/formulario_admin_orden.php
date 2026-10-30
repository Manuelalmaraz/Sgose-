<?php // Acciones visibles únicamente en el detalle autorizado del administrador. ?>
<div class="detalle-grupo">
<h3>Administrar orden</h3>
<?php if($error_admin) { ?><p class="mensaje-error" role="alert"><?= escapar($error_admin) ?></p><?php } ?>
<?php if(isset($_GET['guardado'])) { ?><p role="status">Cambio guardado correctamente.</p><?php } ?>
<p><strong>Técnico asignado:</strong> <?= escapar($orden['tecnico_nombre'] ?? 'Sin asignar') ?></p>
<?php if(!in_array((int)$orden['id_estado'],[7,8],true)) { ?>
<!-- Un solo formulario protege ambas acciones y exige un motivo auditable. -->
<form method="post"><input type="hidden" name="csrf" value="<?= escapar(token_formulario()) ?>"><input type="hidden" name="estado_actual" value="<?= (int)$orden['id_estado'] ?>"><input type="hidden" name="tecnico_actual" value="<?= (int)$orden['id_tecnico'] ?>">
<label for="id_tecnico">Reasignar a técnico activo</label><select id="id_tecnico" name="id_tecnico"><option value="0">Seleccionar técnico</option><?php foreach($conexion->query('SELECT id_usuario,nombre,apellido FROM usuarios WHERE id_rol=2 AND estado=1 ORDER BY nombre') as $fila) { ?><option value="<?= (int)$fila['id_usuario'] ?>"><?= escapar($fila['nombre'].' '.$fila['apellido']) ?></option><?php } ?></select>
<label for="motivo">Motivo de la reasignación o cancelación</label><textarea id="motivo" name="motivo" required maxlength="1000" rows="3"><?= escapar($_POST['motivo'] ?? '') ?></textarea>
<button name="accion" value="reasignar" class="btn">Reasignar orden</button><button name="accion" value="cancelar" class="btn secundario">Cancelar orden</button>
</form>
<?php } else { ?><p>La orden está cerrada. Se conserva su historial para consulta.</p><?php } ?></div>
