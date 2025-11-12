<?php
session_start();

// Incluye el encabezado y la conexión a la base de datos
include 'plantilla/header.php';
include '../../conexion.php';

// Consulta para obtener los eventos activos
$query = "SELECT evt_id, evt_tit, evt_img, evt_des FROM tbl_evento WHERE evt_est = 'activo' ORDER BY evt_fec DESC";
$result = pg_query($conn, $query);
$eventos = pg_fetch_all($result);
?>
<style>
    /* VARIABLES */
    :root {
        --primary-color: #F94144;
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
        justify-content: space-between;
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

    /* Responsivo */
    @media (max-width: 1024px) {
        .evento-card {
            flex: 0 0 calc(40% - 12px);
        }
    }

    @media (max-width: 768px) {
        .evento-card {
            flex: 0 0 calc(75% - 12px);
        }

        .carrusel-botones {
            flex-direction: column;
        }

        .evento-desc-btn {
            flex-direction: column;
            align-items: stretch;
        }

        .evento-desc-btn p {
            margin-bottom: 10px;
        }
    }
</style>

<main>
    <div class="cartelera-container">
        <h1 class="titulo-principal">¡Descubre los Mejores Eventos!</h1>
        <p class="descripcion-texto">
            Te invitamos a sumergirte en experiencias inolvidables. Explora nuestra cartelera y encuentra tu próximo evento favorito.
        </p>
        <div class="carrusel-eventos">
            <div class="carrusel-container">
                <section class="eventos-list" id="eventos-carrusel">
                    <?php
                    // Muestra los eventos si la consulta fue exitosa
                    if ($eventos) {
                        foreach ($eventos as $evento) {
                            $id = htmlspecialchars($evento['evt_id']);
                            $titulo = htmlspecialchars($evento['evt_tit']);
                            $imagen = htmlspecialchars($evento['evt_img']);
                            $descripcion_corta = substr(htmlspecialchars($evento['evt_des']), 0, 70) . '...';
                            echo "<article class='evento-card' onclick=\"window.location.href='compra_usuario.php?id={$id}'\">";
                            echo "<img src='../../{$imagen}' alt='{$titulo}'>";
                            echo "<div class='evento-info'>";
                            echo "<h4>{$titulo}</h4>";
                            echo "<p>{$descripcion_corta}</p>";
                            echo "<a href='compra_usuario.php?id={$id}' class='btn-ver-detalles common-btn-style'>Compra tus boletos</a>";
                            echo "</div>";
                            echo "</article>";
                        }
                    } else {
                        echo "<p style='text-align: center; color: var(--light-text);'>¡Ups! Parece que no hay eventos en este momento. Vuelve pronto.</p>";
                    }
                    ?>
                </section>
            </div>
        </div>
        <div class="carrusel-botones">
            <a href="todos_los_eventos.php" class="boton-ver-mas common-btn-style">Ver todos los eventos</a>
            <div class="carrusel-navegacion">
                <button class="carrusel-btn common-btn-style" onclick="scrollCarrusel(-1)">&#9664;</button>
                <button class="carrusel-btn common-btn-style" onclick="scrollCarrusel(1)">&#9654;</button>
            </div>
        </div>
    </div>
</main>

<script>
    function scrollCarrusel(direction) {
        const carrusel = document.getElementById('eventos-carrusel');
        // Asegura que hay tarjetas para calcular el desplazamiento
        if (carrusel.querySelector('.evento-card')) {
            const scrollAmount = carrusel.querySelector('.evento-card').offsetWidth * 3;
            carrusel.scrollBy({
                left: direction * scrollAmount,
                behavior: 'smooth'
            });
        }
    }
</script>

<?php
include 'plantilla/footer.php';
?>