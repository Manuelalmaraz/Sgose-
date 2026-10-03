<?php

// RECUPERA LA SESIÓN.
session_start();

// BORRA LOS DATOS DE LA SESIÓN.
session_unset();
// DESTRUYE LA SESIÓN.
session_destroy();

// REDIRIGE A INDEX.PHP.
header("Location: index.php");
// DETIENE EL CÓDIGO.
exit;

?>
