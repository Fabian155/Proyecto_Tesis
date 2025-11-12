<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'cajero') {
    header("Location: ../../sesion/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú Cajero</title>
    <style>
        body { font-family: Arial; text-align: center; margin-top: 50px; }
        a, button { padding: 10px 20px; margin: 10px; display: inline-block; text-decoration: none; background: #2196F3; color: white; border: none; border-radius: 5px; }
        a:hover, button:hover { background: #0b7dda; }
    </style>
</head>
<body>
    <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?>!</h2>
    <p>Este es tu menú de cajero.</p>

    <a href="#">Registrar Venta</a>
    <a href="#">Consultar Tickets</a>
    <form method="post" style="display:inline;">
        <button name="logout">Cerrar Sesión</button>
    </form>

    <?php
    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: ../../sesion/login.php");
        exit;
    }
    ?>
</body>
</html>
