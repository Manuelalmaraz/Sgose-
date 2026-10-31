<?php
// Esta página permite al administrador corregir los datos de una orden abierta.
// El diagnóstico, los materiales y el trabajo se siguen editando desde el técnico.

session_start();
require_once '../includes/sesion.php';

// Comprueba que haya una sesión y que el usuario sea administrador.
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header('Location: ../index.php');
    exit();
}

// Lee el número de orden recibido en la dirección y consulta sus datos.
$id = (int) ($_GET['id'] ?? 0);
$stmt = $conexion->prepare('SELECT * FROM orden_servicio WHERE id_orden=?');
$stmt->bind_param('i', $id);
$stmt->execute();
$orden = $stmt->get_result()->fetch_assoc();
if (!$orden) {
    http_response_code(404);
    exit('Orden no encontrada.');
}

$error = '';

// El formulario envía los cambios por POST cuando se pulsa Guardar cambios.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Revisa el token para comprobar que el envío corresponde a esta sesión.
        validar_formulario();

        // Recoge los campos editables y valida sus tamaños.
        $servicio = campo_texto('servicio_solicitado', 255, true);
        $observaciones = campo_texto('observaciones', 10000);
        $categoria = (int) ($_POST['id_categoria'] ?? 0);
        $tecnico = (int) ($_POST['id_tecnico'] ?? 0);
        // Misma entrada de hora escrita en formato AM/PM que Nueva orden.
        $dia = trim($_POST['fecha_programada'] ?? '');
        $hora = trim($_POST['hora_programada'] ?? '');
        $fecha = null;
        if ($dia !== '' || $hora !== '') {
            if (
                !$dia ||
                !preg_match('/^(0?[1-9]|1[0-2]):([0-5][0-9])\s*(AM|PM)$/iD', $hora, $partes)
            ) {
                throw new DomainException(
                    'Completa la fecha y una hora válida, por ejemplo 02:30 PM.'
                );
            }
            // Convierte 12 AM a 00 y suma 12 para las horas de la tarde.
            $hora_24 = (int) $partes[1] % 12;

            if (strtoupper($partes[3]) === 'PM') {
                $hora_24 += 12;
            }

            $minutos = $partes[2];
            $texto = $dia . 'T' . sprintf('%02d:%s', $hora_24, $minutos);
            $fecha_objeto = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $texto);
            if (!$fecha_objeto || $fecha_objeto->format('Y-m-d\TH:i') !== $texto) {
                throw new DomainException('La fecha programada no es válida.');
            }
            $fecha = $fecha_objeto->format('Y-m-d H:i:s');
        }

        // La orden y su historial deben guardarse juntos; si algo falla, se revierte todo.
        $conexion->begin_transaction();

        // Bloquea al técnico antes de la orden, como en creación y reasignación.
        $stmt = $conexion->prepare(
            'SELECT id_usuario
             FROM usuarios
             WHERE id_usuario = ? AND id_rol = 2 AND estado = 1
             FOR UPDATE'
        );
        $stmt->bind_param('i', $tecnico);
        $stmt->execute();
        $tecnico_valido = $stmt->get_result()->num_rows > 0;
        if (!$tecnico_valido) {
            throw new DomainException('Selecciona un técnico activo.');
        }
        $stmt = $conexion->prepare(
            'SELECT id_categoria FROM categorias_servicio WHERE id_categoria=?'
        );
        $stmt->bind_param('i', $categoria);
        $stmt->execute();
        if (!$stmt->get_result()->num_rows) {
            throw new DomainException('Selecciona una categoría válida.');
        }
        $stmt = $conexion->prepare('SELECT * FROM orden_servicio WHERE id_orden=? FOR UPDATE');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $actual = $stmt->get_result()->fetch_assoc();
        if (!$actual) {
            throw new DomainException('La orden ya no existe.');
        }
        $anterior = (int) $actual['id_estado'];
        if (in_array($anterior, [7, 8], true)) {
            throw new DomainException('Las órdenes finalizadas o canceladas no se pueden editar.');
        }
        // Detecta cambios hechos por otro usuario desde que se abrió el formulario.
        if (
            !hash_equals(hash('sha256', json_encode($actual)), (string) ($_POST['version'] ?? ''))
        ) {
            throw new DomainException(
                'La orden cambió mientras la editabas. Vuelve a abrirla antes de guardar.'
            );
        }
        // Compara los valores recibidos con los actuales para describir los cambios.
        $valores = [
            'servicio_solicitado' => $servicio,
            'id_categoria' => $categoria,
            'id_tecnico' => $tecnico,
            'fecha_programada' => $fecha,
            'observaciones' => $observaciones
        ];
        $etiquetas = [
            'servicio_solicitado' => 'Servicio solicitado',
            'id_categoria' => 'Categoría (ID)',
            'id_tecnico' => 'Técnico (ID)',
            'fecha_programada' => 'Fecha programada',
            'observaciones' => 'Observaciones'
        ];
        $nota = 'Orden editada por el administrador.';
        $cambios = 0;
        foreach ($valores as $campo => $valor) {
            if ((string) $valor !== (string) $actual[$campo]) {
                $valor_anterior = $actual[$campo] ?? '(sin registrar)';
                $valor_nuevo = $valor ?? '(sin registrar)';

                $nota .= "\n" . $etiquetas[$campo] . ': ';
                $nota .= $valor_anterior . ' → ' . $valor_nuevo;
                $cambios++;
            }
        }
        if (!$cambios) {
            throw new DomainException('No hay cambios para guardar.');
        }
        // Cambiar técnico requiere nueva aceptación; los avances anteriores se conservan.
        $nuevo = $tecnico !== (int) $actual['id_tecnico'] ? 2 : $anterior;
        $stmt = $conexion->prepare(
            'UPDATE orden_servicio
             SET servicio_solicitado = ?,
                 id_categoria = ?,
                 id_tecnico = ?,
                 fecha_programada = ?,
                 observaciones = ?,
                 id_estado = ?
             WHERE id_orden = ?'
        );
        $stmt->bind_param(
            'siissii',
            $servicio,
            $categoria,
            $tecnico,
            $fecha,
            $observaciones,
            $nuevo,
            $id
        );
        $stmt->execute();
        $actor = (int) $_SESSION['id_usuario'];
        // Registra quién editó, el estado anterior, el nuevo y los campos modificados.
        $stmt = $conexion->prepare(
            'INSERT INTO historial_orden
                (id_orden, id_usuario, id_estado_anterior, id_estado_nuevo, observacion)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iiiis', $id, $actor, $anterior, $nuevo, $nota);
        $stmt->execute();
        $conexion->commit();
        header('Location: ver_orden.php?id=' . $id . '&guardado=1');
        exit();
    } catch (Throwable $e) {
        $conexion->rollback();
        $error =
            $e instanceof DomainException
                ? $e->getMessage()
                : 'No se pudieron guardar los cambios. La orden se conservó.';
        // Los errores de validación se muestran al usuario; los internos van al registro.
        if (!($e instanceof DomainException)) {
            error_log((string) $e);
        }
    }
}
// Carga por separado las categorías y los técnicos para que el formulario sea fácil de seguir.
$categorias = $conexion->query(
    'SELECT id_categoria, nombre_categoria
     FROM categorias_servicio
     ORDER BY nombre_categoria'
);

