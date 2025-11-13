<?php
// Incluye el controlador para ejecutar la lógica de inicio de sesión
// Esto procesará el POST y establecerá las variables $error y $correo si es necesario.
require_once '../controller/sesion_controller.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login IP_Eventos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css"> 
</head>
<body>
    <div class="split-container">
        <div class="left-panel">
            <div class="mountain-bg"></div>
            <div class="logo">
                <img src="../php/imagenes/logoempresa.png" alt="Logo de la empresa">
            </div>
        </div>
        <div class="right-panel">
            <div class="login-container">
                <h2>Iniciar Sesión</h2>
                <p class="subtitle">Accede a tu cuenta</p>
                <form method="POST" action=""> 
                    <div class="input-group">
                        <label for="correo">Correo Electrónico</label>
                        <input type="email" name="correo" id="correo" placeholder=" " required value="<?php echo htmlspecialchars($correo); ?>">
                    </div>
                    <div class="input-group">
                        <label for="contrasena">Contraseña</label>
                        <input type="password" name="contrasena" id="contrasena" placeholder=" " required>
                    </div>
                    <div class="forgot-password">
                        <a href="../sesion/recuperar_formM.php">¿Lo olvidaste?</a>
                    </div>
                    <button type="submit" class="btn-submit">Ingresar</button>
                </form>
                <?php if (!empty($error)) echo "<p id='error'>".htmlspecialchars($error)."</p>"; ?>
                
                <div class="divider">O continúa con</div>
                <button class="btn-google">
                    <img src="../php/imagenes/google.ico" alt="Logo de Google">
                    Google
                </button>
                <div class="create-account">
                    <span>¿No tienes una cuenta?</span>
                    <a href="../../sesion/crear_cuenta.php">Regístrate</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>