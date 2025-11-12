<?php
// Cierre del tag <main> iniciado en header.php
echo '</main>';

// ========================================
// BASE URL Y CONEXIÓN
// ========================================
// Asumiendo la misma ruta relativa que en el header
$baseURL = include __DIR__ . '/../../../ruta_Api.php';
require_once __DIR__ . '/../../../conexion.php';


/* =========================
   PATROCINADORES (activos)
   ========================= */
$sqlPat = "SELECT pat_nom, pat_img, pat_dim 
             FROM tbl_patrocinadores 
             WHERE pat_est='activo' 
             ORDER BY pat_id ASC";
$rsPat = @pg_query($conn, $sqlPat); 
$patro = [];
if ($rsPat) {
    while ($row = pg_fetch_assoc($rsPat)) {
        $dim = trim($row['pat_dim'] ?? '160x160');
        $parts = explode('x', strtolower($dim));
        $w = intval($parts[0] ?? 160);
        $h = intval($parts[1] ?? 160);
        $patro[] = [
            'nom' => $row['pat_nom'],
            'img' => $row['pat_img'],
            'w'   => max(100, min(180, $w)),
            'h'   => max(60, min(100, $h)),
        ];
    }
}

/* =========================
   SOBRE NOSOTROS (activo) - Se obtienen todos los textos completos
   ========================= */
$sqlNos = "SELECT 
              nos_nom_emp, nos_hist, nos_mis, nos_vis,
              nos_cel, nos_dir, nos_correo
            FROM tbl_nosotros
            WHERE nos_est='activo'
            ORDER BY nos_id DESC
            LIMIT 1";
$rsNos  = @pg_query($conn, $sqlNos);
$nos = ($rsNos && @pg_num_rows($rsNos) > 0) ? pg_fetch_assoc($rsNos) : null;

/* helpers */
function urlAbs($base, $path){
    if (!$path) return '';
    return preg_match('#^https?://#i', $path) ? $path : rtrim($base,'/').'/'.ltrim($path,'/');
}

$footerVideoURL = $baseURL . 'php/imagenes/video/fondoinfo.mp4';
?>

<style>
/* ========================================
    VARIABLES Y ESTILOS BASE
    ========================================
*/
:root {
    --primary-color: #e5002b;
    --secondary-color: #0078c7;
    --dark-bg: #1A1A1A;
    --text-color: #F8F9FA;

    /* Colores del Footer */
    --f-bg: #2c3e50;         
    --f-text: #ecf0f1;       
    --f-text-muted: #bdc3c7; 
    --f-accent: #3498db;     
    --f-border: rgba(255, 255, 255, 0.1); 
    --f-card-bg: rgba(255, 255, 255, 0.05); 
}

footer {
    width: 100%;
    margin-top: auto;
    position: relative;
    overflow: hidden;
    background-color: var(--f-bg);
    color: var(--f-text);
    padding: 50px 20px 20px;
    font-family: 'Poppins', sans-serif;
}
.f-container {
    max-width: 1200px;
    margin: 0 auto;
}
.f-section-title {
    color: var(--f-accent);
    font-size: 1.4rem;
    margin-bottom: 20px;
    border-bottom: 2px solid var(--f-border);
    padding-bottom: 10px;
    font-weight: 600;
}
a {
    color: var(--f-accent);
    text-decoration: none;
    transition: color 0.3s ease;
}
a:hover {
    color: #5dade2;
    text-decoration: underline;
}

/* ========================================
    DISEÑO DE COLUMNAS Y CARRUSEL NOSOTROS
    ========================================
*/
.footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr; /* 2/3 para Nosotros, 1/3 para Contacto */
    gap: 40px;
    margin-bottom: 40px;
}

/* Contenedor principal del Carrusel de Nosotros */
.nosotros-carousel-container {
    position: relative;
    padding-bottom: 30px; /* Espacio para los dots */
    overflow: hidden; 
}

