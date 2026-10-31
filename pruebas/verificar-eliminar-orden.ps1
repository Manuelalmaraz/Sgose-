param([Parameter(Mandatory)][string]$AdminPassword,[Parameter(Mandatory)][string]$TecnicoPassword)
# Comprueba el borrado usando exclusivamente una orden temporal con historial.
$ErrorActionPreference='Stop'
function Sql($q){$r=& C:\xampp\mysql\bin\mysql.exe --host=127.0.0.1 --port=3306 -u root -D sgose -N -B -e $q;if($LASTEXITCODE){throw 'Error SQL'};return $r}
function Check($ok,$msg){if(!$ok){throw $msg};Write-Output "OK: $msg"}
function Campo($html,$campo){return [regex]::Match($html,('name="'+$campo+'"\s+value="([^"]+)"')).Groups[1].Value}
$base='http://localhost:8081/sgose';$admin=New-Object Microsoft.PowerShell.Commands.WebRequestSession;$tec=New-Object Microsoft.PowerShell.Commands.WebRequestSession
$null=Invoke-WebRequest "$base/controllers/login.php" -WebSession $admin -Method Post -Body @{correo='admin@sgose.com';password=$AdminPassword} -UseBasicParsing
$null=Invoke-WebRequest "$base/controllers/login.php" -WebSession $tec -Method Post -Body @{correo='estuardo@sgose.com';password=$TecnicoPassword} -UseBasicParsing
$marca='PRUEBA-ELIMINAR-ORDEN-'+[guid]::NewGuid().ToString('N');$numero='BOR-'+(Get-Date -Format yyMMddHHmmss);$id=0
try {
    $cliente=Sql 'SELECT MIN(id_cliente) FROM clientes';$categoria=Sql 'SELECT MIN(id_categoria) FROM categorias_servicio'
    $id=[int](Sql "INSERT INTO orden_servicio(numero_orden,id_cliente,id_categoria,id_tecnico,id_estado,tipo_solicitud,servicio_solicitado) VALUES('$numero',$cliente,$categoria,2,7,'Servicio','$marca'); SELECT LAST_INSERT_ID();")
    $null=Sql "INSERT INTO historial_orden(id_orden,id_usuario,id_estado_nuevo,observacion) VALUES($id,1,7,'Prueba temporal')"
    $url="$base/admin/eliminar_orden.php?id=$id"
    $r=Invoke-WebRequest $url -WebSession $tec -Method Post -Body @{confirmar='eliminar'} -UseBasicParsing
    Check ((Sql "SELECT COUNT(*) FROM orden_servicio WHERE id_orden=$id") -eq 1 -and $r.Content -notmatch '<h1>Eliminar orden') 'Técnico no puede eliminar'
    $r=Invoke-WebRequest $url -WebSession $admin -UseBasicParsing
    $token=Campo $r.Content 'csrf';$version=Campo $r.Content 'version'
    Check ($token -and $version -and (Sql "SELECT COUNT(*) FROM orden_servicio WHERE id_orden=$id") -eq 1) 'GET muestra confirmación sin borrar'
    $p=Invoke-WebRequest "$base/admin/ordenes.php" -WebSession $admin -UseBasicParsing
    Check ($p.Content -match "eliminar_orden.php\?id=$id") 'Botón Eliminar en el listado'
    $r=Invoke-WebRequest $url -WebSession $admin -Method Post -Body @{csrf='incorrecto';version=$version;confirmar='eliminar'} -UseBasicParsing -SkipHttpErrorCheck
    Check ($r.StatusCode -eq 403 -and (Sql "SELECT COUNT(*) FROM orden_servicio WHERE id_orden=$id") -eq 1) 'Token inválido bloqueado'
    $r=Invoke-WebRequest $url -WebSession $admin -Method Post -Body @{csrf=$token;version=$version} -UseBasicParsing
    Check ((Sql "SELECT COUNT(*) FROM historial_orden WHERE id_orden=$id") -eq 1) 'Sin confirmación conserva orden e historial'
    $null=Sql "UPDATE orden_servicio SET observaciones='Cambio de prueba' WHERE id_orden=$id"
    $r=Invoke-WebRequest $url -WebSession $admin -Method Post -Body @{csrf=$token;version=$version;confirmar='eliminar'} -UseBasicParsing
    Check ($r.Content -match 'La orden cambió' -and (Sql "SELECT COUNT(*) FROM orden_servicio WHERE id_orden=$id") -eq 1) 'Confirma nuevamente si la orden cambió'
    $r=Invoke-WebRequest $url -WebSession $admin -UseBasicParsing;$version=Campo $r.Content 'version'
    $r=Invoke-WebRequest $url -WebSession $admin -Method Post -Body @{csrf=$token;version=$version;confirmar='eliminar'} -UseBasicParsing
    Check ($r.Content -match 'Orden eliminada correctamente' -and (Sql "SELECT COUNT(*) FROM orden_servicio WHERE id_orden=$id") -eq 0 -and (Sql "SELECT COUNT(*) FROM historial_orden WHERE id_orden=$id") -eq 0) 'Elimina orden e historial al confirmar'
    Check ((Sql "SELECT COUNT(*) FROM clientes WHERE id_cliente=$cliente") -eq 1 -and (Sql 'SELECT COUNT(*) FROM usuarios WHERE id_usuario=2') -eq 1) 'Conserva cliente y técnico'
} finally {
    if($id){$null=Sql "DELETE FROM historial_orden WHERE id_orden=$id; DELETE FROM orden_servicio WHERE id_orden=$id AND servicio_solicitado='$marca'"}
}
