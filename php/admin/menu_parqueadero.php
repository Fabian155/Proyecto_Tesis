<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

// Contenido específico para este menú
$contenido = '
    <h2>Bienvenido, ' . htmlspecialchars($_SESSION['nombre']) . '!</h2>
    <p>Este es tu menú de Parqueadero.</p>
    
    <a href="crear_parqueadero.php">Gestion parqueaderos</a>
    <br><br>
    <a href="consultar_alquilerPU.php" style="background:#555;">puetso reservado por usuarios</a>

    <br>
    <a href="consultar_alquilerPC.php" style="background:#555;">puestos reservado a por cajeros</a>

    <br><br>
    <a href="menu.php" style="background:#555;">Volver al Menú Principal</a>
';

// Incluir la plantilla que ya tiene header, footer y estilos
include 'plantillaAdmin.php';
