<?php
// Incluye la conexión a la base de datos
include '../../conexion.php';

// Consulta para obtener los eventos activos
$query = "SELECT evt_id, evt_tit, evt_img, evt_des FROM tbl_evento WHERE evt_est = 'activo' ORDER BY evt_fec DESC";
$result = pg_query($conn, $query);
$eventos = pg_fetch_all($result);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartelera de Eventos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* VARIABLES */
        :root {
            --primary-color: #fa4647;
            --secondary-color: #0078c7;
            --dark-bg: #1A1A1A;
            --card-bg: #2C2C2C;
            --text-color: #F8F9FA;
            --light-text: #B0B0B0;
        }

        /* Estilos generales y fondo */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0e172b;
            color: var(--text-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-x: hidden;
        }

        /* Header con video */
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

        /* Contenedor principal */
        .cartelera-container {
            width: 100%;
            max-width: 1100px;
            padding: 20px;
            margin: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            text-align: center;
        }

        /* Títulos y descripciones */
        .titulo-principal {
            font-size: 2em;
            color: var(--secondary-color);
            letter-spacing: 1px;
            animation: fadeDown 1s ease;
        }

        .descripcion-texto {
            font-size: 0.95rem;
            color: var(--light-text);
            line-height: 1.4;
            max-width: 800px;
            margin: auto;
            animation: fadeUp 1s ease;
        }

        /* Carrusel de eventos */
        .carrusel-eventos {
            width: 100%;
            animation: fadeUp 1s ease;
        }

        .carrusel-container {
            position: relative;
            overflow: hidden;
            padding: 5px 0;
        }

        .eventos-list {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding-bottom: 5px;
            scrollbar-width: none;
        }

        .eventos-list::-webkit-scrollbar {
            display: none;
        }

        /* Tarjetas de eventos */
        .evento-card {
            flex: 0 0 calc(28% - 12px);
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .evento-card:hover {
            transform: translateY(-6px) scale(1.03);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.4);
        }

        .evento-card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-bottom: 2px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .evento-card:hover img {
            transform: scale(1.05);
        }

        .evento-info {
            padding: 10px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .evento-info h4 {
            font-size: 1.1rem;
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 6px;
        }

        .evento-info p {
            font-size: 0.8rem;
            color: var(--light-text);
            line-height: 1.3;
            margin-bottom: 10px;
            flex-grow: 1;
            text-align: left;
        }

        /* Estilos de botones */
        .common-btn-style {
            text-decoration: none;
            color: white;
            padding: 12px 20px;
            border-radius: 100px;
            background-color: var(--primary-color);
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-weight: bold;
            white-space: nowrap;
            position: relative;
            overflow: hidden;
        }

        .common-btn-style:hover {
            background-color: var(--secondary-color);
            transform: scale(1.1);
            box-shadow: 0 0 15px var(--secondary-color);
        }

        .common-btn-style:active {
            transform: scale(0.95);
        }

        .common-btn-style::after {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: all 0.5s;
        }

        .common-btn-style:hover::after {
            left: 100%;
        }

        .btn-ver-detalles,
        .boton-ver-mas,
        .carrusel-btn {
            display: inline-block;
            text-align: center;
            width: auto;
            padding: 10px 18px;
            font-size: 0.85em;
            margin-top: auto;
        }

        .btn-ver-detalles {
            padding: 8px 15px;
        }

        /* Navegación del carrusel */
        .carrusel-botones {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            margin-top: 15px;
            gap: 10px;
            animation: fadeUp 1s ease;
        }

        .carrusel-navegacion {
            display: flex;
            gap: 8px;
        }

        /* Animaciones */
        @keyframes fadeDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Estilos para la vista de todos los eventos */
        #todos-eventos-section {
            display: none;
            /* Oculta por defecto */
            margin-top: 50px;
            width: 100%;
        }

        #todos-eventos-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        /* Oculta el carrusel y los botones de navegación en la vista de todos los eventos */
        #carrusel-section.hidden,
        #carrusel-botones-section.hidden {
            display: none;
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
            <div class="logo-container">
                <img src="../imagenes/logoempresa.png" alt="Logo de la empresa">
            </div>
            <div class="menu-links">
                <a href="../../index.php">Inicio</a>
                <a href="../../sesion/login.php">Iniciar Sesión</a>
            </div>
        </div>
    </header>

    <main class="cartelera-container">
        <h1 class="titulo-principal">¡Descubre los Mejores Eventos!</h1>
        <p class="descripcion-texto">
            Te invitamos a sumergirte en experiencias inolvidables. Explora nuestra cartelera y encuentra tu próximo
            evento favorito.
        </p>

        <div class="carrusel-eventos" id="carrusel-section">
            <div class="carrusel-container">
                <section class="eventos-list" id="eventos-carrusel">
                    <?php
                    if ($eventos) {
                        // Limita los eventos a 5 para el carrusel
                        $eventos_carrusel = array_slice($eventos, 0, 5);
                        foreach ($eventos_carrusel as $evento) {
                            $id = htmlspecialchars($evento['evt_id']);
                            $titulo = htmlspecialchars($evento['evt_tit']);
                            $imagen = htmlspecialchars($evento['evt_img']);
                            $descripcion_corta = substr(htmlspecialchars($evento['evt_des']), 0, 70) . '...';
                            echo "<article class='evento-card'>";
                            echo "<a href='compra_visita.php?id={$id}' class='evento-link' style='text-decoration: none;'>";
                            echo "<img src='../../{$imagen}' alt='{$titulo}'>";
                            echo "<div class='evento-info'>";
                            echo "<h4>{$titulo}</h4>";
                            echo "<p>{$descripcion_corta}</p>";
                            echo "<a href='compra_visita.php?id={$id}' class='btn-ver-detalles common-btn-style'>Comprar  entradas</a>";
                            echo "</div>";
                            echo "</a>";
                            echo "</article>";
                        }
                    } else {
                        echo "<p style='text-align: center; color: var(--light-text);'>¡Ups! Parece que no hay eventos en este momento. Vuelve pronto.</p>";
                    }
                    ?>
                </section>
            </div>
        </div>

        <div class="carrusel-botones" id="carrusel-botones-section">
            <button class="boton-ver-mas common-btn-style" onclick="toggleVistaCompleta()">Ver todos los
                eventos</button>
            <div class="carrusel-navegacion">
                <button class="carrusel-btn common-btn-style" onclick="scrollCarrusel(-1)">&#9664;</button>
                <button class="carrusel-btn common-btn-style" onclick="scrollCarrusel(1)">&#9654;</button>
            </div>
        </div>

        <section id="todos-eventos-section">
            <h2 class="titulo-principal">Todos los Eventos</h2>
            <div id="todos-eventos-list">
                <?php
                if ($eventos) {
                    foreach ($eventos as $evento) {
                        $id = htmlspecialchars($evento['evt_id']);
                        $titulo = htmlspecialchars($evento['evt_tit']);
                        $imagen = htmlspecialchars($evento['evt_img']);
                        $descripcion_corta = substr(htmlspecialchars($evento['evt_des']), 0, 150) . '...';
                        echo "<article class='evento-card'>";
                        echo "<a href='compra_visita.php?id={$id}' class='evento-link' style='text-decoration: none;'>";
                        echo "<img src='../../{$imagen}' alt='{$titulo}'>";
                        echo "<div class='evento-info'>";
                        echo "<h4>{$titulo}</h4>";
                        echo "<p>{$descripcion_corta}</p>";
                        echo "<a href='compra_visita.php?id={$id}' class='btn-ver-detalles common-btn-style'>Ver </a>";
                        echo "</div>";
                        echo "</a>";
                        echo "</article>";
                    }
                }
                ?>
            </div>
        </section>
    </main>
    <?php
    include 'plantilla/footer.php';
    ?>

    <script>
        const carrusel = document.getElementById('eventos-carrusel');
        const carruselSection = document.getElementById('carrusel-section');
        const carruselBotonesSection = document.getElementById('carrusel-botones-section');
        const todosEventosSection = document.getElementById('todos-eventos-section');

        function scrollCarrusel(direction) {
            // Se corrigió el cálculo del desplazamiento
            const scrollAmount = carrusel.querySelector('.evento-card').offsetWidth * 1.5;
            carrusel.scrollBy({
                left: direction * scrollAmount,
                behavior: 'smooth'
            });
        }

        function toggleVistaCompleta() {
            if (carruselSection.style.display !== 'none') {
                // Ocultar carrusel y mostrar todos los eventos
                carruselSection.style.display = 'none';
                carruselBotonesSection.querySelector('.carrusel-navegacion').style.display = 'none';
                carruselBotonesSection.querySelector('.boton-ver-mas').textContent = 'Volver al carrusel';

                todosEventosSection.style.display = 'block';
            } else {
                // Ocultar todos los eventos y mostrar carrusel
                carruselSection.style.display = 'block';
                carruselBotonesSection.querySelector('.carrusel-navegacion').style.display = 'flex';
                carruselBotonesSection.querySelector('.boton-ver-mas').textContent = 'Ver todos los eventos';

                todosEventosSection.style.display = 'none';
            }
        }
    </script>
</body>

</html>