/* Carrusel Wrapper for Nosotros */
#carouselNosotros {
    scroll-snap-type: x mandatory;
    scroll-behavior: smooth;
    display: flex;
    overflow-x: scroll; /* Debe tener scroll para el drag */
    -webkit-overflow-scrolling: touch;
    -ms-overflow-style: none;
    scrollbar-width: none;
    gap: 0; /* No queremos gap entre slides */
    cursor: grab; /* Indicador visual de arrastre */
}
#carouselNosotros:active {
    cursor: grabbing;
}
#carouselNosotros::-webkit-scrollbar { display: none; }


/* Estilo para cada bloque (Historia, Misión, Visión) */
.carousel-item-nosotros {
    flex: 0 0 100%; /* Ocupa el 100% del ancho del contenedor visible */
    max-width: 100%;
    scroll-snap-align: start; 
    
    background-color: var(--f-card-bg); 
    padding: 20px;
    margin: 10px 0; 
    border-left: 5px solid var(--f-accent); 
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    box-sizing: border-box; 
    min-height: 250px; /* Altura mínima para mejor consistencia */
}

/* Estilos de contenido interno de Nosotros */
.carousel-item-nosotros h4 {
    color: var(--f-accent); 
    font-size: 1.3rem;
    margin: 0 0 10px 0;
    padding-bottom: 8px;
    border-bottom: 1px dashed var(--f-border);
}

.carousel-item-nosotros h4 i {
    margin-right: 8px;
}

.carousel-item-nosotros p {
    margin-top: 10px;
    font-size: 0.95em;
    line-height: 1.6;
    color: var(--f-text-muted);
    text-align: justify;
}

/* Estilos de Contacto */
.footer-contact-info div { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: var(--f-text-muted); font-size: 0.95rem; }
.footer-contact-info div i { color: var(--f-accent); font-size: 1.1rem; }
.footer-contact-info a { color: var(--f-text-muted); }
.footer-contact-info a:hover { color: var(--f-accent); text-decoration: none; }


/* ========================================
    PATROCINADORES Y VIDEO (Estilos reusados)
    ========================================
*/
.footer-sponsors { overflow: hidden; }
.carousel-container {
    position: relative; overflow: hidden; background-color: rgba(0,0,0,0.1); border-radius: 8px; padding: 30px 0; 
}
.carousel-wrapper {
    display: flex; overflow-x: scroll; scroll-snap-type: x mandatory; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; -ms-overflow-style: none; scrollbar-width: none; gap: 15px; align-items: center; padding: 0; 
}
.carousel-wrapper::-webkit-scrollbar { display: none; }
.carousel-item {
    flex: 0 0 calc(33.333% - 10px); max-width: calc(33.333% - 10px); scroll-snap-align: center; display: flex; justify-content: center; align-items: center; height: 200px; min-width: 100px; box-sizing: border-box; transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out; opacity: 0.7; 
}
.carousel-item:not(.active-center) img {
    width: 150px; height: 150px; border-radius: 50%; object-fit: cover; box-shadow: 0 0 10px rgba(0,0,0,0.3);
}
.carousel-item.active-center { transform: scale(1.6); z-index: 5; opacity: 1; }
.carousel-item.active-center img {
    width: 100px; height: 100px; border-radius: 50%; object-fit: cover; box-shadow: 0 0 15px rgba(0,0,0,0.5);
}
.footer-video {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; object-position: bottom; z-index: -1; opacity: 0.8; animation: zoomVideo 20s infinite alternate ease-in-out;
}
@keyframes zoomVideo {
    from { transform: scale(1); }
    to { transform: scale(1.1); }
}

