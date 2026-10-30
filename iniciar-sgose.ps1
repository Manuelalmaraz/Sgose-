param([switch]$SinNavegador)
# Inicia únicamente los servicios locales de XAMPP que necesita SGOSE.
# No detiene servicios ajenos ni modifica sus puertos o credenciales.
$ErrorActionPreference='Stop'
function PuertoDisponible([int]$puerto) {
    $cliente=[Net.Sockets.TcpClient]::new()
    try { $tarea=$cliente.ConnectAsync('127.0.0.1',$puerto); return ($tarea.Wait(500) -and $cliente.Connected) }
    catch { return $false }
    finally { $cliente.Dispose() }
}
function EsperarPuerto([int]$puerto) {
    for($intento=0;$intento -lt 20;$intento++) {
        if(PuertoDisponible $puerto) { return }
        Start-Sleep -Milliseconds 500
    }
    throw "No se pudo iniciar el servicio del puerto $puerto. Revisa los registros de XAMPP."
}
if(!(PuertoDisponible 3307)) {
    # Exige acceso exclusivo a InnoDB antes de iniciar: evita instancias con datos compartidos.
    $archivo=[IO.File]::Open('C:\xampp\mysql\data\ibdata1','Open','Read','None')
    $archivo.Dispose()
    Start-Process -FilePath 'C:\xampp\mysql\bin\mysqld.exe' -ArgumentList '--defaults-file=C:\xampp\mysql\bin\my.ini','--port=3307','--standalone' -WindowStyle Hidden
    EsperarPuerto 3307
}
if(!(PuertoDisponible 8081)) {
    Start-Process -FilePath 'C:\xampp\apache\bin\httpd.exe' -WindowStyle Hidden
    EsperarPuerto 8081
}
Write-Output 'SGOSE disponible en http://localhost:8081/sgose/'
if(!$SinNavegador) { Start-Process 'http://localhost:8081/sgose/' }
