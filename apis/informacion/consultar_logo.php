<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

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

// Consulta para obtener el logo activo
$query = "
    SELECT log_id, log_nom, log_rut, log_dim, log_est
    FROM tbl_logos
    WHERE log_est = 'activo'
    ORDER BY log_fec_cre DESC
    LIMIT 1
";

$res = pg_query($conn, $query);

if ($res && pg_num_rows($res) > 0) {
    $logo = pg_fetch_assoc($res);

    // Agregar la ruta completa para mostrar en Flutter
    $logo['log_rut'] = make_full_url($baseURL, $logo['log_rut'] ?? null);

    echo json_encode([
        "success" => true,
        "logo" => $logo
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    echo json_encode([
        "success" => false,
        "mensaje" => "No se encontró ningún logo activo"
    ]);
}
?>
