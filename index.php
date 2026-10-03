<!DOCTYPE html>

<html lang="es">
<head>
    
    <meta charset="UTF-8">
    <!-- AJUSTAR PANTALLA DEL DISPOSITIVO. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- NOMBRE DE LA PESTAÑA  -->
    <title>SGOSE - Iniciar sesión</title>

    <!--PRECARGA ESTILOS DEL SISTEMA -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

        <div class="login-container">

    
        <div class="login-card">

            <!-- ENCABEZADO DEL FORMULARIO PRINCIPAL LOGIN  -->
            <div class="logo">
                <div class="icono">⚡</div>

                <div>
                    <h1>SGOSE</h1>
                    <p>
                        Sistema Web de Gestión de<br>
                        Órdenes de Servicio Eléctrico
                    </p>
                </div>
            </div>

            <h2>Iniciar sesión</h2>

            <p class="descripcion">
                Ingresa tus credenciales para acceder al sistema.
            </p>

            <!-- FORMULARIO DE INICIO DE SESIÓN -->
            <form action="controllers/login.php" method="POST">

                <!-- SE SOLICITA UN FORMATO VALIDO PARA EL CAMPO, placeholder MUESTRA COMO UN EJEMPLO.
                     required ESTO PIDE NO DEJAR EL CAMPO VACIO -->
                <div class="campo">
                    <label>Correo electrónico</label>
                    <input
                        type="email"
                        name="correo"
                        placeholder="ejemplo@correo.com"
                        required
                    >
                </div>

                <!-- Contraseña: type="password" NO PERMITE VER LO QUE SE ESCRIBE EN PANTALLA
                      required ESTO PIDE NO DEJAR EL CAMPO VACIO. -->
                <div class="campo">
                    <label>Contraseña</label>
                    <input
                        type="password"
                        name="password"
                        placeholder="Ingrese su contraseña"
                        required
                    >
                </div>

                <!-- BOTON PARA ENVIAR EL FORMULARIO. -->
                <button type="submit">
                    Iniciar sesión
                </button>

            </form>

            <!-- INDICA QUIEN INGRESA AL SISTEMA -->
            <p class="pie">
                Acceso para administrador y técnicos.
            </p>

        </div>

        <!-- PRESENTACION DE LA INFORMACION DEL AEMPRESA -->
        <div class="login-info">

            <div>
                <h2>
                    Servicios eléctricos más eficientes
                </h2>

                <p>
                    Para una gestión organizada de clientes,
                    técnicos y órdenes de servicio.
                </p>
            </div>

        </div>

    </div>

</body>
</html>
