<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");

include '../../conexion.php';

$input = json_decode(file_get_contents("php://input"), true);

// Se ajusta la validación para que 'contrasena' no sea un campo requerido
if (!$input || !isset($input['id'], $input['nombre'], $input['apellido'], $input['correo'], $input['fecha_nac'])) {
    echo json_encode(["error" => "Faltan datos requeridos."]);
    exit;
}

$id         = intval($input['id']);
$nombre     = pg_escape_string($conn, $input['nombre']);
$apellido   = pg_escape_string($conn, $input['apellido']);
$celular    = isset($input['celular']) ? pg_escape_string($conn, $input['celular']) : null;
$correo     = pg_escape_string($conn, $input['correo']);
$fecha_nac  = pg_escape_string($conn, $input['fecha_nac']);

// Inicia la construcción de la query
$query = "UPDATE tbl_usuario SET 
            usr_nom = '$nombre',
            usr_ape = '$apellido',
            usr_cel = '$celular',
            usr_cor = '$correo',
            usr_fec_nac = '$fecha_nac'";

// Si la contraseña existe y no está vacía, se añade a la query de actualización
if (isset($input['contrasena']) && !empty($input['contrasena'])) {
    $contrasena = pg_escape_bytea(hex2bin(hash('sha256', $input['contrasena'])));
    $query .= ", usr_con = '$contrasena'";
}

// Finaliza la query con la cláusula WHERE
$query .= " WHERE usr_id = $id";

$result = pg_query($conn, $query);

if ($result) {
    echo json_encode(["success" => "Usuario actualizado correctamente."]);
} else {
    echo json_encode(["error" => "No se pudo actualizar el usuario."]);
}

pg_close($conn);
?>