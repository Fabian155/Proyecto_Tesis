<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../../conexion.php';

// Leer JSON de entrada
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['com_id'], $input['cantidad_usar'], $input['caj_id'])) {
    echo json_encode(["error" => "Faltan datos obligatorios (com_id, cantidad_usar, caj_id)."]);
    exit;
}

$compraId     = intval($input['com_id']);
$cantidadUsar = intval($input['cantidad_usar']);
$cajeroId     = intval($input['caj_id']);

if ($cantidadUsar <= 0) {
    echo json_encode(["error" => "La cantidad a usar debe ser mayor a 0."]);
    exit;
}

pg_query($conn, "BEGIN");

try {
    // 1️⃣ Buscar boleto
    $qCompra = "SELECT com_uso_con, com_act 
                FROM tbl_compras_boletos 
                WHERE com_id = $compraId";
    $rCompra = pg_query($conn, $qCompra);

    if (!$rCompra || pg_num_rows($rCompra) == 0) {
        throw new Exception("Boleto no encontrado.");
    }

    $compra = pg_fetch_assoc($rCompra);
    $usosRestantes = intval($compra['com_uso_con']); // cantidad que aún se puede usar
    $activo = ($compra['com_act'] === 't' || $compra['com_act'] === 'true' || $compra['com_act'] === '1');

    // 2️⃣ Validar si ya está inactivo
    if (!$activo) {
        throw new Exception("El boleto ya fue usado totalmente. Usos totales: " . ($compra['com_uso_con']));
    }

    // 3️⃣ Validar cantidad a usar
    if ($cantidadUsar > $usosRestantes) {
        throw new Exception("Intento de uso mayor al disponible. Usos restantes: $usosRestantes");
    }

    // 4️⃣ Calcular nuevos valores
    $nuevoUsosRestantes = $usosRestantes - $cantidadUsar;
    $nuevoActivo = ($nuevoUsosRestantes > 0) ? "TRUE" : "FALSE";

    // 5️⃣ Actualizar en la base (solo com_uso_con y com_act)
    $qUpdate = "UPDATE tbl_compras_boletos 
                SET com_uso_con = $nuevoUsosRestantes,
                    com_act = $nuevoActivo,
                    com_fec_edi = CURRENT_TIMESTAMP
                WHERE com_id = $compraId";
    pg_query($conn, $qUpdate);

    // 6️⃣ Insertar registro en tbl_escaneosBoletos con esc_boletosusado
    $qInsert = "INSERT INTO tbl_escaneosBoletos (esc_id_com, esc_id_caj, esc_boletosusado)
                VALUES ($compraId, $cajeroId, $cantidadUsar)";
    pg_query($conn, $qInsert);

    pg_query($conn, "COMMIT");

    // 7️⃣ Respuesta
    echo json_encode([
        "com_id" => $compraId,
        "caj_id" => $cajeroId,
        "usos_restantes" => $nuevoUsosRestantes,
        "activo" => ($nuevoActivo === "TRUE"),
        "mensaje" => ($nuevoActivo === "TRUE") 
            ? "✅ Boleto válido. Se usaron $cantidadUsar usos." 
            : "⚠️ Boleto agotado, ya no quedan más usos."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    echo json_encode(["error" => $e->getMessage()]);
}

pg_close($conn);
?>
