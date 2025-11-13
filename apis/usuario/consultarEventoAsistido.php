<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../conexion.php';

// Cargar y normalizar la baseURL
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

if (!$input || !isset($input['usr_id'])) {
    echo json_encode(["success" => false, "mensaje" => "Falta el id del usuario"]);
    exit;
}

$usr_id = intval($input['usr_id']);

// Consulta: eventos a los que el usuario ya tiene boleto y que están finalizados
$query = "
    SELECT 
        e.evt_id,
        c.com_id,
        e.evt_tit,
        e.evt_img
    FROM tbl_compras_boletos c
    INNER JOIN tbl_evento e ON e.evt_id = c.com_id_evt
    WHERE c.com_id_usr = $usr_id 
      AND c.com_act = TRUE
      AND e.evt_est = 'finalizado'
    ORDER BY c.com_fec DESC
";

$res = pg_query($conn, $query);

$eventos = [];

if ($res) {
    while ($row = pg_fetch_assoc($res)) {
        // Armar URL completa de la imagen
        $row['evt_img'] = make_full_url($baseURL, $row['evt_img'] ?? null);

        $eventos[] = $row;
    }

    echo json_encode(
        ["success" => true, "eventos_asistidos" => $eventos],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} else {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error en la consulta: " . pg_last_error($conn)
    ]);
}
?>
