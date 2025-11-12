<?php
// Permitir solicitudes desde cualquier origen (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Incluir conexión a la base de datos
include '../../conexion.php'; 

// Leer los datos enviados por POST (JSON)
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['correo'])) {
    echo json_encode(["error" => "Falta el correo."]);
    exit;
}

$correo = $input['correo'];

// Primero buscamos en tbl_usuario
$query_usr = "SELECT usr_id, usr_nom, usr_ape 
              FROM tbl_usuario 
              WHERE usr_cor = '$correo' LIMIT 1";

$result_usr = pg_query($conn, $query_usr);

if ($row_usr = pg_fetch_assoc($result_usr)) {
    echo json_encode([
        "tipo" => "usuario",
        "id" => $row_usr['usr_id'],
        "nombre" => $row_usr['usr_nom'] . ' ' . $row_usr['usr_ape']
    ]);
    exit;
}

// Luego buscamos en tbl_cajero
$query_caj = "SELECT caj_id, caj_nom 
              FROM tbl_cajero 
              WHERE caj_ema = '$correo' LIMIT 1";

$result_caj = pg_query($conn, $query_caj);

if ($row_caj = pg_fetch_assoc($result_caj)) {
    echo json_encode([
        "tipo" => "cajero",
        "id" => $row_caj['caj_id'],
        "nombre" => $row_caj['caj_nom']
    ]);
    exit;
}

// Luego buscamos en tbl_admin
$query_adm = "SELECT adm_id, adm_nom
              FROM tbl_admin
              WHERE adm_ema = '$correo' LIMIT 1";

$result_adm = pg_query($conn, $query_adm);

if ($row_adm = pg_fetch_assoc($result_adm)) {
    echo json_encode([
        "tipo"   => "admin",
        "id"     => $row_adm['adm_id'],
        "nombre" => $row_adm['adm_nom']
    ]);
    exit;
}

// Si no se encuentra en ninguna tabla
echo json_encode(["error" => "Correo no registrado."]);

// Cerrar conexión
pg_close($conn);
?>
