<?php
// Formulario del técnico: muestra acciones según el estado autorizado de su orden.
?>
<div class="detalle-grupo" id="editar-orden">
    <h3><?= in_array((int) $orden['id_estado'], [3, 5, 6], true) ? 'Editar orden' : 'Atención del servicio' ?></h3>
    <!-- Mantiene los avisos junto al formulario, visibles al regresar del envío. -->
    <?php if ($error !== '') { ?>
        <p class="mensaje-error" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php } elseif (isset($_GET['mensaje'])) { ?>
        <p role="status">Cambios guardados correctamente.</p>
    <?php } ?>
    <?php if ((int) $orden['id_estado'] === 2) { ?>
    <form method="post" action="ver_orden.php?id=<?= $id_orden ?>#editar-orden">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_orden']) ?>" />
        <input type="hidden" name="estado_actual" value="2" />
        <button class="btn" name="accion" value="aceptar">Aceptar orden</button>
        <label for="motivo">Motivo del rechazo</label>
        <textarea id="motivo" name="motivo" rows="3"><?= htmlspecialchars( $_POST['motivo'] ?? '' ) ?></textarea>
        <button class="btn secundario" name="accion" value="rechazar">Rechazar orden</button>
    </form>
    <?php } elseif (in_array((int) $orden['id_estado'], [3, 5, 6], true)) { ?>
    <form method="post" id="formulario-trabajo" action="ver_orden.php?id=<?= $id_orden ?>#editar-orden">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_orden']) ?>" />
        <input type="hidden" name="estado_actual" value="<?= (int) $orden['id_estado'] ?>" />
        <?php foreach (
    [
        'diagnostico' => 'Diagnóstico',
        'trabajo_realizado' => 'Trabajo realizado',
        'materiales_utilizados' => 'Materiales utilizados'
    ]
    as $campo => $etiqueta
) { ?>
        <label for="<?= $campo ?>"><?= $etiqueta ?></label>
        <textarea id="<?= $campo ?>" name="<?= $campo ?>" rows="4"><?= htmlspecialchars( $_POST[$campo] ?? ($orden[$campo] ?? '') ) ?></textarea>
        <?php } ?>
        <!-- Las observaciones administrativas se muestran sin un campo editable. -->
        <p><strong>Observaciones:</strong><br>
            <?= nl2br(escapar($orden['observaciones'] ?? 'Sin observaciones.')) ?>
        </p>
<!-- El técnico solo puede registrar el importe si todavía está pendiente. -->
<?php if ($orden['costo_servicio'] === null) { ?>
<div class="grupo-formulario">
    <label for="costo_servicio">Precio del servicio (Q)</label>
    <input type="number" id="costo_servicio" name="costo_servicio"
        min="0" max="99999999.99" step="0.01" placeholder="Pendiente"
        value="<?= escapar($_POST['costo_servicio'] ?? $orden['costo_servicio'] ?? '') ?>">
</div>
<?php } else { ?>
<p><strong>Precio del servicio:</strong> Q <?= number_format((float) $orden['costo_servicio'], 2) ?></p>
<p>Solo el administrador puede modificar el precio registrado.</p>
<?php } ?>
        <label for="id_estado">Estado del avance</label>
        <select name="id_estado" id="id_estado">
            <?php foreach ([3 => 'Aceptada', 5 => 'En diagnóstico', 6 => 'En proceso'] as $estado => $nombre) { if ($estado >= (int) $orden['id_estado']) { ?>
            <option value="<?= $estado ?>" <?= $estado === (int) $orden['id_estado'] ? 'selected' : '' ?>><?= $nombre ?></option>
            <?php }
} ?>
        </select>
        <p>Para finalizar completa el diagnóstico y el trabajo realizado.</p>
        <button class="btn" name="accion" value="guardar">Guardar cambios</button>
        <button class="btn" name="accion" value="finalizar">Finalizar orden</button>
    </form>
    <?php } else { ?>
    <p>Esta orden está cerrada para cambios.</p>
    <?php } ?>
</div>
<script src="../js/formulario-tecnico.js" defer></script>

