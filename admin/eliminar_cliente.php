<?php
// Elimina clientes sin órdenes; conserva siempre los datos vinculados al historial.
session_start();
require_once '../includes/sesion.php';
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header('Location: ../index.php');
    exit();
}
$id = (int) ($_GET['id'] ?? 0);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validar_formulario();
        if (($_POST['confirmar'] ?? '') !== 'eliminar') {
            throw new DomainException('Confirma la eliminación antes de continuar.');
        }
        $conexion->begin_transaction();
        // Bloquea al cliente para impedir que se asigne una orden durante la eliminación.
        $stmt = $conexion->prepare('SELECT id_cliente FROM clientes WHERE id_cliente=? FOR UPDATE');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        if (!$existe) {
            throw new DomainException('El cliente no existe o ya fue eliminado.');
        }
        // Incluye órdenes finalizadas y canceladas: tampoco se borran sus referencias.
        $stmt = $conexion->prepare(
            'SELECT id_orden FROM orden_servicio WHERE id_cliente=? LIMIT 1 FOR UPDATE'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $tiene_ordenes = $stmt->get_result()->num_rows > 0;
        if ($tiene_ordenes) {
            throw new DomainException(
                'No se puede eliminar este cliente porque tiene órdenes registradas. Se conserva para mantener su historial.'
            );
        }
        $stmt = $conexion->prepare('DELETE FROM clientes WHERE id_cliente=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $conexion->commit();
        header('Location: clientes.php?eliminado=1');
        exit();
    } catch (Throwable $e) {
        $conexion->rollback();
        $error =
            $e instanceof DomainException
                ? $e->getMessage()
                : 'No se pudo eliminar el cliente. Sus datos se conservaron.';
        if (!($e instanceof DomainException)) {
            error_log((string) $e);
        }
    }
}
// Visitar el enlace solo muestra la confirmación; nunca borra mediante GET.
$stmt = $conexion->prepare('SELECT nombre,apellido,telefono FROM clientes WHERE id_cliente=?');
$stmt->bind_param('i', $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
if (!$cliente) {
    http_response_code(404);
}
cabecera_admin('Eliminar cliente', 'clientes');
?>
<div class="formulario-contenedor">
    <?php if ($error) { ?>
    <p class="mensaje-error" role="alert"><?= escapar($error) ?></p>
    <?php } ?>
    <?php if ($cliente) { ?>
    <p>
        Cliente:
        <strong><?= escapar($cliente['nombre'] . ' ' . $cliente['apellido']) ?></strong>
    </p>
    <p>Teléfono: <?= escapar($cliente['telefono']) ?></p>
    <p>Esta acción elimina permanentemente al cliente si no tiene órdenes registradas.</p>
    <!-- Confirmación explícita y token para evitar solicitudes no autorizadas. -->
    <form method="post">
        <input type="hidden" name="csrf" value="<?= escapar(token_formulario()) ?>" />
        <div class="acciones-formulario">
            <button class="btn-principal" name="confirmar" value="eliminar">Eliminar</button>
            <a class="btn secundario" href="clientes.php">Cancelar</a>
            <a class="btn" href="ordenes.php?cliente=<?= $id ?>">Ver órdenes</a>
        </div>
    </form>
    <?php } else { ?>
    <p>El cliente no existe o ya fue eliminado.</p>
    <a class="btn" href="clientes.php">Volver</a>
    <?php } ?>
</div>
<?php pie_admin(); ?>
