param([Parameter(Mandatory)][string]$AdminPassword)
# Crea y elimina exclusivamente un cliente y una orden temporales para verificar el flujo.
$ErrorActionPreference='Stop'
function Sql($q){$r=& C:\xampp\mysql\bin\mysql.exe --host=127.0.0.1 --port=3306 -u root -D sgose -N -B -e $q;if($LASTEXITCODE){throw 'Error SQL'};return $r}
function Check($ok,$msg){if(!$ok){throw $msg};Write-Output "OK: $msg"}
$base='http://localhost:8081/sgose';$s=New-Object Microsoft.PowerShell.Commands.WebRequestSession
$null=Invoke-WebRequest "$base/controllers/login.php" -WebSession $s -Method Post -Body @{correo='admin@sgose.com';password=$AdminPassword} -UseBasicParsing
$marca='PRUEBA-BORRAR-'+[guid]::NewGuid().ToString('N');$id=0;$orden=0
try {
 $id=[int](Sql "INSERT INTO clientes(nombre,telefono) VALUES('$marca','5555-0000'); SELECT LAST_INSERT_ID();")
 $url="$base/admin/eliminar_cliente.php?id=$id"
 $r=Invoke-WebRequest $url -WebSession $s -UseBasicParsing
 $token=[regex]::Match($r.Content,'name="csrf" value="([^"]+)"').Groups[1].Value
 Check ($token -and (Sql "SELECT COUNT(*) FROM clientes WHERE id_cliente=$id") -eq 1) 'Abrir confirmación conserva al cliente'
 $r=Invoke-WebRequest $url -WebSession $s -Method Post -Body @{csrf='invalido';confirmar='eliminar'} -UseBasicParsing -SkipHttpErrorCheck
 Check ($r.StatusCode -eq 403 -and (Sql "SELECT COUNT(*) FROM clientes WHERE id_cliente=$id") -eq 1) 'Token inválido bloqueado'
 $r=Invoke-WebRequest $url -WebSession $s -Method Post -Body @{csrf=$token} -UseBasicParsing
 Check ((Sql "SELECT COUNT(*) FROM clientes WHERE id_cliente=$id") -eq 1) 'Confirmación obligatoria'
 $numero='DEL-C-'+(Get-Date -Format yyMMddHHmmss)
 $orden=[int](Sql "INSERT INTO orden_servicio(numero_orden,id_cliente,id_categoria,id_estado,tipo_solicitud,servicio_solicitado) SELECT '$numero',$id,MIN(id_categoria),7,'Servicio','$marca' FROM categorias_servicio; SELECT LAST_INSERT_ID();")
 $r=Invoke-WebRequest $url -WebSession $s -Method Post -Body @{csrf=$token;confirmar='eliminar'} -UseBasicParsing
 Check ($r.Content -match 'tiene órdenes registradas' -and (Sql "SELECT COUNT(*) FROM clientes WHERE id_cliente=$id") -eq 1) 'Cliente con orden finalizada e historial se conserva'
 $null=Sql "DELETE FROM orden_servicio WHERE id_orden=$orden AND servicio_solicitado='$marca'";$orden=0
 $r=Invoke-WebRequest $url -WebSession $s -Method Post -Body @{csrf=$token;confirmar='eliminar'} -UseBasicParsing
 Check ($r.Content -match 'Cliente eliminado correctamente' -and (Sql "SELECT COUNT(*) FROM clientes WHERE id_cliente=$id") -eq 0) 'Cliente sin órdenes eliminado al confirmar'
 $r=Invoke-WebRequest $url -WebSession $s -UseBasicParsing -SkipHttpErrorCheck
 Check ($r.StatusCode -eq 404) 'Cliente eliminado muestra página no encontrada'
} finally {
 if($orden){$null=Sql "DELETE FROM orden_servicio WHERE id_orden=$orden AND servicio_solicitado='$marca'"}
 if($id){$null=Sql "DELETE FROM clientes WHERE id_cliente=$id AND nombre='$marca'"}
}
