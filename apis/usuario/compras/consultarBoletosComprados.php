<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include '../../../conexion.php';

// Cargar ruta base desde ruta_Api.php
$baseURL = include __DIR__ . '/../../../ruta_Api.php';
if (!$baseURL || !is_string($baseURL)) {
    // Fallback por si ruta_Api.php no devuelve string
    $baseURL = "http://localhost/IP_Eventos/";
}

// Normalizar base (termina con '/')
$baseURL = rtrim($baseURL, '/') . '/';

// Helper para construir URLs absolutas
function make_full_url(string $base, ?string $path): ?string {
    if (empty($path)) return null;

    // Si ya es absoluta (http/https), dejar igual
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    // Quitar slashes iniciales al path y unir
    return $base . ltrim($path, '/');
}

// Leer datos de entrada
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['usr_id'])) {
    echo json_encode(["success" => false, "mensaje" => "Falta el id del usuario"]);
    exit;
}

$usr_id = intval($input['usr_id']);

// Consulta para traer todas las compras con datos del evento
$query = "
    SELECT 
        c.*,
        e.evt_tit,
        e.evt_img
    FROM tbl_compras_boletos c
    INNER JOIN tbl_evento e ON e.evt_id = c.com_id_evt
    WHERE c.com_id_usr = $1
    ORDER BY c.com_fec DESC
";

// Ejecutar consulta de forma segura
$res = pg_query_params($conn, $query, [$usr_id]);

$compras = [];

if ($res) {
    while ($row = pg_fetch_assoc($res)) {

        // Normalizar estado de verificación a los 3 permitidos
        $estado = $row['com_est_verif'] ?? 'por_validar';
        $permitidos = ['por_validar', 'transferencia_no_valida', 'validado'];
        if (!in_array($estado, $permitidos, true)) {
            $estado = 'por_validar';
        }
        $row['com_est_verif'] = $estado;

        // Construir URLs usando baseURL
        $row['evt_img']        = make_full_url($baseURL, $row['evt_img'] ?? null);
        $row['com_qr']         = make_full_url($baseURL, $row['com_qr'] ?? null);
        $row['com_ruta_qr_parq']= make_full_url($baseURL, $row['com_ruta_qr_parq'] ?? null);

        $compras[] = $row;
    }

    echo json_encode(
        ["success" => true, "compras" => $compras],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} else {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error en la consulta: " . pg_last_error($conn)
    ]);
}
