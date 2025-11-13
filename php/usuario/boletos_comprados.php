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
    }

    .container-boletos {
        max-width: 1200px;
        margin: auto;
        padding: 20px;
        animation: fadeIn 1s ease-out;
    }

    .titulo-seccion {
        text-align: center;
        color: var(--secondary-color);
        margin-bottom: 30px;
        font-weight: 700;
        animation: fadeDown 1s ease;
    }

    /* Contenedor de la grilla */
    .boletos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }

    /* Estilo del filtro de fecha */
    .filter-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
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
    
    /* Estilo de la tarjeta de boleto */
    .boleto-card {
        display: flex;
        flex-direction: column;
        background: var(--card-bg);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
        height: 100%;
        display: none;
    }

    .boleto-card.visible {
        display: flex;
    }

    .boleto-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5);
    }

    .boleto-card-header {
        position: relative;
        height: 200px;
        overflow: hidden;
    }

    .boleto-card-header img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }

    .boleto-card:hover .boleto-card-header img {
        transform: scale(1.05);
    }

    .overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .boleto-card:hover .overlay {
        opacity: 1;
    }

    .overlay-text {
        color: white;
        font-size: 1.2rem;
        font-weight: 600;
        text-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .boleto-card-body {
        padding: 15px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        flex-grow: 1;
    }

    .boleto-card-body h4 {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 5px;
    }

    .boleto-card-body p {
        margin: 0;
        font-size: 0.9rem;
        color: var(--light-text);
        line-height: 1.4;
    }

    .boleto-card-body .precio {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-color);
        margin-top: 10px;
    }

    /* Estilos de paginación */
    .pagination-container {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 20px;
    }
    .pagination-btn {
        background-color: transparent;
        color: var(--light-text);
        border: 1px solid var(--light-text);
        padding: 8px 15px;
        font-size: 1rem;
        font-weight: bold;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease, color 0.3s ease;
    }
    .pagination-btn.active, .pagination-btn:hover:not(.disabled) {
        background-color: var(--primary-color);
        color: var(--text-color);
        border-color: var(--primary-color);
    }
    .pagination-btn.disabled {
        cursor: not-allowed;
        opacity: 0.5;
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
    }
    .friendly-message strong {
        color: var(--primary-color);
    }

    /* Modal - Nuevo diseño de boleto */
    .modal-qr {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.85);
        justify-content: center;
        align-items: center;
        animation: fadeIn 0.3s ease;
    }

    .modal-content-qr {
        background-color: var(--card-bg);
        margin: auto;
        padding: 0; /* Eliminar padding para que el contenido ocupe todo el espacio */
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
        position: relative;
        text-align: center;
        width: 90%;
        max-width: 750px; /* Aumentar el ancho para el diseño horizontal */
        height: auto;
        max-height: 500px;
        display: flex;
        animation: scaleUp 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        overflow: hidden;
    }
    
    .qr-section {
        flex: 0 0 40%; /* El QR ocupa el 40% del ancho */
        background-color: var(--dark-bg);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 20px;
        position: relative; /* Para posicionar el mensaje del QR */
    }

    .qr-section h3 {
        color: var(--text-color);
        margin-bottom: 15px;
        text-align: center;
    }

    .qr-section .modal-image-container {
        padding: 10px;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .qr-section .modal-image-container img {
        width: 100%;
        max-width: 200px;
        height: auto;
    }

    .ticket-separator {
        position: relative;
        width: 20px;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--card-bg);
    }

    .ticket-separator::before {
        content: '';
        position: absolute;
        width: 2px;
        height: 100%;
        background-image: radial-gradient(circle, var(--dark-bg) 2px, transparent 2px);
        background-size: 4px 12px;
    }
    
    .data-section {
        flex: 1; /* Ocupa el espacio restante */
        padding: 30px;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .data-section .data-header {
        text-align: right;
        margin-bottom: 15px;
    }

    .data-section .data-header h3 {
        color: var(--secondary-color);
        margin-bottom: 5px;
    }

    .data-section .data-header p {
        font-size: 0.9em;
        color: var(--light-text);
    }

    .data-section .qr-data {
        background: none;
        padding: 0;
        margin-top: 10px;
        text-align: left;
        color: var(--light-text);
        flex-grow: 1;
        overflow-y: auto;
    }
    .data-section .qr-data p {
        margin: 5px 0;
        line-height: 1.4;
    }
    
    .data-section .qr-data strong {
        color: var(--secondary-color); /* Las claves ahora en color secundario */
        font-weight: 600;
    }

    .modal-buttons {
        margin-top: 20px;
        display: flex;
        justify-content: center; /* Centrar botones en la sección QR */
        gap: 10px;
    }
    
    .modal-btn {
        padding: 8px 15px;
        font-size: 0.9em;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        color: var(--text-color); /* Color de texto blanco para botones rojos */
        background-color: var(--primary-color); /* Todos los botones en rojo */
    }

    .modal-btn:hover {
        background-color: #e03a3d; /* Un rojo un poco más oscuro al pasar el mouse */
        transform: scale(1.05);
    }

    /* Mensaje amigable debajo del QR */
    .qr-message {
        font-size: 0.9em;
        color: var(--light-text);
        margin-top: 15px;
        text-align: center;
        max-width: 90%;
    }

    .close-btn {
        color: var(--light-text);
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.2s;
        z-index: 10;
    }

    .close-btn:hover,
    .close-btn:focus {
        color: var(--primary-color);
    }

    /* Animaciones */
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes fadeDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes scaleUp { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    /* Media query para pantallas más pequeñas */
    @media (max-width: 768px) {
        .modal-content-qr {
            flex-direction: column;
            max-width: 450px;
            max-height: none;
        }
        .qr-section, .data-section {
            flex: 1;
        }
        .ticket-separator {
            width: 100%;
            height: 20px;
        }
        .ticket-separator::before {
            width: 100%;
            height: 2px;
            background-image: radial-gradient(circle, var(--dark-bg) 2px, transparent 2px);
            background-size: 12px 4px;
        }
        .data-section .data-header {
            text-align: center;
        }
        .modal-buttons {
            justify-content: center;
            margin-top: 15px;
        }
        .qr-section .modal-buttons {
            margin-top: 15px; /* Ajuste para el mensaje debajo del QR */
        }
    }
</style>

<div class="container-boletos">
    <h2 class="titulo-seccion">Tus Boletos Comprados</h2>
    <div class="filter-container">
        <label for="date-filter" style="color: var(--light-text);">Filtrar por fecha:</label>
        <input type="date" id="date-filter">
        <button id="reset-btn" class="reset-btn">Limpiar filtro</button>
    </div>
    <div id="boletos-list" class="boletos-grid">
        <p id="loading-message" class="friendly-message">
            <i class="fas fa-spinner fa-spin"></i> Estamos buscando tus boletos. ¡Un momento, por favor!
        </p>
    </div>
    <div id="pagination-container" class="pagination-container">
        </div>
</div>

<div id="qrModal" class="modal-qr">
    <div class="modal-content-qr">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        
        <div class="qr-section">
            <h3 id="modal-title"></h3>
            <div id="modal-image-container" class="modal-image-container">
                <img id="qr-image" src="" alt="Código QR del Boleto">
            </div>
            <p id="qr-display-message" class="qr-message"></p>
            <div class="modal-buttons">
                <button class="modal-btn" onclick="downloadQR()"><i class="fas fa-download"></i> Guardar QR</button>
                <button id="toggle-qr-btn" class="modal-btn" style="display: none;" onclick="toggleQR()"></button>
            </div>
        </div>
        
        <div class="ticket-separator"></div>

        <div class="data-section">
            <div class="data-header">
                <h3>Detalles del Boleto</h3>
                <p>Aquí tienes toda la información importante. ¡Disfruta el evento!</p>
            </div>
            <div id="qr-data-container" class="qr-data">
                </div>
        </div>

    </div>
</div>

<?php
include 'plantilla/footer.php';
?>

<script>
    const userId = <?php echo json_encode($user_id); ?>;
    const boletosList = document.getElementById('boletos-list');
    const loadingMessage = document.getElementById('loading-message');
    const paginationContainer = document.getElementById('pagination-container');
    const dateFilter = document.getElementById('date-filter');
    const resetBtn = document.getElementById('reset-btn');
    const qrModal = document.getElementById('qrModal');
    const qrImage = document.getElementById('qr-image');
    const modalTitle = document.getElementById('modal-title');
    const qrDataContainer = document.getElementById('qr-data-container');
    const toggleQrBtn = document.getElementById('toggle-qr-btn');
    const qrDisplayMessage = document.getElementById('qr-display-message'); // Nuevo elemento
    const boletosPorPagina = 3;

    let allBoletos = [];
    let filteredBoletos = [];
    let currentPage = 1;
    let currentTicketData = null;
    let isBoleto = true;

    async function fetchBoletos() {
        loadingMessage.style.display = 'block';
        try {
            const response = await fetch('../../apis/usuario/compras/consultarBoletosComprados.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    usr_id: userId
                })
            });
            const data = await response.json();

            loadingMessage.style.display = 'none';

            if (data.success && data.compras.length > 0) {
                // Decodificar los QR para cada boleto de forma automática
                await Promise.all(data.compras.map(async (compra) => {
                    compra.decodedQR = await decodeQRCodeFromImage(compra.com_qr);
                    if (compra.com_ruta_qr_parq) {
                        compra.decodedParkingQR = await decodeQRCodeFromImage(compra.com_ruta_qr_parq);
                    }
                }));

                allBoletos = data.compras;
                filteredBoletos = allBoletos;
                renderBoletos();
            } else {
                boletosList.innerHTML = `
                    <div class="friendly-message">
                        ¡Parece que aún no tienes boletos! 🎟️
                        <p>Cuando compres boletos para un evento, los verás aquí. ¡Anímate a explorar y encontrar tu próximo plan!</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error al obtener los boletos:', error);
            loadingMessage.innerHTML = `
                <div class="friendly-message">
                    Ups... Algo salió mal. 😥
                    <p>No pudimos cargar tus boletos en este momento. Por favor, intenta de nuevo más tarde.</p>
                </div>
            `;
        }
    }

    async function decodeQRCodeFromImage(imageUrl) {
        if (!imageUrl) return 'No disponible';

        try {
            const img = new Image();
            img.crossOrigin = "Anonymous";
            img.src = imageUrl;
            await new Promise(resolve => img.onload = resolve);
            
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.width = img.width;
            canvas.height = img.height;
            context.drawImage(img, 0, 0, img.width, img.height);
            
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height);
            
            return code ? code.data : 'QR no válido';
        } catch (error) {
            console.error('Error al decodificar el QR:', error);
            return 'Error al leer QR';
        }
    }

    function renderBoletos() {
        boletosList.innerHTML = '';
        paginationContainer.innerHTML = '';

        if (filteredBoletos.length === 0) {
            boletosList.innerHTML = `
                <div class="friendly-message">
                    ¡Oh, vaya! No encontramos boletos para esta fecha. 🤔
                    <p>Intenta con otra fecha o <a href="#" id="clear-filter-link" style="color: var(--secondary-color);">limpia el filtro</a> para ver todos tus boletos de nuevo.</p>
                </div>
            `;
            document.getElementById('clear-filter-link').addEventListener('click', (e) => {
                e.preventDefault();
                dateFilter.value = '';
                filterBoletosByDate();
            });
            return;
        }

        filteredBoletos.forEach((compra, index) => {
            let paymentDetails = `<p><strong>Método de pago:</strong> ${compra.com_met}</p>`;
            if (compra.com_met === 'transferencia') {
                paymentDetails += `<p><strong>Número de transacción:</strong> ${compra.com_num_transf}</p>`;
            }
            
            const card = document.createElement('div');
            card.className = 'boleto-card';
            card.dataset.compra = JSON.stringify(compra);
            
            card.innerHTML = `
                <div class="boleto-card-header">
                    <img src="${compra.evt_img}" alt="${compra.evt_tit}">
                    <div class="overlay">
                        <span class="overlay-text"><i class="fas fa-eye me-2"></i> Ver boletos</span>
                    </div>
                </div>
                <div class="boleto-card-body">
                    <h4>${compra.evt_tit}</h4>
                    <p><strong>Fecha de compra:</strong> ${new Date(compra.com_fec).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    <p><strong>Cantidad de boletos:</strong> ${compra.com_can_bol}</p>
                    ${paymentDetails}
                    <p class="precio"><strong>Precio total:</strong> $${parseFloat(compra.com_pre_tot).toFixed(2)}</p>
                </div>
            `;
            boletosList.appendChild(card);
        });

        renderPage(currentPage);
        renderPagination();

        boletosList.querySelectorAll('.boleto-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('a')) {
                    e.stopPropagation();
                    return;
                }
                const compraData = JSON.parse(card.dataset.compra);
                openModal(compraData);
            });
        });
    }

    function formatQRData(data) {
        if (!data || data === 'QR no válido' || data === 'Error al leer QR') {
            return `<p><strong>Contenido del QR:</strong> ${data}</p>`;
        }
        
        // Expresión regular para encontrar los pares clave: valor
        const pairs = data.split(/(?=\s(?:ID|Parqueadero|Usuario|Evento|Lugar|Cantidad|Total|Método|N°|Reserva|Puesto|Fecha)[^:]*:\s)/);
        
        let formattedText = '';
        pairs.forEach(pair => {
            // Reemplaza "Clave:" por "<strong>Clave:</strong>"
            const styledPair = pair.replace(/^([^:]+):/, '<strong>$1:</strong>');
            formattedText += `<p>${styledPair.trim()}</p>`;
        });
        
        return formattedText;
    }

    function renderPage(page) {
        const cards = boletosList.querySelectorAll('.boleto-card');
        const startIndex = (page - 1) * boletosPorPagina;
        const endIndex = startIndex + boletosPorPagina;

        cards.forEach((card, index) => {
            if (index >= startIndex && index < endIndex) {
                card.classList.add('visible');
            } else {
                card.classList.remove('visible');
            }
        });
        currentPage = page;
    }

    function renderPagination() {
        paginationContainer.innerHTML = '';
        const totalPages = Math.ceil(filteredBoletos.length / boletosPorPagina);
        
        if (totalPages <= 1) return;

        const prevBtn = document.createElement('button');
        prevBtn.className = 'pagination-btn';
        prevBtn.textContent = 'Anterior';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                renderPage(currentPage - 1);
                renderPagination();
            }
        });
        if (currentPage === 1) prevBtn.classList.add('disabled');
        paginationContainer.appendChild(prevBtn);

        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = 'pagination-btn';
            pageBtn.textContent = i;
            if (i === currentPage) {
                pageBtn.classList.add('active');
            }
            pageBtn.addEventListener('click', () => {
                renderPage(i);
                renderPagination();
            });
            paginationContainer.appendChild(pageBtn);
        }

        const nextBtn = document.createElement('button');
        nextBtn.className = 'pagination-btn';
        nextBtn.textContent = 'Siguiente';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                renderPage(currentPage + 1);
                renderPagination();
            }
        });
        if (currentPage === totalPages) nextBtn.classList.add('disabled');
        paginationContainer.appendChild(nextBtn);
    }

    function filterBoletosByDate() {
        const selectedDate = dateFilter.value;
        if (selectedDate) {
            filteredBoletos = allBoletos.filter(compra => {
                const compraDate = new Date(compra.com_fec).toISOString().split('T')[0];
                return compraDate === selectedDate;
            });
        } else {
            filteredBoletos = allBoletos;
        }
        currentPage = 1;
        renderBoletos();
    }

    function openModal(compra) {
        currentTicketData = compra;
        isBoleto = true;
        updateModalContent();
        qrModal.style.display = 'flex';
        toggleQrBtn.style.display = compra.com_ruta_qr_parq ? 'block' : 'none';
    }

    function updateModalContent() {
        if (!currentTicketData) return;
        const url = isBoleto ? currentTicketData.com_qr : currentTicketData.com_ruta_qr_parq;
        const decodedData = isBoleto ? currentTicketData.decodedQR : currentTicketData.decodedParkingQR;
        
        qrImage.src = url;
        modalTitle.textContent = isBoleto ? 'Tu Boleto para el Evento' : 'Tu Boleto de Parqueadero';
        toggleQrBtn.textContent = isBoleto ? 'Ver Boleto de Parqueadero' : 'Ver Boleto del Evento';
        
        // Mensajes amigables
        if (isBoleto) {
            qrDisplayMessage.textContent = '¡Listo para el evento! Presenta este código en la entrada principal.';
        } else {
            qrDisplayMessage.textContent = '¡Tu espacio te espera! Presenta este código en la entrada del parqueadero.';
        }

        qrDataContainer.innerHTML = formatQRData(decodedData);
    }

    function toggleQR() {
        isBoleto = !isBoleto;
        updateModalContent();
    }

    function downloadQR() {
        const url = qrImage.src;
        const a = document.createElement('a');
        a.href = url;
        a.download = isBoleto ? `boleto_${currentTicketData.com_num_bol}.png` : `parqueadero_${currentTicketData.com_bol_id}.png`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    function closeModal() {
        qrModal.style.display = 'none';
        currentTicketData = null;
    }

    window.onclick = function(event) {
        if (event.target == qrModal) {
            closeModal();
        }
    }

    dateFilter.addEventListener('change', filterBoletosByDate);
    resetBtn.addEventListener('click', () => {
        dateFilter.value = '';
        filterBoletosByDate();
    });

    document.addEventListener('DOMContentLoaded', fetchBoletos);
</script>