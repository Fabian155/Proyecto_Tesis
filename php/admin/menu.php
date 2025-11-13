<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}
?>
<style>
.content-wrapper {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;

    /* Fondo blanco sin imagen */
    background-color: #ffffff;
    min-height: 105vh;
}
.container {
    background: transparent; /* sin efecto de vidrio */
    padding: 0;
    border-radius: 0;
    box-shadow: none;
    border: none;
    width: 100%;
    max-width: 100%;
    text-align: center;
    color: black;
}
</style>

<div class="content-wrapper">
    <div class="container">
        <!-- Contenido eliminado -->
    </div>
</div>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
?>
