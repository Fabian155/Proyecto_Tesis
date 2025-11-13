<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include '../../conexion.php';

$query = "SELECT * FROM tbl_parqueadero ORDER BY par_id ASC";
$res = pg_query($conn, $query);

$parqueaderos = [];

if ($res) {
    while ($row = pg_fetch_assoc($res)) {
        $parqueaderos[] = [
            "par_id"      => $row['par_id'],
            "par_nom"     => $row['par_nom'],
            "par_ubi"     => $row['par_ubi'],
            "par_cap"     => $row['par_cap'],
            "par_id_adm"  => $row['par_id_adm'],
            "par_fec_cre" => $row['par_fec_cre'],
            "par_fec_edi" => $row['par_fec_edi']
        ];
    }

    echo json_encode($parqueaderos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["error" => "No se pudieron obtener los parqueaderos"]);
}

pg_close($conn);
?>
