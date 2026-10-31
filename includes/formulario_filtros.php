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
    <!-- Categorías y servicios reales para filtrar las órdenes del administrador. -->
    <select name="categoria" aria-label="Categoría">
        <option value="0">Todas las categorías</option>
        <?php foreach ($conexion->query('SELECT id_categoria,nombre_categoria FROM categorias_servicio ORDER BY nombre_categoria') as $fila) { ?>
            <option value="<?= (int) $fila['id_categoria'] ?>" <?= $categoria_filtro == $fila['id_categoria'] ? 'selected' : '' ?>>
                <?= escapar($fila['nombre_categoria']) ?>
            </option>
        <?php } ?>
    </select>

    <select name="servicio" aria-label="Servicio" style="max-width: 260px;">
        <option value="">Todos los servicios</option>
        <?php foreach ($conexion->query('SELECT DISTINCT servicio_solicitado FROM orden_servicio ORDER BY servicio_solicitado') as $fila) { ?>
            <option value="<?= escapar($fila['servicio_solicitado']) ?>" <?= $servicio_filtro === $fila['servicio_solicitado'] ? 'selected' : '' ?>>
                <?= escapar($fila['servicio_solicitado']) ?>
            </option>
        <?php } ?>
    </select>
    <?php } ?>
    <button>Buscar</button>
    <a href="ordenes.php" class="btn-limpiar">Limpiar</a>
</form>
