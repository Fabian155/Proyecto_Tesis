<?php
// ¡ATENCIÓN!: Asegúrate de que no haya NINGÚN espacio, línea o caracter antes de esta etiqueta <?php

// ⚠️ RUTA CORREGIDA: Desde la raíz (index.php) a la plantilla
include 'php/plantilla/plantilla_info/head.php';

// ⚠️ RUTA CORREGIDA: Desde la raíz (index.php) a la conexión
require_once 'conexion.php';


$baseURL = include __DIR__ . '/ruta_Api.php';


// --- 1. Obtener información de la empresa (MANTENIDO) ---
$query = "SELECT nos_nom_emp, nos_men_ini, nos_link_app, nos_url_img_android, nos_img_url_ios, nos_url_app_ios 
          FROM tbl_nosotros WHERE nos_est = 'activo' ORDER BY nos_id DESC LIMIT 1";
$result = pg_query($conn, $query);

if ($result && pg_num_rows($result) > 0) {
    $nos = pg_fetch_assoc($result);
    // 🔥 CORRECCIÓN ANTERIOR: Usar Operador de Coalescencia Nula (?? '') para evitar error "Passing null is deprecated"
    $nombreEmpresa = htmlspecialchars($nos['nos_nom_emp'] ?? '');
    $mensajeInicio = nl2br(htmlspecialchars($nos['nos_men_ini'] ?? ''));
    $linkAppAndroid = htmlspecialchars($nos['nos_link_app'] ?? '');
    $imgAndroidPath = htmlspecialchars($nos['nos_url_img_android'] ?? '');
    $imgIosPath = htmlspecialchars($nos['nos_img_url_ios'] ?? '');
    $linkAppIos = htmlspecialchars($nos['nos_url_app_ios'] ?? '');
} else {
    // Valores por defecto 
    $nombreEmpresa = "Vive Cultura";
    $mensajeInicio = "Descubre experiencias únicas que combinan cine, cultura y tradiciones.";
    $linkAppAndroid = "https://play.google.com/store/apps/details?id=com.ironproductions.vivecultura";
    $imgAndroidPath = 'assets/img/google-play-badge.png';
    $imgIosPath = 'assets/img/app-store-badge.png';
    $linkAppIos = 'https://apps.apple.com/us/app/vive-cultura/id1234567890';
}


// ----------------------------------------------------
// --- 2. LÓGICA DE GALERÍA DE EVENTOS (MANTENIDO) ---
// ----------------------------------------------------

$galeria_query = "SELECT G.gal_url, G.gal_des, G.gal_id_evt, E.evt_tit
              FROM tbl_galeria G
              LEFT JOIN tbl_evento E ON G.gal_id_evt = E.evt_id
              ORDER BY E.evt_fec DESC, G.gal_fec_sub DESC";
$galeria_result = pg_query($conn, $galeria_query);

if (!$galeria_result) {
    die("Error al consultar la galería: " . pg_last_error($conn));
}

// Agrupar imágenes por evento
$eventos_galeria = [];
while ($row = pg_fetch_assoc($galeria_result)) {
    $evt_id = $row['gal_id_evt'];
    $evt_id_key = $evt_id ?? 0; 
    $evt_tit = htmlspecialchars($row['evt_tit'] ?? 'Galería General');
    
    if (!isset($eventos_galeria[$evt_id_key])) {
        $eventos_galeria[$evt_id_key] = [
            'titulo' => $evt_tit,
            'imagenes' => []
        ];
    }
    $eventos_galeria[$evt_id_key]['imagenes'][] = [
        'url' => htmlspecialchars($row['gal_url']),
        'descripcion' => htmlspecialchars($row['gal_des'])
    ];
}

// ----------------------------------------------------
// --- FIN LÓGICA DE GALERÍA ---
// ----------------------------------------------------


// --- 3. Obtener información de los eventos activos (MANTENIDO) ---
// Se limita a 4 eventos para la vista previa en el index
$eventos_query = "SELECT evt_id, evt_tit, evt_img, evt_des, evt_fec, evt_lug FROM tbl_evento WHERE evt_est = 'activo' ORDER BY evt_fec ASC LIMIT 4";
$eventos_result = pg_query($conn, $eventos_query);

if ($eventos_result === false) {
    die("Error en la consulta de eventos: " . pg_last_error($conn));
}

$eventos = pg_fetch_all($eventos_result);
if ($eventos === false) {
    $eventos = [];
}

