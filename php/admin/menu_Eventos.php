<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

// Contenido específico para este menú
$contenido = '
    <h2>Bienvenido, ' . htmlspecialchars($_SESSION['nombre']) . '!</h2>
    <p>Este es tu menú de Eventos.</p>
    
    <a href="crear_evento.php">Gestion Evento</a>
    <br>
    <a href="consultar_boletosCU.php" style="background:#555;">Boletos Comprados por usuarios</a>
    <br>
    <a href="consultar_boletosCC.php" style="background:#555;">Boletos vendidos por cajeros</a>

    <br><br>
    <a href="menu.php" style="background:#555;">Volver al Menú Principal</a>

';

// Incluir la plantilla que ya tiene header, footer y estilos
include 'plantillaAdmin.php';
