<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
// (Opcional) si usas fetch con JSON: 
// header("Access-Control-Allow-Headers: Content-Type, Authorization");

include '../../../conexion.php';
$baseURL = include __DIR__ . '/../../../ruta_Api.php';

// Normalizar base (que siempre termine en /)
$baseURL = rtrim($baseURL, '/') . '/';

// Función para crear URLs absolutas
function make_full_url(string $base, ?string $path): ?string {
    if (empty($path)) return null;
    if (preg_match('#^https?://#i', $path)) {
        return $path; // ya es absoluta
    }
    return $base . ltrim($path, '/');
}

// Leer datos de entrada
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['usr_id'])) {
    echo json_encode(["success" => false, "mensaje" => "Falta el id del usuario"]);
    exit;
}

$usr_id = intval($input['usr_id']);

// Consulta: premios asignados
$query = "
    SELECT 
        pa.pre_asg_id,
        pa.pre_asg_fec_ent,
        pa.pre_asg_recogido AS recogido,
        pa.pre_asg_id_com,
        p.pre_id,
        p.pre_nom,
        p.pre_des,
        p.pre_can,
        p.pre_img
    FROM tbl_premio_asignado pa
    INNER JOIN tbl_premio p ON p.pre_id = pa.pre_asg_id_pre
    INNER JOIN tbl_compras_boletos c ON c.com_id = pa.pre_asg_id_com
    WHERE c.com_id_usr = $1
    ORDER BY pa.pre_asg_fec_ent DESC
";

$res = pg_query_params($conn, $query, [$usr_id]);

$premios = [];

// Función segura para normalizar a booleano
function to_bool($v) {
    if (is_bool($v)) return $v;
    $s = strtolower(trim((string)$v));
    return in_array($s, ['1','t','true','on','yes','y','si','sí'], true);
}

if ($res) {
    while ($row = pg_fetch_assoc($res)) {
        // Imagen con baseURL
        $row['pre_img'] = make_full_url($baseURL, $row['pre_img'] ?? null);

        // Normalizar recogido a booleano
        $row['recogido'] = to_bool($row['recogido']);

        $premios[] = $row;
    }

    echo json_encode([
        "success" => true,
        "tiene_premios" => count($premios) > 0,
        "cantidad" => count($premios),
        "premios" => $premios
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} else {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error en la consulta: " . pg_last_error($conn)
    ]);
}
