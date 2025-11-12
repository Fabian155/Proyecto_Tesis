<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'usuario') {
    header("Location: ../../sesion/login.php");
    exit;
}

// Lógica de cierre de sesión
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../../sesion/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartelera de Eventos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #F94144;
            --secondary-color: #F3722C;
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

        /* Encabezado */
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
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Video de fondo */
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
            from { transform: scale(1); }
            to { transform: scale(1.1); }
        }
        
        /* Contenido del encabezado */
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            height: 100%;
            position: relative;
            z-index: 1;
        }

        /* Logo */
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
            filter: drop-shadow(0 0 8px rgba(255,255,255,0.5));
            transition: transform 0.5s ease, filter 0.5s ease;
        }

        .logo-container img:hover {
            transform: scale(1.1) rotate(-3deg);
            filter: drop-shadow(0 0 15px rgba(255,255,255,0.8));
        }

        /* Menús */
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

        /* Efecto al pasar el mouse */
        .menu-links a:hover,
        .menu-links button:hover {
            background-color: #e60029;
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(230,0,41,0.6);
        }

        .menu-links a:active,
        .menu-links button:active {
            transform: scale(0.95);
        }

        /* Pequeño brillo animado en los botones */
        .menu-links a::after,
        .menu-links button::after {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.5s;
        }

        .menu-links a:hover::after,
        .menu-links button:hover::after {
            left: 100%;
        }

        /* Pie de página */
        footer {
            width: 100%;
            padding: 20px 50px;
            box-sizing: border-box;
            background-color: transparent;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            animation: fadeInDown 1s ease;
        }
        
        /* Contenido del pie de página */
        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .footer-content h2 {
            font-size: 2em;
            color: var(--text-color);
            margin-bottom: 20px;
            text-shadow: 0 0 10px rgba(255,255,255,0.5);
        }

        .sponsors-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
            padding: 20px 0;
        }

        .sponsor-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            color: var(--light-text);
            font-weight: bold;
            transition: transform 0.3s ease, color 0.3s ease;
        }

        .sponsor-item:hover {
            transform: translateY(-10px);
            color: var(--text-color);
        }

        .sponsor-item img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            border-radius: 10px;
            filter: grayscale(100%);
            transition: filter 0.5s ease;
        }

        .sponsor-item img:hover {
            filter: grayscale(0%);
        }
    </style>
</head>
<body>
    <header>
        <video autoplay muted loop class="header-video">
            <source src="../imagenes/video/fondoinfo.mp4" type="video/mp4">
            Tu navegador no soporta el video.
        </video>
        <div class="header-content">
            <div class="menu-links menu-left">
                <a href="boletos_comprados.php">Mis Boletos</a>
                <a href="boletos_comprados.php">Últimos eventos que fui</a>
            </div>
            <div class="logo-container">
                <img src="../imagenes/logoempresa.png" alt="Logo de la empresa">
            </div>
            <div class="menu-links menu-right">
                <a href="regalos.php">Regalos</a>
                <form method="post" style="display:inline;">
                    <button name="logout">Cerrar Sesión</button>
                </form>
            </div>
        </div>
    </header>

    <main style="padding: 20px; text-align: center;">
        <h1>Contenido de la página</h1>
        <p>Este es el cuerpo principal donde se mostrará la cartelera de eventos.</p>
    </main>

    <footer>
        <video autoplay muted loop class="header-video">
            <source src="../imagenes/video/fondoinfo.mp4" type="video/mp4">
            Tu navegador no soporta el video.
        </video>
        <div class="footer-content">
            <h2>Nuestros Patrocinadores</h2>
            <div class="sponsors-container">
                <div class="sponsor-item">
                    <img src="https://orquideatech.com/wp-content/uploads/2021/07/nws-julio1-26.png"
                        alt="Logo de Empresa A">
                    <p>Empresa A</p>
                </div>
                <div class="sponsor-item">
                    <img src="https://orquideatech.com/wp-content/uploads/2021/07/nws-julio1-26.png"
                        alt="Logo de Comercio B">
                    <p>Comercio B</p>
                </div>
                <div class="sponsor-item">
                    <img src="https://orquideatech.com/wp-content/uploads/2021/07/nws-julio1-26.png"
                        alt="Logo de Tienda C">
                    <p>Tienda C</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>