/* Botones, dots y derechos de autor CSS (Reusado para ambos carruseles) */
.carousel-button {
    position: absolute; top: 50%; transform: translateY(-50%); background-color: var(--f-accent); color: var(--f-bg); border: none; border-radius: 50%; width: 35px; height: 35px; display: flex; justify-content: center; align-items: center; cursor: pointer; z-index: 10; box-shadow: 0 2px 5px rgba(0,0,0,0.3); transition: background-color 0.3s ease;
}
.carousel-button:hover { background-color: #5dade2; }
.carousel-button.prev { left: 10px; }
.carousel-button.next { right: 10px; }
.carousel-dots { text-align: center; margin-top: 20px; }
.carousel-dot { display: inline-block; width: 10px; height: 10px; background-color: var(--f-text-muted); border-radius: 50%; margin: 0 5px; cursor: pointer; transition: background-color 0.3s ease, transform 0.3s ease; }
.carousel-dot.active { background-color: var(--f-accent); transform: scale(1.2); }
.footer-rights {
    text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--f-border); color: var(--f-text-muted); font-size: 0.85rem;
}

/* Responsive */
@media (max-width: 900px) {
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
}
@media (max-width: 600px) {
    .carousel-item { flex: 0 0 100%; max-width: 100%; opacity: 1; height: 100px; }
    .carousel-item.active-center { transform: scale(1); }
    .carousel-item img, .carousel-item.active-center img { width: 80px; height: 80px; }
    .f-section-title { text-align: center; }
}
</style>

