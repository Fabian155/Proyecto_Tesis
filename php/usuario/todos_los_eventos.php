<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'usuario') {
    header("Location: ../../sesion/login.php");
    exit;
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../../sesion/login.php");
    exit;
}

// Incluir el encabezado
include 'plantilla/header.php';
?>
<style>
    /* Estilos específicos para el contenido de esta página */
    :root {
        --primary-color: #F94144;
        --secondary-color: #F3722C;
        --dark-bg: #0e172b;
        --card-bg: #2C2C2C;
        --text-color: #0078c7; /* Color principal de texto, ajustado a azul */
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

    main {
        flex-grow: 1;
        width: 100%;
        padding: 20px;
        max-width: 1200px;
        box-sizing: border-box;
        text-align: center;
        animation: fadeIn 1.5s ease;
        margin-top: 20px;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* Estilos de la cuadrícula de eventos */
    .eventos-grid-container {
        width: 100%;
        padding: 20px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 25px;
        justify-items: center;
    }

    h1 {
        font-size: 2.5em;
        margin-bottom: 20px;
        color: var(--text-color);
        text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.7);
        text-align: center;
        width: 100%;
    }

    /* Tarjeta de Evento */
    .evento-card {
        background: var(--card-bg);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        text-align: left;
        cursor: pointer;
        width: 100%;
    }

    .evento-card:hover {
        transform: translateY(-8px) scale(1.03);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.6);
    }

    .evento-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-bottom: 2px solid var(--primary-color);
    }

    .evento-info {
        padding: 12px;
    }

    .evento-info h4 {
        margin: 0 0 5px;
        font-size: 1.2rem;
        color: var(--primary-color);
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
    }
    
    .evento-info p {
        font-size: 0.8rem;
        color: var(--light-text);
        line-height: 1.3;
        margin-bottom: 12px;
    }

    .btn-ver-detalles {
        display: block;
        width: 100%;
        padding: 8px 0;
        background-color: var(--primary-color);
        color: white;
        text-align: center;
        border: none;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
        text-decoration: none;
    }

    .btn-ver-detalles:hover {
        background-color: var(--secondary-color);
        transform: translateY(-2px);
    }

    /* Media Queries para dispositivos más pequeños */
    @media (max-width: 768px) {
        .eventos-grid-container {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }
    }
</style>
<main>
    <h1>Todos los Eventos Activos</h1>
    <section class="eventos-grid-container">
        <?php
        // Incluye la conexión a la base de datos
        include '../../conexion.php';

        // Obtiene todos los eventos activos
        $query = "SELECT evt_id, evt_tit, evt_img, evt_des FROM tbl_evento WHERE evt_est = 'activo' ORDER BY evt_fec DESC";
        $result = pg_query($conn, $query);
        $eventos = pg_fetch_all($result);
        
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
                echo "<a href='compra_usuario.php?id={$id}' class='btn-ver-detalles'>Compra tus boletos</a>";
                echo "</div>";
                echo "</article>";
            }
        } else {
            echo "<p style='text-align: center; grid-column: 1 / -1;'>¡Ups! Parece que no hay eventos en este momento. Vuelve pronto.</p>";
        }
        ?>
    </section>
</main>
<?php
// Incluir el pie de página
include 'plantilla/footer.php';
?>