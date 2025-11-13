<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include '../../conexion.php';

$query = "
    SELECT *
    FROM tbl_redes_sociales
    WHERE red_est = 'activo'
    ORDER BY red_fec_cre DESC
";

$res = pg_query($conn, $query);

$redes = [];
$baseURL = "http://172.16.117.56:8080/IP_Eventos/";

if ($res) {
    while ($row = pg_fetch_assoc($res)) {
        if (!empty($row['red_ico'])) {
            $row['red_ico'] = $baseURL . $row['red_ico'];
        }
        $redes[] = $row;
    }

    echo json_encode([
        "success" => true,
        "redes_sociales" => $redes
    ]);
} else {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al consultar redes sociales"
    ]);
}
?>
