<?php
// Se incluye después de session_start: revoca sesiones de cuentas inactivas
// y aplica de inmediato los cambios de rol guardados por el administrador.
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/utilidades.php';
if (isset($_SESSION['id_usuario'])) {
    $sesion_id = (int) $_SESSION['id_usuario'];
    $sesion_stmt = $conexion->prepare(
        'SELECT nombre,apellido,id_rol,estado FROM usuarios WHERE id_usuario=?'
    );
    $sesion_stmt->bind_param('i', $sesion_id);
    $sesion_stmt->execute();
    $sesion_usuario = $sesion_stmt->get_result()->fetch_assoc();
    if (!$sesion_usuario || !$sesion_usuario['estado']) {
        session_unset();
        session_destroy();
        header('Location: ../index.php?error=inactivo');
        exit();
    }
    $_SESSION['id_rol'] = (int) $sesion_usuario['id_rol'];
    $_SESSION['nombre'] = $sesion_usuario['nombre'];
    $_SESSION['apellido'] = $sesion_usuario['apellido'];
}
