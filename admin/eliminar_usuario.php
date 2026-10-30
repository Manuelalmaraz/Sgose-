<?php
// Confirma y elimina únicamente cuentas sin órdenes ni movimientos asociados.
// Los usuarios con actividad se desactivan desde Editar para conservar la trazabilidad.
session_start();
require_once '../includes/sesion.php';
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header('Location: ../index.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validar_formulario();
        if (($_POST['confirmar'] ?? '') !== 'eliminar') {
            throw new DomainException('Confirma la eliminación antes de continuar.');
        }
        $conexion->begin_transaction();
        // Mismo orden de bloqueo que la edición: evita perder al último administrador.
        $cuentas = $conexion->query('SELECT id_usuario,id_rol,estado FROM usuarios ORDER BY id_usuario FOR UPDATE');
        $actor_valido = false;
        $destino = null;
        $otros_admins = 0;
        foreach ($cuentas as $cuenta) {
            if ($cuenta['id_usuario'] == $_SESSION['id_usuario'] && $cuenta['id_rol'] == 1 && $cuenta['estado'] == 1) {
                $actor_valido = true;
            }
            if ($cuenta['id_usuario'] == $id) $destino = $cuenta;
            if ($cuenta['id_usuario'] != $id && $cuenta['id_rol'] == 1 && $cuenta['estado'] == 1) $otros_admins++;
        }
        if (!$actor_valido) throw new DomainException('Tu cuenta ya no tiene acceso de administrador.');
        if (!$destino) throw new DomainException('Usuario no encontrado.');
        if ($id == $_SESSION['id_usuario']) throw new DomainException('No puedes eliminar tu propia cuenta.');
        if (!$otros_admins) throw new DomainException('Debe quedar al menos un administrador activo.');

        // Se consultan también órdenes cerradas: sus referencias nunca se borran en cascada.
        $stmt = $conexion->prepare('SELECT id_orden FROM orden_servicio WHERE id_tecnico=? LIMIT 1 FOR UPDATE');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $tiene_ordenes = $stmt->get_result()->num_rows > 0;
        $stmt = $conexion->prepare('SELECT id_historial FROM historial_orden WHERE id_usuario=? LIMIT 1 FOR UPDATE');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        // Consume ambos resultados antes de decidir; permite revertir sin consultas pendientes.
        $tiene_historial = $stmt->get_result()->num_rows > 0;
        if ($tiene_ordenes || $tiene_historial) {
            throw new DomainException('Este usuario tiene órdenes o historial. Desactívalo desde Editar para conservar sus registros.');
        }
        $stmt = $conexion->prepare('DELETE FROM usuarios WHERE id_usuario=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $conexion->commit();
        header('Location: usuarios.php?eliminado=1');
        exit;
    } catch (Throwable $e) {
        $conexion->rollback();
        $error = $e instanceof DomainException ? $e->getMessage() : 'No se pudo eliminar el usuario. Sus registros se conservaron.';
        if (!$e instanceof DomainException) error_log((string)$e);
    }
}

// GET solo muestra la confirmación; la eliminación exige POST y token de sesión.
$stmt = $conexion->prepare('SELECT nombre,apellido,correo FROM usuarios WHERE id_usuario=?');
$stmt->bind_param('i', $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
if (!$usuario) {
    http_response_code(404);
    cabecera_admin('Usuario no encontrado', 'usuarios');
    echo '<p>El usuario no existe o ya fue eliminado.</p><a class="btn" href="usuarios.php">Volver</a>';
    pie_admin();
    exit;
}
cabecera_admin('Eliminar usuario', 'usuarios');
?>
<div class="formulario-contenedor">
    <?php if ($error) { ?><p class="mensaje-error" role="alert"><?= escapar($error) ?></p><?php } ?>
    <p>Usuario: <strong><?= escapar($usuario['nombre'].' '.$usuario['apellido']) ?></strong></p>
    <p><?= escapar($usuario['correo']) ?></p>
    <p>Esta acción elimina permanentemente la cuenta si no tiene órdenes ni historial.</p>
    <!-- La confirmación es explícita y no se ejecuta desde el enlace del listado. -->
    <?php if ($id != $_SESSION['id_usuario']) { ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= escapar(token_formulario()) ?>">
        <div class="acciones-formulario">
            <button class="btn-principal" name="confirmar" value="eliminar">Confirmar eliminación</button>
            <a class="btn secundario" href="usuarios.php">Cancelar</a>
            <a class="btn" href="usuario.php?id=<?= $id ?>">Editar / desactivar</a>
        </div>
    </form>
    <?php } else { ?><p>No puedes eliminar tu propia cuenta.</p><a class="btn" href="usuarios.php">Volver</a><?php } ?>
</div>
<?php pie_admin(); ?>
