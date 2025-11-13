<?php
session_start();
$error = "";

// Conexión a la base de datos
include '../conexion.php';

// Consultar el logo activo
$query = "SELECT log_rut FROM tbl_logos WHERE log_est = 'activo' LIMIT 1";
$result = pg_query($conn, $query);

$logo = '';
if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);
    $logo = '../' . $row['log_rut']; // Agrega ../ porque estás dentro de /sesion/
} else {
    // Logo por defecto si no hay activo
    $logo = '../php/imagenes/logoempresa.png';
}

// Función SHA-256 binario
function sha256_bin($string)
{
    return hex2bin(hash('sha256', $string));
}

// Función de login interno
function check_login($conn, $correo, $contrasena)
{
    $hash_bin = pg_escape_bytea(sha256_bin($contrasena));

    $tablas = [
        ["tbl_usuario", "usr_cor", "usr_id", "usr_nom", "usr_con", "usuario"],
        ["tbl_cajero", "caj_ema", "caj_id", "caj_nom", "caj_con", "cajero"],
        ["tbl_admin", "adm_ema", "adm_id", "adm_nom", "adm_con", "admin"]
    ];

    foreach ($tablas as $t) {
        [$table, $email_field, $id_field, $name_field, $pass_field, $tipo] = $t;

        $correo_esc = pg_escape_string($conn, $correo);
        $query = "SELECT $id_field, $name_field, $pass_field FROM $table WHERE $email_field = '$correo_esc' LIMIT 1";
        $res = pg_query($conn, $query);

        if ($res && $row = pg_fetch_assoc($res)) {
            if ($hash_bin === $row[$pass_field]) {
                // Login correcto
                return [
                    "tipo" => $tipo,
                    "id" => $row[$id_field],
                    "nombre" => $row[$name_field]
                ];
            } else {
                return ["error" => "Contraseña incorrecta."];
            }
        }
    }

    return ["error" => "Correo no registrado o contraseña incorrecta."];
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo']);
    $contrasena = trim($_POST['contrasena']);

    if (empty($correo) || empty($contrasena)) {
        // La validación de jQuery manejará esto en el frontend,
        // pero se mantiene por seguridad en el backend.
        $error = "Por favor ingresa correo y contraseña.";
    } else {
        $result = check_login($conn, $correo, $contrasena);

        if (isset($result['tipo'])) {
            $_SESSION['id'] = $result['id'];
            $_SESSION['nombre'] = $result['nombre'];
            $_SESSION['tipo'] = $result['tipo'];

            // 🔥 Lógica para volver al formulario de compra si venía de ahí
            if ($result['tipo'] === 'usuario' && isset($_GET['from']) && $_GET['from'] === 'compra') {
                echo "<script>
                    const redirectUrl = localStorage.getItem('redirectAfterLogin');
                    if (redirectUrl) {
                        // Reemplaza la ruta completa de compra_visita a compra_usuario
                        window.location.href = redirectUrl.replace('php/info/compra_visita.php', 'php/usuario/compra_usuario.php');
                    } else {
                        window.location.href = '../php/usuario/menu.php';
                    }
                </script>";
                exit;
            }

            // 🔹 Si no viene de compra, redirige normalmente
            switch ($result['tipo']) {
                case 'usuario':
                    header('Location: ../php/usuario/menu.php');
                    exit;
                case 'cajero':
                    header('Location: ../php/cajero/menu.php');
                    exit;
                case 'admin':
                    header('Location: ../php/admin/menu.php');
                    exit;
            }
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login IP_Eventos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            background-color: #0F172A;
            color: #F9FAFB;
            overflow: hidden;
        }

        .split-container {
            display: flex;
            width: 100%;
            height: 100%;
        }

        .left-panel {
            flex: 1;
            background-color: #1E293B;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            box-shadow: 10px 0 20px rgba(0, 0, 0, 0.2);
        }

        .mountain-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            clip-path: polygon(0 40%, 50% 10%, 100% 40%, 100% 0, 0 0);
            background-color: #1E293B;
            z-index: -1;
            transition: all 0.5s ease-in-out;
        }

        .left-panel .logo {
            z-index: 1;
        }

        .left-panel .logo img {
            max-width: 300px;
            height: auto;
        }

        .right-panel {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background-color: #0F172A;
        }

        .login-container {
            background-color: transparent;
            padding: 0;
            border-radius: 0;
            text-align: center;
            width: 100%;
            max-width: 400px;
            box-shadow: none;
            position: relative;
            z-index: 1;
        }

        h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        p.subtitle {
            font-size: 16px;
            color: #64748B;
            margin-bottom: 30px;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            display: block;
            color: #64748B;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .input-group input {
            width: 100%;
            padding: 15px;
            background-color: #1E293B;
            border: none;
            border-radius: 10px;
            color: #F9FAFB;
            font-size: 16px;
            box-sizing: border-box;
        }

        /* Estilo para los campos con error */
        .input-group input.error {
            border: 2px solid #DC2626 !important; /* Rojo fuerte */
        }

        .forgot-password {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: #94A3B8;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #F9FAFB;
            text-decoration: underline;
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background-color: #1D4ED8;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #1E40AF;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #64748B;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #334155;
        }

        .divider:not(:empty)::before {
            margin-right: .5em;
        }

        .divider:not(:empty)::after {
            margin-left: .5em;
        }

        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 15px;
            border: 1px solid #334155;
            background-color: transparent;
            color: #F9FAFB;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-google img {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }

        .btn-google:hover {
            background-color: #21324a;
        }

        .create-account {
            margin-top: 20px;
            font-size: 14px;
            color: #64748B;
        }

        .create-account a {
            color: #F9FAFB;
            font-weight: 600;
            text-decoration: none;
        }

        .create-account a:hover {
            text-decoration: underline;
        }

        #error {
            color: #ffcc00;
            margin-top: 10px;
            font-size: 14px;
        }
        
        /* Estilo para los mensajes de error de jQuery Validation */
        label.error {
            color: #DC2626; /* Rojo para el mensaje de error */
            font-size: 12px;
            margin-top: 5px;
            display: block; /* Asegura que el mensaje se muestre debajo del input */
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .left-panel {
                display: none;
            }

            .right-panel {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="split-container">
        <div class="left-panel">
            <div class="mountain-bg"></div>
            <div class="logo">
                <img src="<?php echo $logo; ?>" alt="Logo de la empresa">
            </div>
        </div>
        <div class="right-panel">
            <div class="login-container">
                <h2>Iniciar Sesión</h2>
                <p class="subtitle">Accede a tu cuenta</p>
                <form method="POST" id="frm_login">
                    <div class="input-group">
                        <label for="correo">Correo Electrónico</label>
                        <input type="email" name="correo" id="correo" placeholder=" " required
                            value="<?php echo isset($correo) ? htmlspecialchars($correo) : ''; ?>">
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
                <?php if (!empty($error)) echo "<p id='error'>$error</p>"; ?>
                <div class="divider">O continúa con</div>
                <a href="google_redirect.php" class="btn-google">
                    <img src="../php/imagenes/google.ico" alt="Logo de Google">
                    Google
                </a>
                <div class="create-account">
                    <span>¿No tienes una cuenta?</span>
                    <a href="../sesion/crear_cuenta.php">Regístrate</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/localization/messages_es.min.js"></script>

    <script>
        $(document).ready(function() {
            // Asegúrate de que el ID del formulario en el HTML sea 'frm_login'
            $("#frm_login").validate({
                rules: {
                    "correo": {
                        required: true,
                        email: true
                    },
                    "contrasena": {
                        required: true
                    }
                },
                messages: {
                    "correo": {
                        required: "Por favor, ingresa tu correo electrónico.",
                        email: "Por favor, ingresa un formato de correo válido."
                    },
                    "contrasena": {
                        required: "Por favor, ingresa tu contraseña."
                    }
                },
                // Función para resaltar el input con error
                highlight: function(element, errorClass, validClass) {
                    $(element).addClass(errorClass).removeClass(validClass);
                    // Opcional: añadir clase al grupo contenedor si es necesario para estilos más complejos
                    // $(element).closest('.input-group').addClass('has-error');
                },
                // Función para quitar el resaltado cuando es válido
                unhighlight: function(element, errorClass, validClass) {
                    $(element).removeClass(errorClass).addClass(validClass);
                    // Opcional: quitar clase al grupo contenedor
                    // $(element).closest('.input-group').removeClass('has-error');
                },
                // Coloca el mensaje de error directamente después del input
                errorPlacement: function(error, element) {
                    error.insertAfter(element);
                }
            });
        });
    </script>
</body>

</html>