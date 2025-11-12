<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include '../conexion.php';

$baseURL = include __DIR__ . '/../ruta_Api.php';


// Función para procesar imágenes (si existe ruta en la DB)
function procesarImagen($ruta, $baseURL) {
    return $ruta ? $baseURL . $ruta : null;
}

// Función para ejecutar consulta y devolver datos
function getData($conn, $query, $procesarImg = [], $baseURL = "") {
    $res = pg_query($conn, $query);
    $data = [];
    if ($res) {
        while ($row = pg_fetch_assoc($res)) {
            foreach ($procesarImg as $campo) {
                if (isset($row[$campo])) {
                    $row[$campo] = procesarImagen($row[$campo], $baseURL);
                }
            }
            $data[] = $row;
        }
    }
    return $data;
}

$response = [
    "success" => true,
    "eventos" => getData($conn, "SELECT * FROM tbl_evento", ["evt_img"], $baseURL),
    "usuarios" => getData($conn, "SELECT * FROM tbl_usuario"),
    "cajeros" => getData($conn, "SELECT * FROM tbl_cajero"),
    "administradores" => getData($conn, "SELECT * FROM tbl_admin"),
    "premios" => getData($conn, "SELECT * FROM tbl_premio", ["pre_img"], $baseURL),
    "premios_asignados" => getData($conn, "SELECT * FROM tbl_premio_asignado"),
    "parqueaderos" => getData($conn, "SELECT * FROM tbl_parqueadero"),
    "puestos_parqueadero" => getData($conn, "SELECT * FROM tbl_puestos_parqueadero"),
    "compras" => getData($conn, "SELECT * FROM tbl_compras_boletos"),
    "reservas" => getData($conn, "SELECT * FROM tbl_reservas_parqueadero"),
    "galeria" => getData($conn, "SELECT * FROM tbl_galeria", ["gal_url"], $baseURL),
    "ventas_cajero" => getData($conn, "SELECT * FROM tbl_ventas_cajero"),
    "escaneos_boletos" => getData($conn, "SELECT * FROM tbl_escaneosBoletos"),
    "escaneos_parqueaderos" => getData($conn, "SELECT * FROM tbl_escaneosParqueaderos"),
    "cajero_asignacion_parqueadero" => getData($conn, "SELECT * FROM tbl_cajero_asignacion_parqueadero"),
    "logos" => getData($conn, "SELECT * FROM tbl_logos", ["log_rut"], $baseURL),
    "patrocinadores" => getData($conn, "SELECT * FROM tbl_patrocinadores", ["pat_img"], $baseURL),
    "redes_sociales" => getData($conn, "SELECT * FROM tbl_redes_sociales", ["red_ico"], $baseURL),
    "nosotros" => getData($conn, "SELECT * FROM tbl_nosotros", ["nos_img_hist", "nos_img_mis", "nos_img_vis", "nos_logo_banco", "nos_url_img_android", "nos_img_url_ios"], $baseURL),
];

echo json_encode($response);
?>
