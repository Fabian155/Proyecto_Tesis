<?php
// Mantener el código PHP de conexión y lógica de datos sin cambios
include __DIR__ . '/../../../conexion.php';
$baseURL = include __DIR__ . '/../../../ruta_Api.php';


/* =========================
   PATROCINADORES (activos)
   ========================= */
$sqlPat = "SELECT pat_nom, pat_img, pat_dim 
            FROM tbl_patrocinadores 
            WHERE pat_est='activo' 
            ORDER BY pat_id ASC";
$rsPat = pg_query($conn, $sqlPat);
$patro = [];
if ($rsPat) {
    while ($row = pg_fetch_assoc($rsPat)) {
        $dim = trim($row['pat_dim'] ?: '160x160');
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
   SOBRE NOSOTROS (activo)
   ========================= */
$sqlNos = "SELECT 
              nos_nom_emp, nos_hist, nos_img_hist, nos_dim_hist,
              nos_mis, nos_img_mis, nos_dim_mis,
              nos_vis, nos_img_vis, nos_dim_vis,
              nos_cel, nos_dir, nos_correo,
              nos_men_ini, nos_link_app
            FROM tbl_nosotros
            WHERE nos_est='activo'
            ORDER BY nos_id DESC
            LIMIT 1";
$rsNos  = pg_query($conn, $sqlNos);
$nos = ($rsNos && pg_num_rows($rsNos) > 0) ? pg_fetch_assoc($rsNos) : null;

/* helpers */
function dimXY($dimStr, $def=100){
    $dim = trim($dimStr ?: "{$def}x{$def}");
    $p = explode('x', strtolower($dim));
    $w = intval($p[0] ?? $def);
    $h = intval($p[1] ?? $def);
    return [ max(40,min(160,$w)), max(40,min(160,$h)) ]; 
}
function urlAbs($base, $path){
    if (!$path) return '';
    return preg_match('#^https?://#i', $path) ? $path : rtrim($base,'/').'/'.ltrim($path,'/');
}
$res = function($t,$n=180){ $t=strip_tags($t); return mb_strlen($t)>$n ? mb_substr($t,0,$n).'…' : $t; };

// Información para la sección "Conócenos"
$conocenos_items = [];
if (trim($nos['nos_hist'] ?? '')) $conocenos_items[] = ['tit' => 'Historia', 'icon' => 'fas fa-history', 'link' => urlAbs($baseURL, 'php/info/nosotros.php') . '#historia'];
if (trim($nos['nos_mis'] ?? '')) $conocenos_items[] = ['tit' => 'Misión', 'icon' => 'fas fa-bullseye', 'link' => urlAbs($baseURL, 'php/info/nosotros.php') . '#mision'];
if (trim($nos['nos_vis'] ?? '')) $conocenos_items[] = ['tit' => 'Visión', 'icon' => 'fas fa-eye', 'link' => urlAbs($baseURL, 'php/info/nosotros.php') . '#vision'];

// Para el enlace "Leer más sobre nosotros"
$nosotros_link = urlAbs($baseURL, 'php/info/nosotros.php');
?>
<footer id="site-footer">
    <style>
        /* Paleta de Colores Minimalista y Elegante */
        :root {
            --f-bg: #2c3e50; /* Gris azulado oscuro */
            --f-text: #ecf0f1; /* Gris muy claro */
            --f-text-muted: #bdc3c7; /* Gris claro para texto secundario */
            --f-accent: #3498db; /* Azul vibrante */
            --f-border: rgba(255, 255, 255, 0.1); /* Borde sutil */
        }

        /* Estilos Base del Footer */
        #site-footer {
            background-color: var(--f-bg);
            color: var(--f-text);
            padding: 50px 20px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        .f-text-muted {
            color: var(--f-text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
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

        /* Diseño de Columnas con Grid */
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        /* Estilo para la sección "Conócenos" y "Contacto" - sin cambios */
        .footer-about p { margin-bottom: 15px; }
        .footer-conocenos ul { list-style: none; padding: 0; margin: 0; }
        .footer-conocenos ul li { margin-bottom: 10px; }
        .footer-conocenos ul li a { display: flex; align-items: center; gap: 10px; color: var(--f-text-muted); }
        .footer-conocenos ul li a:hover { color: var(--f-accent); }
        .footer-conocenos ul li a i { font-size: 1.1rem; color: var(--f-accent); transition: color 0.3s ease; }
        .footer-conocenos ul li a:hover i { color: #5dade2; }
        .footer-contact-info div { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: var(--f-text-muted); font-size: 0.95rem; }
        .footer-contact-info div i { color: var(--f-accent); font-size: 1.1rem; }
        .footer-contact-info a { color: var(--f-text-muted); }
        .footer-contact-info a:hover { color: var(--f-accent); text-decoration: none; }


        /* ======================================= */
        /* Carrusel de Patrocinadores - **ACTUALIZADO** */
        /* ======================================= */
        .footer-sponsors {
             /* Permite que el contenido del carrusel sobresalga del ancho normal de la columna */
            overflow: hidden; 
        }
        .carousel-container {
            position: relative;
            overflow: hidden;
            background-color: rgba(0,0,0,0.1);
            border-radius: 8px;
            /* Aumentamos el padding vertical para acomodar el zoom */
            padding: 30px 0; 
        }
        .carousel-wrapper {
            display: flex;
            overflow-x: scroll;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            -ms-overflow-style: none;
            scrollbar-width: none;
            gap: 15px; /* Aumentamos el espacio entre logos */
            align-items: center;
            padding: 0; 
        }
        .carousel-wrapper::-webkit-scrollbar {
            display: none;
        }
        .carousel-item {
            /* CLAVE: Mostrar 3 ítems a la vez (33.33% menos el gap) */
            flex: 0 0 calc(33.333% - 10px); 
            max-width: calc(33.333% - 10px);
            scroll-snap-align: center; 
            display: flex;
            justify-content: center;
            align-items: center;
            /* Aumentamos la altura base del item */
            height: 200px; 
            min-width: 100px;
            box-sizing: border-box;
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out; 
            opacity: 0.7; 
        }

        /* LOGOS: Hacemos el contenedor redondo y un poco más grande */
        .carousel-item:not(.active-center) img {
             /* Tamaño base aumentado */
            width: 150px; 
            height: 150px;
            /* Propiedad para hacerlo redondo */
            border-radius: 50%; 
            object-fit: cover; /* Asegura que la imagen cubra el círculo */
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }

        /* Estilo para el elemento central (ZOOM más grande) */
        .carousel-item.active-center {
            transform: scale(1.6); /* **ZOOM MUCHO MÁS GRANDE** (1.6 = 60% más grande) */
            z-index: 5; 
            opacity: 1; 
        }
        
        /* Aseguramos que el logo centrado también sea redondo y mantenga el tamaño */
        .carousel-item.active-center img {
            width: 100px; /* Tamaño del logo central sin escalar */
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
        }

        /* Media Query para móvil: solo 1 item visible */
        @media (max-width: 600px) {
            .carousel-item {
                flex: 0 0 100%; 
                max-width: 100%;
                opacity: 1; 
                height: 100px;
            }
            .carousel-item.active-center {
                 transform: scale(1); /* Desactivar el zoom en móvil */
            }
             .carousel-item img {
                width: 80px; 
                height: 80px;
            }
             .carousel-item.active-center img {
                width: 80px; 
                height: 80px;
            }
        }
        
        /* Botones, dots y derechos de autor CSS - sin cambios */
        .carousel-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: var(--f-accent);
            color: var(--f-bg);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            transition: background-color 0.3s ease;
        }
        .carousel-button:hover {
            background-color: #5dade2;
        }
        .carousel-button.prev { left: 10px; }
        .carousel-button.next { right: 10px; }

        .carousel-dots {
            text-align: center;
            margin-top: 20px;
        }
        .carousel-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--f-text-muted);
            border-radius: 50%;
            margin: 0 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .carousel-dot.active {
            background-color: var(--f-accent);
            transform: scale(1.2);
        }

        .footer-rights {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--f-border);
            color: var(--f-text-muted);
            font-size: 0.85rem;
        }
        @media (max-width: 768px) {
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            .f-section-title {
                text-align: center;
            }
            .footer-contact-info div {
                justify-content: center;
            }
        }
    </style>

    <div class="f-container">
        <div class="footer-grid">
            <div class="footer-column footer-about">
                <h3 class="f-section-title"><?= htmlspecialchars($nos['nos_nom_emp'] ?? 'Nombre de la Empresa') ?></h3>
                <p class="f-text-muted">
                    <?= $res($nos['nos_hist'] ?? 'Aquí una breve descripción de la historia de la empresa o un mensaje de bienvenida. Contamos con años de experiencia en el sector y nos dedicamos a ofrecer soluciones de calidad.') ?>
                </p>
                <a href="<?= htmlspecialchars($nosotros_link) ?>" class="f-link">Leer más sobre nosotros <i class="fas fa-arrow-right"></i></a>
            </div>

            <div class="footer-column footer-conocenos">
                <h3 class="f-section-title">Conócenos</h3>
                <ul>
                    <?php if(!empty($conocenos_items)): ?>
                        <?php foreach($conocenos_items as $item): ?>
                            <li><a href="<?= htmlspecialchars($item['link']) ?>"><i class="<?= htmlspecialchars($item['icon']) ?>"></i> <?= htmlspecialchars($item['tit']) ?></a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a href="<?= htmlspecialchars($nosotros_link) ?>#historia"><i class="fas fa-history"></i> Historia</a></li>
                        <li><a href="<?= htmlspecialchars($nosotros_link) ?>#mision"><i class="fas fa-bullseye"></i> Misión</a></li>
                        <li><a href="<?= htmlspecialchars($nosotros_link) ?>#vision"><i class="fas fa-eye"></i> Visión</a></li>
                    <?php endif; ?>
                </ul>
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
                                <img src="<?= $img ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
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
    function setupCarousel(containerId, dotId, itemClass, autoplay = true, reverseAutoplay = false) {
        const cont = document.getElementById(containerId);
        if(!cont) return;

        const wrapper = cont.closest('.carousel-container');
        const caroType = containerId.replace('carousel','').toLowerCase();
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

        // Calcula el desplazamiento para centrar un ítem específico
        const getScrollCenter = (itemIndex) => {
            if (!items[itemIndex]) return 0;
            const item = items[itemIndex];
            // Fórmula para centrar: posición inicial - (ancho visible / 2) + (ancho del item / 2)
            return item.offsetLeft - (cont.clientWidth / 2) + (item.offsetWidth / 2);
        };

        // Aplica la clase de zoom al ítem activo
        const setActiveZoom = (activeIndex) => {
            items.forEach((item, index) => {
                const isMobile = window.innerWidth <= 600;
                // Aplicar solo si no es móvil
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
            
            // Encuentra el ítem más cercano al centro VISIBLE
            const centerScroll = cont.scrollLeft + cont.clientWidth / 2;
            let closestItemIndex = 0;
            let minDistance = Infinity;

            items.forEach((item, index) => {
                const itemCenter = item.offsetLeft + item.offsetWidth / 2;
                const distance = Math.abs(itemCenter - centerScroll);
                if (distance < minDistance) {
                    minDistance = distance;
                    closestItemIndex = index;
                }
            });
            
            currentPage = closestItemIndex;
            updateDots();
            setActiveZoom(currentPage); 
        };
        
        generateDots();
        cont.addEventListener('scroll', ()=>{ requestAnimationFrame(sync); });
        window.addEventListener('resize', ()=>{
            generateDots();
            sync();
            // Reajustar la posición para mantener el item centrado después del resize
            scrollToPage(currentPage); 
        });

        // Inicializar el carrusel en la posición 0 y aplicar el zoom al primer elemento
        scrollToPage(0); 
        setActiveZoom(0);

        // --- Funcionalidad de Botones ---
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

        // --- Autoplay (3 segundos y reversa) ---
        let auto;
        if (autoplay) {
            const startAutoPlay = () => {
                 clearInterval(auto);
                 auto = setInterval(() => {
                    // Mover de derecha a izquierda (Reverse Autoplay = true)
                    currentPage = (currentPage - 1 + totalItems) % totalItems;
                    scrollToPage(currentPage);
                }, 3000); // 3 Segundos
            };
            startAutoPlay();
            [cont, prev, next].filter(el => el).forEach(el => {
                el.addEventListener('mouseenter', ()=>clearInterval(auto));
                el.addEventListener('mouseleave', startAutoPlay);
            });
        }

        // --- Swipe Táctil/Mouse (sin cambios) ---
        let startX=0, scL=0, isDown=false;
        cont.addEventListener('mousedown', e=>{isDown=true; startX=e.pageX - cont.offsetLeft; scL=cont.scrollLeft;});
        cont.addEventListener('mouseleave', ()=>{isDown=false;});
        cont.addEventListener('mouseup', ()=>{isDown=false;});
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
        // Autoplay es True, ReverseAutoplay es True para el movimiento DERECHA A IZQUIERDA
        setupCarousel('carouselPatro', 'dotsPatro', '.carousel-item', true, true); 
    });
    </script>
</footer>