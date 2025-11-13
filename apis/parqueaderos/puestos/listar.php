<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../../conexion.php';

// Obtener datos de entrada
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['par_id'])) {
    echo json_encode(["error" => "Falta el ID del parqueadero"]);
    exit;
}

$par_id = intval($input['par_id']);

// Consulta puestos disponibles de ese parqueadero
$query = "
    SELECT * 
    FROM tbl_puestos_parqueadero 
    WHERE pue_id_par = $par_id
      AND pue_est = 'disponible'
    ORDER BY pue_id ASC
";

$res = pg_query($conn, $query);

$puestos = [];

if ($res) {
    while ($row = pg_fetch_assoc($res)) {
        $puestos[] = [
            "pue_id"      => $row['pue_id'],
            "pue_num"     => $row['pue_num'],
            "pue_est"     => $row['pue_est'],
            "pue_id_par"  => $row['pue_id_par'],
            "pue_id_adm"  => $row['pue_id_adm'],
            "pue_fec_cre" => $row['pue_fec_cre'],
            "pue_fec_edi" => $row['pue_fec_edi']
        ];
    }

    echo json_encode($puestos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["error" => "No se pudieron obtener los puestos"]);
}

pg_close($conn);
?>