<footer id="site-footer">
    <video autoplay muted loop class="footer-video">
        <source src="<?= htmlspecialchars($footerVideoURL) ?>" type="video/mp4">
        Tu navegador no soporta el video.
    </video>
    <div class="f-container">
        <div class="footer-grid">
            
            <div class="footer-column footer-text-content">
                <h3 class="f-section-title"><?= htmlspecialchars($nos['nos_nom_emp'] ?? 'Nombre de la Empresa') ?></h3>
                
                <div class="nosotros-carousel-container">
                    <button class="carousel-button prev" aria-label="Anterior" type="button" data-caro="nosotros"><i class="fas fa-chevron-left"></i></button>
                    
                    <div class="carousel-wrapper" id="carouselNosotros">
                        
                        <?php if (trim($nos['nos_hist'] ?? '')): ?>
                            <div class="carousel-item-nosotros">
                                <h4><i class="fas fa-history"></i> Historia</h4>
                                <p><?= nl2br(htmlspecialchars($nos['nos_hist'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (trim($nos['nos_mis'] ?? '')): ?>
                            <div class="carousel-item-nosotros">
                                <h4><i class="fas fa-bullseye"></i> Misión</h4>
                                <p><?= nl2br(htmlspecialchars($nos['nos_mis'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (trim($nos['nos_vis'] ?? '')): ?>
                            <div class="carousel-item-nosotros">
                                <h4><i class="fas fa-eye"></i> Visión</h4>
                                <p><?= nl2br(htmlspecialchars($nos['nos_vis'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                    
                    <button class="carousel-button next" aria-label="Siguiente" type="button" data-caro="nosotros"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="carousel-dots" id="dotsNosotros"></div>
            </div>

            <div class="footer-column footer-contact-info">
                <h3 class="f-section-title">Contáctanos</h3>
                <div>
                    <i class="fas fa-phone"></i> <span><?= htmlspecialchars($nos['nos_cel'] ?? 'No disponible') ?></span>
                </div>
                <div>
                    <i class="fas fa-envelope"></i> <a href="mailto:<?= htmlspecialchars($nos['nos_correo'] ?? 'info@empresa.com') ?>"><?= htmlspecialchars($nos['nos_correo'] ?? 'info@empresa.com') ?></a>
                </div>
                <div>
                    <i class="fas fa-map-marker-alt"></i> <span><?= htmlspecialchars($nos['nos_dir'] ?? 'Dirección no disponible') ?></span>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="#" class="f-social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="f-social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="f-social-icon"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>

        <div class="footer-column footer-sponsors">
            <h3 class="f-section-title" style="text-align: center;">Nuestros Patrocinadores</h3>
            <div class="carousel-container">
                <button class="carousel-button prev" aria-label="Anterior Patrocinador" type="button" data-caro="patro"><i class="fas fa-chevron-left"></i></button>
                <div class="carousel-wrapper" id="carouselPatro">
                    <?php if(!empty($patro)): ?>
                        <?php foreach($patro as $p): 
                            $img = urlAbs($baseURL, $p['img']);
                        ?>
                            <div class="carousel-item">
                                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="f-text-muted" style="width: 100%; text-align: center; padding: 20px;">No hay patrocinadores activos.</div>
                    <?php endif; ?>
                </div>
                <button class="carousel-button next" aria-label="Siguiente Patrocinador" type="button" data-caro="patro"><i class="fas fa-chevron-right"></i></button>
                <div class="carousel-dots" id="dotsPatro"></div>
            </div>
        </div>

        <div class="footer-rights">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($nos['nos_nom_emp'] ?? 'Nombre de la Empresa') ?>. Todos los derechos reservados.
        </div>
    </div>

    <script>
    /**
     * Configura un carrusel dinámico.
     * @param {string} containerId - ID del elemento que contiene los items (carouselWrapper).
     * @param {string} dotId - ID del contenedor de los puntos de navegación.
     * @param {string} itemClass - Clase CSS de cada item (e.g., '.carousel-item' o '.carousel-item-nosotros').
     * @param {boolean} [zoomEffect=true] - Aplica el efecto de zoom (active-center). Ideal para imágenes, no para texto.
     * @param {boolean} [autoplay=false] - Habilita el desplazamiento automático.
     * @param {boolean} [reverseAutoplay=false] - Invierte la dirección del desplazamiento automático (si autoplay es true).
     */
    function setupCarousel(containerId, dotId, itemClass, zoomEffect = true, autoplay = false, reverseAutoplay = false) {
        const cont = document.getElementById(containerId);
        if(!cont) return;

        // Busca el contenedor padre para encontrar los botones (soporta .carousel-container y .nosotros-carousel-container)
        const wrapper = cont.closest('.carousel-container') || cont.closest('.nosotros-carousel-container');
        
        // Determina el tipo de carrusel (patro o nosotros) para asociar botones
        const caroType = wrapper.querySelector(`[data-caro]`).dataset.caro;
        const prev = wrapper.querySelector(`.carousel-button.prev[data-caro="${caroType}"]`);
        const next = wrapper.querySelector(`.carousel-button.next[data-caro="${caroType}"]`);
        
        const items = Array.from(cont.querySelectorAll(itemClass));
        const dotsWrap = document.getElementById(dotId);

        if(items.length < 2){ 
            if(prev) prev.style.display='none'; 
            if(next) next.style.display='none'; 
            if(dotsWrap) dotsWrap.style.display='none';
            return; 
        }

        let currentPage = 0;
        const totalItems = items.length;

        const getScrollCenter = (itemIndex) => {
            if (!items[itemIndex]) return 0;
            const item = items[itemIndex];
            // Si es un carrusel de ancho 100% (Nosotros), solo scrollea al inicio del item.
            if (itemClass === '.carousel-item-nosotros') {
                return item.offsetLeft; 
            }
            // Para otros carruseles (Patrocinadores), scrollea al centro del item.
            return item.offsetLeft - (cont.clientWidth / 2) + (item.offsetWidth / 2);
        };

        const setActiveZoom = (activeIndex) => {
            if (!zoomEffect) return; // Deshabilita el zoom para el carrusel de texto
            
            items.forEach((item, index) => {
                const isMobile = window.innerWidth <= 600;
                item.classList.toggle('active-center', index === activeIndex && !isMobile);
            });
        };

        const generateDots = () => {
            if (!dotsWrap) return;
            dotsWrap.innerHTML = '';
            for(let i=0;i<totalItems;i++){
                const d=document.createElement('span');
                d.classList.add('carousel-dot');
                d.dataset.page=i;
                d.addEventListener('click',()=>{
                    currentPage = i;
                    scrollToPage(currentPage);
                });
                dotsWrap.appendChild(d);
            }
            updateDots();
        };

        const updateDots = () => {
            if (!dotsWrap) return;
            dotsWrap.querySelectorAll('.carousel-dot').forEach((s,i)=>{
                s.classList.toggle('active', i===currentPage);
            });
        };
        
        const scrollToPage = (pageIndex) => {
            if (items.length === 0) return;
            cont.scrollTo({left: getScrollCenter(pageIndex), behavior:'smooth'});
            currentPage = pageIndex;
            updateDots();
            setActiveZoom(currentPage); 
        };

        const sync = () => {
            if (items.length === 0) return;
            
            let closestItemIndex = 0;
            if (itemClass === '.carousel-item-nosotros') {
                // Cálculo simple de índice para carrusel de ancho 100%
                closestItemIndex = Math.round(cont.scrollLeft / cont.clientWidth);
            } else {
                // Cálculo de centro para carrusel de items parciales (Patrocinadores)
                const centerScroll = cont.scrollLeft + cont.clientWidth / 2;
                let minDistance = Infinity;
                items.forEach((item, index) => {
                    const itemCenter = item.offsetLeft + item.offsetWidth / 2;
                    const distance = Math.abs(itemCenter - centerScroll);
                    if (distance < minDistance) {
                        minDistance = distance;
                        closestItemIndex = index;
                    }
                });
            }
            
            currentPage = closestItemIndex;
            updateDots();
            setActiveZoom(currentPage); 
        };
        
        generateDots();
        cont.addEventListener('scroll', ()=>{ requestAnimationFrame(sync); });
        window.addEventListener('resize', ()=>{
            generateDots();
            sync();
            // Re-scrollea al item actual si es un carrusel 100% al redimensionar.
            if (itemClass === '.carousel-item-nosotros') {
                 scrollToPage(currentPage);
            }
        });

        scrollToPage(0); 
        setActiveZoom(0);

        if (prev) {
            prev.addEventListener('click', ()=>{
                currentPage = (currentPage - 1 + totalItems) % totalItems;
                scrollToPage(currentPage);
            });
        }
        if (next) {
            next.addEventListener('click', ()=>{
                currentPage = (currentPage + 1) % totalItems;
                scrollToPage(currentPage);
            });
        }

        let auto;
        if (autoplay) {
            const startAutoPlay = () => {
                clearInterval(auto);
                auto = setInterval(() => {
                    if (reverseAutoplay) {
                        currentPage = (currentPage - 1 + totalItems) % totalItems;
                    } else {
                        currentPage = (currentPage + 1) % totalItems;
                    }
                    scrollToPage(currentPage);
                }, 3000); 
            };
            startAutoPlay();
            [cont, prev, next].filter(el => el).forEach(el => {
                el.addEventListener('mouseenter', ()=>clearInterval(auto));
                el.addEventListener('mouseleave', startAutoPlay);
            });
        }

        // Implementación de arrastre (draggable scroll)
        let startX=0, scL=0, isDown=false;
        cont.addEventListener('mousedown', e=>{isDown=true; cont.style.cursor='grabbing'; startX=e.pageX - cont.offsetLeft; scL=cont.scrollLeft;});
        cont.addEventListener('mouseleave', ()=>{isDown=false; cont.style.cursor='grab';});
        cont.addEventListener('mouseup', ()=>{isDown=false; cont.style.cursor='grab';});
        cont.addEventListener('mousemove', e=>{
            if(!isDown) return;
            e.preventDefault();
            const x = e.pageX - cont.offsetLeft;
            const walk = (x - startX) * 1.5;
            cont.scrollLeft = scL - walk;
        });
    }

    // Inicializar carruseles
    document.addEventListener('DOMContentLoaded', () => {
        // 1. Carrusel de Patrocinadores: con zoom, con autoplay inverso
        setupCarousel('carouselPatro', 'dotsPatro', '.carousel-item', true, true, true); 
        
        // 2. Carrusel de Nosotros: SIN zoom (false), SIN autoplay (false)
        setupCarousel('carouselNosotros', 'dotsNosotros', '.carousel-item-nosotros', false, false);
    });
    </script>
</footer>

</body>
</html>