<!-- Conserva el cliente al filtrar su historial de órdenes. -->
<form method="get" class="filtros">
    <?php if ( $cliente_filtro && $_SESSION['id_rol'] == 1 ) { ?>
    <input type="hidden" name="cliente" value="<?= $cliente_filtro ?>" />
    <?php } ?>
    <input
        type="search"
        name="buscar"
        aria-label="Buscar por número o cliente"
        placeholder="Buscar por número o cliente"
        value="<?= htmlspecialchars( $busqueda ) ?>"
    />
    <select name="estado" aria-label="Estado">
        <option value="0">Todos los estados</option>
        <?php foreach ( $conexion->query('SELECT * FROM estados_orden ORDER BY id_estado') as $fila ) { ?>
        <option value="<?= (int) $fila['id_estado'] ?>" <?= $estado_filtro == $fila['id_estado'] ? 'selected' : '' ?>><?= htmlspecialchars($fila['nombre_estado']) ?></option>
        <?php } ?>
    </select>
    <?php if ($_SESSION['id_rol'] == 1) { ?>
    <select name="tecnico" aria-label="Técnico">
        <option value="0">Todos los técnicos</option>
        <?php foreach ( $conexion->query( 'SELECT id_usuario,nombre,apellido FROM usuarios WHERE id_rol=2 ORDER BY nombre' ) as $fila ) { ?>
        <option value="<?= (int) $fila['id_usuario'] ?>" <?= $tecnico_filtro == $fila['id_usuario'] ? 'selected' : '' ?>><?= htmlspecialchars($fila['nombre'] . ' ' . $fila['apellido']) ?></option>
        <?php } ?>
    </select>
    <?php } ?>
    <button>Buscar</button>
    <a href="ordenes.php" class="btn-limpiar">Limpiar</a>
</form>
