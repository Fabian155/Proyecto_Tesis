<?php
include '../plantilla/plantilla_info/head.php';
require_once '../../conexion.php';

// =========================
// CONSULTA DE GALERÍA
// =========================
$query = "SELECT G.gal_url, G.gal_des, G.gal_id_evt, E.evt_tit
          FROM tbl_galeria G
          LEFT JOIN tbl_evento E ON G.gal_id_evt = E.evt_id
          ORDER BY E.evt_fec DESC, G.gal_fec_sub DESC";
$result = pg_query($conn, $query);

if (!$result) {
    die("Error al consultar la galería: " . pg_last_error($conn));
}

// Agrupar imágenes por evento
$eventos = [];
while ($row = pg_fetch_assoc($result)) {
    $evt_id = $row['gal_id_evt'];
    $evt_tit = htmlspecialchars($row['evt_tit'] ?? 'Sin Evento');
    if (!isset($eventos[$evt_id])) {
        $eventos[$evt_id] = [
            'titulo' => $evt_tit,
            'imagenes' => []
        ];
    }
    $eventos[$evt_id]['imagenes'][] = [
        'url' => htmlspecialchars($row['gal_url']),
        'descripcion' => htmlspecialchars($row['gal_des'])
    ];
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    color: white;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}

/* ---------------------------------- */
/* Estilos Generales */
/* ---------------------------------- */
.content-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 20px;
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
    max-width: 1300px;
}

.event-section {
    background-color: rgba(0,0,0,0.5);
    border-radius: 15px;
    backdrop-filter: blur(5px);
    padding: 15px;
    width: calc(50% - 20px);
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

/* ---------------------------------- */
/* 🏃‍♂️ Estilos del Carrusel Infinito (Marquee) */
/* ---------------------------------- */

/* Contenedor del Carrusel (Viewport) - Oculta el contenido que se desplaza */
.carousel-viewport {
    width: 100%;
    height: 200px; /* Altura fija para las miniaturas */
    overflow: hidden;
    border-radius: 10px;
    position: relative;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
}

/* Contenedor de la Pista Animada (el que se mueve) */
.carousel-track-infinite {
    display: flex;
    width: max-content; /* Permite que el contenido se extienda */
    animation: scroll-infinite 20s linear infinite; /* Animación de desplazamiento: 20s de duración, lineal, infinito */
}

/* Opcional: Pausar la animación al pasar el mouse */
.carousel-viewport:hover .carousel-track-infinite {
    animation-play-state: paused;
}

/* Contenido de Carrusel (Un set de imágenes) */
.carousel-content-infinite {
    display: flex;
    flex-shrink: 0; /* No permite que el bloque se encoja */
    gap: 10px; 
    padding-right: 10px; /* Espacio entre el último y el primer elemento al hacer loop */
}

/* 🖼️ Item del Carrusel (Miniatura) */
.gallery-item-infinite {
    width: 200px; /* Ancho fijo para cada miniatura */
    height: 200px;
    flex-shrink: 0;
    cursor: pointer;
    overflow: hidden;
    border-radius: 10px;
    box-shadow: 0 0 5px rgba(0,0,0,0.5);
    transition: transform 0.3s ease;
}

.gallery-item-infinite:hover {
    transform: scale(1.03); /* Efecto de zoom sutil al pasar el mouse */
}

.gallery-item-infinite img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* === KEYFRAMES para el movimiento de bucle infinito === */
@keyframes scroll-infinite {
    0% {
        transform: translateX(0);
    }
    /* Se desplaza el 50% de su propio ancho (que es la longitud del contenido original) */
    100% {
        transform: translateX(-50%);
    }
}


/* ---------------------------------- */
/* Controles y Botones */
/* ---------------------------------- */
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
    transition: background-color 0.3s;
}

.expand-btn:hover {
    background-color: #e60029;
}


/* ---------------------------------- */
/* Estilos de Modales (Sin cambios mayores) */
/* ---------------------------------- */
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
#fullGalleryModal .modal-grid {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 10px;
}

/* Carrusel de miniaturas en el modal de imagen individual */
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
    .event-section { width: 90%; }
    #fullGalleryModal .modal-grid { grid-template-columns: repeat(2,1fr); }
    .modal-content img { max-width: 95%; max-height: 60vh; }
    .modal-carousel-container { width: 95%; }
}

@media (max-width:600px) {
    #fullGalleryModal .modal-grid { grid-template-columns: 1fr; }
    .modal-thumbnail { width: 60px; height: 60px; }
}
</style>

