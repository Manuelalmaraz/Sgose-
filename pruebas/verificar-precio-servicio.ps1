param([Parameter(Mandatory)][string]$AdminPassword,[Parameter(Mandatory)][string]$TecnicoPassword)
# Verifica precios con una orden temporal: registro, edición por administrador y bloqueo del técnico.
$ErrorActionPreference='Stop'
$base='http://localhost:8081/sgose'
function Sql($q){$r=& C:\xampp\mysql\bin\mysql.exe --host=127.0.0.1 --port=3306 -u root -D sgose -N -B -e $q;if($LASTEXITCODE){throw 'Error SQL'};return $r}
function Check($ok,$msg){if(!$ok){throw $msg};Write-Output "OK: $msg"}
function GetToken($html,$campo){return [regex]::Match($html,('name="'+$campo+'"\s+value="([^"]+)"')).Groups[1].Value}
function Send($path,$sesion,$body){$r=Invoke-WebRequest "$base/$path" -WebSession $sesion -Method Post -Body $body -UseBasicParsing;if($r.Content -match 'Fatal error|Warning:|Uncaught'){throw 'Error PHP'};return $r}
$admin=New-Object Microsoft.PowerShell.Commands.WebRequestSession
$tec=New-Object Microsoft.PowerShell.Commands.WebRequestSession
$null=Send 'controllers/login.php' $admin @{correo='admin@sgose.com';password=$AdminPassword}
$null=Send 'controllers/login.php' $tec @{correo='estuardo@sgose.com';password=$TecnicoPassword}
$marca='PRUEBA-PRECIO-'+[guid]::NewGuid().ToString('N');$id=0
try {
    $cliente=Sql 'SELECT MIN(id_cliente) FROM clientes';$categoria=Sql 'SELECT MIN(id_categoria) FROM categorias_servicio'
    $p=Invoke-WebRequest "$base/admin/nueva_orden.php" -WebSession $admin -UseBasicParsing
    $body=@{csrf=(GetToken $p.Content 'csrf');id_cliente=$cliente;id_categoria=$categoria;id_tecnico=2;tipo_solicitud='Servicio';servicio_solicitado=$marca;observaciones='';fecha_programada='';hora_programada='';costo_servicio='125.50'}
    $null=Send 'admin/nueva_orden.php' $admin $body
    $id=[int](Sql "SELECT id_orden FROM orden_servicio WHERE servicio_solicitado='$marca'")
    Check ($id -gt 0 -and (Sql "SELECT costo_servicio FROM orden_servicio WHERE id_orden=$id") -eq '125.50') 'Administrador registra precio al crear'
    $p=Invoke-WebRequest "$base/tecnico/ver_orden.php?id=$id" -WebSession $tec -UseBasicParsing
    $t=GetToken $p.Content 'csrf'
    $null=Send "tecnico/ver_orden.php?id=$id" $tec @{csrf=$t;estado_actual=2;accion='aceptar'}
    $p=Invoke-WebRequest "$base/tecnico/ver_orden.php?id=$id" -WebSession $tec -UseBasicParsing
    Check ($p.Content -match 'Q 125.50' -and $p.Content -notmatch 'name="costo_servicio"') 'Técnico ve el precio sin campo de edición'
    $trabajo=@{csrf=$t;estado_actual=3;id_estado=3;accion='guardar';diagnostico='Diagnóstico de prueba';trabajo_realizado='Trabajo de prueba';materiales_utilizados='';observaciones='';costo_servicio='10.00'}
    $r=Send "tecnico/ver_orden.php?id=$id" $tec $trabajo
    Check ($r.Content -match 'Solo el administrador puede modificar' -and (Sql "SELECT costo_servicio FROM orden_servicio WHERE id_orden=$id") -eq '125.50') 'Servidor bloquea cambio de precio por técnico'
    foreach($precio in @('-1','2.345','100000000','250.75','')) {
        $p=Invoke-WebRequest "$base/admin/editar_orden.php?id=$id" -WebSession $admin -UseBasicParsing
        $editar=@{csrf=(GetToken $p.Content 'csrf');version=(GetToken $p.Content 'version');servicio_solicitado=$marca;id_categoria=$categoria;id_tecnico=2;observaciones='';fecha_programada='';hora_programada='';costo_servicio=$precio}
        $r=Send "admin/editar_orden.php?id=$id" $admin $editar
        if($precio -in '-1','2.345','100000000') {Check ((Sql "SELECT costo_servicio FROM orden_servicio WHERE id_orden=$id") -eq '125.50') "Importe inválido $precio rechazado"}
        elseif($precio){Check ((Sql "SELECT costo_servicio FROM orden_servicio WHERE id_orden=$id") -eq '250.75') 'Administrador modifica precio'}
        else{Check ((Sql "SELECT COUNT(*) FROM orden_servicio WHERE id_orden=$id AND costo_servicio IS NULL") -eq 1) 'Precio pendiente no equivale a cero'}
    }
    $p=Invoke-WebRequest "$base/tecnico/ver_orden.php?id=$id" -WebSession $tec -UseBasicParsing
    Check ($p.Content -match 'name="costo_servicio"') 'Técnico puede registrar precio pendiente'
    $trabajo.costo_servicio='0';$null=Send "tecnico/ver_orden.php?id=$id" $tec $trabajo
    Check ((Sql "SELECT costo_servicio FROM orden_servicio WHERE id_orden=$id") -eq '0.00') 'Técnico registra servicio gratuito sin confundirlo con pendiente'
    $trabajo.costo_servicio='90';$r=Send "tecnico/ver_orden.php?id=$id" $tec $trabajo
    Check ((Sql "SELECT costo_servicio FROM orden_servicio WHERE id_orden=$id") -eq '0.00') 'Técnico no modifica un precio ya registrado'
    $trabajo.costo_servicio='0';$trabajo.accion='finalizar';$null=Send "tecnico/ver_orden.php?id=$id" $tec $trabajo
    Check ((Sql "SELECT COUNT(*) FROM orden_servicio WHERE id_orden=$id AND id_estado=7 AND costo_servicio=0") -eq 1) 'Finalizar conserva el precio'
    Check ((Sql "SELECT COUNT(*) FROM historial_orden WHERE id_orden=$id AND observacion LIKE '%Precio del servicio%'") -ge 4) 'Registro y cambios quedan en el historial'
} finally {
    if($id){$null=Sql "DELETE FROM historial_orden WHERE id_orden=$id; DELETE FROM orden_servicio WHERE id_orden=$id AND servicio_solicitado='$marca'"}
}
