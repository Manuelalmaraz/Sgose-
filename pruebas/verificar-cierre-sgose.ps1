param([Parameter(Mandatory)][string]$AdminPassword,[Parameter(Mandatory)][string]$TecnicoPassword,[Parameter(Mandatory)][string]$OtroTecnicoPassword)
# Repite el recorrido técnico antes de verificar los módulos del cierre.
. "$PSScriptRoot\verificar-sgose.ps1" -AdminPassword $AdminPassword -TecnicoPassword $TecnicoPassword -OtroTecnicoPassword $OtroTecnicoPassword
function Token($path,$session) {
    $p=Page $path $session
    $t=[regex]::Match($p.Content,'name="csrf" value="([^"]+)"').Groups[1].Value
    if(!$t){throw "Falta token en $path"}; return $t
}
function Send($path,$session,$body) {
    $r=Invoke-WebRequest "$base/$path" -WebSession $session -Method Post -Body $body -UseBasicParsing -SkipHttpErrorCheck
    Check ($r.Content -notmatch '(?i)Fatal error|Warning:|Uncaught') "POST $path sin errores PHP"
    return $r
}
$stamp=Get-Date -Format yyyyMMddHHmmss
$email="prueba-sgose-$stamp@example.test"
$tempPassword=[guid]::NewGuid().ToString('N')
$userId=0; $clientId=0; $orderId=0
try {
    # Alta y edición sin modificar cuentas previas; las contraseñas nunca se imprimen.
    $body=@{csrf=(Token 'admin/usuario.php' $admin);nombre='Prueba';apellido='SGOSE';correo=$email;password=$tempPassword;id_rol=2;estado=1}
    $r=Send 'admin/usuario.php' $admin $body
    $userId=[int](Sql "SELECT id_usuario FROM usuarios WHERE correo='$email'")
    Check ($userId -gt 0) 'Alta de técnico real'
    $r=Send 'admin/usuario.php' $admin $body
    Check ($r.Content -match 'correo ya está registrado') 'Correo duplicado con mensaje claro'
    $tempSession=New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $r=Send 'controllers/login.php' $tempSession @{correo=$email;password=$tempPassword}
    Check ($r.Content -match 'Panel del técnico') 'Login del usuario recién creado'
    $r=Page 'admin/usuarios.php' $tempSession
    Check ($r.Content -notmatch 'Gestión de usuarios') 'Técnico no administra usuarios'
    $edit=@{csrf=(Token "admin/usuario.php?id=$userId" $admin);nombre='Prueba editada';apellido='SGOSE';correo=$email;password='';id_rol=2;estado=1}
    $r=Send "admin/usuario.php?id=$userId" $admin $edit
    Check ((Sql "SELECT nombre FROM usuarios WHERE id_usuario=$userId") -eq 'Prueba editada') 'Edición de usuario'
    $tempSession=New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $r=Send 'controllers/login.php' $tempSession @{correo=$email;password=$tempPassword}
    Check ($r.Content -match 'Panel del técnico') 'Contraseña vacía conserva acceso'
    $actor=Sql "SELECT id_usuario FROM usuarios WHERE correo='admin@sgose.com'"
    $parts=(Sql "SELECT nombre,apellido,correo FROM usuarios WHERE id_usuario=$actor") -split "`t"
    $r=Send "admin/usuario.php?id=$actor" $admin @{csrf=(Token "admin/usuario.php?id=$actor" $admin);nombre=$parts[0];apellido=$parts[1];correo=$parts[2];password='';id_rol=1;estado=0}
    Check ($r.Content -match 'No puedes desactivar') 'Autodesactivación del administrador bloqueada'
    # El cliente creado desde Nueva orden debe quedar seleccionado al regresar.
    $r=Send 'admin/nuevo_cliente.php?desde=orden' $admin @{csrf=(Token 'admin/nuevo_cliente.php?desde=orden' $admin);desde='orden';nombre="PRUEBA-SGOSE-$stamp";apellido='Temporal';telefono='5555-0000';correo='';direccion='Prueba'}
    $clientId=[int](Sql "SELECT id_cliente FROM clientes WHERE nombre='PRUEBA-SGOSE-$stamp'")
    Check ($clientId -gt 0 -and $r.Content -match ('value="'+$clientId+'"\s+selected')) 'Nuevo cliente regresa seleccionado a la orden'
    $r=Send "admin/editar_cliente.php?id=$clientId" $admin @{csrf=(Token "admin/editar_cliente.php?id=$clientId" $admin);nombre="PRUEBA-SGOSE-$stamp";apellido='Actualizado';telefono='5555-0001';correo='temporal@example.test';direccion='Dirección actualizada'}
    Check ((Sql "SELECT telefono FROM clientes WHERE id_cliente=$clientId") -eq '5555-0001') 'Edición real del cliente'
    $r=Page "admin/clientes.php?buscar=PRUEBA-SGOSE-$stamp" $admin
    Check ($r.Content -match 'Actualizado') 'Búsqueda de clientes'
    # Rechazo, reasignación, aceptación y cancelación con historial completo.
    $r=Send 'admin/nueva_orden.php' $admin @{csrf=(Token 'admin/nueva_orden.php' $admin);id_cliente=$clientId;id_categoria=$categoria;tipo_solicitud='Servicio';servicio_solicitado="PRUEBA-SGOSE-CIERRE-$stamp";id_tecnico=2;fecha_programada='2026-11-02';hora_programada='10:30 AM';observaciones='Prueba temporal'}
    $orderId=[int](Sql "SELECT id_orden FROM orden_servicio WHERE servicio_solicitado='PRUEBA-SGOSE-CIERRE-$stamp'")
    Check ($orderId -gt 0) 'Creación con fecha programada'
    $r=PostOrder $orderId $tecnico @{accion='rechazar';motivo='Prueba reasignación'}
    $adminBody=@{csrf=(Token "admin/ver_orden.php?id=$orderId" $admin);estado_actual=4;tecnico_actual=2;accion='reasignar';id_tecnico=$userId;motivo='Reasignación de prueba'}
    $r=Send "admin/ver_orden.php?id=$orderId" $admin $adminBody
    Check ((Sql "SELECT COUNT(*) FROM orden_servicio WHERE id_orden=$orderId AND id_tecnico=$userId AND id_estado=2") -eq 1) 'Reasignación de rechazada a técnico activo'
    $r=Send "admin/ver_orden.php?id=$orderId" $admin $adminBody
    Check ((Sql "SELECT COUNT(*) FROM historial_orden WHERE id_orden=$orderId") -eq 3) 'Reasignación repetida no duplica historial'
    $r=Page "tecnico/ver_orden.php?id=$orderId" $tecnico
    Check ($r.Content -notmatch "PRUEBA-SGOSE-CIERRE-$stamp") 'Técnico anterior pierde acceso'
    $r=PostOrder $orderId $tempSession @{accion='aceptar'}
    Check ((Sql "SELECT id_estado FROM orden_servicio WHERE id_orden=$orderId") -eq 3) 'Nuevo técnico acepta la reasignación'
    $edit.estado=0
    $r=Send "admin/usuario.php?id=$userId" $admin $edit
    Check ($r.Content -match 'Reasigna o cierra' -and (Sql "SELECT estado FROM usuarios WHERE id_usuario=$userId") -eq 1) 'Desactivación bloqueada mientras tiene órdenes activas'
    $r=Send "admin/ver_orden.php?id=$orderId" $admin @{csrf=(Token "admin/ver_orden.php?id=$orderId" $admin);estado_actual=3;tecnico_actual=$userId;accion='cancelar';motivo='Cancelación de prueba'}
    Check ((Sql "SELECT id_estado FROM orden_servicio WHERE id_orden=$orderId") -eq 8) 'Cancelación administrativa'
    Check ((Sql "SELECT COUNT(*) FROM historial_orden WHERE id_orden=$orderId") -eq 5) 'Historial de rechazo, reasignación, aceptación y cancelación'
    $r=Send "admin/ver_orden.php?id=$orderId" $admin @{csrf=$adminBody.csrf;estado_actual=8;tecnico_actual=$userId;accion='reasignar';id_tecnico=3;motivo='No permitido'}
    Check ((Sql "SELECT id_estado FROM orden_servicio WHERE id_orden=$orderId") -eq 8) 'Orden cancelada no se reasigna'
    $r=Page "admin/ordenes.php?cliente=$clientId" $admin
    Check ($r.Content -match "PRUEBA-SGOSE-CIERRE-$stamp") 'Historial del cliente conectado con sus órdenes'
    $r=Send "admin/usuario.php?id=$userId" $admin $edit
    Check ((Sql "SELECT estado FROM usuarios WHERE id_usuario=$userId") -eq 0) 'Desactivar técnico sin órdenes pendientes'
    $r=Page 'tecnico/dashboard.php' $tempSession
    Check ($r.Content -notmatch 'Panel del técnico') 'Sesión existente revocada al desactivar'
    $r=Send 'controllers/login.php' $tempSession @{correo=$email;password=$tempPassword}
    Check ($r.Content -notmatch 'Panel del técnico') 'Login inactivo bloqueado'
    $r=Send 'admin/nuevo_cliente.php' $admin @{csrf='invalido';nombre='NO-GUARDAR';telefono='123'}
    Check ($r.StatusCode -eq 403) 'Alta de cliente protegida con token'
    foreach($path in @('admin/dashboard.php','admin/clientes.php','admin/usuarios.php','admin/historial.php','admin/ordenes.php','admin/nueva_orden.php','tecnico/dashboard.php','tecnico/ordenes.php')) {
        $session=if($path -like 'admin/*'){$admin}else{$tecnico}; $null=Page $path $session
    }
} finally {
    # Retira solo los registros de esta ejecución; conserva datos previos.
    if($orderId -gt 0){$null=Sql "DELETE FROM historial_orden WHERE id_orden=$orderId; DELETE FROM orden_servicio WHERE id_orden=$orderId AND servicio_solicitado='PRUEBA-SGOSE-CIERRE-$stamp';"}
    if($clientId -gt 0){$null=Sql "DELETE FROM clientes WHERE id_cliente=$clientId AND nombre='PRUEBA-SGOSE-$stamp';"}
    if($userId -gt 0){$null=Sql "DELETE FROM usuarios WHERE id_usuario=$userId AND correo='$email';"}
    Write-Output 'Datos temporales del cierre retirados.'
}