<div class="content-wrapper">
    <h2 class="section-title">Nuestra Galería de Eventos</h2>
    <div class="line-divider"></div>

    <div class="event-sections-grid">
        <?php foreach ($eventos as $evento_id => $evento): 
            $images = $evento['imagenes'];
            $num_images = count($images);
        ?>
            <div class="event-section" data-event-id="<?= $evento_id ?>" data-images='<?= json_encode($images) ?>'>
                <h3 class="event-title"><?= $evento['titulo'] ?></h3>

                <?php if ($num_images > 0): ?>
                <div class="carousel-viewport">
                    <div class="gallery-container">
                        <div class="carousel-track-infinite">
                            
                            <div class="carousel-content-infinite">
                                <?php 
                                foreach ($images as $slide_index => $imagen): 
                                ?>
                                    <div class="gallery-item-infinite" 
                                         data-global-index="<?= $slide_index ?>">
                                        <img src="../../<?= $imagen['url'] ?>" alt="<?= $imagen['descripcion'] ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($num_images > 1): ?>
                            <div class="carousel-content-infinite" aria-hidden="true">
                                <?php 
                                foreach ($images as $slide_index => $imagen): 
                                ?>
                                    <div class="gallery-item-infinite" 
                                         data-global-index="<?= $slide_index ?>">
                                        <img src="../../<?= $imagen['url'] ?>" alt="<?= $imagen['descripcion'] ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <div class="controls-container">
                    <button class="expand-btn">Ver más</button>
                </div>
                <?php else: ?>
                    <p style="text-align:center; color:gray;">No hay imágenes para este evento.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
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

<div id="fullGalleryModal" class="modal">
    <span class="close">&times;</span>
    <div class="modal-content">
        <h3 id="fullGalleryTitle" class="event-title"></h3>
        <div id="modalGalleryGrid" class="modal-grid"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // === REFERENCIAS A LOS MODALES ===
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const modalDescription = document.getElementById('modalDescription');
    const modalCarousel = document.getElementById('modalCarousel');

    const fullGalleryModal = document.getElementById('fullGalleryModal');
    const fullGalleryTitle = document.getElementById('fullGalleryTitle');
    const modalGalleryGrid = document.getElementById('modalGalleryGrid');
    
    let currentEventImages = []; 

    // === FUNCIONES DE CONTROL DE MODAL ===

    const openImageModal = (index, images) => {
        const imgArray = images || currentEventImages;

        if (imgArray.length === 0 || index < 0 || index >= imgArray.length) {
            console.error("Índice o array de imágenes no válido.");
            return;
        }

        currentEventImages = imgArray;
        
        const img = imgArray[index];
        const src = `../../${img.url}`;
        const desc = img.descripcion || '';

        modalImage.src = src;
        modalImage.setAttribute('data-current-index', index);
        modalDescription.textContent = desc;

        // Construir carrusel de miniaturas (para el modal grande)
        modalCarousel.innerHTML = '';
        if (imgArray.length > 1) { 
            imgArray.forEach((item, i) => {
                const thumbnail = document.createElement('div');
                thumbnail.className = 'modal-thumbnail' + (i === index ? ' active' : '');
                thumbnail.innerHTML = `<img src="../../${item.url}" alt="${item.descripcion || ''}">`;
                
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

    const openFullGallery = (title, images) => {
        currentEventImages = images;
        fullGalleryTitle.textContent = title;
        modalGalleryGrid.innerHTML = '';

        images.forEach((img, index) => { 
            const div = document.createElement('div');
            const src = `../../${img.url}`;
            const desc = img.descripcion || '';
            
            // Usamos un div simple con la miniatura para la cuadrícula
            div.className = 'gallery-grid-item';
            div.style.overflow = 'hidden';
            div.style.borderRadius = '8px';
            div.style.cursor = 'pointer';
            div.innerHTML = `<img src="${src}" alt="${desc}" style="width:100%; height:100%; object-fit:cover;">`;

            div.addEventListener('click', () => {
                closeFullGallery(); 
                openImageModal(index, images);
            });

            modalGalleryGrid.appendChild(div);
        });

        fullGalleryModal.style.display = 'flex';
        fullGalleryModal.style.zIndex = '10000';
    };

    const closeFullGallery = () => {
        fullGalleryModal.style.display = 'none';
        modalGalleryGrid.innerHTML = '';
        currentEventImages = [];
    };

    // === CIERRES DE MODAL ===
    fullGalleryModal.querySelector('.close').addEventListener('click', closeFullGallery);
    imageModal.querySelector('.close').addEventListener('click', closeImageModal);

    fullGalleryModal.addEventListener('click', e => { if (e.target === fullGalleryModal) closeFullGallery(); });
    imageModal.addEventListener('click', e => { 
        // Cierra solo si se hace clic en el fondo oscuro
        if (e.target === imageModal || e.target === imageModal.querySelector('.close')) {
            closeImageModal(); 
        }
    });

    // === LÓGICA DEL CARRUSEL DE PREVIEW (MOVIMIENTO INFINITO CSS) ===
    
    document.querySelectorAll('.event-section').forEach(section => {
        const expandBtn = section.querySelector('.expand-btn');
        
        // 1. Configurar clics en CADA imagen (miniaturas) para abrir el modal individual
        section.querySelectorAll('.gallery-item-infinite').forEach((imgItem) => {
            imgItem.addEventListener('click', () => {
                const images = JSON.parse(section.getAttribute('data-images'));
                // Obtener el índice global
                const globalIndex = parseInt(imgItem.getAttribute('data-global-index'));
                openImageModal(globalIndex, images); 
            });
        });


        // 2. Configurar el botón "Ver más"
        if (expandBtn) {
            expandBtn.addEventListener('click', () => {
                const title = section.querySelector('.event-title').textContent;
                const images = JSON.parse(section.getAttribute('data-images'));
                openFullGallery(title, images);
            });
        }
    });
});
</script>

<?php include '../plantilla/plantilla_info/footer.php'; ?>