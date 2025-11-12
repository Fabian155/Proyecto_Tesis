<?php
// ========================================
// INCLUDES Y CONFIGURACIÓN INICIAL
// ========================================

// La URL base de tu proyecto, asumiendo una estructura similar a la del ejemplo
$baseURL = include __DIR__ . '/../../../ruta_Api.php';
// Conexión a la base de datos, asumiendo un archivo de conexión
require_once __DIR__ . '/../../../conexion.php';

// Inicia la sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica si el usuario ha iniciado sesión y tiene el tipo 'usuario'
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'usuario') {
    // Usa $baseURL para la redirección de forma parametrizable
    header("Location: " . $baseURL . "sesion/login.php");
    exit;
}

// Cierra la sesión si se hace clic en el botón de cerrar sesión
if (isset($_POST['logout'])) {
    session_destroy();
    // Usa $baseURL para la redirección de forma parametrizable
    header("Location: " . $baseURL . "index.php");
    exit;
}

// Obtiene la URL de la página actual para marcar el enlace activo
$current_page = basename($_SERVER['PHP_SELF']);

// Helper para marcar el enlace activo
function isActive($file, $current){ 
    return $current === $file ? 'active' : ''; 
}


// ========================================
// OBTENER DATOS DINÁMICOS DEL ENCABEZADO
// ========================================

// --- LOGO ACTIVO (tbl_logos) ---
// La URL por defecto se usa como fallback
$logoURL = $baseURL . 'php/imagenes/logoempresa.png'; 
$qLogo = "SELECT log_rut FROM tbl_logos WHERE log_est = 'activo' LIMIT 1";
$rLogo = @pg_query($conn, $qLogo);

if ($rLogo && pg_num_rows($rLogo) > 0) {
    $ruta = trim(pg_fetch_result($rLogo, 0, 'log_rut'));
    if ($ruta !== '') {
        // Asegura que la ruta sea absoluta si no es una URL completa
        $logoURL = preg_match('#^https?://#i', $ruta)
            ? $ruta
            : rtrim($baseURL, '/') . '/' . ltrim($ruta, '/');
    }
}

// --- VIDEO DE FONDO (Si fuera configurable) ---
// Mantendremos la ruta fija, pero podrías obtenerla de una tabla de configuración
$videoURL = $baseURL . 'php/imagenes/video/fondoinfo.mp4';

// --- GIF DE CARGA (Si fuera configurable) ---
// Mantendremos la ruta fija, pero podrías obtenerla de una tabla de configuración
$loadingGifURL = 'https://ipeventos.alwaysdata.net/php/imagenes/gif/cargando.gif'; 
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartelera de Eventos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* [NO SE MODIFICA EL DISEÑO - COPIA FIEL DE LA VERSIÓN ORIGINAL] */
        :root {
            --primary-color: #e5002b;
            --secondary-color: #0078c7;
            --dark-bg: #1A1A1A;
            --card-bg: #2C2C2C;
            --text-color: #F8F9FA;
            --light-text: #B0B0B0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark-bg);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-x: hidden;
        }

        header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 50px;
            box-sizing: border-box;
            background-color: transparent;
            position: relative;
            z-index: 1000;
            height: 150px;
            overflow: hidden;
            animation: fadeInDown 1s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
            opacity: 0.8;
            animation: zoomVideo 20s infinite alternate ease-in-out;
        }

        @keyframes zoomVideo {
            from {
                transform: scale(1);
            }

            to {
                transform: scale(1.1);
            }
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            height: 100%;
            position: relative;
            z-index: 1;
        }

        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 0 20px;
            transition: transform 0.5s ease;
        }

        .logo-container img {
            height: 135px;
            margin-bottom: 5px;
            filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.5));
            transition: transform 0.5s ease, filter 0.5s ease;
        }

        .logo-container img:hover {
            transform: scale(1.1) rotate(-3deg);
            filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.8));
        }

        .menu-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .menu-links a,
        .menu-links button {
            text-decoration: none;
            color: white;
            font-size: 1.1em;
            padding: 12px 20px;
            border-radius: 100px;
            background-color: rgba(31, 41, 58, 0.7);
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-weight: bold;
            white-space: nowrap;
            position: relative;
            overflow: hidden;
        }

        .menu-links a:hover,
        .menu-links button:hover {
            background-color: #e60029;
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(230, 0, 41, 0.6);
        }

        .menu-links a:active,
        .menu-links button:active {
            transform: scale(0.95);
        }

        .menu-links a::after,
        .menu-links button::after {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: all 0.5s;
        }

        .menu-links a:hover::after,
        .menu-links button:hover::after {
            left: 100%;
        }

        /* Estilo para el enlace activo */
        .menu-links a.active,
        .menu-links a.active:hover {
            background-color: #195183;
            /* Color solicitado */
            transform: scale(1);
            box-shadow: none;
        }

        main {
            flex-grow: 1;
            width: 100%;
            padding: 20px;
            max-width: 1200px;
            box-sizing: border-box;
            text-align: center;
            animation: fadeIn 1.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s, visibility 0s linear 0.5s;
        }

        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
            transition: opacity 0.5s;
        }

        .loading-overlay img {
            width: 150px;
            height: auto;
        }
    </style>
