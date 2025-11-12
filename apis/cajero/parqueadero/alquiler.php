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
    !isset($input['asi_id_pue']) ||
    !isset($input['asi_id_caj']) ||
    !isset($input['asi_precio']) ||
    !isset($input['asi_caj_correo']) ||
    !isset($input['asi_caj_tel'])
) {
    echo json_encode(["success" => false, "mensaje" => "Faltan datos"]);
    exit;
}

$asi_id_par      = intval($input['asi_id_par']);        // ID parqueadero
$asi_id_pue      = intval($input['asi_id_pue']);        // ID puesto
$asi_id_caj      = intval($input['asi_id_caj']);        // ID cajero
$asi_precio      = floatval($input['asi_precio']);      // Precio cobrado
$asi_caj_correo  = pg_escape_string($conn, $input['asi_caj_correo']); // Correo del que alquila
$asi_caj_tel     = pg_escape_string($conn, $input['asi_caj_tel']);    // Teléfono del que alquila

// 0. Validar si el puesto está ocupado
$query_check = "SELECT pue_est, pue_num FROM tbl_puestos_parqueadero WHERE pue_id = $asi_id_pue";
$res_check = pg_query($conn, $query_check);

if ($res_check && $row_check = pg_fetch_assoc($res_check)) {
    if ($row_check['pue_est'] === 'ocupado') {
        echo json_encode([
            "success" => false,
            "mensaje" => "El puesto ya está ocupado"
        ]);
        pg_close($conn);
        exit;
    }
    $pue_num = $row_check['pue_num']; // guardamos el número del puesto
}

// Iniciamos transacción
pg_query("BEGIN");

// 1. Insertar en tabla de asignaciones
$query_asig = "
    INSERT INTO tbl_cajero_asignacion_parqueadero
    (asi_id_par, asi_id_pue, asi_id_caj, asi_precio, asi_caj_correo, asi_caj_tel, asi_est)
    VALUES ($asi_id_par, $asi_id_pue, $asi_id_caj, $asi_precio, '$asi_caj_correo', '$asi_caj_tel', 'activo')
    RETURNING asi_id
";

$res_asig = pg_query($conn, $query_asig);

if ($res_asig && $row = pg_fetch_assoc($res_asig)) {
    $asi_id = $row['asi_id'];

    // 2. Cambiar estado del puesto a ocupado
    $query_pue = "
        UPDATE tbl_puestos_parqueadero
        SET pue_est = 'ocupado', pue_fec_edi = NOW()
        WHERE pue_id = $asi_id_pue
    ";
    $res_pue = pg_query($conn, $query_pue);

    if ($res_pue) {
        // 3. Traer solo el nombre del cajero
        $query_caj = "SELECT caj_nom FROM tbl_cajero WHERE caj_id = $asi_id_caj";
        $res_caj = pg_query($conn, $query_caj);
        $cajero_nombre = "";
        if ($res_caj && $row_caj = pg_fetch_assoc($res_caj)) {
            $cajero_nombre = $row_caj['caj_nom'];
        }

        pg_query("COMMIT");

        echo json_encode([
            "success" => true,
            "mensaje" => "Puesto asignado correctamente",
            "asi_id"  => $asi_id,
            "puesto"  => [
                "pue_id"  => $asi_id_pue,
                "pue_num" => $pue_num,
                "pue_est" => "ocupado"
            ],
            "cajero" => [
                "nombre"  => $cajero_nombre
            ],
            "alquilo" => [
                "correo"  => $asi_caj_correo,
                "telefono"=> $asi_caj_tel,
                "precio"  => $asi_precio
            ]
        ]);
    } else {
        pg_query("ROLLBACK");
        echo json_encode(["success" => false, "mensaje" => "Error al actualizar el puesto"]);
    }
} else {
    pg_query("ROLLBACK");
    echo json_encode(["success" => false, "mensaje" => "Error al registrar la asignación"]);
}

pg_close($conn);
?>