$tecnicos = $conexion->query(
    'SELECT id_usuario, nombre, apellido
             FROM usuarios
             WHERE id_rol = 2 AND estado = 1
             ORDER BY nombre'
);

// Al fallar una validación, conserva lo escrito para no obligar a llenar todo de nuevo.
$servicio_formulario = $_POST['servicio_solicitado'] ?? $orden['servicio_solicitado'];
$categoria_seleccionada = $_POST['id_categoria'] ?? $orden['id_categoria'];
$tecnico_seleccionado = $_POST['id_tecnico'] ?? $orden['id_tecnico'];
$observaciones_formulario = $_POST['observaciones'] ?? $orden['observaciones'];

$fecha_formulario = '';
$hora_formulario = '';

if ($orden['fecha_programada']) {
    $fecha_formulario = date('Y-m-d', strtotime($orden['fecha_programada']));
    $hora_formulario = date('h:i A', strtotime($orden['fecha_programada']));
}

$fecha_formulario = $_POST['fecha_programada'] ?? $fecha_formulario;
$hora_formulario = $_POST['hora_programada'] ?? $hora_formulario;

// La versión permite detectar si alguien cambió la orden mientras estaba abierta aquí.
$version_formulario = $_POST['version'] ?? hash('sha256', json_encode($orden));
$orden_cerrada = in_array((int) $orden['id_estado'], [7, 8], true);