</head>

<body>
    <div id="loading-spinner" class="loading-overlay">
        <img src="<?= htmlspecialchars($loadingGifURL) ?>" alt="Cargando...">
    </div>

    <header>
        <video autoplay muted loop class="header-video">
            <source src="<?= htmlspecialchars($videoURL) ?>" type="video/mp4">
            Tu navegador no soporta el video.
        </video>
        <div class="header-content">
            <div class="menu-links menu-left">
                <a href="<?= $baseURL ?>php/usuario/boletos_comprados.php"
                    class="<?= isActive('boletos_comprados.php', $current_page); ?>">Mis Boletos</a>
                <a href="<?= $baseURL ?>php/usuario/ultimos_eventos.php"
                    class="<?= isActive('ultimos_eventos.php', $current_page); ?>">Eventos Pasados</a>
                <a href="<?= $baseURL ?>php/usuario/menu.php" 
                    class="<?= isActive('menu.php', $current_page); ?>">Cartelera</a>
            </div>
            <div class="logo-container">
                <img src="<?= htmlspecialchars($logoURL) ?>" alt="Logo de la empresa">
                <?php if (isset($_SESSION['nombre'])): ?>
                    <p style="font-size: 1.1em;color:white; font-weight:bold; margin-top:8px;">
                        <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="menu-links menu-right">
                <a href="<?= $baseURL ?>php/usuario/perfil.php" 
                    class="<?= isActive('perfil.php', $current_page); ?>">Mi cuenta</a>
                <a href="<?= $baseURL ?>php/usuario/regalos.php"
                    class="<?= isActive('regalos.php', $current_page); ?>">Regalos</a>
                <form method="post" style="display:inline;">
                    <button name="logout">Cerrar Sesión</button>
                </form>
            </div>
        </div>
    </header>
    <main>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const loadingOverlay = document.getElementById('loading-spinner');
                const menuLinks = document.querySelectorAll('.menu-links a, .menu-links button');

                menuLinks.forEach(link => {
                    link.addEventListener('click', (event) => {
                        // Evita que el formulario de logout active el spinner y lo mantenga
                        if (link.closest('form') && link.name === 'logout') {
                            return;
                        }

                        loadingOverlay.classList.add('active');
                    });
                });

                // Oculta el spinner si el usuario navega hacia atrás o la página carga rápido
                window.addEventListener('pageshow', (event) => {
                    if (event.persisted) {
                        loadingOverlay.classList.remove('active');
                    }
                });
            });
        </script>