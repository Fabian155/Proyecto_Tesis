<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include '../../conexion.php';

$query = "
    SELECT *
    FROM tbl_nosotros
    WHERE nos_est = 'activo'
    ORDER BY nos_fec_cre DESC
    LIMIT 1
";

$res = pg_query($conn, $query);

if ($res && pg_num_rows($res) > 0) {
    $nosotros = pg_fetch_assoc($res);

    // IP base
    $baseURL = "http://172.16.117.56:8080/IP_Eventos/";

    // Convertir las rutas relativas en rutas completas
    if (!empty($nosotros['nos_img_hist'])) {
        $nosotros['nos_img_hist'] = $baseURL . $nosotros['nos_img_hist'];
    }
    if (!empty($nosotros['nos_img_mis'])) {
        $nosotros['nos_img_mis'] = $baseURL . $nosotros['nos_img_mis'];
    }
    if (!empty($nosotros['nos_img_vis'])) {
        $nosotros['nos_img_vis'] = $baseURL . $nosotros['nos_img_vis'];
    }
    if (!empty($nosotros['nos_logo_banco'])) {
        $nosotros['nos_logo_banco'] = $baseURL . $nosotros['nos_logo_banco'];
    }

    echo json_encode([
        "success" => true,
        "nosotros" => $nosotros
    ]);
} else {
    echo json_encode([
        "success" => false,
        "mensaje" => "No se encontró información activa en la tabla Nosotros"
    ]);
}
?>
