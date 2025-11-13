<?php
// C:\xampp\htdocs\IP_Eventos\php\usuario\ultimos_eventos.php

session_start();

// Incluye el archivo de conexión a la base de datos
require_once '../../conexion.php'; 

// Verifica si la sesión del usuario está activa
if (!isset($_SESSION['id'])) {
    header("Location: ../../sesion/login.php");
    exit;
}

$usr_id = $_SESSION['id'];

// Incluye el encabezado y el pie de página de la plantilla
include 'plantilla/header.php';

// Consulta mejorada para obtener eventos únicos a los que el usuario asistió
$query = "
SELECT
    G.gal_url,
    G.gal_des,
    G.gal_fec_sub,
    G.gal_id_evt,
    E.evt_tit,
    E.evt_fec
FROM
    tbl_galeria G
INNER JOIN
    tbl_evento E ON G.gal_id_evt = E.evt_id
WHERE
    G.gal_id_evt IN (
        SELECT DISTINCT C.com_id_evt
        FROM tbl_compras_boletos C
        INNER JOIN tbl_evento EV ON C.com_id_evt = EV.evt_id
        WHERE
            C.com_id_usr = $1
            AND C.com_act = TRUE
            AND EV.evt_est = 'finalizado'
    )
ORDER BY
    E.evt_fec DESC, G.gal_fec_sub DESC;
";

// Prepara y ejecuta la consulta de forma segura
$result = pg_query_params($conn, $query, array($usr_id));

if (!$result) {
    die("Error al consultar la galería de eventos asistidos: " . pg_last_error($conn));
}

// Almacenar las imágenes agrupadas por evento
$eventos = [];
while ($row = pg_fetch_assoc($result)) {
    $evt_id = $row['gal_id_evt'];
    $evt_tit = htmlspecialchars($row['evt_tit'] ?? 'Sin Título');
    $evt_fec = date('d/m/Y', strtotime($row['evt_fec']));

    if (!isset($eventos[$evt_id])) {
        $eventos[$evt_id] = [
            'titulo' => $evt_tit,
            'fecha_evento' => $evt_fec,
            'imagenes' => []
        ];
    }
    
    $eventos[$evt_id]['imagenes'][] = [
        'url' => htmlspecialchars($row['gal_url']),
        'descripcion' => htmlspecialchars($row['gal_des']),
        'fecha_subida' => date('d/m/Y', strtotime($row['gal_fec_sub']))
    ];
}
?>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.0.0/dist/jsQR.min.js"></script>

