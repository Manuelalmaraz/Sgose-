param([Parameter(Mandatory)][string]$AdminPassword)
# Verifica eliminación usando una cuenta y una orden creadas solo para esta prueba.
$ErrorActionPreference='Stop'
$base='http://localhost:8081/sgose'
function Sql($q) { $r=& C:\xampp\mysql\bin\mysql.exe --host=127.0.0.1 --port=3306 -u root -D sgose -N -B -e $q; if($LASTEXITCODE){throw 'Error SQL de prueba'}; return $r }
function Check($ok,$msg){if(!$ok){throw $msg}; Write-Output "OK: $msg"}
$session=New-Object Microsoft.PowerShell.Commands.WebRequestSession
$null=Invoke-WebRequest "$base/controllers/login.php" -WebSession $session -Method Post -Body @{correo='admin@sgose.com';password=$AdminPassword} -UseBasicParsing
$p=Invoke-WebRequest "$base/admin/usuario.php" -WebSession $session -UseBasicParsing
$token=[regex]::Match($p.Content,'name="csrf" value="([^"]+)"').Groups[1].Value
$email='prueba-eliminar-'+[guid]::NewGuid().ToString('N')+'@example.test'
$id=0; $orderId=0
try {
    $null=Invoke-WebRequest "$base/admin/usuario.php" -WebSession $session -Method Post -Body @{csrf=$token;nombre='Prueba';apellido='Eliminar';correo=$email;password=[guid]::NewGuid().ToString('N');id_rol=2;estado=1} -UseBasicParsing
    $id=[int](Sql "SELECT id_usuario FROM usuarios WHERE correo='$email'")
    Check ($id -gt 0) 'Cuenta temporal creada'
    $p=Invoke-WebRequest "$base/admin/eliminar_usuario.php?id=$id" -WebSession $session -UseBasicParsing
    Check ($p.Content -match 'Confirmar eliminación' -and (Sql "SELECT COUNT(*) FROM usuarios WHERE id_usuario=$id") -eq 1) 'Abrir confirmación no elimina'
    $r=Invoke-WebRequest "$base/admin/eliminar_usuario.php?id=$id" -WebSession $session -Method Post -Body @{csrf='incorrecto';confirmar='eliminar'} -SkipHttpErrorCheck -UseBasicParsing
    Check ($r.StatusCode -eq 403 -and (Sql "SELECT COUNT(*) FROM usuarios WHERE id_usuario=$id") -eq 1) 'Token inválido bloqueado'
    $r=Invoke-WebRequest "$base/admin/eliminar_usuario.php?id=$id" -WebSession $session -Method Post -Body @{csrf=$token} -UseBasicParsing
    Check ((Sql "SELECT COUNT(*) FROM usuarios WHERE id_usuario=$id") -eq 1) 'Confirmación obligatoria'
    $numero='DEL-'+(Get-Date -Format yyMMddHHmmss)
    $orderId=[int](Sql "INSERT INTO orden_servicio(numero_orden,id_cliente,id_categoria,id_tecnico,id_estado,tipo_solicitud,servicio_solicitado) SELECT '$numero',MIN(c.id_cliente),MIN(s.id_categoria),$id,2,'Servicio','PRUEBA-ELIMINAR' FROM clientes c CROSS JOIN categorias_servicio s; SELECT LAST_INSERT_ID();")
    $r=Invoke-WebRequest "$base/admin/eliminar_usuario.php?id=$id" -WebSession $session -Method Post -Body @{csrf=$token;confirmar='eliminar'} -UseBasicParsing
    Check ($r.Content -match 'tiene órdenes o historial' -and (Sql "SELECT COUNT(*) FROM usuarios WHERE id_usuario=$id") -eq 1) 'Usuario con orden se conserva'
    # Simula actividad histórica sin asignación actual al usuario temporal.
    $null=Sql "INSERT INTO historial_orden(id_orden,id_usuario,id_estado_nuevo,observacion) VALUES($orderId,$id,2,'PRUEBA-ELIMINAR'); UPDATE orden_servicio SET id_tecnico=NULL WHERE id_orden=$orderId;"
    $r=Invoke-WebRequest "$base/admin/eliminar_usuario.php?id=$id" -WebSession $session -Method Post -Body @{csrf=$token;confirmar='eliminar'} -UseBasicParsing
    Check ($r.Content -match 'tiene órdenes o historial' -and (Sql "SELECT COUNT(*) FROM usuarios WHERE id_usuario=$id") -eq 1) 'Usuario con historial se conserva'
    $null=Sql "DELETE FROM historial_orden WHERE id_orden=$orderId; DELETE FROM orden_servicio WHERE id_orden=$orderId AND servicio_solicitado='PRUEBA-ELIMINAR';"
    $orderId=0
    $r=Invoke-WebRequest "$base/admin/eliminar_usuario.php?id=$id" -WebSession $session -Method Post -Body @{csrf=$token;confirmar='eliminar'} -UseBasicParsing
    Check ($r.Content -match 'Usuario eliminado correctamente' -and (Sql "SELECT COUNT(*) FROM usuarios WHERE id_usuario=$id") -eq 0) 'Cuenta sin actividad eliminada al confirmar'
} finally {
    if($orderId){$null=Sql "DELETE FROM historial_orden WHERE id_orden=$orderId; DELETE FROM orden_servicio WHERE id_orden=$orderId AND servicio_solicitado='PRUEBA-ELIMINAR';"}
    if($id){$null=Sql "DELETE FROM usuarios WHERE id_usuario=$id AND correo='$email';"}
}

