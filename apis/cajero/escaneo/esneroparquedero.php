<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../../conexion.php';

// Leer JSON de entrada
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['res_id'], $input['caj_id'])) {
    echo json_encode(["error" => "Faltan datos obligatorios (res_id, caj_id)."]);
    exit;
}

$reservaId = intval($input['res_id']);
$cajeroId  = intval($input['caj_id']);

pg_query($conn, "BEGIN");

try {
    // 1️⃣ Buscar la reserva
    $qReserva = "SELECT res_est, res_id_par, res_id_pue 
                 FROM tbl_reservas_parqueadero 
                 WHERE res_id = $reservaId";
    $rReserva = pg_query($conn, $qReserva);

    if (!$rReserva || pg_num_rows($rReserva) == 0) {
        throw new Exception("Reserva no encontrada.");
    }

    $reserva = pg_fetch_assoc($rReserva);
    $estado  = $reserva['res_est'];
    $parque  = $reserva['res_id_par'];
    $puesto  = $reserva['res_id_pue'];

    // 2️⃣ Validar estado
    if ($estado !== 'activa') {
        throw new Exception("🚫 La reserva del parqueadero $parque (puesto $puesto) ya fue usada.");
    }

    // 3️⃣ Actualizar estado a desactiva
    $qUpdate = "UPDATE tbl_reservas_parqueadero
                SET res_est = 'desactiva',
                    res_fec = CURRENT_TIMESTAMP
                WHERE res_id = $reservaId";
    pg_query($conn, $qUpdate);

    // 4️⃣ Insertar registro en tbl_escaneosParqueaderos
    $qInsert = "INSERT INTO tbl_escaneosParqueaderos (esc_id_res, esc_id_caj)
                VALUES ($reservaId, $cajeroId)";
    pg_query($conn, $qInsert);

    pg_query($conn, "COMMIT");

    // 5️⃣ Respuesta
    echo json_encode([
        "res_id"   => $reservaId,
        "caj_id"   => $cajeroId,
        "parque"   => $parque,
        "puesto"   => $puesto,
        "estado"   => "desactiva",
        "mensaje"  => "✅ Reserva válida. Se marcó como usada y se registró el escaneo."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    echo json_encode(["error" => $e->getMessage()]);
}

pg_close($conn);
?>
