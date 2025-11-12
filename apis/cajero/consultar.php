<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include '../../conexion.php';

// Obtener el ID desde JSON
$input = json_decode(file_get_contents("php://input"), true);
$id = isset($input['id']) ? intval($input['id']) : null;

if (!$id) {
    echo json_encode(["error" => "Falta el ID del cajero."]);
    exit;
}

// Consulta del cajero
$query = "SELECT caj_id, caj_nom, caj_usr, caj_ema, caj_id_adm, caj_fec_cre, caj_fec_edi
          FROM tbl_cajero
          WHERE caj_id = $id";

$result = pg_query($conn, $query);

if ($result && pg_num_rows($result) > 0) {
    $cajero = pg_fetch_assoc($result);
    echo json_encode([
        "success" => true,
        "cajero" => $cajero
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "success" => false,
        "mensaje" => "Cajero no encontrado."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

pg_close($conn);
?>
