<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$input = json_decode(file_get_contents("php://input"), true);

if (isset($input['nombre']) && !empty($input['nombre'])) {
    $_SESSION['nombre'] = $input['nombre'];
    echo json_encode(["success" => "Sesión actualizada correctamente."]);
} else {
    echo json_encode(["error" => "No se pudo actualizar la sesión."]);
}
?>