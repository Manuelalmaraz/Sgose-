<?php // Valida el token de sesión y envía las acciones del técnico al servicio transaccional. ?>
<?php
$error = '';
$_SESSION['csrf_orden'] = $_SESSION['csrf_orden'] ?? bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_orden'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        $error = 'El formulario venció. Recarga la página.';
    } else {
        require_once __DIR__ . '/../includes/ordenes.php';
        try {
            actualizar_orden($conexion, $id_orden, (int)$id_tecnico, $_POST);
            header('Location: ver_orden.php?id=' . $id_orden . '&mensaje=guardada');
            exit;
        } catch (DomainException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log((string)$e);
            $error = 'No se pudo guardar. Los cambios no fueron aplicados; intenta nuevamente.';
        }
    }
}
?>

