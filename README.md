# SGOSE — Sistema de gestión de órdenes de servicio

Versión local completada el 29 de octubre de 2026. PHP, MySQL/MariaDB y CSS, manteniendo la estructura sencilla del proyecto.

## Abrir el sistema

Dirección: http://localhost:8081/sgose/
Carpeta activa: C:\xampp\htdocs\sgose

Apache usa 8081 y la base original de XAMPP usa 3306. El MySQL80 de la práctica está detenido y se inicia manualmente para evitar conflictos de puerto. Para volver a iniciar SGOSE después de reiniciar Windows, ejecuta desde esta carpeta:

```powershell
powershell -File .\iniciar-sgose.ps1
```

El iniciador comprueba los puertos y exige acceso exclusivo al archivo InnoDB antes de iniciar MySQL. Si otro proceso utiliza esos datos, se detiene sin abrir una segunda instancia.

La conexión admite SGOSE_DB_HOST, SGOSE_DB_PORT, SGOSE_DB_USER, SGOSE_DB_PASSWORD y SGOSE_DB_NAME. Los valores predeterminados corresponden al XAMPP local existente. No se cambió el esquema de las tablas. Las cuentas originales y sus contraseñas se conservaron.

## Uso

Administrador:
1. Registrar o editar clientes y buscar por nombre o teléfono. Historial abre las órdenes de ese cliente.
2. Crear una orden, seleccionar categoría y técnico activo, y programar fecha opcional. Un cliente creado desde Nueva orden regresa seleccionado.
3. Consultar órdenes y filtrar por cliente, número, estado o técnico.
4. Reasignar una orden abierta o rechazada: indicar técnico y motivo. El destinatario debe aceptarla; el trabajo anterior permanece registrado.
5. Cancelar una orden abierta con motivo. La cancelación conserva los datos y su historial.
6. Crear o editar usuarios, cambiar rol, activar/desactivar y cambiar contraseña. Eliminar abre una confirmación y solo permite borrar cuentas sin órdenes ni historial; tu propia cuenta está protegida. Dejar la contraseña vacía al editar conserva la actual.

Técnico:
1. Consultar solo sus órdenes asignadas.
2. Aceptar o rechazar, indicando motivo al rechazar.
3. Guardar diagnóstico, trabajo realizado, materiales y observaciones; avanzar a En diagnóstico o En proceso.
4. Finalizar con diagnóstico y trabajo obligatorios. Se guarda la fecha de finalización.

Ambos paneles muestran datos reales. Una orden finalizada o cancelada queda disponible para consulta sin permitir nuevos cambios.

## Integridad y acceso

Las actualizaciones de orden y su historial se guardan en una transacción. Los formularios validan tokens de sesión; los bloqueos y la comprobación del estado evitan duplicar movimientos por envíos repetidos. Los datos mostrados se escapan para que el texto no se ejecute como HTML.

No se elimina a usuarios vinculados con movimientos. No es posible desactivar la propia cuenta administradora ni dejar el sistema sin administrador activo. Para desactivar o cambiar el rol de un técnico, primero se deben resolver sus órdenes pendientes. Las cuentas desactivadas pierden acceso también en sesiones abiertas.

## Archivos para revisar en Visual Studio Code

- admin/usuarios.php: listado, búsqueda y cifras reales de usuarios.
- admin/usuario.php: alta, edición, contraseña y estado de cuentas.
- admin/nuevo_cliente.php y admin/editar_cliente.php: formulario compartido de clientes.
- admin/ver_orden.php: consulta y acciones del administrador.
- includes/administrar_orden.php: reasignación y cancelación con historial.
- includes/ordenes.php: ciclo del técnico y transacciones.
- includes/crear_orden.php: creación validada de la orden y su historial.
- includes/sesion.php y includes/utilidades.php: comprobación de cuenta activa y formularios.
- iniciar-sgose.ps1: inicio local de los servicios necesarios.

Los archivos nuevos incluyen comentarios que explican su propósito y los controles relevantes.

## Verificación realizada

Sintaxis de todos los PHP y pruebas HTTP con MySQL real: creación de cliente desde orden, edición y búsqueda de clientes, alta y edición de usuarios, correo duplicado, acceso con contraseña conservada, autodesactivación bloqueada, aceptación y rechazo, diagnóstico y trabajo, finalización, reasignación, cancelación, historial, filtros, token inválido, acceso de otro técnico, envíos repetidos y revocación de sesión al desactivar.

Las pruebas usan registros temporales y los retiran al finalizar. Las órdenes y cuentas originales se conservan. Los scripts están en pruebas/; reciben las contraseñas por parámetros y no las almacenan:

```powershell
.\pruebas\verificar-cierre-sgose.ps1 -AdminPassword <clave-admin> -TecnicoPassword <clave-tecnico-1> -OtroTecnicoPassword <clave-tecnico-2>
```

Esta entrega corresponde al sistema local. Publicarlo en internet requiere configurar el servidor y sus credenciales de producción.


