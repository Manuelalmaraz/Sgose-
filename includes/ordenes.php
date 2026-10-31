<?php
// Estados comprobados en la base SGOSE: 2 asignada, 3 aceptada, 4 rechazada,
// 5 en diagnóstico, 6 en proceso, 7 finalizada, 8 cancelada.
function actualizar_orden(mysqli $conexion, int $id_orden, int $id_tecnico, array $datos): void
{
    $conexion->begin_transaction();
    try {
        // Bloquea la orden para que dos envíos no registren movimientos duplicados.
        $stmt = $conexion->prepare(
            'SELECT * FROM orden_servicio WHERE id_orden = ? AND id_tecnico = ? FOR UPDATE'
        );
        $stmt->bind_param('ii', $id_orden, $id_tecnico);
        $stmt->execute();
        $orden = $stmt->get_result()->fetch_assoc();
        if (!$orden) {
            throw new DomainException('La orden no está asignada a este técnico.');
        }
        $anterior = (int) $orden['id_estado'];
        if ((int) ($datos['estado_actual'] ?? 0) !== $anterior) {
            throw new DomainException('La orden cambió. Recarga la página antes de continuar.');
        }
        // Decide qué operación pidió el técnico: aceptar, rechazar, guardar o finalizar.
        $accion = $datos['accion'] ?? '';
        $nuevo = $anterior;
        if ($accion === 'aceptar' || $accion === 'rechazar') {
            if ($anterior !== 2) {
                throw new DomainException('Solo se puede aceptar o rechazar una orden asignada.');
            }
            $nuevo = $accion === 'aceptar' ? 3 : 4;
            $nota =
                $accion === 'aceptar'
                    ? 'Orden aceptada por el técnico.'
                    : trim($datos['motivo'] ?? '');
            if ($nota === '') {
                throw new DomainException('Indica el motivo del rechazo.');
            }
            $stmt = $conexion->prepare(
                'UPDATE orden_servicio SET id_estado = ? WHERE id_orden = ?'
            );
            $stmt->bind_param('ii', $nuevo, $id_orden);
        } elseif ($accion === 'guardar' || $accion === 'finalizar') {
            if (!in_array($anterior, [3, 5, 6], true)) {
                throw new DomainException('Esta orden no admite cambios de trabajo.');
            }
            // Recibe el avance que el técnico escribió en el formulario.
            $diagnostico = trim($datos['diagnostico'] ?? '');
            $trabajo = trim($datos['trabajo_realizado'] ?? '');
            $materiales = trim($datos['materiales_utilizados'] ?? '');
            $observaciones = trim($datos['observaciones'] ?? '');
            $nuevo = (int) ($datos['id_estado'] ?? $anterior);
            $permitidos = [3 => [3, 5, 6], 5 => [5, 6], 6 => [6]];
            if (!in_array($nuevo, $permitidos[$anterior], true)) {
                throw new DomainException('El estado seleccionado no es válido.');
            }
            $fecha = null;
            if ($accion === 'finalizar') {
                if ($diagnostico === '' || $trabajo === '') {
                    throw new DomainException(
                        'Completa el diagnóstico y el trabajo realizado antes de finalizar.'
                    );
                }
                $nuevo = 7;
                $fecha = date('Y-m-d H:i:s');
            }
            if (
                $nuevo === $anterior &&
                $diagnostico === (string) $orden['diagnostico'] &&
                $trabajo === (string) $orden['trabajo_realizado'] &&
                $materiales === (string) $orden['materiales_utilizados'] &&
                $observaciones === (string) $orden['observaciones']
            ) {
                throw new DomainException('No hay cambios para guardar.');
            }
            $stmt = $conexion->prepare(
                'UPDATE orden_servicio
             SET id_estado=?, diagnostico=?, trabajo_realizado=?, materiales_utilizados=?, observaciones=?, fecha_finalizacion=?
             WHERE id_orden=?'
            );
            $stmt->bind_param(
                'isssssi',
                $nuevo,
                $diagnostico,
                $trabajo,
                $materiales,
                $observaciones,
                $fecha,
                $id_orden
            );
            $nota =
                $accion === 'finalizar' ? 'Orden finalizada.' : 'Avance del servicio actualizado.';
            foreach (
                [
                    'diagnostico' => 'Diagnóstico',
                    'trabajo_realizado' => 'Trabajo realizado',
                    'materiales_utilizados' => 'Materiales',
                    'observaciones' => 'Observaciones'
                ]
                as $campo => $etiqueta
            ) {
                $valor = trim($datos[$campo] ?? '');
                if ($valor !== (string) $orden[$campo]) {
                    $nota .= "\n" . $etiqueta . ': ' . ($valor === '' ? '(vacío)' : $valor);
                }
            }
        } else {
            throw new DomainException('Acción no válida.');
        }
        $stmt->execute();
        $stmt = $conexion->prepare(
            'INSERT INTO historial_orden (id_orden,id_usuario,id_estado_anterior,id_estado_nuevo,observacion)
             VALUES (?,?,?,?,?)'
        );
        $stmt->bind_param('iiiis', $id_orden, $id_tecnico, $anterior, $nuevo, $nota);
        $stmt->execute();
        $conexion->commit();
    } catch (Throwable $error) {
        $conexion->rollback();
        throw $error;
    }
}

