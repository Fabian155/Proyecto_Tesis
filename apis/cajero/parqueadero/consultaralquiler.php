<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../../conexion.php';

// Leer datos de entrada
$input = json_decode(file_get_contents("php://input"), true);

if (
    !$input ||
    !isset($input['asi_id_par']) ||
    !isset($input['asi_id_pue'])
) {
    echo json_encode(["success" => false, "mensaje" => "Faltan datos"]);
    exit;
}

$asi_id_par = intval($input['asi_id_par']);
$asi_id_pue = intval($input['asi_id_pue']);

// Consulta la asignación activa del puesto en ese parqueadero
$query = "
    SELECT a.asi_id, a.asi_precio, a.asi_caj_correo, a.asi_caj_tel, a.asi_est,
           p.pue_num,
           c.caj_nom
    FROM tbl_cajero_asignacion_parqueadero a
    INNER JOIN tbl_puestos_parqueadero p ON a.asi_id_pue = p.pue_id
    INNER JOIN tbl_cajero c ON a.asi_id_caj = c.caj_id
    WHERE a.asi_id_par = $asi_id_par
      AND a.asi_id_pue = $asi_id_pue
      AND a.asi_est = 'activo'
    ORDER BY a.asi_fec_cre DESC
    LIMIT 1
";

$res = pg_query($conn, $query);

if ($res && pg_num_rows($res) > 0) {
    $row = pg_fetch_assoc($res);

    $respuesta = [
        "success" => true,
        "asi_id"  => $row['asi_id'],
        "puesto"  => [
            "pue_id"  => $asi_id_pue,
            "pue_num" => $row['pue_num'],
            "pue_est" => $row['asi_est']
        ],
        "cajero" => [
            "nombre" => $row['caj_nom']
        ],
        "alquilo" => [
            "correo"  => $row['asi_caj_correo'],
            "telefono"=> $row['asi_caj_tel'],
            "precio"  => floatval($row['asi_precio'])
        ]
    ];

    echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} else {
    echo json_encode([
        "success" => false,
        "mensaje" => "No hay asignación activa para este puesto"
    ]);
}

pg_close($conn);
?>
