<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include '../../conexion.php';

$query = "
    SELECT *
    FROM tbl_patrocinadores
    WHERE pat_est = 'activo'
    ORDER BY pat_fec_cre DESC
";

$res = pg_query($conn, $query);

$patrocinadores = [];
$baseURL = "http://172.16.117.56:8080/IP_Eventos/";

if ($res) {
    while ($row = pg_fetch_assoc($res)) {
        if (!empty($row['pat_img'])) {
            $row['pat_img'] = $baseURL . $row['pat_img'];
        }
        $patrocinadores[] = $row;
    }

    echo json_encode([
        "success" => true,
        "patrocinadores" => $patrocinadores
    ]);
} else {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al consultar patrocinadores"
    ]);
}
?>
