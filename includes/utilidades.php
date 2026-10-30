<?php
// Utilidades compartidas: escape de salida y protección de formularios de SGOSE.
function escapar($valor): string { return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8'); }
function token_formulario(): string {
    // El token permanece en la sesión y nunca se toma de la URL.
    return $_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(32)));
}
function validar_formulario(): void {
    if (!hash_equals(token_formulario(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        throw new DomainException('El formulario venció. Recarga la página e intenta nuevamente.');
    }
}
function campo_texto(string $campo, int $limite, bool $obligatorio = false): string {
    $valor = trim((string)($_POST[$campo] ?? ''));
    if (($obligatorio && $valor === '') || mb_strlen($valor) > $limite) {
        throw new DomainException('Revisa el campo ' . str_replace('_', ' ', $campo) . ' (máximo ' . $limite . ' caracteres).');
    }
    return $valor;
}
// Encabezado sencillo que conserva el menú y estilos de las pantallas existentes.
function cabecera_admin(string $titulo, string $activo): void { ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= escapar($titulo) ?> - SGOSE</title><link rel="stylesheet" href="../css/admin.css"><link rel="stylesheet" href="../css/orden.css"></head><body>
<aside class="sidebar"><h2>SGOSE</h2><?php foreach (['dashboard'=>'Dashboard','clientes'=>'Clientes','ordenes'=>'Órdenes','usuarios'=>'Usuarios','historial'=>'Historial'] as $ruta=>$nombre) { ?><a href="<?= $ruta ?>.php" class="<?= $activo === $ruta ? 'activo' : '' ?>"><?= $nombre ?></a><?php } ?><a href="../logout.php">Cerrar sesión</a></aside>
<main class="contenido"><div class="encabezado"><h1><?= escapar($titulo) ?></h1></div>
<?php }
function pie_admin(): void { echo '</main></body></html>'; }
