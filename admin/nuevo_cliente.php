<?php
// Alta y edición de clientes; conserva el regreso a Nueva orden con el cliente seleccionado.
session_start();
require_once '../includes/sesion.php';
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header('Location: ../index.php');
    exit();
}
// El mismo formulario sirve para registrar un cliente o editar uno existente.
$editar = defined('EDITAR_CLIENTE');
$id = $editar ? (int) ($_GET['id'] ?? 0) : 0;
$cliente = ['nombre' => '', 'apellido' => '', 'telefono' => '', 'correo' => '', 'direccion' => ''];
$mensaje = '';
$desde = ($_POST['desde'] ?? ($_GET['desde'] ?? '')) === 'orden' ? 'orden' : '';
if ($editar) {
    $stmt = $conexion->prepare('SELECT * FROM clientes WHERE id_cliente=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    if (!$cliente) {
        http_response_code(404);
        exit('Cliente no encontrado.');
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Valida el envío, los campos obligatorios y la dirección de correo.
        validar_formulario();
        $nombre = campo_texto('nombre', 100, true);
        $apellido = campo_texto('apellido', 100);
        $telefono = campo_texto('telefono', 20, true);
        $correo = campo_texto('correo', 150);
        $direccion = campo_texto('direccion', 255);
        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Escribe un correo válido o deja el campo vacío.');
        }
        if ($editar) {
            $stmt = $conexion->prepare(
                'UPDATE clientes SET nombre=?,apellido=?,telefono=?,correo=?,direccion=? WHERE id_cliente=?'
            );
            $stmt->bind_param('sssssi', $nombre, $apellido, $telefono, $correo, $direccion, $id);
        } else {
            $stmt = $conexion->prepare(
                'INSERT INTO clientes(nombre,apellido,telefono,correo,direccion) VALUES(?,?,?,?,?)'
            );
            $stmt->bind_param('sssss', $nombre, $apellido, $telefono, $correo, $direccion);
        }
        $stmt->execute();
        if (!$editar) {
            $id = $stmt->insert_id;
        }
        header(
            'Location: ' .
                ($desde === 'orden'
                    ? 'nueva_orden.php?id_cliente=' . $id
                    : 'clientes.php?mensaje=guardado')
        );
        exit();
    } catch (Throwable $e) {
        $mensaje =
            $e instanceof DomainException ? $e->getMessage() : 'No se pudo guardar el cliente.';
        if (!($e instanceof DomainException)) {
            error_log((string) $e);
        }
    }
    foreach (['nombre', 'apellido', 'telefono', 'correo', 'direccion'] as $campo) {
        $cliente[$campo] = $_POST[$campo] ?? '';
    }
}
// Muestra el formulario con los datos actuales o con los valores que se intentaron guardar.
cabecera_admin($editar ? 'Editar cliente' : 'Nuevo cliente', 'clientes');
?>
<div class="formulario-contenedor">
    <?php if ($mensaje) { ?>
    <p class="mensaje-error" role="alert"><?= escapar($mensaje) ?></p>
    <?php } ?>
    <!-- Los datos se conservan tras una validación fallida y siempre se escapan al mostrarlos. -->
    <form method="post">
        <input type="hidden" name="csrf" value="<?= escapar( token_formulario() ) ?>" />
        <input type="hidden" name="desde" value="<?= $desde ?>" />
        <?php foreach (
    [
        'nombre' => ['Nombre', 100],
        'apellido' => ['Apellido', 100],
        'telefono' => ['Teléfono', 20],
        'correo' => ['Correo', 150],
        'direccion' => ['Dirección', 255]
    ]
    as $campo => $info
) { ?>
        <div class="grupo-formulario">
            <label for="<?= $campo ?>"><?= $info[0] ?></label>
            <input
                name="<?= $campo ?>"
                id="<?= $campo ?>"
                type="<?= $campo === 'correo' ? 'email' : 'text' ?>"
                maxlength="<?= $info[1] ?>"
                value="<?= escapar($cliente[$campo]) ?>"
                <?= in_array( $campo, ['nombre', 'telefono'] ) ? 'required' : '' ?>
            />
        </div>
        <?php } ?>
        <button class="btn-principal">Guardar cliente</button>
        <a class="btn secundario" href="<?= $desde === 'orden' ? 'nueva_orden.php' : 'clientes.php' ?>">Volver</a>
    </form>
</div>
<?php pie_admin(); ?>

