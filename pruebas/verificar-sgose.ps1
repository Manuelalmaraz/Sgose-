param(
    [Parameter(Mandatory)] [string]$AdminPassword,
    [Parameter(Mandatory)] [string]$TecnicoPassword,
    [Parameter(Mandatory)] [string]$OtroTecnicoPassword
)
$ErrorActionPreference='Stop'
$base='http://localhost:8081/sgose'
$mysql='C:\xampp\mysql\bin\mysql.exe'
function Sql([string]$query) { $r = & $mysql --host=127.0.0.1 --port=3306 -u root -D sgose -N -B -e $query; if ($LASTEXITCODE -ne 0) { throw 'Falló consulta de prueba' }; return $r }
function Check($ok,$message) { if(!$ok){throw $message}; Write-Output "OK: $message" }
function Page($path,$session) { $r=Invoke-WebRequest "$base/$path" -WebSession $session -UseBasicParsing; Check ($r.Content -notmatch '(?i)Fatal error|Warning:|Uncaught') "Página $path sin errores PHP"; return $r }
function PostOrder($id,$session,$values) {
    $page=Page "tecnico/ver_orden.php?id=$id" $session
    $token=[regex]::Match($page.Content,'name="csrf"\s+value="([^"]+)"').Groups[1].Value
    $state=Sql "SELECT id_estado FROM orden_servicio WHERE id_orden=$id"
    $script:lastCsrf=$token; $values['csrf']=$token; $values['estado_actual']=$state
    return Invoke-WebRequest "$base/tecnico/ver_orden.php?id=$id" -WebSession $session -Method Post -Body $values -UseBasicParsing
}
$created=@()
try {
    $admin=New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $tecnico=New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $otro=New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $null=Invoke-WebRequest "$base/controllers/login.php" -WebSession $admin -Method Post -Body @{correo='admin@sgose.com';password=$AdminPassword} -UseBasicParsing
    $null=Invoke-WebRequest "$base/controllers/login.php" -WebSession $tecnico -Method Post -Body @{correo='estuardo@sgose.com';password=$TecnicoPassword} -UseBasicParsing
    $null=Invoke-WebRequest "$base/controllers/login.php" -WebSession $otro -Method Post -Body @{correo='fran@sgose.com';password=$OtroTecnicoPassword} -UseBasicParsing
    $p=Page 'admin/dashboard.php' $admin; Check ($p.Content -match 'Panel general') 'Login administrador'
    $p=Page 'tecnico/dashboard.php' $tecnico; Check ($p.Content -match 'Panel del técnico') 'Login técnico'
    $cliente=Sql 'SELECT MIN(id_cliente) FROM clientes'; $categoria=Sql 'SELECT MIN(id_categoria) FROM categorias_servicio'
    foreach($caso in @('FINALIZAR','RECHAZAR')) {
        $marker='PRUEBA-SGOSE-'+$caso+'-'+(Get-Date -Format yyyyMMddHHmmss)
        $form=Page 'admin/nueva_orden.php' $admin
        $adminToken=[regex]::Match($form.Content,'name="csrf"\s+value="([^"]+)"').Groups[1].Value
        $r=Invoke-WebRequest "$base/admin/nueva_orden.php" -WebSession $admin -Method Post -Body @{csrf=$adminToken;id_cliente=$cliente;id_categoria=$categoria;tipo_solicitud='Servicio';servicio_solicitado=$marker;id_tecnico='2';fecha_programada='';observaciones='Prueba temporal de integración'} -UseBasicParsing
        $id=Sql "SELECT id_orden FROM orden_servicio WHERE servicio_solicitado='$marker'"
        Check ($id -match '^\d+$') "Crear orden $caso con fecha opcional"
        $created += [int]$id
        Check ((Sql "SELECT COUNT(*) FROM historial_orden WHERE id_orden=$id") -eq 1) 'Creación con historial'
        $p=Page "tecnico/ver_orden.php?id=$id" $otro
        Check ($p.Content -notmatch [regex]::Escape($marker)) 'Otro técnico no puede ver el detalle'
        if($caso -eq 'RECHAZAR') {
            $r=PostOrder $id $tecnico @{accion='rechazar';motivo=''}
            Check ((Sql "SELECT id_estado FROM orden_servicio WHERE id_orden=$id") -eq 2) 'Rechazo sin motivo bloqueado'
            $r=PostOrder $id $tecnico @{accion='rechazar';motivo='Fuera de disponibilidad de prueba'}
            Check ((Sql "SELECT id_estado FROM orden_servicio WHERE id_orden=$id") -eq 4) 'Rechazo con motivo e historial'
        } else {
            $r=PostOrder $id $tecnico @{accion='aceptar'}
            Check ((Sql "SELECT id_estado FROM orden_servicio WHERE id_orden=$id") -eq 3) 'Aceptar orden'
            $r=Invoke-WebRequest "$base/tecnico/ver_orden.php?id=$id" -WebSession $tecnico -Method Post -Body @{csrf=$script:lastCsrf;estado_actual=2;accion='aceptar'} -UseBasicParsing
            Check ((Sql "SELECT COUNT(*) FROM historial_orden WHERE id_orden=$id") -eq 2) 'Envío repetido bloqueado sin duplicar historial'
            $r=PostOrder $id $tecnico @{accion='finalizar';id_estado=3;diagnostico='';trabajo_realizado=''}
            Check ((Sql "SELECT id_estado FROM orden_servicio WHERE id_orden=$id") -eq 3) 'Finalización incompleta bloqueada'
            $r=PostOrder $id $tecnico @{accion='guardar';id_estado=5;diagnostico='Diagnóstico <script>alert(1)</script>';trabajo_realizado='';materiales_utilizados='Sin materiales';observaciones='No debe cambiar'}
            Check ((Sql "SELECT id_estado FROM orden_servicio WHERE id_orden=$id") -eq 5) 'Guardar diagnóstico'
            Check ($r.Content -match '&lt;script&gt;') 'Texto escapado en pantalla'
            $r=PostOrder $id $tecnico @{accion='guardar';id_estado=6;diagnostico='Diagnóstico de prueba';trabajo_realizado='Reparación de prueba';materiales_utilizados='Cable de prueba';observaciones='Prueba'}
            Check ((Sql "SELECT id_estado FROM orden_servicio WHERE id_orden=$id") -eq 6) 'Guardar trabajo y materiales en proceso'
            $r=PostOrder $id $tecnico @{accion='finalizar';id_estado=6;diagnostico='Diagnóstico de prueba';trabajo_realizado='Reparación de prueba';materiales_utilizados='Cable de prueba';observaciones='Servicio completado'}
            Check ((Sql "SELECT COUNT(*) FROM orden_servicio WHERE id_orden=$id AND id_estado=7 AND fecha_finalizacion IS NOT NULL") -eq 1) 'Finalizar con fecha'
            $r=Invoke-WebRequest "$base/tecnico/ver_orden.php?id=$id" -WebSession $tecnico -Method Post -Body @{csrf=$script:lastCsrf;estado_actual=7;accion='guardar';id_estado=6;diagnostico='Cambio inválido'} -UseBasicParsing
            Check ((Sql "SELECT id_estado FROM orden_servicio WHERE id_orden=$id") -eq 7) 'Orden finalizada bloqueada para cambios'
            Check ((Sql "SELECT COUNT(*) FROM historial_orden WHERE id_orden=$id") -eq 5) 'Historial completo sin entradas por intentos fallidos'
            $p=Page "admin/ver_orden.php?id=$id" $admin
            Check ($p.Content -match 'Reparación de prueba' -and $p.Content -match 'Orden finalizada') 'Administrador ve trabajo e historial'
            $p=Page 'admin/historial.php' $admin; Check ($p.Content -match 'Orden finalizada') 'Historial general real'
            $r=Invoke-WebRequest "$base/tecnico/ver_orden.php?id=$id" -WebSession $tecnico -Method Post -Body @{csrf='incorrecto';estado_actual=7;accion='aceptar'} -UseBasicParsing -SkipHttpErrorCheck
            Check ($r.StatusCode -eq 403) 'Protección del formulario'
        }
    }
    foreach($path in @('admin/dashboard.php','admin/ordenes.php','admin/historial.php')) { $null=Page $path $admin }
    $null=Page 'tecnico/dashboard.php' $tecnico
    $p=Page 'admin/ordenes.php?estado=4' $admin; Check ($p.Content -match 'value="4" selected') 'Filtro de estados del administrador'
    $p=Page 'tecnico/ordenes.php?buscar=NO-EXISTE-SGOSE' $tecnico; Check ($p.Content -notmatch 'href="ver_orden.php') 'Búsqueda del técnico sin resultados'
} finally {
    foreach($id in $created) { $null=Sql "DELETE FROM historial_orden WHERE id_orden=$id; DELETE FROM orden_servicio WHERE id_orden=$id AND servicio_solicitado LIKE 'PRUEBA-SGOSE-%';" }
    Write-Output "Órdenes temporales retiradas: $($created.Count)"
}






