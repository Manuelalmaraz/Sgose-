<?php
// Crea la orden y su primer historial en una transacción; valida referencias y fechas.
if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        validar_formulario();
        $id_cliente=(int)($_POST['id_cliente'] ?? 0); $id_categoria=(int)($_POST['id_categoria'] ?? 0); $id_tecnico=(int)($_POST['id_tecnico'] ?? 0);
        $tipo_solicitud=campo_texto('tipo_solicitud',20,true);
        $servicio_solicitado=campo_texto('servicio_solicitado',255,true); $observaciones=campo_texto('observaciones',10000);
        if(!in_array($tipo_solicitud,['Servicio','Presupuesto'],true)) throw new DomainException('Selecciona un tipo de solicitud válido.');
        // Acepta la hora escrita con AM/PM y la convierte a 24 horas para MySQL.
        $dia=trim($_POST['fecha_programada'] ?? '');
        $hora=trim($_POST['hora_programada'] ?? '');
        $fecha_programada=null;
        if($dia!=='' || $hora!=='') {
            if($dia==='' || $hora==='') throw new DomainException('Selecciona la fecha y la hora para programar la orden.');
            if(!preg_match('/^(0?[1-9]|1[0-2]):([0-5][0-9])\s*(AM|PM)$/iD',$hora,$partes)) throw new DomainException('Escribe una hora válida con AM o PM, por ejemplo 02:30 PM.');
            $hora=sprintf('%02d:%s', ((int)$partes[1] % 12) + (strtoupper($partes[3])==='PM' ? 12 : 0), $partes[2]);
            $fecha_texto=$dia.'T'.$hora;
            $fecha=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$fecha_texto);
            if(!$fecha || $fecha->format('Y-m-d\TH:i')!==$fecha_texto) throw new DomainException('La fecha programada no es válida.');
            $fecha_programada=$fecha->format('Y-m-d H:i:s');
        }
        $conexion->begin_transaction();
        // Impide asignar a un técnico mientras se desactiva su cuenta.
        $stmt=$conexion->prepare('SELECT id_usuario FROM usuarios WHERE id_usuario=? AND id_rol=2 AND estado=1 FOR UPDATE');
        $stmt->bind_param('i',$id_tecnico); $stmt->execute();
        if(!$stmt->get_result()->num_rows) throw new DomainException('Selecciona un técnico activo.');
        foreach(['clientes'=>['id_cliente',$id_cliente],'categorias_servicio'=>['id_categoria',$id_categoria]] as $tabla=>$referencia) {
            // Identificadores de esta lista fija, valores enlazados como parámetros.
            $stmt=$conexion->prepare('SELECT '.$referencia[0].' FROM '.$tabla.' WHERE '.$referencia[0].'=?');
            $stmt->bind_param('i',$referencia[1]); $stmt->execute();
            if(!$stmt->get_result()->num_rows) throw new DomainException('Selecciona un cliente y una categoría válidos.');
        }
        $numero_orden='OS-'.date('ymdHis').'-'.bin2hex(random_bytes(2)); $id_estado=2;
        $stmt=$conexion->prepare('INSERT INTO orden_servicio(numero_orden,id_cliente,id_categoria,id_tecnico,id_estado,tipo_solicitud,servicio_solicitado,fecha_programada,observaciones) VALUES(?,?,?,?,?,?,?,?,?)');
        $stmt->bind_param('siiiissss',$numero_orden,$id_cliente,$id_categoria,$id_tecnico,$id_estado,$tipo_solicitud,$servicio_solicitado,$fecha_programada,$observaciones);
        $stmt->execute(); $id_orden=$stmt->insert_id; $id_usuario=(int)$_SESSION['id_usuario'];
        $nota='Orden creada y asignada al técnico ID '.$id_tecnico.'.';
        $stmt=$conexion->prepare('INSERT INTO historial_orden(id_orden,id_usuario,id_estado_anterior,id_estado_nuevo,observacion) VALUES(?,?,NULL,?,?)');
        $stmt->bind_param('iiis',$id_orden,$id_usuario,$id_estado,$nota); $stmt->execute();
        $conexion->commit(); header('Location: ordenes.php?mensaje=creada'); exit;
    } catch(Throwable $e) {
        $conexion->rollback();
        $error=$e instanceof DomainException ? $e->getMessage() : 'No se pudo guardar la orden y su historial. Revisa los datos.';
        if(!$e instanceof DomainException) error_log((string)$e);
    }
}

