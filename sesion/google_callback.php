<?php
// C:\xampp\htdocs\IP_Eventos\sesion\google_callback.php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

// ---------- CONFIG GOOGLE ----------
$client = new Google_Client();
$client->setClientId("978051410585-l710fs6btsbnhitq8v10eqmdit9bj232.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("http://localhost/IP_Eventos/sesion/google_callback.php");
$client->addScope(["email", "profile"]);

// ---------- VALIDAR CODE ----------
if (!isset($_GET['code'])) {
    // Si no viene el code, vuelve al login mostrando error
    header("Location: ./login.php?e=Falta+code+de+Google");
    exit;
}

// ---------- INTERCAMBIAR CODE POR TOKEN ----------
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
    header("Location: ./login.php?e=" . urlencode("Error token: " . ($token['error_description'] ?? $token['error'])));
    exit;
}

$client->setAccessToken($token);

// ---------- LEER PERFIL (CORREO) ----------
$oauth = new Google_Service_Oauth2($client);
$userInfo = $oauth->userinfo->get();

$email = $userInfo->email ?? null;
if (!$email) {
    header("Location: ./login.php?e=No+se+obtuvo+correo+de+Google");
    exit;
}

// ---------- LLAMAR A TU API ----------
$urlApi = "http://localhost/IP_Eventos/apis/sesion/loginC.php";
$payload = json_encode(["correo" => $email], JSON_UNESCAPED_UNICODE);

// Intento 1: file_get_contents (requiere allow_url_fopen=On)
$result = false;
$opts = [
    "http" => [
        "header"  => "Content-Type: application/json\r\n",
        "method"  => "POST",
        "content" => $payload,
        "timeout" => 10
    ]
];
$ctx = stream_context_create($opts);
$result = @file_get_contents($urlApi, false, $ctx);

// Intento 2 (fallback): cURL
if ($result === false) {
    if (function_exists('curl_init')) {
        $ch = curl_init($urlApi);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $result = curl_exec($ch);
        if ($result === false) {
            $err = curl_error($ch);
            curl_close($ch);
            header("Location: ./login.php?e=" . urlencode("No se pudo contactar la API: $err"));
            exit;
        }
        curl_close($ch);
    } else {
        header("Location: ./login.php?e=No+se+pudo+contactar+la+API");
        exit;
    }
}

// ---------- PROCESAR RESPUESTA DE LA API ----------
$data = json_decode($result, true);
if (!is_array($data)) {
    header("Location: ./login.php?e=Respuesta+inv%C3%A1lida+de+la+API");
    exit;
}

if (isset($data['error'])) {
    // Ej: {"error":"Correo no registrado."}
    header("Location: ./login.php?e=" . urlencode($data['error']));
    exit;
}

// Esperamos {"tipo":"usuario|cajero|admin","id":"..","nombre":".."}
if (!isset($data['tipo'], $data['id'], $data['nombre'])) {
    header("Location: ./login.php?e=Datos+incompletos+de+la+API");
    exit;
}

// ---------- GUARDAR SESIÓN ----------
$_SESSION['id']     = $data['id'];
$_SESSION['nombre'] = $data['nombre'];
$_SESSION['tipo']   = $data['tipo'];

// ---------- REDIRECCIÓN SEGÚN TIPO ----------
switch ($data['tipo']) {
    case 'usuario':
        header("Location: ../php/usuario/menu.php");
        exit;
    case 'cajero':
        header("Location: ../php/cajero/menu.php");
        exit;
    case 'admin':
        header("Location: ../php/admin/menu.php");
        exit;
    default:
        header("Location: ./login.php?e=Tipo+de+usuario+no+reconocido");
        exit;
}
