<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include '../../conexion.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['asignaciones'])) {
    echo json_encode(["success" => false, "message" => "No se recibieron asignaciones de cajero a parqueadero"]);
    exit;
}

$response = [];
foreach ($input['asignaciones'] as $row) {
    $columns = array_keys($row);
    $values = array_map(function($v) use ($conn) {
        return $v === null ? "NULL" : "'" . pg_escape_string($conn, $v) . "'";
    }, array_values($row));

    $updates = [];
    foreach ($columns as $i => $col) {
        $updates[] = "$col = " . $values[$i];
    }

    $query = "INSERT INTO tbl_cajero_asignacion_parqueadero (" . implode(",", $columns) . ")
              VALUES (" . implode(",", $values) . ")
              ON CONFLICT (asi_id) DO UPDATE SET " . implode(",", $updates);

    $ok = pg_query($conn, $query);
    $response[] = $ok ? "ok" : pg_last_error($conn);
}

echo json_encode(["success" => true, "cajero_asignacion_parqueadero" => $response]);
?>
