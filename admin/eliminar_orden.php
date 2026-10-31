<?php
// Solo el administrador puede eliminar una orden y sus movimientos asociados.
session_start();
require_once '../includes/sesion.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header('Location: ../index.php');
    exit;
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

        // Vuelve a revisar los permisos dentro de la operación de borrado.
        $actor = (int) $_SESSION['id_usuario'];
        $stmt = $conexion->prepare(
            'SELECT id_usuario FROM usuarios
             WHERE id_usuario = ? AND id_rol = 1 AND estado = 1 FOR UPDATE'
        );
        $stmt->bind_param('i', $actor);
        $stmt->execute();
        $autorizado = $stmt->get_result()->num_rows > 0;

        if (!$autorizado) {
            throw new DomainException('Tu cuenta ya no tiene acceso de administrador.');
        }

        // Impide editar la orden al mismo tiempo que se está eliminando.
        $stmt = $conexion->prepare('SELECT * FROM orden_servicio WHERE id_orden = ? FOR UPDATE');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $actual = $stmt->get_result()->fetch_assoc();

        if (!$actual) {
            throw new DomainException('La orden no existe o ya fue eliminada.');
        }

        if (!hash_equals(hash('sha256', json_encode($actual)), (string) ($_POST['version'] ?? ''))) {
            throw new DomainException('La orden cambió. Revisa sus datos y vuelve a confirmar.');
        }

        // Primero borra los movimientos, porque hacen referencia a esta orden.
        // Si alguna consulta falla, rollback conserva ambos registros.
        $stmt = $conexion->prepare('DELETE FROM historial_orden WHERE id_orden = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $stmt = $conexion->prepare('DELETE FROM orden_servicio WHERE id_orden = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $conexion->commit();
        header('Location: ordenes.php?mensaje=eliminada');
        exit;
    } catch (Throwable $e) {
        $conexion->rollback();
        $error = $e instanceof DomainException
            ? $e->getMessage()
            : 'No se pudo eliminar la orden. Sus datos se conservaron.';

        if (!($e instanceof DomainException)) {
            error_log((string) $e);
        }
    }
}

// Abrir el enlace muestra la confirmación; nunca elimina mediante GET.
$stmt = $conexion->prepare('SELECT * FROM orden_servicio WHERE id_orden = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$orden = $stmt->get_result()->fetch_assoc();

if (!$orden) {
    http_response_code(404);
}

cabecera_admin('Eliminar orden', 'ordenes');
?>

<div class="formulario-contenedor">
    <?php if ($error !== '') { ?>
        <p class="mensaje-error" role="alert"><?= escapar($error) ?></p>
    <?php } ?>

    <?php if ($orden) { ?>
        <p><strong>Orden:</strong> <?= escapar($orden['numero_orden']) ?></p>
        <p><strong>Servicio:</strong> <?= escapar($orden['servicio_solicitado']) ?></p>
        <p>
            <strong>Precio:</strong>
            <?= $orden['costo_servicio'] === null ? 'Pendiente' : 'Q ' . number_format((float) $orden['costo_servicio'], 2) ?>
        </p>
        <p>Se eliminarán permanentemente esta orden y su historial. El cliente y el técnico se conservan.</p>

        <!-- El botón envía la confirmación y el token de la sesión por POST. -->
        <form method="post">
            <input type="hidden" name="csrf" value="<?= escapar(token_formulario()) ?>">
            <input type="hidden" name="version" value="<?= escapar(hash('sha256', json_encode($orden))) ?>">

            <div class="acciones-formulario">
                <button class="btn-principal" name="confirmar" value="eliminar">Eliminar</button>
                <a class="btn secundario" href="ordenes.php">Cancelar</a>
            </div>
        </form>
    <?php } else { ?>
        <p>La orden no existe o ya fue eliminada.</p>
        <a class="btn" href="ordenes.php">Volver</a>
    <?php } ?>
</div>

<?php pie_admin(); ?>
