<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");

include '../../conexion.php';

$input = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
if (!$input || !isset($input['id'], $input['nombre'], $input['usuario'], $input['correo'])) {
    echo json_encode(["error" => "Faltan datos requeridos."]);
    exit;
}

$id      = intval($input['id']);
$nombre  = pg_escape_string($conn, $input['nombre']);
$usuario = pg_escape_string($conn, $input['usuario']);
$correo  = pg_escape_string($conn, $input['correo']);

// Inicia la construcción de la query
$query = "UPDATE tbl_cajero SET 
            caj_nom = '$nombre',
            caj_usr = '$usuario',
            caj_ema = '$correo',
            caj_fec_edi = CURRENT_TIMESTAMP";

// Si la contraseña existe y no está vacía, se añade a la query de actualización
if (isset($input['contrasena']) && !empty($input['contrasena'])) {
    // Encripta la contraseña con SHA-256
    $contrasena = pg_escape_bytea(hex2bin(hash('sha256', $input['contrasena'])));
    $query .= ", caj_con = '$contrasena'";
}

// Finaliza la query con la cláusula WHERE
$query .= " WHERE caj_id = $id";

$result = pg_query($conn, $query);

if ($result) {
    echo json_encode(["success" => "Cajero actualizado correctamente."]);
} else {
    echo json_encode(["error" => "No se pudo actualizar el cajero."]);
}

pg_close($conn);
?>
