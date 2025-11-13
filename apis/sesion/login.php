<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../conexion.php';

// Función SHA-256 binario
function sha256_bin($string) {
    return hex2bin(hash('sha256', $string));
}

// Leer datos
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['correo'], $input['contrasena'])) {
    echo json_encode(["error" => "Faltan datos requeridos."]);
    exit;
}

$correo    = pg_escape_string($conn, trim($input['correo']));
$contrasena = trim($input['contrasena']);
$hash_bin  = pg_escape_bytea(sha256_bin($contrasena));

// Función para comparar login
function check_login($conn, $table, $email_field, $id_field, $name_field, $pass_field, $hash_bin, $tipo) {
    $query = "SELECT $id_field, $name_field, $pass_field 
              FROM $table
              WHERE $email_field = '$GLOBALS[correo]' LIMIT 1";
    $res = pg_query($conn, $query);
    if (!$res) return ["error" => "Error en consulta SQL."]; // chequeo por fallo
    if ($row = pg_fetch_assoc($res)) {
        if ($hash_bin === $row[$pass_field]) {
            return ["tipo" => $tipo, "id" => $row[$id_field], "nombre" => $row[$name_field]];
        } else {
            return ["error" => "Contraseña incorrecta."];
        }
    }
    return null;
}

// tbl_usuario
$result = check_login($conn, "tbl_usuario", "usr_cor", "usr_id", "usr_nom", "usr_con", $hash_bin, "usuario");
if ($result) { echo json_encode($result); exit; }

// tbl_cajero
$result = check_login($conn, "tbl_cajero", "caj_ema", "caj_id", "caj_nom", "caj_con", $hash_bin, "cajero");
if ($result) { echo json_encode($result); exit; }

// tbl_admin
$result = check_login($conn, "tbl_admin", "adm_ema", "adm_id", "adm_nom", "adm_con", $hash_bin, "admin");
if ($result) { echo json_encode($result); exit; }

// Si no coincide en ninguna tabla
echo json_encode(["error" => "Correo no registrado o contraseña incorrecta."]);

pg_close($conn);
?>