// --- FIN Obtener información de los eventos activos ---
?>


<style>
    /* --- AJUSTES DE ESTRUCTURA PARA LAYOUT SOLICITADO (Programación/Galería | Cartelera abajo) --- */
    .page-layout {
        display: flex;
        flex-direction: column; /* Apila el contenido verticalmente */
        align-items: center;
        padding: 20px;
        color: white;
        background-color: #f1f1f1ff;
    }

    .main-content-area {
        display: flex;
        flex-grow: 1;
        max-width: 1400px;
        width: 100%;
        gap: 30px; 
        margin-bottom: 30px; /* Espacio entre el contenido principal y la cartelera */
    }

    .content-wrapper {
        /* Columna izquierda (Programación y Bienvenida) */
        flex: 1; 
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 30px;
        padding: 0;
    }

    .galeria-sidebar {
        /* Columna derecha (Galería) - ¡Nuevo nombre para reflejar el cambio! */
        width: 450px; /* Un poco más ancho para la galería */
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .cartelera-section {
        /* Sección Cartelera (Abajo) */
        width: 100%;
        max-width: 1400px;
    }

    /* Estilos de la versión anterior (mantenidos) */
    .text-box {
        background: rgba(0, 0, 0, 0.7);
        padding: 30px;
        border-radius: 10px;
        max-width: 100%;
        margin-bottom: 0;
        text-align: center;
    }

    .text-box h2 {
        color: #00bfff;
        margin-bottom: 15px;
    }

    a.link {
        color: #00bfff;
        text-decoration: none;
        font-weight: bold;
    }

    a.link:hover {
        text-decoration: underline;
    }
    
    .app-links-container {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 20px;
        margin-bottom: 30px;
    }

    .app-link-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: white;
        transition: transform 0.3s;
    }

    .app-link-item:hover {
        transform: translateY(-5px);
    }

    /* 🔥 CORRECCIÓN SOLICITADA: Dimensiones uniformes para Android e iOS */
    .app-link-item img {
        width: 120px; 
        height: 40px; /* Altura fija para uniformidad (ajusta según tus imágenes) */
        object-fit: contain; /* Asegura que la imagen se ajuste sin distorsionarse */
        border-radius: 5px;
        border: 2px solid #444;
        transition: border-color 0.3s;
    }
    /* 🔥 CONTENEDOR ESPECÍFICO PARA LAS IMÁGENES DE APP */
    .app-badge-wrapper {
        width: 120px;
        height: 40px; 
        overflow: hidden;
        border-radius: 5px;
        border: 2px solid #444;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .app-badge-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: contain; 
        border: none; /* Quitamos el borde duplicado */
        border-radius: 0;
    }


    .app-link-item:hover .app-badge-wrapper {
        border-color: #00bfff;
    }
    .app-link-item:hover img {
        border-color: #00bfff;
    }

    .app-link-item span {
        font-size: 0.9em;
        color: #00bfff;
        font-weight: bold;
        margin-top: 5px;
    }
    
    .section-title {
        font-size: 2em;
        text-align: center;
        margin-bottom: 10px;
    }

    .line-divider {
        width: 80%;
        height: 2px;
        background-color: rgba(255,255,255,0.4);
        margin: 20px auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 800px; 
    }

    .line-divider::before, .line-divider::after {
        content: '';
        width: 12px; height: 12px;
        background-color: white;
        border-radius: 50%;
    }

    .event-sections-grid {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 40px;
        width: 100%;
        margin-bottom: 0;
    }

    .event-section {
        background-color: rgba(0,0,0,0.5);
        border-radius: 15px;
        backdrop-filter: blur(5px);
        padding: 15px;
        width: 100%; 
        min-width: 280px;
        display: flex;
        flex-direction: column;
        align-items: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        overflow: hidden; 
    }

    .event-title {
        font-size: 1.4em;
        text-align: center;
        margin-bottom: 10px;
        color: #0176c7;
    }

    .carousel-viewport {
        width: 100%;
        height: 200px; 
        overflow: hidden;
        border-radius: 10px;
        position: relative;
        box-shadow: 0 0 10px rgba(0,0,0,0.3);
    }

    .gallery-container {
        width: 100%;
        height: 100%;
        position: relative;
    }

    .gallery-item {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 1s ease-in-out; 
        cursor: pointer;
    }

    .gallery-item.active {
        opacity: 1;
        z-index: 1; 
    }

    .gallery-image-wrapper {
        width: 100%;
        height: 100%;
        border-radius: 10px;
        overflow: hidden;
        transition: transform 0.3s ease;
    }

    .gallery-image-wrapper:hover {
        transform: scale(1.03);
    }

    .gallery-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .controls-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
        width: 100%;
    }

    .expand-btn {
        background-color: #0176c7;
        color: white;
        border: none;
        padding: 8px 12px;
        font-size: 1em;
        cursor: pointer;
        border-radius: 5px;
        text-decoration: none; 
        transition: background-color 0.3s;
        display: inline-block; 
    }

    .expand-btn:hover {
        background-color: #e60029;
    }

    .carousel-dots {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin: 5px 0;
    }

    .dot {
        width: 10px;
        height: 10px;
        background-color: rgba(255, 255, 255, 0.5);
        border-radius: 50%;
        cursor: pointer;
        transition: background-color 0.3s, transform 0.3s;
    }

    .dot.active {
        background-color: #e60029; 
        transform: scale(1.2);
    }

    .modal {
        display: none;
        position: fixed;
        left: 0; top: 0;
        width: 100%; height: 100%;
        background-color: rgba(0,0,0,0.95);
        justify-content: center;
        align-items: center;
        flex-direction: column;
        z-index: 9999;
        animation: modal-open 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
    }

    @keyframes modal-open {
        from { opacity: 0; transform: scale(0.8); }
        to { opacity: 1; transform: scale(1); }
    }

    .modal-content {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .modal-content img {
        max-width: 85%;
        max-height: 75vh;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(255,255,255,0.3);
    }
    .modal p {
        margin-top: 15px;
        text-align: center;
        font-size: 1.1em;
        max-width: 85%;
    }
    .close {
        position: absolute;
        top: 15px;
        right: 35px;
        color: #fff;
        font-size: 35px;
        cursor: pointer;
        z-index: 10002; 
    }
    .close:hover {
        color: #e60029;
    }
    .modal-carousel-container {
        margin-top: 20px;
        width: 90%;
        max-width: 800px;
        overflow-x: auto;
        white-space: nowrap;
        padding: 10px 0;
        border-radius: 10px;
        background-color: rgba(0, 0, 0, 0.4);
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: flex-start;
        scroll-behavior: smooth;
    }

    .modal-thumbnail {
        display: inline-block;
        width: 80px;
        height: 80px;
        margin: 0 5px;
        cursor: pointer;
        border: 3px solid transparent;
        border-radius: 8px;
        transition: all 0.3s ease;
        overflow: hidden;
        flex-shrink: 0;
    }

    .modal-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 4px;
        vertical-align: middle;
    }

    .modal-thumbnail.active {
        border-color: #e60029;
    }

    @media (max-width:900px) {
        /* En pantallas pequeñas, se adapta a una sola columna */
        .main-content-area {
            flex-direction: column;
        }
        .galeria-sidebar {
            width: 100%; /* Ocupa todo el ancho */
            max-width: initial;
            order: 1; /* Mantiene la galería arriba en el móvil */
        }
        .content-wrapper {
            order: 2; /* Mueve la bienvenida abajo en el móvil */
        }
        .event-section { width: 90%; }
        .modal-content img { max-width: 95%; max-height: 60vh; }
        .modal-carousel-container { width: 95%; }
    }

    @media (max-width:600px) {
        .modal-thumbnail { width: 60px; height: 60px; }
        .app-links-container { gap: 15px; }
        .app-badge-wrapper { width: 100px; height: 35px; } /* Ajuste de tamaño en móvil */
        .app-link-item img { width: 100%; height: 100%; } 
    }

    /* CARTELERA (Sección Inferior) */
    .cartelera-container {
        width: 100%;
        background-color: #2c2c2c;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
    }
    .cartelera-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    .cartelera-header h2 {
        font-size: 1.8em; /* Un poco más grande al ser la sección principal de abajo */
        color: #f8f9fa;
        text-align: center;
        width: 100%;
        margin: 0;
    }
    .cartelera-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* 2 a 4 columnas */
        gap: 20px;
        margin-top: 20px;
    }
    
    /* Ocultamos elementos innecesarios */
    .cartelera-header > i, .cartelera-header > div, .calendar {
        display: none !important; 
    }
    
    .event-card {
        background: #3a3a3a;
        border-radius: 8px;
        padding: 15px;
        display: flex;
        flex-direction: column; /* Cambiado a columna para que la imagen ocupe la parte superior */
        gap: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        transition: transform 0.3s;
    }
    .event-card:hover {
        transform: translateY(-5px);
    }
    .event-card-link-wrapper {
        text-decoration: none; 
        color: inherit;
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex-grow: 1;
    }
    .event-card-img {
        width: 100%;
        height: 150px; /* Altura fija para la imagen del evento */
        border-radius: 4px;
        object-fit: cover;
    }
    .event-card-info {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: flex-start;
    }
    .event-card-info p {
        margin: 0;
        font-size: 0.85em;
        color: #bbb;
    }
    .event-card-title {
        font-size: 1.2em;
        font-weight: bold;
        color: #00bfff;
        margin-bottom: 5px !important;
        line-height: 1.2;
    }
    .event-card-time {
        font-size: 0.9em;
        color: #f8f9fa;
        font-weight: 500;
        display: flex;
        align-items: center;
        margin-bottom: 5px !important;
    }
    .event-card-time i {
        margin-right: 5px;
        color: #f8f9fa;
    }
    .event-card-actions {
        margin-top: 10px;
        width: 100%;
        text-align: right;
    }
    .event-card-room-btn {
        background: #e60029;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.9em;
        font-weight: bold;
        transition: background 0.3s;
        display: inline-block;
    }
    .event-card-room-btn:hover {
        background: #ff3355;
    }
