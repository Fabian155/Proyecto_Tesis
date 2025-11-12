<?php
include '../plantilla/plantilla_info/head.php';
include '../../conexion.php';

$baseURL = include __DIR__ . '/../../ruta_Api.php';


// Consulta a la tabla de información "Nosotros"
// ¡Asegúrate de que esta consulta funcione correctamente con tu DB!
$query = "SELECT * FROM tbl_nosotros WHERE nos_est = 'activo' ORDER BY nos_id DESC LIMIT 1";
$result = pg_query($conn, $query);

if (!$result || pg_num_rows($result) === 0) {
    die("<p style='color:white; text-align:center;'>No se encontró información activa en la tabla tbl_nosotros.</p>");
}

$nos = pg_fetch_assoc($result);

// Procesar rutas completas de imágenes
$img_mis = $baseURL . $nos['nos_img_mis'];
$img_vis = $baseURL . $nos['nos_img_vis'];
$img_hist = $baseURL . $nos['nos_img_hist'];
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #333; /* Color de texto más legible */
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        background-color: #f4f4f9; /* Fondo claro para contraste */
    }

    .content-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 60px 20px;
        background-color: #dddfe2ff; /* Fondo oscuro elegante */
        color: white; /* Texto blanco para el fondo oscuro */
    }

    .section-title {
        font-size: 2.5em;
        margin-bottom: 5px;
        text-align: center;
        color: #e60029; /* Color de acento (rojo) */
        font-weight: 700;
    }

    .line-divider {
        width: 100px;
        height: 4px;
        background-color: #0176c7; /* Color de acento (azul) */
        margin: 20px auto 40px auto;
        border-radius: 2px;
    }

    /* Nuevo contenedor para las tarjetas (Flexbox/Grid) */
    .cards-grid-container {
        display: flex;
        flex-wrap: wrap; /* Permite que las tarjetas se envuelvan */
        justify-content: center;
        gap: 30px;
        max-width: 1200px;
        width: 100%;
        margin-top: 20px;
    }

    /* Estilo de la tarjeta individual (Profesional y limpio) */
    .card {
        background-color: #ffffff; /* Fondo blanco para las tarjetas */
        color: #333;
        border-radius: 12px;
        width: calc(33.33% - 30px); /* 3 tarjetas por fila, restando el gap */
        min-width: 280px; /* Tamaño mínimo para asegurar legibilidad */
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    .card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    }

    .card-image-wrapper {
        overflow: hidden;
        height: 200px; /* Altura fija para la imagen */
    }
    
    .card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .card:hover img {
        transform: scale(1.05);
    }

    .card-content {
        padding: 25px;
        flex-grow: 1; /* Para que el contenido crezca y mantenga el mismo tamaño */
        display: flex;
        flex-direction: column;
    }

    .card-content h3 {
        margin: 0 0 15px 0;
        font-size: 1.6em;
        text-align: left;
        color: #0176c7; /* Color de acento (azul) */
        border-bottom: 2px solid #e60029; /* Línea de acento debajo del título */
        padding-bottom: 5px;
    }

    .card-content p {
        margin: 0;
        font-size: 1em;
        line-height: 1.6;
        text-align: justify;
        color: #555;
    }

    /* Media Queries para responsividad */
    @media (max-width: 992px) {
        .card {
            width: calc(50% - 30px); /* 2 tarjetas por fila en tabletas */
        }
    }

    @media (max-width: 600px) {
        .card {
            width: 100%; /* 1 tarjeta por fila en móviles */
        }

        .content-wrapper {
            padding: 40px 10px;
        }

        .section-title {
            font-size: 2em;
        }
    }
</style>

<div class="content-wrapper">
    <h2 class="section-title">Nuestra Esencia</h2>
    <div class="line-divider"></div>

    <div class="cards-grid-container">

        <?php if (!empty($nos['nos_mis'])): ?>
        <div class="card">
            <div class="card-image-wrapper">
                <img src="<?= $img_mis ?>" alt="Misión">
            </div>
            <div class="card-content">
                <h3>Misión 🎯</h3>
                <p><?= htmlspecialchars($nos['nos_mis']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($nos['nos_vis'])): ?>
        <div class="card">
            <div class="card-image-wrapper">
                <img src="<?= $img_vis ?>" alt="Visión">
            </div>
            <div class="card-content">
                <h3>Visión 💡</h3>
                <p><?= htmlspecialchars($nos['nos_vis']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($nos['nos_hist'])): ?>
        <div class="card">
            <div class="card-image-wrapper">
                <img src="<?= $img_hist ?>" alt="Historia">
            </div>
            <div class="card-content">
                <h3>Historia 📜</h3>
                <p><?= htmlspecialchars($nos['nos_hist']) ?></p>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <div class="line-divider" style="margin-top: 40px;"></div>
</div>

<?php include '../plantilla/plantilla_info/footer.php'; ?>