<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../conexion.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['nombre'], $input['usuario'], $input['contrasena'], $input['id_admin'])) {
    echo json_encode(["error" => "Faltan datos requeridos."]);
    exit;
}

$nombre   = pg_escape_string($conn, $input['nombre']);
$usuario  = pg_escape_string($conn, $input['usuario']);
$correo   = isset($input['correo']) ? pg_escape_string($conn, $input['correo']) : null;
$id_admin = (int) $input['id_admin'];

// SHA-256 binario
$contrasena = pg_escape_bytea(hex2bin(hash('sha256', $input['contrasena'])));

$query = "INSERT INTO tbl_cajero 
          (caj_nom, caj_usr, caj_con, caj_ema, caj_id_adm) 
          VALUES ('$nombre', '$usuario', '$contrasena', '$correo', $id_admin)";

$result = pg_query($conn, $query);

if ($result) {
    echo json_encode(["success" => "Cajero insertado correctamente."]);
} else {
    echo json_encode(["error" => "No se pudo insertar el cajero."]);
}

pg_close($conn);
?>
