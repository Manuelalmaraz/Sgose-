<?php // Formulario del técnico: muestra acciones según el estado autorizado de su orden. ?>
<?php if ($error !== '') { ?><p role="alert"><?= htmlspecialchars($error) ?></p><?php } ?>
<?php if (isset($_GET['mensaje'])) { ?><p role="status">Cambios guardados correctamente.</p><?php } ?>
<div class="detalle-grupo">
<h3>Atención del servicio</h3>
<?php if ((int)$orden['id_estado'] === 2) { ?>
<form method="post">
<input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_orden']) ?>">
<input type="hidden" name="estado_actual" value="2">
<button class="btn" name="accion" value="aceptar">Aceptar orden</button>
<label for="motivo">Motivo del rechazo</label>
<textarea id="motivo" name="motivo" rows="3"><?= htmlspecialchars($_POST['motivo'] ?? '') ?></textarea>
<button class="btn secundario" name="accion" value="rechazar">Rechazar orden</button>
</form>
<?php } elseif (in_array((int)$orden['id_estado'], [3,5,6], true)) { ?>
<form method="post">
<input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_orden']) ?>">
<input type="hidden" name="estado_actual" value="<?= (int)$orden['id_estado'] ?>">
<?php foreach (['diagnostico'=>'Diagnóstico', 'trabajo_realizado'=>'Trabajo realizado', 'materiales_utilizados'=>'Materiales utilizados', 'observaciones'=>'Observaciones'] as $campo=>$etiqueta) { ?>
<label for="<?= $campo ?>"><?= $etiqueta ?></label>
<textarea id="<?= $campo ?>" name="<?= $campo ?>" rows="4"><?= htmlspecialchars($_POST[$campo] ?? $orden[$campo] ?? '') ?></textarea>
<?php } ?>
<label for="id_estado">Estado del avance</label>
<select name="id_estado" id="id_estado">
<?php foreach ([3=>'Aceptada',5=>'En diagnóstico',6=>'En proceso'] as $estado=>$nombre) { if ($estado >= (int)$orden['id_estado']) { ?>
<option value="<?= $estado ?>" <?= $estado === (int)$orden['id_estado'] ? 'selected' : '' ?>><?= $nombre ?></option>
<?php }} ?>
</select>
<p>Para finalizar completa el diagnóstico y el trabajo realizado.</p>
<button class="btn" name="accion" value="guardar">Guardar avance</button>
<button class="btn" name="accion" value="finalizar">Finalizar orden</button>
</form>
<?php } else { ?><p>Esta orden está cerrada para cambios.</p><?php } ?>
</div>

