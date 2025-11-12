<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../../conexion.php';

// 🔹 Establecer la hora local de Ecuador (Guayaquil)
date_default_timezone_set('America/Guayaquil');


$input = json_decode(file_get_contents("php://input"), true);

if (
    !$input ||
    !isset($input['ven_id_evt'], $input['ven_id_caj'], $input['ven_cor_cli'], $input['ven_tel_cli'], $input['ven_can_bol'])
) {
    echo json_encode(["error" => "Faltan datos obligatorios."]);
    exit;
}

$evento     = intval($input['ven_id_evt']);
$cajero     = intval($input['ven_id_caj']);
$correoCli  = pg_escape_string($conn, $input['ven_cor_cli']);
$telCli     = pg_escape_string($conn, $input['ven_tel_cli']);
$cantidad   = intval($input['ven_can_bol']);

// Inicializa variables para la lógica de premios
$premioAsignadoId = 'NULL';
$premioAsignado = null;

// Inicia transacción
pg_query($conn, "BEGIN");

try {
    // 1️⃣ Validar evento y obtener el ID del administrador (para la asignación del premio)
    $qEvento = "SELECT evt_tit, evt_pre, evt_disponibles, evt_fec, evt_lug, evt_id_adm
                FROM tbl_evento 
                WHERE evt_id=$evento AND evt_est='activo'";
    $rEvento = pg_query($conn, $qEvento);
    if (!$rEvento || pg_num_rows($rEvento) == 0) throw new Exception("Evento no encontrado o no activo.");
    $eventoData = pg_fetch_assoc($rEvento);
    $adminId = $eventoData['evt_id_adm']; // ID del administrador para la asignación del premio

    if ($eventoData['evt_disponibles'] < $cantidad) throw new Exception("No hay suficientes boletos disponibles.");
    
    // 2️⃣ Lógica de Asignación de Premio
    $qPremio = "SELECT pre_id, pre_nom FROM tbl_premio WHERE pre_id_evt=$evento AND pre_can > 0 LIMIT 1";
    $rPremio = pg_query($conn, $qPremio);

    if ($rPremio && pg_num_rows($rPremio) > 0) {
        $premioData = pg_fetch_assoc($rPremio);
        $premioId = $premioData['pre_id'];

        // Insertar en tbl_premio_asignado (solo con el ID del premio y del admin por ahora)
        $qAsignacion = "INSERT INTO tbl_premio_asignado (pre_asg_id_pre, pre_asg_id_adm) 
                        VALUES ($premioId, $adminId) RETURNING pre_asg_id";
        $rAsignacion = pg_query($conn, $qAsignacion);

        if (!$rAsignacion) throw new Exception("Error al asignar el premio.");
        
        $premioAsignadoId = pg_fetch_assoc($rAsignacion)['pre_asg_id'];
        $premioAsignado = $premioData['pre_nom'];

        // Reducir la cantidad de premios disponibles
        pg_query($conn, "UPDATE tbl_premio SET pre_can = pre_can - 1 WHERE pre_id=$premioId");
    }

    // 3️⃣ Calcular precio total
    $precioTotal = floatval($eventoData['evt_pre']) * $cantidad;

    // 4️⃣ Insertar venta (agregando ven_id_pre_asg)
    $qVenta = "INSERT INTO tbl_ventas_cajero 
    (ven_id_evt, ven_id_caj, ven_cor_cli, ven_tel_cli, ven_can_bol, ven_pre_tot, ven_id_pre_asg) 
    VALUES ($evento, $cajero, '$correoCli', '$telCli', $cantidad, $precioTotal, $premioAsignadoId)
    RETURNING ven_id";
    $rVenta = pg_query($conn, $qVenta);
    if (!$rVenta) throw new Exception("No se pudo registrar la venta.");
    $venta = pg_fetch_assoc($rVenta);
    $ventaId = $venta['ven_id'];
    
    // Si se asignó un premio, actualizamos el campo pre_asg_id_ven
    if ($premioAsignadoId != 'NULL') {
        pg_query($conn, "UPDATE tbl_premio_asignado SET pre_asg_id_ven=$ventaId WHERE pre_asg_id=$premioAsignadoId");
    }

    // 5️⃣ Actualizar boletos disponibles
    pg_query($conn, "UPDATE tbl_evento SET evt_disponibles = evt_disponibles - $cantidad WHERE evt_id=$evento");

    pg_query($conn, "COMMIT");

    // 6️⃣ Respuesta
    $respuesta = [
        "venta_id" => $ventaId,
        "evento" => $eventoData['evt_tit'],
        "cantidad_boletos" => $cantidad,
        "precio_total" => $precioTotal,
        "cliente_correo" => $correoCli,
        "cliente_telefono" => $telCli
    ];
    
    if ($premioAsignado) {
        $respuesta['premio_asignado'] = [
            "id_asignacion" => intval($premioAsignadoId),
            "nombre" => $premioAsignado
        ];
    }
    
    echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    echo json_encode(["error" => $e->getMessage()]);
}

pg_close($conn); 
?>