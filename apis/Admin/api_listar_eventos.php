<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include '../../conexion.php';

// Cargar y normalizar la base
$baseURL = include __DIR__ . '/../../ruta_Api.php';
$baseURL = rtrim((string)$baseURL, '/') . '/';

// Helper para armar URLs absolutas (respeta si ya son http/https)
function make_full_url(string $base, ?string $path): ?string {
    if (empty($path)) return null;
    if (preg_match('#^https?://#i', $path)) return $path;
    return $base . ltrim($path, '/');
}

// Traer solo eventos en estado activo
$query = "SELECT evt_id, evt_tit, evt_des, evt_fec, evt_lug, evt_pre, 
                 evt_capacidad, evt_disponibles, evt_est, evt_img 
          FROM tbl_evento
          WHERE evt_est = 'activo'
          ORDER BY evt_fec DESC";

$res = pg_query($conn, $query);

$eventos = [];

if ($res) {
    while ($row = pg_fetch_assoc($res)) {
        // URL completa para la imagen del evento (si existe)
        $row['evt_img'] = make_full_url($baseURL, $row['evt_img'] ?? null);
        $eventos[] = $row;
    }

    echo json_encode(
        ["success" => true, "eventos" => $eventos],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} else {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error: " . pg_last_error($conn)
    ]);
}
