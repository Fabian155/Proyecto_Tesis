<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Incluye el encabezado y pie de página
include 'plantilla/header.php';
?>

<style>
    :root {
        --primary-color: #00A676;
        --secondary-color: #FFC107;
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

    .container-premios {
        max-width: 900px;
        margin: auto;
        padding: 20px;
        flex-grow: 1;
        animation: fadeIn 1s ease-out;
    }

    .titulo-seccion {
        text-align: center;
        color: #0177c4;
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
        background-color: #ab343e;
        border-color: #ab343e;
        color: var(--text-color);
    }
    
    #premios-list {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
    }

    .premio-card {
        display: none; /* Oculto por defecto para la paginación y el filtro */
        flex-direction: column;
        background: var(--card-bg);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
        width: 100%;
        max-width: 250px;
    }

    .premio-card.active {
        display: flex;
    }

    .premio-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5);
    }

    .premio-card-header {
        position: relative;
        height: 150px;
        overflow: hidden;
    }

    .premio-card-header img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
        padding: 5px;
        background-color: white;
        border-radius: 10px;
    }

    .premio-card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('https://us.123rf.com/450wm/uximetcpavel/uximetcpavel2310/uximetcpavel231000820/215290154-caja-con-cinta-roja-sobre-fondo-blanco.jpg?ver=6');
        background-size: cover;
        background-position: center;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        transition: transform 0.4s ease, border-radius 0.4s ease;
        z-index: 1;
    }

    .premio-card:hover .premio-card-header::before {
        transform: scale(0.8) rotate(5deg);
        border-radius: 50% 10px 50% 10px;
        opacity: 0;
    }

    .premio-card-body {
        padding: 10px;
        text-align: center;
    }

    .premio-card-body h5 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #f03f42;
        margin-bottom: 5px;
        height: 2.8rem;
        overflow: hidden;
    }

    .premio-card-body p {
        margin: 0;
        font-size: 0.8rem;
        color: var(--light-text);
        line-height: 1.3;
        height: 2.2rem;
        overflow: hidden;
    }

    .premio-card-body .fecha {
        font-size: 0.9rem;
        font-weight: 700;
        color: #0177c4;
        margin-top: 10px;
    }

    .modal-premio {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        background-color: rgba(0, 0, 0, 0.85);
        justify-content: center;
        align-items: center;
        animation: fadeIn 0.3s ease;
    }

    .modal-content-premio {
        background-color: var(--card-bg);
        margin: auto;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
        position: relative;
        text-align: center;
        width: 90%;
        max-width: 450px;
        transform: scale(0);
        animation: zoomIn 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55) forwards;
        z-index: 1100;
    }

    .confetti-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 2000;
    }

    .close-btn {
        color: #0177c4;
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.2s;
        z-index: 2001;
    }

    .close-btn:hover,
    .close-btn:focus {
        color: #f03f42;
    }

    .modal-title {
        color: #0177c4;
        margin-bottom: 15px;
    }

    .modal-image-container {
        background-color: white;
        padding: 10px;
        border-radius: 10px;
        display: inline-block;
        margin-bottom: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .modal-image-container img {
        width: 100%;
        max-width: 250px;
        height: auto;
    }

    .modal-description {
        color: var(--light-text);
        margin-top: 10px;
    }

    .modal-info {
        font-weight: bold;
        color: var(--text-color);
        margin-top: 15px;
        font-size: 1rem;
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
    
    .friendly-message a {
        color: var(--secondary-color);
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
        background-color: #f03f42;
        border-color: #f03f42;
    }
    
    .all-events-btn:hover {
        background-color: #f03f42;
        border-color: #f03f42;
    }


    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes fadeDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes zoomIn {
        from { transform: scale(0); }
        to { transform: scale(1); }
    }

    @media (min-width: 992px) {
        .premio-card {
            width: calc(33.33% - 14px);
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<div class="container-premios">
    <h2 class="titulo-seccion">¡Felicidades, tienes regalos esperando! 🎁</h2>
    
    <div class="filter-container">
        <label for="date-filter">Filtrar por fecha:</label>
        <input type="date" id="date-filter">
        <button id="reset-btn" class="reset-btn">Limpiar filtro</button>
    </div>
    
    <p id="loading-message" class="friendly-message active">
        Buscando tus regalos... ¡Casi listos!
    </p>
    
    <div id="no-premios-message" class="friendly-message">
        Parece que todavía no tienes regalos. ¡Participa en nuestros eventos y sorpréndete!
    </div>
    
    <div id="no-filter-results" class="friendly-message">
        ¡Oh, vaya! No encontramos regalos para esta fecha. 😢
        <p>Intenta con otra fecha o <a href="#" id="clear-filter-link" style="color: #0177c4;">limpia el filtro</a> para ver todos tus premios de nuevo.</p>
    </div>
    
    <div id="premios-list" class="row">
        </div>
    
    <div id="pagination-container" class="pagination-container">
        </div>
</div>

<div id="premioModal" class="modal-premio">
    <canvas class="confetti-canvas"></canvas>
    <div class="modal-content-premio">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3 id="modal-title" class="modal-title"></h3>
        <div id="modal-image-container" class="modal-image-container">
            <img id="premio-image" src="" alt="Imagen del Premio">
        </div>
        <p id="premio-description" class="modal-description"></p>
        <p class="modal-info mt-3">¡Ven a nuestras oficinas para reclamar tu premio. Te esperamos!</p>
    </div>
</div>

<?php
include 'plantilla/footer.php';
?>

<script>
    const userId = <?php echo json_encode($user_id); ?>;
    const premiosList = document.getElementById('premios-list');
    const loadingMessage = document.getElementById('loading-message');
    const noPremiosMessage = document.getElementById('no-premios-message');
    const noFilterResults = document.getElementById('no-filter-results');
    const dateFilter = document.getElementById('date-filter');
    const resetBtn = document.getElementById('reset-btn');
    const paginationContainer = document.getElementById('pagination-container');

    const premioModal = document.getElementById('premioModal');
    const premioImage = document.getElementById('premio-image');
    const modalTitle = document.getElementById('modal-title');
    const premioDescription = document.getElementById('premio-description');
    const confettiCanvas = premioModal.querySelector('.confetti-canvas');

    const premiosPerPage = 3;
    let premiosData = [];
    let filteredPremios = [];
    let currentPage = 1;

    async function fetchPremios() {
        loadingMessage.style.display = 'block';
        try {
            const response = await fetch('../../apis/usuario/regalos/consultar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usr_id: userId })
            });
            const data = await response.json();
            loadingMessage.style.display = 'none';
            noPremiosMessage.style.display = 'none';

            if (data.success && data.tiene_premios) {
                premiosData = data.premios;
                filteredPremios = [...premiosData];
                renderPremios();
                setupPagination();
            } else {
                noPremiosMessage.style.display = 'block';
            }
        } catch (error) {
            console.error('Error al obtener los premios:', error);
            loadingMessage.textContent = 'Hubo un error al cargar los regalos. ¡Intenta de nuevo!';
        }
    }

    function renderPremios() {
        premiosList.innerHTML = '';
        const start = (currentPage - 1) * premiosPerPage;
        const end = start + premiosPerPage;
        const premiosToShow = filteredPremios.slice(start, end);

        if (premiosToShow.length === 0) {
            noFilterResults.style.display = 'block';
        } else {
            noFilterResults.style.display = 'none';
        }

        premiosToShow.forEach(premio => {
            const imageUrl = premio.pre_img || 'https://via.placeholder.com/200?text=Premio';
            const card = document.createElement('div');
            card.className = 'premio-card active';
            card.setAttribute('data-premio', JSON.stringify(premio));
            card.innerHTML = `
                <div class="premio-card-header">
                    <img src="${imageUrl}" alt="${premio.pre_nom}">
                </div>
                <div class="premio-card-body">
                    <h5>${premio.pre_nom}</h5>
                    <p>${premio.pre_des}</p>
                    <p class="fecha">Obtuviste este regalo el: ${new Date(premio.pre_asg_fec_ent).toLocaleDateString('es-ES', {
                        year: 'numeric', month: 'long', day: 'numeric'
                    })}</p>
                </div>
            `;
            card.addEventListener('click', () => {
                openModal(premio);
            });
            premiosList.appendChild(card);
        });
    }

    function setupPagination() {
        paginationContainer.innerHTML = '';
        const totalPages = Math.ceil(filteredPremios.length / premiosPerPage);

        if (totalPages > 1) {
            for (let i = 1; i <= totalPages; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.classList.add('page-btn');
                if (i === currentPage) {
                    pageBtn.classList.add('active');
                }
                pageBtn.addEventListener('click', () => {
                    currentPage = i;
                    renderPremios();
                    updatePaginationButtons();
                });
                paginationContainer.appendChild(pageBtn);
            }
        }

        if (premiosData.length > premiosPerPage) {
            const viewAllBtn = document.createElement('button');
            viewAllBtn.textContent = 'Ver todos';
            viewAllBtn.classList.add('all-events-btn');
            viewAllBtn.addEventListener('click', toggleViewAll);
            paginationContainer.appendChild(viewAllBtn);
        }
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
            premiosList.innerHTML = '';
            filteredPremios.forEach(premio => {
                const imageUrl = premio.pre_img || 'https://via.placeholder.com/200?text=Premio';
                const card = document.createElement('div');
                card.className = 'premio-card active';
                card.setAttribute('data-premio', JSON.stringify(premio));
                card.innerHTML = `
                    <div class="premio-card-header">
                        <img src="${imageUrl}" alt="${premio.pre_nom}">
                    </div>
                    <div class="premio-card-body">
                        <h5>${premio.pre_nom}</h5>
                        <p>${premio.pre_des}</p>
                        <p class="fecha">Obtuviste este regalo el: ${new Date(premio.pre_asg_fec_ent).toLocaleDateString('es-ES', {
                            year: 'numeric', month: 'long', day: 'numeric'
                        })}</p>
                    </div>
                `;
                card.addEventListener('click', () => {
                    openModal(premio);
                });
                premiosList.appendChild(card);
            });
            paginationContainer.querySelectorAll('.page-btn').forEach(btn => btn.style.display = 'none');
            viewAllBtn.textContent = 'Ocultar todos';
        } else {
            renderPremios();
            paginationContainer.querySelectorAll('.page-btn').forEach(btn => btn.style.display = 'inline-block');
            viewAllBtn.textContent = 'Ver todos';
        }
    }
    
    function filterPremiosByDate() {
        const selectedDate = dateFilter.value;
        noFilterResults.style.display = 'none';

        if (selectedDate === '') {
            filteredPremios = [...premiosData];
            paginationContainer.style.display = 'flex';
        } else {
            filteredPremios = premiosData.filter(premio => {
                const premioDate = new Date(premio.pre_asg_fec_ent);
                const formattedDate = premioDate.toISOString().split('T')[0];
                return formattedDate === selectedDate;
            });
            paginationContainer.style.display = 'none';
        }

        currentPage = 1;
        renderPremios();
        if (selectedDate === '') {
             setupPagination();
        }
    }
    
    dateFilter.addEventListener('change', filterPremiosByDate);
    
    resetBtn.addEventListener('click', () => {
        dateFilter.value = '';
        filterPremiosByDate();
    });
    
    document.getElementById('clear-filter-link').addEventListener('click', (e) => {
        e.preventDefault();
        dateFilter.value = '';
        filterPremiosByDate();
    });

    function openModal(premio) {
        const imageUrl = premio.pre_img || 'https://via.placeholder.com/200?text=Premio';
        modalTitle.textContent = premio.pre_nom;
        premioImage.src = imageUrl;
        premioDescription.textContent = premio.pre_des;
        premioModal.style.display = 'flex';
        triggerConfetti();
    }

    function closeModal() {
        premioModal.style.display = 'none';
        const ctx = confettiCanvas.getContext('2d');
        ctx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);
    }

    function triggerConfetti() {
        const myConfetti = confetti.create(confettiCanvas, {
            resize: true,
            useWorker: true
        });
        myConfetti({
            particleCount: 150,
            spread: 90,
            origin: { y: 0.6 }
        });
    }

    window.onclick = function(event) {
        if (event.target == premioModal) {
            closeModal();
        }
    }

    document.addEventListener('DOMContentLoaded', fetchPremios);
</script>