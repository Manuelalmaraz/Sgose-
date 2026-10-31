<?php
// Aplica filtros preparados sin perder la restricción de propiedad del técnico.
?>
<?php
$busqueda = trim($_GET['buscar'] ?? '');
$estado_filtro = max(0, (int) ($_GET['estado'] ?? 0));
$tecnico_filtro =
    $_SESSION['id_rol'] == 1
        ? max(0, (int) ($_GET['tecnico'] ?? 0))
        : (int) $_SESSION['id_usuario'];
$patron_busqueda = '%' . $busqueda . '%';
$cliente_filtro = max(0, (int) ($_GET['cliente'] ?? 0));
// Los filtros adicionales pertenecen al listado del administrador.
$categoria_filtro = $_SESSION['id_rol'] == 1 ? max(0, (int) ($_GET['categoria'] ?? 0)) : 0;
$servicio_filtro = $_SESSION['id_rol'] == 1 ? trim($_GET['servicio'] ?? '') : '';
if ($cliente_filtro && $_SESSION['id_rol'] == 1) {
    // Solo se concatena el identificador convertido a entero, nunca texto de entrada.
    $sql = str_replace(
        'ORDER BY o.id_orden DESC',
        'WHERE o.id_cliente=' . $cliente_filtro . ' ORDER BY o.id_orden DESC',
        $sql
    );
}
$sql = str_replace('ORDER BY o.id_orden DESC', '', $sql); // Mantiene la restricción del técnico y añade filtros preparados.
$sql .=
    (strpos($sql, 'WHERE ') !== false ? ' AND ' : ' WHERE ') .
    '(o.numero_orden LIKE ? OR CONCAT_WS(" ",c.nombre,c.apellido) LIKE ?) AND (?=0 OR o.id_estado=?) AND (?=0 OR o.id_tecnico=?)';

if ($_SESSION['id_rol'] == 1) {
    // Cada menú es opcional y se puede combinar con los demás filtros.
    $sql .= " AND (?=0 OR o.id_categoria=?) AND (?='' OR o.servicio_solicitado=?)";
}
$sql .= ' ORDER BY o.id_orden DESC';
$stmt = $conexion->prepare($sql);
if ($_SESSION['id_rol'] == 2) {
    $stmt->bind_param(
        'issiiii',
        $id_tecnico,
        $patron_busqueda,
        $patron_busqueda,
        $estado_filtro,
        $estado_filtro,
        $tecnico_filtro,
        $tecnico_filtro
    );
} else {
    $stmt->bind_param(
        'ssiiiiiiss',
        $patron_busqueda,
        $patron_busqueda,
        $estado_filtro,
        $estado_filtro,
        $tecnico_filtro,
        $tecnico_filtro,
        $categoria_filtro,
        $categoria_filtro,
        $servicio_filtro,
        $servicio_filtro
    );
}
$stmt->execute();
$resultado = $stmt->get_result();

