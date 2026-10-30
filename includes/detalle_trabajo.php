<?php // Presenta los datos técnicos registrados sin permitir su edición en esta vista. ?>
<div class="detalle-grupo">
<h3>Trabajo registrado</h3>
<?php foreach (['diagnostico'=>'Diagnóstico','trabajo_realizado'=>'Trabajo realizado','materiales_utilizados'=>'Materiales utilizados','observaciones'=>'Observaciones','fecha_finalizacion'=>'Fecha de finalización'] as $campo=>$etiqueta) { ?>
<p><strong><?= $etiqueta ?>:</strong><br><?= nl2br(htmlspecialchars($orden[$campo] ?? 'Sin registrar')) ?></p>
<?php } ?>
</div>