cabecera_admin('Editar orden ' . $orden['numero_orden'], 'ordenes');
?>

<div class="formulario-contenedor">
    <!-- Muestra el mensaje si falta algún dato o no se pudo guardar. -->
    <?php if ($error) { ?>
    <p class="mensaje-error" role="alert"><?= escapar($error) ?></p>
    <?php } ?>

    <?php if ($orden_cerrada) { ?>

    <p>Esta orden está cerrada y no admite cambios.</p>
    <a class="btn" href="ordenes.php">Volver</a>

    <?php } else { ?>

    <form method="post">
        <!-- Estos datos protegen el envío y detectan cambios de otro usuario. -->
        <input type="hidden" name="csrf" value="<?= escapar(token_formulario()) ?>" />
        <input type="hidden" name="version" value="<?= escapar($version_formulario) ?>" />

        <div class="grupo-formulario">
            <label for="servicio">Servicio solicitado</label>
            <textarea id="servicio" name="servicio_solicitado" maxlength="255" rows="3" required>
<?= escapar($servicio_formulario) ?></textarea>
        </div>

        <!-- Las categorías salen de la base de datos. -->
        <div class="grupo-formulario">
            <label for="id_categoria">Categoría</label>
            <select id="id_categoria" name="id_categoria" required>
                <option value="">Seleccionar</option>

                <?php while ($categoria_fila = $categorias->fetch_assoc()) { ?>
                <option value="<?= (int) $categoria_fila['id_categoria'] ?>" <?= $categoria_seleccionada == $categoria_fila['id_categoria'] ? 'selected' : '' ?>><?= escapar($categoria_fila['nombre_categoria']) ?></option>
                <?php } ?>
            </select>
        </div>

        <!-- Solo permite seleccionar técnicos que tengan su cuenta activa. -->
        <div class="grupo-formulario">
            <label for="id_tecnico">Técnico</label>
            <select id="id_tecnico" name="id_tecnico" required>
                <option value="">Seleccionar</option>

                <?php while ($tecnico_fila = $tecnicos->fetch_assoc()) { ?>
                <option value="<?= (int) $tecnico_fila['id_usuario'] ?>" <?= $tecnico_seleccionado == $tecnico_fila['id_usuario'] ? 'selected' : '' ?>><?= escapar( $tecnico_fila['nombre'] . ' ' . $tecnico_fila['apellido'] ) ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="programacion-alineada">
            <div class="grupo-formulario">
                <label for="fecha">Fecha programada</label>
                <input type="date" id="fecha" name="fecha_programada" value="<?= escapar($fecha_formulario) ?>" />
            </div>

            <div class="grupo-formulario">
                <label for="hora">Hora programada</label>
                <input
                    type="text"
                    id="hora"
                    name="hora_programada"
                    maxlength="8"
                    placeholder="02:30 PM"
                    value="<?= escapar($hora_formulario) ?>"
                />
            </div>
        </div>

        <div class="grupo-formulario">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" rows="3" maxlength="10000">
<?= escapar($observaciones_formulario) ?></textarea>
        </div>

        <div class="acciones-formulario">
            <button class="btn-principal">Guardar cambios</button>
            <a class="btn secundario" href="ordenes.php">Cancelar</a>
        </div>
    </form>

    <?php } ?>
</div>

<?php pie_admin(); ?>

