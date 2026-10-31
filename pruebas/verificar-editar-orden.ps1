param([Parameter(Mandatory)][string]$AdminPassword)
# Prueba la edición administrativa sobre una orden temporal, sin alterar las existentes.
$ErrorActionPreference='Stop'
function Sql($q){$r=& C:\xampp\mysql\bin\mysql.exe --host=127.0.0.1 --port=3306 -u root -D sgose -N -B -e $q;if($LASTEXITCODE){throw 'Error SQL'};return $r}
function Check($ok,$msg){if(!$ok){throw $msg};Write-Output "OK: $msg"}
$base='http://localhost:8081/sgose';$s=New-Object Microsoft.PowerShell.Commands.WebRequestSession
$null=Invoke-WebRequest "$base/controllers/login.php" -WebSession $s -Method Post -Body @{correo='admin@sgose.com';password=$AdminPassword} -UseBasicParsing
$marca='PRUEBA-EDITAR-'+[guid]::NewGuid().ToString('N');$id=0
try {
 $categoria=Sql 'SELECT MIN(id_categoria) FROM categorias_servicio'
 $numero='ED-'+(Get-Date -Format yyMMddHHmmss)
 $id=[int](Sql "INSERT INTO orden_servicio(numero_orden,id_cliente,id_categoria,id_tecnico,id_estado,tipo_solicitud,servicio_solicitado,diagnostico) SELECT '$numero',MIN(id_cliente),$categoria,2,3,'Servicio','$marca','Diagnóstico que se conserva' FROM clientes; SELECT LAST_INSERT_ID();")
 $url="$base/admin/editar_orden.php?id=$id"
 $p=Invoke-WebRequest $url -WebSession $s -UseBasicParsing
 $token=[regex]::Match($p.Content,'name="csrf"\s+value="([^"]+)"').Groups[1].Value
 $version=[regex]::Match($p.Content,'name="version"\s+value="([^"]+)"').Groups[1].Value
 Check ($token -and $version) 'Formulario de edición disponible'
 $lista=Invoke-WebRequest "$base/admin/ordenes.php" -WebSession $s -UseBasicParsing
 Check ($lista.Content -match "editar_orden.php\?id=$id") 'Botón Editar en el listado'
 $body=@{csrf=$token;version=$version;servicio_solicitado="$marca editado";id_categoria=$categoria;id_tecnico=2;fecha_programada='2026-11-02';hora_programada='02:30 PM';observaciones='Cambio administrativo'}
 $r=Invoke-WebRequest $url -WebSession $s -Method Post -Body $body -UseBasicParsing
 Check ($r.Content -notmatch 'Fatal error|Warning:|Uncaught') 'Guardado sin errores PHP'
 Check ((Sql "SELECT COUNT(*) FROM orden_servicio WHERE id_orden=$id AND id_estado=3 AND fecha_programada='2026-11-02 14:30:00' AND observaciones='Cambio administrativo' AND diagnostico='Diagnóstico que se conserva'") -eq 1) 'Datos editados y trabajo técnico conservado'
 Check ((Sql "SELECT COUNT(*) FROM historial_orden WHERE id_orden=$id AND observacion LIKE '%Orden editada%'") -eq 1) 'Edición guardada en historial'
 $r=Invoke-WebRequest $url -WebSession $s -Method Post -Body $body -UseBasicParsing
 Check ($r.Content -match 'La orden cambió' -and (Sql "SELECT COUNT(*) FROM historial_orden WHERE id_orden=$id") -eq 1) 'Formulario antiguo bloqueado'
 $p=Invoke-WebRequest $url -WebSession $s -UseBasicParsing
 $body.version=[regex]::Match($p.Content,'name="version"\s+value="([^"]+)"').Groups[1].Value
 $body.id_tecnico=3
 $r=Invoke-WebRequest $url -WebSession $s -Method Post -Body $body -UseBasicParsing
 Check ((Sql "SELECT COUNT(*) FROM orden_servicio WHERE id_orden=$id AND id_tecnico=3 AND id_estado=2") -eq 1) 'Cambiar técnico requiere nueva aceptación'
 $null=Sql "UPDATE orden_servicio SET id_estado=7 WHERE id_orden=$id"
 $p=Invoke-WebRequest $url -WebSession $s -UseBasicParsing
 Check ($p.Content -match 'cerrada y no admite cambios' -and $p.Content -notmatch '<form method="post">') 'Orden finalizada no muestra formulario'
 $r=Invoke-WebRequest $url -WebSession $s -Method Post -Body $body -UseBasicParsing
 Check ((Sql "SELECT id_estado FROM orden_servicio WHERE id_orden=$id") -eq 7) 'Orden finalizada protegida ante POST'
} finally {
 if($id){$null=Sql "DELETE FROM historial_orden WHERE id_orden=$id; DELETE FROM orden_servicio WHERE id_orden=$id AND numero_orden='$numero'"}
}

