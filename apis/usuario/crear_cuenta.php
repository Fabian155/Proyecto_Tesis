<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../conexion.php';

$input = json_decode(file_get_contents("php://input"), true);

if (
    !$input ||
    !isset($input['nombre'], $input['apellido'], $input['correo'], $input['contrasena'], $input['fecha_nac'])
) {
    echo json_encode(["error" => "Faltan datos requeridos."]);
    exit;
}

$nombre    = pg_escape_string($conn, $input['nombre']);
$apellido  = pg_escape_string($conn, $input['apellido']);
$celular   = isset($input['celular']) ? pg_escape_string($conn, $input['celular']) : null;
$correo    = pg_escape_string($conn, $input['correo']);
$fecha_nac = pg_escape_string($conn, $input['fecha_nac']);

// === Validación: correo no debe existir en admin, cajero ni usuario ===
$correoParam = strtolower($correo);

// Admin
$existe_admin = false;
if ($res = pg_query_params(
    $conn,
    "SELECT 1 FROM tbl_admin WHERE adm_ema IS NOT NULL AND lower(adm_ema) = $1 LIMIT 1",
    [$correoParam]
)) {
    $existe_admin = pg_num_rows($res) > 0;
    pg_free_result($res);
}

// Cajero
$existe_cajero = false;
if (!$existe_admin) {
    if ($res = pg_query_params(
        $conn,
        "SELECT 1 FROM tbl_cajero WHERE caj_ema IS NOT NULL AND lower(caj_ema) = $1 LIMIT 1",
        [$correoParam]
    )) {
        $existe_cajero = pg_num_rows($res) > 0;
        pg_free_result($res);
    }
}

// Usuario (misma tabla de inserción)
$existe_usuario = false;
if (!$existe_admin && !$existe_cajero) {
    if ($res = pg_query_params(
        $conn,
        "SELECT 1 FROM tbl_usuario WHERE usr_cor IS NOT NULL AND lower(usr_cor) = $1 LIMIT 1",
        [$correoParam]
    )) {
        $existe_usuario = pg_num_rows($res) > 0;
        pg_free_result($res);
    }
}

if ($existe_admin || $existe_cajero || $existe_usuario) {
    echo json_encode(["error" => "El correo ya está registrado."]);
    pg_close($conn);
    exit;
}
// === Fin validación ===

// SHA-256 binario -> BYTEA
$contrasena = pg_escape_bytea($conn, hex2bin(hash('sha256', $input['contrasena'])));

// Manejo de NULL para celular
$celular_sql = is_null($celular) || $celular === '' ? "NULL" : "'$celular'";

$query = "
    INSERT INTO tbl_usuario (usr_nom, usr_ape, usr_cel, usr_cor, usr_fec_nac, usr_con)
    VALUES ('$nombre', '$apellido', $celular_sql, '$correo', '$fecha_nac', '$contrasena')
";

$result = pg_query($conn, $query);

if ($result) {
    echo json_encode(["success" => "Usuario insertado correctamente."]);
} else {
    echo json_encode(["error" => "No se pudo insertar el usuario."]);
}

pg_close($conn);
?>
