<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../conexion.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['nombre'], $input['usuario'], $input['contrasena'])) {
    echo json_encode(["error" => "Faltan datos requeridos."]);
    exit;
}

$nombre     = pg_escape_string($conn, $input['nombre']);
$usuario    = pg_escape_string($conn, $input['usuario']);
$correo     = isset($input['correo']) ? pg_escape_string($conn, $input['correo']) : null;

// Convertir SHA-256 a binario
$contrasena = pg_escape_bytea(hex2bin(hash('sha256', $input['contrasena'])));

$query = "INSERT INTO tbl_admin 
          (adm_nom, adm_usr, adm_con, adm_ema) 
          VALUES ('$nombre', '$usuario', '$contrasena', '$correo')";

$result = pg_query($conn, $query);

if ($result) {
    echo json_encode(["success" => "Administrador creado correctamente."]);
} else {
    echo json_encode(["error" => "No se pudo crear el administrador."]);
}

pg_close($conn);
?>
