<?php
// Alta y edición de usuarios. Se desactivan cuentas sin borrar sus historiales.
session_start();
require_once '../includes/sesion.php';
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header('Location: ../index.php');
    exit();
}
// Si llega un ID se edita el usuario; sin ID se prepara un registro nuevo.
$id = max(0, (int) ($_GET['id'] ?? 0));
$error = '';
$usuario = ['nombre' => '', 'apellido' => '', 'correo' => '', 'id_rol' => 2, 'estado' => 1];
if ($id) {
    $stmt = $conexion->prepare(
        'SELECT nombre,apellido,correo,id_rol,estado FROM usuarios WHERE id_usuario=?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    if (!$usuario) {
        http_response_code(404);
        exit('Usuario no encontrado.');
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Comprueba el envío antes de recibir los datos de la cuenta.
        validar_formulario();
        $nombre = campo_texto('nombre', 100, true);
        $apellido = campo_texto('apellido', 100, true);
        $correo = campo_texto('correo', 150, true);
        $rol = (int) ($_POST['id_rol'] ?? 0);
        $estado = (int) ($_POST['estado'] ?? -1);
        $clave = (string) ($_POST['password'] ?? '');
        if (
            !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
            !in_array($rol, [1, 2], true) ||
            !in_array($estado, [0, 1], true)
        ) {
            throw new DomainException('Revisa el correo, rol y estado.');
        }
        if ((!$id || $clave !== '') && (strlen($clave) < 8 || strlen($clave) > 72)) {
            throw new DomainException('La contraseña debe tener entre 8 y 72 bytes.');
        }
        $conexion->begin_transaction();
        // Bloquea las cuentas para impedir cambios simultáneos que dejen al sistema sin administrador.
        $cuentas = $conexion
            ->query('SELECT id_usuario,id_rol,estado FROM usuarios ORDER BY id_usuario FOR UPDATE')
            ->fetch_all(MYSQLI_ASSOC);
        $admins = 0;
        $actor_valido = false;
        foreach ($cuentas as $cuenta) {
            if ($cuenta['id_rol'] == 1 && $cuenta['estado'] == 1 && $cuenta['id_usuario'] != $id) {
                $admins++;
            }
            if (
                $cuenta['id_usuario'] == $_SESSION['id_usuario'] &&
                $cuenta['id_rol'] == 1 &&
                $cuenta['estado'] == 1
            ) {
                $actor_valido = true;
            }
        }
        if (!$actor_valido) {
            throw new DomainException('Tu cuenta ya no tiene acceso de administrador.');
        }
        if ($id == $_SESSION['id_usuario'] && ($rol !== 1 || $estado !== 1)) {
            throw new DomainException(
                'No puedes desactivar tu propia cuenta ni quitarte el rol de administrador.'
            );
        }
        if ($id && !$admins && ($rol !== 1 || $estado !== 1)) {
            throw new DomainException('Debe quedar al menos un administrador activo.');
        }
        if ($id && ($rol !== 2 || $estado !== 1)) {
            $stmt = $conexion->prepare(
                'SELECT id_orden FROM orden_servicio WHERE id_tecnico=? AND id_estado IN (2,3,5,6) LIMIT 1 FOR UPDATE'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows) {
                throw new DomainException(
                    'Reasigna o cierra las órdenes pendientes antes de desactivar al técnico o cambiar su rol.'
                );
            }
        }
        // Guarda la contraseña como hash; al editar, una clave vacía conserva la anterior.
        $hash = $clave !== '' ? password_hash($clave, PASSWORD_DEFAULT) : '';
        if ($id) {
            $stmt = $conexion->prepare(
                'UPDATE usuarios
             SET nombre=?,apellido=?,correo=?,id_rol=?,estado=?,password=IF(?="",password,?)
             WHERE id_usuario=?'
            );
            $stmt->bind_param(
                'sssiissi',
                $nombre,
                $apellido,
                $correo,
                $rol,
                $estado,
                $hash,
                $hash,
                $id
            );
        } else {
            $stmt = $conexion->prepare(
                'INSERT INTO usuarios(nombre,apellido,correo,id_rol,estado,password) VALUES(?,?,?,?,?,?)'
            );
            $stmt->bind_param('sssiis', $nombre, $apellido, $correo, $rol, $estado, $hash);
        }
        $stmt->execute();
        $conexion->commit();
        header('Location: usuarios.php?guardado=1');
        exit();
    } catch (Throwable $e) {
        $conexion->rollback();
        $error =
            $e instanceof DomainException
                ? $e->getMessage()
                : ((int) $e->getCode() === 1062
                    ? 'Ese correo ya está registrado.'
                    : 'No se pudo guardar el usuario.');
        if (!($e instanceof DomainException)) {
            error_log((string) $e);
        }
    }
    // Recupera valores válidos de texto, sin devolver nunca la contraseña al navegador.
    foreach (['nombre', 'apellido', 'correo', 'id_rol', 'estado'] as $campo) {
        $usuario[$campo] = $_POST[$campo] ?? $usuario[$campo];
    }
}
cabecera_admin($id ? 'Editar usuario' : 'Nuevo usuario', 'usuarios');
?>
<div class="formulario-contenedor">
    <?php if ($error) { ?>
    <p class="mensaje-error" role="alert"><?= escapar($error) ?></p>
    <?php } ?>
    <!-- La contraseña vacía al editar conserva el hash previamente guardado. -->
    <form method="post">
        <input type="hidden" name="csrf" value="<?= escapar(token_formulario()) ?>" />
        <?php foreach ( ['nombre' => 'Nombre', 'apellido' => 'Apellido', 'correo' => 'Correo'] as $campo => $etiqueta ) { ?>
        <div class="grupo-formulario">
            <label for="<?= $campo ?>"><?= $etiqueta ?></label>
            <input
                id="<?= $campo ?>"
                name="<?= $campo ?>"
                type="<?= $campo === 'correo' ? 'email' : 'text' ?>"
                maxlength="<?= $campo === 'correo' ? 150 : 100 ?>"
                value="<?= escapar( $usuario[$campo] ) ?>"
                required
            />
        </div>
        <?php } ?>
        <div class="grupo-formulario">
            <label for="password">Contraseña <?= $id ? '(dejar vacía para conservarla)' : '' ?></label>
            <input
                type="password"
                name="password"
                id="password"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                <?= $id ? '' : 'required' ?>
            />
        </div>
        <div class="grupo-formulario">
            <label for="id_rol">Rol</label>
            <select name="id_rol" id="id_rol">
                <?php foreach ( [1 => 'Administrador', 2 => 'Técnico'] as $valor => $nombre ) { ?>
                <option value="<?= $valor ?>" <?= $usuario['id_rol'] == $valor ? 'selected' : '' ?>><?= $nombre ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="grupo-formulario">
            <label for="estado">Estado</label>
            <select name="estado" id="estado">
                <option value="1" <?= $usuario[ 'estado' ] == 1 ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= $usuario['estado'] == 0 ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <button class="btn-principal">Guardar usuario</button>
        <a class="btn secundario" href="usuarios.php">Volver</a>
    </form>
</div>
<?php pie_admin(); ?>

