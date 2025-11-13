<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

$client = new Google_Client();
$client->setClientId("978051410585-l710fs6btsbnhitq8v10eqmdit9bj232.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("http://localhost/IP_Eventos/sesion/google_callback.php");
$client->addScope(["email", "profile"]);
$client->setAccessType('offline');
$client->setPrompt('select_account');

$authUrl = $client->createAuthUrl();
header("Location: $authUrl");
exit;
