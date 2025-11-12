<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../../conexion.php';

// Leer JSON de entrada
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['res_id'])) {
    echo json_encode(["error" => "Falta el ID de la reserva (res_id)."]);
    exit;
}

$reservaId = intval($input['res_id']);

try {
    // Consulta con JOIN para traer todos los datos necesarios
    $qReserva = "
        SELECT 
            r.res_id,
            r.res_est,
            r.res_fec,
            u.usr_id,
            u.usr_nom,
            u.usr_ape,
            e.evt_id,
            e.evt_tit,
            p.par_id,
            p.par_nom,
            pp.pue_id,
            pp.pue_num
        FROM tbl_reservas_parqueadero r
        JOIN tbl_compras_boletos c ON r.res_id_com = c.com_id
        JOIN tbl_usuario u ON c.com_id_usr = u.usr_id
        JOIN tbl_evento e ON c.com_id_evt = e.evt_id
        JOIN tbl_parqueadero p ON r.res_id_par = p.par_id
        JOIN tbl_puestos_parqueadero pp ON r.res_id_pue = pp.pue_id
        WHERE r.res_id = $reservaId
    ";

    $rReserva = pg_query($conn, $qReserva);

    if (!$rReserva || pg_num_rows($rReserva) == 0) {
        throw new Exception("Reserva no encontrada.");
    }

    $reserva = pg_fetch_assoc($rReserva);

    // Preparar respuesta
    echo json_encode([
        "res_id"        => intval($reserva['res_id']),
        "estado"        => $reserva['res_est'],
        "fecha_reserva" => $reserva['res_fec'],
        "usuario"       => [
            "id"      => intval($reserva['usr_id']),
            "nombre"  => $reserva['usr_nom'],
            "apellido"=> $reserva['usr_ape']
        ],
        "evento"        => [
            "id"     => intval($reserva['evt_id']),
            "titulo" => $reserva['evt_tit']
        ],
        "parqueadero"   => [
            "id"   => intval($reserva['par_id']),
            "nombre"=> $reserva['par_nom']
        ],
        "puesto"        => [
            "id"   => intval($reserva['pue_id']),
            "numero"=> $reserva['pue_num']
        ],
        "mensaje"       => $reserva['res_est'] === 'activa' 
                            ? "✅ Reserva activa."
                            : "⚠️ Reserva desactivada."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}

pg_close($conn);
?>