</style>

<div class="page-layout">
    <div class="main-content-area">
        
        <div class="content-wrapper">
            
            <div class="text-box">
                <h2>¡Bienvenido a <?= $nombreEmpresa ?>!</h2>
                <p><?= $mensajeInicio ?></p>
                <p>
                    Revisa nuestra <a href="php/info/menu_visita.php" class="link">cartelera</a> y compra tus entradas,
                    o descarga nuestra aplicación:
                </p>
                
                <div class="app-links-container">
                    <a href="<?= $linkAppAndroid ?>" target="_blank" class="app-link-item">
                        <div class="app-badge-wrapper">
                             <img src="<?= $baseURL . $imgAndroidPath ?>" alt="Google Play Store">
                        </div>
                        <span>Android</span>
                    </a>
                    
                    <a href="<?= $linkAppIos ?>" target="_blank" class="app-link-item">
                        <div class="app-badge-wrapper">
                             <img src="<?= $baseURL . $imgIosPath ?>" alt="App Store">
                        </div>
                        <span>iOS</span>
                    </a>
                </div>
            </div>
            
            </div>
        
        <div class="galeria-sidebar">
            <h2 class="section-title" style="margin-top:0;">Galería de Eventos</h2>
            
            <div class="event-sections-grid">
                <?php 
                // ⚠️ Se mantiene la lógica de mostrar solo el primer bloque de galería
                $galeria_mostrada = false;
                foreach ($eventos_galeria as $evento_id => $evento):  
                    
                    if ($galeria_mostrada) {
                        break;
                    }
                    
                    $images = $evento['imagenes'];
                    if (empty($images)) continue;
                    
                    $num_images = count($images);
                    $num_slides = $num_images;  
                ?>
                    <div class="event-section" data-event-id="<?= $evento_id ?>" data-images='<?= json_encode($images) ?>'>
                        <h3 class="event-title"><?= $evento['titulo'] ?></h3>

                        <div class="carousel-viewport">
                            <div class="gallery-container">
                                <?php 
                                // Iterar sobre CADA imagen
                                foreach ($images as $slide_index => $imagen): 
                                ?>
                                    <div class="gallery-item" data-slide-index="<?= $slide_index ?>">
                                        <div class="gallery-image-wrapper" data-src="<?= $baseURL . $imagen['url'] ?>" data-desc="<?= $imagen['descripcion'] ?>" data-global-index="<?= $slide_index ?>">
                                            <img src="<?= $baseURL . $imagen['url'] ?>" alt="<?= $imagen['descripcion'] ?>">
                                    </div>
                                </div>
                        <?php endforeach; ?>
                        </div>
                        </div>

                        <?php if ($num_images > 0): ?>
                        <div class="controls-container">
                            <?php if ($num_images > 1): ?>
                            <div class="carousel-dots" data-slides="<?= $num_slides ?>">
                                <?php for ($i = 0; $i < $num_slides; $i++): ?>
                                    <span class="dot" data-slide-to="<?= $i ?>"></span>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                            
                            <a href="<?= $baseURL ?>php/info/galeria.php" class="expand-btn">Ver más</a>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php 
                $galeria_mostrada = true;
                endforeach; 
                ?>
            </div>
        </div>
    </div>
    
    <div class="cartelera-section">
        <div class="cartelera-container">
            <div class="cartelera-header">
                <h2>Cartelera de Eventos</h2>
            </div>
            
            <div class="cartelera-grid">
                <?php if (!empty($eventos)): ?>
                    <?php foreach ($eventos as $evento): 
                        $id = htmlspecialchars($evento['evt_id']);
                        $titulo = htmlspecialchars($evento['evt_tit']);
                        $imagen = empty($evento['evt_img']) ? 'path/to/default/image.jpg' : htmlspecialchars($evento['evt_img']);
                        
                        $fecha_obj = new DateTime($evento['evt_fec']);
                        
                        // 🔥 CORRECCIÓN PARA PHP 8.1+: Reemplazando setlocale() y strftime() con IntlDateFormatter
                        
                        // 1. Obtener Horario (format() ya es moderno)
                        $horario = $fecha_obj->format('H:i');

                        // 2. Formatear Día de la Semana con IntlDateFormatter
                        // Se necesita la extensión 'intl' habilitada en el servidor.
                        if (class_exists('IntlDateFormatter')) {
                            $formatterDia = new IntlDateFormatter(
                                'es_ES',
                                IntlDateFormatter::FULL, // Usamos FULL para obtener el nombre completo (ej: Lunes)
                                IntlDateFormatter::NONE, 
                                'America/Guayaquil', // Zona horaria (Ajusta si es necesario, si no, usa null)
                                IntlDateFormatter::GREGORIAN,
                                'EEEE' // Patrón para el día de la semana completo
                            );
                            $dia_semana_largo = $formatterDia->format($fecha_obj);
                            
                            // 3. Formatear Fecha Completa con IntlDateFormatter
                            $formatterFecha = new IntlDateFormatter(
                                'es_ES',
                                IntlDateFormatter::FULL, 
                                IntlDateFormatter::NONE, 
                                'America/Guayaquil', // Zona horaria (Ajusta si es necesario)
                                IntlDateFormatter::GREGORIAN,
                                'd \'de\' MMMM' // Patrón para '11 de Noviembre'
                            );
                            $fecha_completa = $formatterFecha->format($fecha_obj);

                        } else {
                            // Fallback (Manteniendo el código anterior DEPRECADO, pero minimizando el uso)
                            setlocale(LC_TIME, 'es_ES.utf8', 'es_ES', 'es'); 
                            $dia_semana_largo = strftime('%A', $fecha_obj->getTimestamp()); 
                            $fecha_completa = strftime('%e de %B', $fecha_obj->getTimestamp()); 
                        }
                        
                        $tipo_simulado = "Entradas"; 
                    ?>
                    <div class="event-card">
                        <a href="php/info/compra_visita.php?id=<?= $id ?>" class="event-card-link-wrapper">
                            <img src="<?= $baseURL . $imagen ?>" alt="<?= $titulo ?>" class="event-card-img">
                            <div class="event-card-info">
                                <p class="event-card-time"><i class="far fa-clock"></i><?= $horario ?> | <?= ucfirst($dia_semana_largo) ?></p>
                                <p class="event-card-title"><?= $titulo ?></p>
                                <p style="font-size: 0.9em; color: #fff;"><?= $fecha_completa ?></p>
                                <p style="font-size: 0.7em; color: #ff9900; margin-top: 5px !important;"><?= $tipo_simulado ?></p>
                            </div>
                        </a>
                        <div class="event-card-actions">
                            <a href="php/info/compra_visita.php?id=<?= $id ?>" class="event-card-room-btn">
                                <i class="fas fa-ticket-alt"></i> Comprar
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #aaa; width: 100%; margin-top: 20px;">No hay eventos programados para mostrar.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<div id="imageModal" class="modal">
    <span class="close">&times;</span>
    <div class="modal-content">
        <img id="modalImage" src="" alt="">
        <p id="modalDescription"></p>
    </div>
    <div id="modalCarousel" class="modal-carousel-container">
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // === REFERENCIAS A LOS MODALES ===
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const modalDescription = document.getElementById('modalDescription');
    const modalCarousel = document.getElementById('modalCarousel');

    let currentEventImages = []; 
    const intervalIDs = {}; 

    const baseURL = '<?= $baseURL ?>'; 


    // === FUNCIONES DE CONTROL DE MODAL ===
    const openImageModal = (index, images) => {
        const imgArray = images || currentEventImages;

        if (imgArray.length === 0 || index < 0 || index >= imgArray.length) {
            console.error("Índice o array de imágenes no válido.");
            return;
        }

        currentEventImages = imgArray;
        
        const img = imgArray[index];
        const src = baseURL + img.url; 
        const desc = img.descripcion || '';

        modalImage.src = src;
        modalImage.setAttribute('data-current-index', index);
        modalDescription.textContent = desc;

        // Construir carrusel de miniaturas
        modalCarousel.innerHTML = '';
        if (imgArray.length > 1) { 
            imgArray.forEach((item, i) => {
                const thumbnail = document.createElement('div');
                thumbnail.className = 'modal-thumbnail' + (i === index ? ' active' : '');
                thumbnail.innerHTML = `<img src="${baseURL + item.url}" alt="${item.descripcion || ''}">`; 
                
                thumbnail.addEventListener('click', () => {
                    openImageModal(i); 
                });

                modalCarousel.appendChild(thumbnail);
            });
            modalCarousel.style.display = 'flex';
            
            // Desplazar el carrusel para centrar la activa
            const activeThumbnail = modalCarousel.querySelector('.modal-thumbnail.active');
            if (activeThumbnail) {
                modalCarousel.scrollLeft = activeThumbnail.offsetLeft - (modalCarousel.clientWidth / 2) + (activeThumbnail.clientWidth / 2);
            }
        } else {
            modalCarousel.style.display = 'none';
        }

        imageModal.style.display = 'flex';
        imageModal.style.zIndex = '10001'; 
    };

    const closeImageModal = () => {
        imageModal.style.display = 'none';
        modalCarousel.innerHTML = '';
        currentEventImages = [];
    };

    // === CIERRES DE MODAL ===
    imageModal.querySelector('.close').addEventListener('click', closeImageModal);

    imageModal.addEventListener('click', e => { 
        if (e.target === imageModal || e.target === imageModal.querySelector('.close')) {
            closeImageModal(); 
        }
    });

    // === LÓGICA DEL CARRUSEL DE PREVIEW (FADE/AUTOPLAY) ===
    document.querySelectorAll('.event-section').forEach(section => {
        const eventId = section.getAttribute('data-event-id');
        const dotsContainer = section.querySelector('.carousel-dots');
        const slides = section.querySelectorAll('.gallery-item');
        
        let currentSlide = 0;
        const totalSlides = slides.length;

        if (totalSlides === 0) return;
        
        const showSlide = (index) => {
            if (index < 0) {
                currentSlide = totalSlides - 1;
            } else if (index >= totalSlides) {
                currentSlide = 0;
            } else {
                currentSlide = index;
            }

            slides.forEach((slide, i) => {
                slide.classList.remove('active');
            });
            slides[currentSlide].classList.add('active');
            
            if (dotsContainer) {
                section.querySelectorAll('.dot').forEach((dot, i) => {
                    dot.classList.remove('active');
                    if (i === currentSlide) {
                        dot.classList.add('active');
                    }
                });
            }
        };

        // 1. Inicializar
        showSlide(0);

        // 2. Configurar eventos para las burbujas
        if (dotsContainer) {
            dotsContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('dot')) {
                    const slideIndex = parseInt(e.target.getAttribute('data-slide-to'));
                    clearInterval(intervalIDs[eventId]);
                    showSlide(slideIndex);
                    // Reiniciar el autoplay
                    intervalIDs[eventId] = setInterval(() => showSlide(currentSlide + 1), 3000);
                }
            });
        }
        
        // 3. Configurar clics para abrir el modal individual
        section.querySelectorAll('.gallery-image-wrapper').forEach((imgWrapper) => {
            imgWrapper.addEventListener('click', () => {
                const images = JSON.parse(section.getAttribute('data-images'));
                const globalIndex = parseInt(imgWrapper.getAttribute('data-global-index'));
                
                clearInterval(intervalIDs[eventId]); 
                
                openImageModal(globalIndex, images); 
            });
        });

        // 4. Autoplay (Solo si hay más de 1 imagen)
        if (totalSlides > 1) {
             intervalIDs[eventId] = setInterval(() => {
                showSlide(currentSlide + 1);
            }, 3000); 
        }
    });
});
</script>


<?php include 'php/plantilla/plantilla_info/footer.php'; ?>