<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

// Aquí se define el contenido que irá en la plantilla
$contenido = '
    <h2>Bienvenido, ' . htmlspecialchars($_SESSION['nombre']) . '!</h2>
    <p>Este es tu menú de Cajeros.</p>
    
    <a href="gestion_cajero.php">Gestion cajeros</a>
    <br>
    <a href="consultar_escaneos_qr.php">Escaneos</a>
    <br>
    <a href="menu.php" style="background:#555;">Volver al Menú Principal</a>

';

// Finalmente incluyes la plantilla
include 'plantillaAdmin.php';
