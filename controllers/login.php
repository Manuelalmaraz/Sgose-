<?php

// INICIA  SESIÓN.
session_start();


// CARGA LA CONEXIÓN A DATOS.
require_once "../config/conexion.php";

// SOLO ACEPTA SOLICITUDES POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

// RECIBE LOS DATOS
$correo = trim($_POST["correo"] ?? "");
$password = $_POST["password"] ?? "";

// RECHAZA CAMPOS VACÍOS EN LAS CREDNCIALES .
if (empty($correo) || empty($password)) {
    header("Location: ../index.php?error=campos");
    exit;
}

// BUSCAR AL USUARIO Y SU ROL POR CORREO.
$sql = "SELECT
            u.id_usuario,
            u.nombre,
            u.apellido,
            u.correo,
            u.password,
            u.estado,
            r.id_rol,
            r.nombre_rol
        FROM usuarios u
        INNER JOIN roles r
            ON u.id_rol = r.id_rol
        WHERE u.correo = ?
        LIMIT 1";

// CONSULTA.
$stmt = $conexion->prepare($sql);

// ASIGNA EL CORREO 
$stmt->bind_param("s", $correo);

// EJECUTA LA CONSULTA.
$stmt->execute();

// RECIBE RESULTADO.
$resultado = $stmt->get_result();

// FALLA AL NO ENCUENTRA AL USUARIO.
if ($resultado->num_rows !== 1) {
    header("Location: ../index.php?error=credenciales");
    exit;
}

// GUARDA LOS DATOS EN UN ARRAY.
$usuario = $resultado->fetch_assoc();

// RECHAZA USUARIOS INACTIVOS.
if ($usuario["estado"] != 1) {
    header("Location: ../index.php?error=inactivo");
    exit;
}

// COMPROBAR LA CONTRASEÑA.
if (!password_verify($password, $usuario["password"])) {
    header("Location: ../index.php?error=credenciales");
    exit;
}

// GUARDA LOS DATOS DEL USUARIO EN LA SESIÓN.
$_SESSION["id_usuario"] = $usuario["id_usuario"];
$_SESSION["nombre"] = $usuario["nombre"];
$_SESSION["apellido"] = $usuario["apellido"];
$_SESSION["correo"] = $usuario["correo"];
$_SESSION["id_rol"] = $usuario["id_rol"];
$_SESSION["rol"] = $usuario["nombre_rol"];

// MANDA AL ADMINISTRADOR A SU PANEL.
if ($usuario["id_rol"] == 1) {

    header("Location: ../admin/dashboard.php");
    exit;

} elseif ($usuario["id_rol"] == 2) {

    // MANDA AL TÉCNICO A SU PANEL.
    header("Location: ../tecnico/dashboard.php");
    exit;

}

// VUELVE AL INICIO SI EL ROL NO ES VÁLIDO.
header("Location: ../index.php?error=rol");
exit;

?>