<style>
    /* VARIABLES */
    :root {
        --primary-color: #F94144;
        --secondary-color: #0078c7;
        --dark-bg: #0e172b;
        --card-bg: #1A2234;
        --text-color: #F8F9FA;
        --light-text: #B0B0B0;
    }

    body {
        background-color: var(--dark-bg);
        color: var(--text-color);
        font-family: 'Poppins', sans-serif;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .container-gallery {
        max-width: 1400px;
        margin: auto;
        padding: 20px;
        flex-grow: 1;
        animation: fadeIn 1s ease-out;
    }

    .titulo-seccion {
        text-align: center;
        color: var(--secondary-color);
        margin-bottom: 30px;
        font-weight: 700;
        animation: fadeDown 1s ease;
    }

    /* Estilo del filtro de fecha */
    .filter-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .filter-container label {
        color: var(--light-text);
        white-space: nowrap;
    }

    .filter-container input[type="date"] {
        background-color: var(--card-bg);
        color: var(--text-color);
        border: 1px solid var(--light-text);
        padding: 8px;
        border-radius: 5px;
        font-family: 'Poppins', sans-serif;
    }

    .filter-container input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
    }

    .reset-btn {
        background-color: transparent;
        color: var(--light-text);
        border: 1px solid var(--light-text);
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .reset-btn:hover {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: var(--text-color);
    }
    
    /* Contenedor de la grilla */
    .event-sections-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 30px;
        justify-items: center;
    }

    /* Estilo de la tarjeta de evento */
    .event-section {
        display: none; /* Oculto por defecto para la paginación */
        flex-direction: column;
        background: var(--card-bg);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        animation: fadeIn 0.8s ease-out;
    }

    .event-section.active {
        display: flex;
    }

    .event-section:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5);
    }

    .event-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--primary-color);
        text-align: center;
        padding: 15px;
        margin: 0;
    }
    
    .event-date {
        font-size: 0.9rem;
        font-weight: 400;
        color: var(--light-text);
        text-align: center;
        margin-top: -10px;
        margin-bottom: 10px;
    }

    .gallery-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        padding: 8px;
        background-color: rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(5px);
        border-radius: 8px;
        overflow: hidden;
        position: relative;
    }

    .gallery-item {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        overflow: hidden;
        position: relative;
        cursor: pointer;
        transition: transform 0.3s ease;
        height: 150px;
    }
    
    .gallery-item:hover {
        transform: scale(1.05);
    }

    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    
    .image-date {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background-color: rgba(0, 0, 0, 0.6);
        color: white;
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 0.7rem;
    }

    .controls-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        padding: 15px;
    }
    
    .nav-btn, .expand-btn {
        background-color: #e03a3d;
        color: var(--text-color);
        border: none;
        padding: 8px 12px;
        font-size: 1em;
        cursor: pointer;
        border-radius: 5px;
        transition: background-color 0.3s;
        font-weight: 600;
        text-transform: uppercase;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .nav-btn:hover, .expand-btn:hover {
        background-color: var(--primary-color);
    }
    
    .nav-btn:disabled {
        background-color: #6b292aff;
        cursor: not-allowed;
    }

    /* Mensajes amigables */
    .friendly-message {
        text-align: center;
        padding: 2rem;
        border-radius: 15px;
        background-color: rgba(255, 255, 255, 0.05);
        margin: 20px auto;
        font-size: 1.2rem;
        color: var(--light-text);
        display: none;
    }
    .friendly-message.active {
        display: block;
    }

    .friendly-message strong {
        color: var(--primary-color);
    }

    /* Paginación */
    .pagination-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .page-btn, .all-events-btn {
        background-color: var(--card-bg);
        color: var(--text-color);
        border: 1px solid var(--light-text);
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s, border-color 0.3s, color 0.3s;
    }

    .page-btn.active, .page-btn:hover {
        background-color: #f24043;
        border-color: var(--secondary-color);
    }
    
    .all-events-btn:hover {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    /* Modal Styling */
    .modal {
        display: none;
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        justify-content: center;
        align-items: center;
        z-index: 1050;
        animation: fadeIn 0.3s ease;
    }
    
    .modal-content {
        position: relative;
        max-width: 90%;
        max-height: 90%;
        background-color: var(--card-bg);
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.7);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        animation: scaleUp 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
    }
    
    .modal-content img {
        max-width: 100%;
        max-height: 70vh;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.7);
        object-fit: contain;
    }

    .modal-content p {
        margin-top: 15px;
        text-align: center;
        font-size: 1em;
        color: var(--light-text);
        padding: 10px;
        background-color: rgba(0, 0, 0, 0.2);
        border-radius: 10px;
    }

    .close {
        color: var(--light-text);
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 28px;
        font-weight: bold;
        transition: color 0.2s;
        cursor: pointer;
        z-index: 10;
    }
    
    .close:hover, .close:focus {
        color: var(--primary-color);
    }

    .modal-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-top: 20px;
    }

    .download-btn {
        background-color: var(--primary-color);
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 1em;
        cursor: pointer;
        border-radius: 50px;
        transition: background-color 0.3s, transform 0.3s;
        margin-top: 15px;
    }

    .download-btn:hover {
        background-color: #e03a3d;
        transform: scale(1.05);
    }
    
    .download-all-btn {
        background-color: var(--secondary-color);
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 1em;
        cursor: pointer;
        border-radius: 50px;
        transition: background-color 0.3s, transform 0.3s;
        margin-top: 10px;
    }

    .download-all-btn:hover {
        background-color: #00609b;
        transform: scale(1.05);
    }

    /* Animaciones */
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes fadeDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes scaleUp { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    /* Media queries */
    @media (max-width: 1200px) {
        .modal-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    @media (max-width: 900px) {
        .event-sections-grid {
            grid-template-columns: 1fr;
        }
        
        .gallery-container {
            grid-template-columns: 1fr;
        }
        
        .modal-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 600px) {
        .modal-grid {
            grid-template-columns: 1fr;
        }
        .filter-container {
            flex-direction: column;
        }
    }
</style>

<div class="container-gallery">
    <h2 class="titulo-seccion">Tus Recuerdos del Evento 📸</h2>
    
    <div class="filter-container">
        <label for="date-filter">Filtrar por fecha de evento:</label>
        <input type="date" id="date-filter">
        <button id="reset-btn" class="reset-btn">Limpiar filtro</button>
    </div>

    <div id="loading-message" class="friendly-message active">
        <i class="fas fa-spinner fa-spin"></i> Estamos buscando tus mejores recuerdos. ¡Un momento, por favor!
    </div>
    
    <div id="no-events-message" class="friendly-message">
        ¡Vaya! Parece que aún no hay recuerdos para mostrar. 🤔
        <p>Cuando la galería del evento esté disponible, la encontrarás aquí. ¡Esperamos que lo hayas pasado genial!</p>
    </div>

    <div id="no-filter-results" class="friendly-message">
        ¡Oh, vaya! No encontramos fotos para esta fecha. 😢
        <p>Intenta con otra fecha o <a href="#" id="clear-filter-link" style="color: var(--secondary-color);">limpia el filtro</a> para ver todos tus recuerdos de nuevo.</p>
    </div>
    
    <div id="event-gallery-grid" class="event-sections-grid" style="display: none;">
        <?php foreach ($eventos as $evento_id => $evento): ?>
            <div class="event-section" data-event-date="<?= date('Y-m-d', strtotime(str_replace('/', '-', $evento['fecha_evento']))) ?>" data-images='<?= json_encode($evento['imagenes']) ?>'>
                <h3 class="event-title"><?= $evento['titulo'] ?></h3>
                <p class="event-date">Evento: <?= $evento['fecha_evento'] ?></p>
                
                <div class="gallery-container">
                    <?php 
                    $count = 0;
                    foreach ($evento['imagenes'] as $imagen): 
                        if ($count >= 2) break;
                    ?>
                        <div class="gallery-item" data-src="../../<?= $imagen['url'] ?>" data-desc="<?= $imagen['descripcion'] ?>" data-date="<?= $imagen['fecha_subida'] ?>">
                            <img src="../../<?= $imagen['url'] ?>" alt="<?= $imagen['descripcion'] ?>">
                            <span class="image-date"><?= $imagen['fecha_subida'] ?></span>
                        </div>
                    <?php 
                    $count++;
                    endforeach; ?>
                </div>
                
                <div class="controls-container">
                    <?php if (count($evento['imagenes']) > 2): ?>
                        <button class="nav-btn prev"><i class="fas fa-arrow-left"></i></button>
                        <button class="nav-btn next"><i class="fas fa-arrow-right"></i></button>
                    <?php endif; ?>
                    <button class="expand-btn">Ver todas</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="pagination-container" class="pagination-container">
        </div>
</div>

<div id="imageModal" class="modal">
    <div class="modal-content">
        <span class="close-image-modal close">&times;</span>
        <img id="modalImage" src="" alt="">
        <p id="modalDescription"></p>
        <button id="downloadBtn" class="download-btn"><i class="fas fa-download"></i> Descargar Imagen</button>
    </div>
</div>

<div id="fullGalleryModal" class="modal">
    <div class="modal-content">
        <span class="close-gallery-modal close">&times;</span>
        <h3 id="fullGalleryTitle" class="titulo-seccion"></h3>
        <button id="downloadAllBtn" class="download-all-btn"><i class="fas fa-download"></i> Descargar todas las fotos</button>
        <div id="modalGalleryGrid" class="modal-grid">
        </div>
    </div>
</div>

<?php
include 'plantilla/footer.php';
?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const eventGalleryGrid = document.getElementById('event-gallery-grid');
        const eventSections = document.querySelectorAll('.event-section');
        const loadingMessage = document.getElementById('loading-message');
        const noEventsMessage = document.getElementById('no-events-message');
        const noFilterResults = document.getElementById('no-filter-results');
        const dateFilter = document.getElementById('date-filter');
        const resetBtn = document.getElementById('reset-btn');
        const paginationContainer = document.getElementById('pagination-container');

        const imageModal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const modalDescription = document.getElementById('modalDescription');
        const downloadBtn = document.getElementById('downloadBtn');
        const closeImageModal = document.querySelector('.close-image-modal');
        
        const fullGalleryModal = document.getElementById('fullGalleryModal');
        const fullGalleryTitle = document.getElementById('fullGalleryTitle');
        const modalGalleryGrid = document.getElementById('modalGalleryGrid');
        const downloadAllBtn = document.getElementById('downloadAllBtn');
        const closeFullGalleryModal = document.querySelector('.close-gallery-modal');

        let isFullGalleryModalOpen = false;
        let currentPage = 1;
        const eventsPerPage = 3;

        const eventosData = <?php echo json_encode(array_values($eventos)); ?>;

        loadingMessage.style.display = 'none';
        if (eventosData.length > 0) {
            eventGalleryGrid.style.display = 'grid';
            setupPagination();
        } else {
            noEventsMessage.style.display = 'block';
        }

        function showPage(page) {
            eventSections.forEach((section, index) => {
                const start = (page - 1) * eventsPerPage;
                const end = start + eventsPerPage;
                if (index >= start && index < end) {
                    section.style.display = 'flex';
                } else {
                    section.style.display = 'none';
                }
            });
            updatePaginationButtons();
        }

        function setupPagination() {
            paginationContainer.innerHTML = '';
            const totalPages = Math.ceil(eventSections.length / eventsPerPage);

            for (let i = 1; i <= totalPages; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.classList.add('page-btn');
                if (i === currentPage) {
                    pageBtn.classList.add('active');
                }
                pageBtn.addEventListener('click', () => {
                    currentPage = i;
                    showPage(currentPage);
                });
                paginationContainer.appendChild(pageBtn);
            }

            if (eventSections.length > eventsPerPage) {
                const viewAllBtn = document.createElement('button');
                viewAllBtn.textContent = 'Ver todos';
                viewAllBtn.classList.add('all-events-btn');
                viewAllBtn.addEventListener('click', toggleViewAll);
                paginationContainer.appendChild(viewAllBtn);
            }

            showPage(currentPage);
        }

        function updatePaginationButtons() {
            paginationContainer.querySelectorAll('.page-btn').forEach(btn => {
                btn.classList.remove('active');
                if (parseInt(btn.textContent) === currentPage) {
                    btn.classList.add('active');
                }
            });
        }

        function toggleViewAll() {
            const viewAllBtn = paginationContainer.querySelector('.all-events-btn');
            if (viewAllBtn.textContent === 'Ver todos') {
                eventSections.forEach(section => section.style.display = 'flex');
                paginationContainer.querySelectorAll('.page-btn').forEach(btn => btn.style.display = 'none');
                viewAllBtn.textContent = 'Ocultar todos';
            } else {
                showPage(currentPage);
                paginationContainer.querySelectorAll('.page-btn').forEach(btn => btn.style.display = 'inline-block');
                viewAllBtn.textContent = 'Ver todos';
            }
        }
        
        function filterEventsByDate() {
            const selectedDate = dateFilter.value;
            let foundResults = false;
            
            eventSections.forEach(section => {
                const eventDate = section.getAttribute('data-event-date');
                if (selectedDate === '' || eventDate === selectedDate) {
                    section.style.display = 'flex';
                    foundResults = true;
                } else {
                    section.style.display = 'none';
                }
            });

            if (!foundResults && selectedDate !== '') {
                noFilterResults.style.display = 'block';
                eventGalleryGrid.style.display = 'none';
            } else {
                noFilterResults.style.display = 'none';
                if (eventosData.length > 0) {
                    eventGalleryGrid.style.display = 'grid';
                }
            }
            paginationContainer.style.display = selectedDate === '' ? 'flex' : 'none';
        }

        dateFilter.addEventListener('change', filterEventsByDate);
        resetBtn.addEventListener('click', () => {
            dateFilter.value = '';
            filterEventsByDate();
            setupPagination();
        });
        document.getElementById('clear-filter-link').addEventListener('click', (e) => {
            e.preventDefault();
            dateFilter.value = '';
            filterEventsByDate();
            setupPagination();
        });

        closeImageModal.addEventListener('click', () => {
            imageModal.style.display = 'none';
            if (isFullGalleryModalOpen) {
                fullGalleryModal.style.display = 'flex';
            }
        });

        imageModal.addEventListener('click', (event) => {
            if (event.target === imageModal) {
                imageModal.style.display = 'none';
                if (isFullGalleryModalOpen) {
                    fullGalleryModal.style.display = 'flex';
                }
            }
        });

        closeFullGalleryModal.addEventListener('click', () => {
            fullGalleryModal.style.display = 'none';
            isFullGalleryModalOpen = false;
        });

        fullGalleryModal.addEventListener('click', (event) => {
            if (event.target === fullGalleryModal) {
                fullGalleryModal.style.display = 'none';
                isFullGalleryModalOpen = false;
            }
        });

        downloadBtn.addEventListener('click', () => {
            const imageUrl = modalImage.src;
            const a = document.createElement('a');
            a.href = imageUrl;
            a.download = `foto_${imageUrl.split('/').pop()}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });

        downloadAllBtn.addEventListener('click', () => {
            const currentImagesData = JSON.parse(fullGalleryModal.getAttribute('data-images'));
            currentImagesData.forEach((img, index) => {
                setTimeout(() => {
                    const a = document.createElement('a');
                    a.href = `../../${img.url}`;
                    a.download = `imagen_${index + 1}_${img.url.split('/').pop()}`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }, 500 * index); 
            });
        });

        eventSections.forEach(section => {
            const images = JSON.parse(section.getAttribute('data-images'));
            const container = section.querySelector('.gallery-container');
            const prevBtn = section.querySelector('.nav-btn.prev');
            const nextBtn = section.querySelector('.nav-btn.next');
            const expandBtn = section.querySelector('.expand-btn');
            
            let currentIndex = 0;

            function showItems() {
                container.innerHTML = '';
                for (let i = currentIndex; i < currentIndex + 2 && i < images.length; i++) {
                    const imgData = images[i];
                    const itemDiv = document.createElement('div');
                    itemDiv.classList.add('gallery-item');
                    itemDiv.dataset.src = `../../${imgData.url}`;
                    itemDiv.dataset.desc = imgData.descripcion;
                    itemDiv.innerHTML = `<img src="../../${imgData.url}" alt="${imgData.descripcion}">
                                        <span class="image-date">${imgData.fecha_subida}</span>`;
                    
                    itemDiv.addEventListener('click', function() {
                        openSingleImageModal(this);
                    });
                    
                    container.appendChild(itemDiv);
                }
                
                if (prevBtn) prevBtn.disabled = currentIndex === 0;
                if (nextBtn) nextBtn.disabled = currentIndex >= images.length - 2;
            }
            
            if (images.length > 2) {
                showItems();
                if (prevBtn) prevBtn.style.display = 'inline-block';
                if (nextBtn) nextBtn.style.display = 'inline-block';
                if (expandBtn) expandBtn.style.display = 'inline-block';
            } else {
                container.innerHTML = '';
                images.forEach(imgData => {
                    const itemDiv = document.createElement('div');
                    itemDiv.classList.add('gallery-item');
                    itemDiv.dataset.src = `../../${imgData.url}`;
                    itemDiv.dataset.desc = imgData.descripcion;
                    itemDiv.innerHTML = `<img src="../../${imgData.url}" alt="${imgData.descripcion}">
                                        <span class="image-date">${imgData.fecha_subida}</span>`;
                    
                    itemDiv.addEventListener('click', function() {
                        openSingleImageModal(this);
                    });
                    container.appendChild(itemDiv);
                });
                
                if (prevBtn) prevBtn.style.display = 'none';
                if (nextBtn) nextBtn.style.display = 'none';
                if (expandBtn) expandBtn.style.display = 'none';
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    if (currentIndex > 0) {
                        currentIndex--;
                        showItems();
                    }
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    if (currentIndex < images.length - 2) {
                        currentIndex++;
                        showItems();
                    }
                });
            }

            function openSingleImageModal(element) {
                const src = element.getAttribute('data-src');
                const desc = element.getAttribute('data-desc');
                modalImage.src = src;
                modalDescription.textContent = desc;
                imageModal.style.display = 'flex';
                isFullGalleryModalOpen = fullGalleryModal.style.display === 'flex';
            }

            if (expandBtn) {
                expandBtn.addEventListener('click', () => {
                    const eventTitle = section.querySelector('.event-title').textContent;
                    const imageData = JSON.parse(section.getAttribute('data-images'));
                    
                    fullGalleryTitle.textContent = eventTitle;
                    modalGalleryGrid.innerHTML = '';
                    fullGalleryModal.setAttribute('data-images', JSON.stringify(imageData));
                    
                    imageData.forEach(img => {
                        const itemDiv = document.createElement('div');
                        itemDiv.classList.add('gallery-item');
                        itemDiv.dataset.src = `../../${img.url}`;
                        itemDiv.dataset.desc = img.descripcion;
                        itemDiv.innerHTML = `<img src="../../${img.url}" alt="${img.descripcion}">
                                            <span class="image-date">${img.fecha_subida}</span>`;
                        
                        itemDiv.addEventListener('click', () => {
                            modalImage.src = `../../${img.url}`;
                            modalDescription.textContent = img.descripcion;
                            fullGalleryModal.style.display = 'none'; 
                            imageModal.style.display = 'flex';
                            isFullGalleryModalOpen = true; 
                        });
                        modalGalleryGrid.appendChild(itemDiv);
                    });
                    
                    fullGalleryModal.style.display = 'flex';
                });
            }
        });
    });
</script>