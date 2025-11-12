<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

session_start();
include '../../conexion.php';  
require __DIR__ . '/../../vendor/autoload.php'; // autoload de Composer para PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Función SHA-256 binario
function sha256_bin($string) {
    return pg_escape_bytea(hex2bin(hash('sha256', trim($string))));
}

// Función para enviar correo con PHPMailer
function enviarCorreoNuevaClave($correo, $nuevaClave) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sepedad029@gmail.com';
        $mail->Password   = 'ksuegvmvbnucigjc'; // contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('sepedad029@gmail.com', 'Recuperación de Cuenta - Iron Producciones');
        $mail->addAddress($correo);

        $mail->isHTML(true);
        $mail->Subject = 'Nueva contraseña generada';
        $mail->Body    = "
        <h3>Recuperación de Cuenta</h3>
        <p>Se ha generado una nueva contraseña para tu cuenta.</p>
        <p><b>Tu nueva contraseña es:</b></p>
        <h2 style='color:#2563eb;'>$nuevaClave</h2>
        <p>Por seguridad, te recomendamos cambiarla al iniciar sesión.</p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Leer datos JSON
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['correo'])) {
    echo json_encode(["error" => "Debe ingresar un correo."]);
    exit;
}

$correo = pg_escape_string($conn, trim($input['correo']));
$tabla  = null;
$campoCorreo = null;
$campoClave  = null;

// Tablas donde puede estar el correo
$tablas = [
    ["tabla" => "tbl_usuario", "correo" => "usr_cor", "clave" => "usr_con"],
    ["tabla" => "tbl_admin",   "correo" => "adm_ema", "clave" => "adm_con"],
    ["tabla" => "tbl_cajero",  "correo" => "caj_ema", "clave" => "caj_con"]
];

foreach ($tablas as $t) {
    $query = "SELECT * FROM {$t['tabla']} WHERE {$t['correo']} = '$correo' LIMIT 1";
    $result = pg_query($conn, $query);

    if ($result && pg_num_rows($result) > 0) {
        $tabla = $t['tabla'];
        $campoCorreo = $t['correo'];
        $campoClave  = $t['clave'];
        break;
    }
}

if (!$tabla) {
    echo json_encode(["error" => "El correo no está registrado en el sistema."]);
    pg_close($conn);
    exit;
}

/* === NUEVA LÓGICA DE RECUPERACIÓN === */

// Generar una nueva contraseña aleatoria segura (10 caracteres)
$nuevaClave = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789@#%&"), 0, 10);
$hash = sha256_bin($nuevaClave);

// Actualizar contraseña en la base de datos
$update = "UPDATE $tabla SET $campoClave = '$hash' WHERE $campoCorreo = '$correo'";
$ok = pg_query($conn, $update);

if ($ok && enviarCorreoNuevaClave($correo, $nuevaClave)) {
    echo json_encode([
        "success" => "Se ha enviado una nueva contraseña a tu correo electrónico.",
        "tabla"   => $tabla
    ]);
} else {
    echo json_encode(["error" => "No se pudo enviar el correo o actualizar la contraseña."]);
}

pg_close($conn);
?>
