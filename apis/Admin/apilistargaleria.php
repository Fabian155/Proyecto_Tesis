<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../conexion.php';

// Cargar y normalizar la base URL
$baseURL = include __DIR__ . '/../../ruta_Api.php';
$baseURL = rtrim((string)$baseURL, '/') . '/';

// Función para armar URLs absolutas
function make_full_url(string $base, ?string $path): ?string {
    if (empty($path)) return null;
    if (preg_match('#^https?://#i', $path)) return $path; // ya es absoluta
    return $base . ltrim($path, '/');
}

// Leer datos de entrada
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['evt_id'])) {
    echo json_encode(["success" => false, "mensaje" => "Falta el id del evento"]);
    exit;
}

$evt_id = intval($input['evt_id']);

// Consulta a la tabla tbl_galeria
$query = "SELECT gal_id, gal_url, gal_des, gal_fec_sub 
          FROM tbl_galeria 
          WHERE gal_id_evt = $evt_id
          ORDER BY gal_fec_sub DESC";

$res = pg_query($conn, $query);

$galeria = [];

if ($res) {
    while ($row = pg_fetch_assoc($res)) {
        // Usar baseURL para la imagen
        $row['gal_url'] = make_full_url($baseURL, $row['gal_url'] ?? null);

        $galeria[] = $row;
    }

    echo json_encode(
        ["success" => true, "imagenes" => $galeria],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} else {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error: " . pg_last_error($conn)
    ]);
}
?>
