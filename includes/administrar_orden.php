<?php
// Procesa las acciones administrativas con transacción y control de estado.
$error_admin = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validar_formulario();
        // El motivo permite explicar la reasignación o cancelación en el historial.
        $motivo = campo_texto('motivo', 1000, true);
        $accion = $_POST['accion'] ?? '';
        $tecnico = (int) ($_POST['id_tecnico'] ?? 0);
        $conexion->begin_transaction();
        // Valida y bloquea al destinatario antes de asignarle una orden.
        if ($accion === 'reasignar') {
            $stmt = $conexion->prepare(
                'SELECT nombre,apellido FROM usuarios WHERE id_usuario=? AND id_rol=2 AND estado=1 FOR UPDATE'
            );
            $stmt->bind_param('i', $tecnico);
            $stmt->execute();
            $destino = $stmt->get_result()->fetch_assoc();
            if (!$destino) {
                throw new DomainException('Selecciona un técnico activo.');
            }
        }
        $stmt = $conexion->prepare(
            'SELECT id_estado,id_tecnico FROM orden_servicio WHERE id_orden=? FOR UPDATE'
        );
        $stmt->bind_param('i', $id_orden);
        $stmt->execute();
        $actual = $stmt->get_result()->fetch_assoc();
        if (!$actual) {
            throw new DomainException('Orden no encontrada.');
        }
        $anterior = (int) $actual['id_estado'];
        if (
            $anterior !== (int) ($_POST['estado_actual'] ?? 0) ||
            (int) $actual['id_tecnico'] !== (int) ($_POST['tecnico_actual'] ?? 0)
        ) {
            throw new DomainException('La orden cambió. Recarga la página antes de continuar.');
        }
        if (in_array($anterior, [7, 8], true)) {
            throw new DomainException('Una orden finalizada o cancelada no admite cambios.');
        }
        if ($accion === 'reasignar') {
            // No se elimina el trabajo ya registrado; el nuevo técnico debe aceptar.
            if ((int) $actual['id_tecnico'] === $tecnico && $anterior !== 4) {
                throw new DomainException('Selecciona otro técnico.');
            }
            $nuevo = 2;
            $stmt = $conexion->prepare(
                'UPDATE orden_servicio SET id_tecnico=?,id_estado=2 WHERE id_orden=?'
            );
            $stmt->bind_param('ii', $tecnico, $id_orden);
            $nota =
                'Orden reasignada a ' .
                $destino['nombre'] .
                ' ' .
                $destino['apellido'] .
                ' (ID ' .
                $tecnico .
                '); técnico anterior ID ' .
                (int) $actual['id_tecnico'] .
                '. Motivo: ' .
                $motivo;
        } elseif ($accion === 'cancelar') {
            $nuevo = 8;
            $stmt = $conexion->prepare('UPDATE orden_servicio SET id_estado=8 WHERE id_orden=?');
            $stmt->bind_param('i', $id_orden);
            $nota = 'Orden cancelada por el administrador. Motivo: ' . $motivo;
        } else {
            throw new DomainException('Acción no válida.');
        }
        $stmt->execute();
        // Identifica al administrador que realizó el cambio.
        $actor = (int) $_SESSION['id_usuario'];
        $stmt = $conexion->prepare(
            'INSERT INTO historial_orden(id_orden,id_usuario,id_estado_anterior,id_estado_nuevo,observacion) VALUES(?,?,?,?,?)'
        );
        $stmt->bind_param('iiiis', $id_orden, $actor, $anterior, $nuevo, $nota);
        $stmt->execute();
        $conexion->commit();
        header('Location: ver_orden.php?id=' . $id_orden . '&guardado=1');
        exit();
    } catch (Throwable $e) {
        $conexion->rollback();
        $error_admin =
            $e instanceof DomainException
                ? $e->getMessage()
                : 'No se pudo guardar la acción. Intenta nuevamente.';
        if (!($e instanceof DomainException)) {
            error_log((string) $e);
        }
    }
}

