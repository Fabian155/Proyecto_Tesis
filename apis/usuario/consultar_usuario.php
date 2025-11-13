<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include '../../conexion.php';

// Obtener el ID desde la URL o el JSON
$input = json_decode(file_get_contents("php://input"), true);
$id = isset($input['id']) ? intval($input['id']) : null;

if (!$id) {
    echo json_encode(["error" => "Falta el ID del usuario."]);
    exit;
}

$query = "SELECT usr_id, usr_nom, usr_ape, usr_cel, usr_cor, usr_fec_nac
          FROM tbl_usuario
          WHERE usr_id = $id";

$result = pg_query($conn, $query);

if ($result && pg_num_rows($result) > 0) {
    $usuario = pg_fetch_assoc($result);
    echo json_encode($usuario);
} else {
    echo json_encode(["error" => "Usuario no encontrado."]);
}

pg_close($conn);
?>
