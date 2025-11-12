<?php
// compra_visita.php
// Este script permite a un visitante simular la compra de entradas y lo redirige a la página de login para concretar la compra.

// Incluye la conexión a la base de datos
include '../../conexion.php';

// Verifica si se proporcionó un ID de evento en la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: menu.php");
    exit();
}

$evento_id = $_GET['id'];

// --- Consultas a la base de datos ---

// 1. Consulta para obtener todos los detalles del evento de forma segura
$query_evento = 'SELECT evt_tit, evt_des, evt_fec, evt_lug, evt_pre, evt_capacidad, evt_disponibles, evt_img FROM tbl_evento WHERE evt_id = $1 AND evt_est = \'activo\'';
$prep_evento = pg_prepare($conn, 'event_query', $query_evento);
if (!$prep_evento) {
    die("Error al preparar la consulta de evento: " . pg_last_error($conn));
}
$result_evento = pg_execute($conn, 'event_query', [$evento_id]);
$evento = pg_fetch_assoc($result_evento);

// Si no se encuentra el evento, redirige a la página principal
if (!$evento) {
    header("Location: menu.php");
    exit();
}

// 2. Consulta para obtener todos los parqueaderos que tienen puestos disponibles
$query_parqueaderos = '
    SELECT T1.par_id, T1.par_nom
    FROM tbl_parqueadero T1
    JOIN tbl_puestos_parqueadero T2 ON T1.par_id = T2.pue_id_par
    WHERE T2.pue_est = \'disponible\'
    GROUP BY T1.par_id
    ORDER BY T1.par_nom ASC
';
$result_parqueaderos = pg_query($conn, $query_parqueaderos);
$parqueaderos = pg_fetch_all($result_parqueaderos);

// 3. Consulta para obtener todos los puestos de parqueadero disponibles
$query_puestos = '
    SELECT pue_id, pue_num, pue_id_par
    FROM tbl_puestos_parqueadero 
    WHERE pue_est = \'disponible\'
    ORDER BY pue_id_par, pue_num ASC
';
$result_puestos = pg_query($conn, $query_puestos);
$puestos = pg_fetch_all($result_puestos);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprar Entradas para <?php echo htmlspecialchars($evento['evt_tit']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* Estilos del encabezado */
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow-x: hidden;
            font-family: Arial, sans-serif;
        }

        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
            filter: blur(6px) brightness(0.5);
            transform: scale(1.05);
        }

        .main-container {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        /* Estilos de la barra de navegación */
        .navbar {
            display: flex;
            justify-content: flex-start;
            padding: 1px;
            gap: 2px;
            align-items: center;
        }

        .logo-container {
            margin-right: 120px;
            margin-left: 200px;
        }

        .logo-container img {
            width: 190px;
            height: auto;
            display: block;
        }

        .navbar-item {
            text-decoration: none;
            color: white;
            font-size: 1.2em;
            padding: 20px 20px;
            border-radius: 100px;
            background-color: rgba(31, 41, 58, 0.7);
            transition: background-color 0.3s, color 0.3s;
        }

        .navbar-item:hover,
        .navbar-item:focus {
            background-color: #e60029;
            color: white;
        }

        .navbar-item.active {
            background-color: #0176c7;
            color: white;
        }

        .navbar-item:active {
            transform: scale(0.98);
        }

        /* Contenido de la página de compra */
        
        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding: 20px;
            width: 100%;
            max-width: 1200px;
        }

        .content-section {
            display: flex;
            flex-direction: row;
            gap: 20px;
            justify-content: center;
            align-items: flex-start;
            flex-wrap: nowrap;
            width: 100%;
        }

        .event-info-section {
            flex: 1;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            padding: 15px;
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: 8px;
            backdrop-filter: blur(4px);
            text-align: left;
            position: relative;
        }

        .buy-section {
            flex: 0.8;
            min-width: 300px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            position: relative;
        }

        .event-title-container {
            width: 100%;
            margin-bottom: 15px;
            text-align: center;
        }

        .event-title-container h1 {
            font-size: 2em;
            color: white;
            margin: 0;
            line-height: 1.2;
        }

        .event-main-content {
            display: flex;
            flex-direction: row;
            gap: 20px;
            align-items: flex-start;
        }

        .movie-poster {
            flex-shrink: 0;
            width: 250px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5);
        }

        .movie-poster img {
            width: 100%;
            border-radius: 8px;
            display: block;
        }

        .movie-details {
            flex: 1;
            text-align: left;
        }

        .movie-details h3 {
            font-size: 1.2em;
            color: white;
            margin-bottom: 10px;
        }

        .movie-details p {
            font-size: 0.9em;
            line-height: 1.4;
            margin-bottom: 15px;
        }

        .details-grid {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 8px;
        }

        .details-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9em;
        }

        .details-item i {
            color: #F94144;
            font-size: 1em;
            flex-shrink: 0;
            width: 20px;
            text-align: center;
        }

        .details-item span {
            font-weight: bold;
            color: #B0B0B0;
            margin-right: 4px;
        }

        .details-item .info-value {
            color: #F8F9FA;
        }

        .countdown-container {
            margin-top: 15px;
            background-color: rgba(249, 65, 68, 0.1);
            border: 1px solid #F94144;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
        }

        .countdown-container p {
            font-size: 0.9em;
            color: #B0B0B0;
            margin-bottom: 8px;
        }

        .countdown {
            display: flex;
            justify-content: center;
            gap: 12px;
            font-size: 1.2em;
            font-weight: bold;
            color: #F94144;
        }

        .countdown div {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 45px;
        }

        .countdown .label {
            font-size: 0.6em;
            font-weight: normal;
            color: #B0B0B0;
            margin-top: 4px;
        }

        /* Estilo base para todas las tarjetas de sección */
        .section-card {
            background-color: #2C2C2C;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
            margin-bottom: 15px;
        }

        h2 {
            color: white;
            margin-bottom: 10px;
            border-bottom: 2px solid #F94144;
            padding-bottom: 6px;
            font-size: 1.2em;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .quantity-selector button {
            background: none;
            border: 2px solid #F94144;
            color: #F94144;
            font-size: 1em;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }

        .quantity-selector button:hover {
            background-color: #F94144;
            color: white;
        }

        .quantity-selector span {
            font-size: 1.2em;
            font-weight: bold;
        }

        .switch-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 15px;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 45px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #F94144;
        }

        input:checked+.slider:before {
            transform: translateX(20px);
        }

        .parking-options, .payment-options {
            margin-top: 15px;
        }

        .parking-grid, .payment-grid {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .option-button {
            background-color: #3B3B3B;
            color: white;
            border: 2px solid transparent;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s, border-color 0.3s;
            font-size: 0.9em;
        }

        .option-button:hover {
            background-color: #555;
        }

        .option-button.selected {
            background-color: #F94144;
            border-color: white;
        }

        .total-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #333;
        }

        .total-section span {
            font-size: 1.2em;
            font-weight: bold;
        }

        .total-price {
            color: #F94144;
        }

        .purchase-btn {
            background-color: #F94144;
            color: white;
            border: none;
            padding: 10px;
            font-size: 1em;
            font-weight: bold;
            border-radius: 40px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 15px;
        }

        .purchase-btn:hover {
            background-color: #F3722C;
        }

        .line-divider {
            width: 80%;
            height: 2px;
            background-color: rgba(255, 255, 255, 0.5);
            margin: 25px auto;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .line-divider::before,
        .line-divider::after {
            content: '';
            width: 12px;
            height: 12px;
            background-color: white;
            border-radius: 50%;
        }

        .section-title {
            font-size: 2em;
            margin: 15px 0 5px 0;
            text-align: center;
        }
        
        .sponsors-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 252px;
            padding: 20px 0;
            margin-top: 25px;
        }

        .sponsor-item {
            text-align: center;
        }

        .sponsor-item img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0176c7;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s ease;
        }

        .sponsor-item img:hover {
            transform: scale(1.05);
        }

        .sponsor-item p {
            font-size: 1em;
            font-weight: bold;
            color: #FFD700;
            margin-top: 10px;
        }

        @media (max-width: 900px) {
            .sponsors-container {
                flex-direction: column;
                gap: 25px;
            }
        }
    </style>
</head>

<body>
    <video autoplay muted loop class="video-background">
        <source src="../imagenes/video/fondoinfo.mp4" type="video/mp4">
        Tu navegador no soporta videos HTML5.
    </video>

    <div class="main-container">
        <div class="navbar">
            <div class="logo-container">
                <img src="../imagenes/logoempresa.png" alt="Logo Empresa">
            </div>
            <a href="../../index.php" class="navbar-item">Inicio</a>
            <a href="../../sesion/login.php" class="navbar-item active">Iniciar Sesión</a>
            <a href="galeria.php" class="navbar-item">Galería</a>
            <a href="nosotros.php" class="navbar-item">Sobre Nosotros</a>
            <a href="contacto.php" class="navbar-item">Contacto</a>
        </div>
        
        <div class="content-wrapper">
            <div class="content-section">
                <div class="event-info-section">
                    <div class="event-title-container">
                        <h1><?php echo htmlspecialchars($evento['evt_tit']); ?></h1>
                    </div>
    
                    <div class="event-main-content">
                        <div class="movie-poster">
                            <img src="../../<?php echo htmlspecialchars($evento['evt_img']); ?>"
                                alt="Portada del evento: <?php echo htmlspecialchars($evento['evt_tit']); ?>">
                        </div>
                        <div class="movie-details">
                            <h3>Detalles del Evento</h3>
                            <p><?php echo nl2br(htmlspecialchars($evento['evt_des'])); ?></p>
                            <div class="details-grid">
                                <div class="details-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Fecha y Hora:</span> <span
                                        class="info-value"><?php echo date('d-m-Y H:i', strtotime($evento['evt_fec'])); ?></span>
                                </div>
                                <div class="details-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Ubicación:</span> <span
                                        class="info-value"><?php echo htmlspecialchars($evento['evt_lug']); ?></span>
                                </div>
                                <div class="details-item">
                                    <i class="fas fa-dollar-sign"></i>
                                    <span>Costo por Entrada:</span> <span
                                        class="info-value">$<?php echo number_format($evento['evt_pre'], 2); ?></span>
                                </div>
                                <div class="details-item">
                                    <i class="fas fa-users"></i>
                                    <span>Entradas Disponibles:</span> <span
                                        class="info-value"><?php echo htmlspecialchars($evento['evt_disponibles']); ?> de
                                        <?php echo htmlspecialchars($evento['evt_capacidad']); ?></span>
                                </div>
                            </div>
                            <div class="countdown-container">
                                <p>¡El evento inicia en:</p>
                                <div id="countdown" class="countdown">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
    
                <div class="buy-section">
                    <form id="purchase-form">
                        <input type="hidden" name="evento_id" value="<?php echo htmlspecialchars($evento_id); ?>">
    
                        <div class="section-card">
                            <h2>Elige tus Entradas</h2>
                            <p style="color: #B0B0B0; text-align: center;">Selecciona cuántas entradas deseas
                                comprar. ¡Asegúrate de que no superen las disponibles!</p>
                            <div class="quantity-selector">
                                <button type="button" id="decrease">-</button>
                                <span id="quantity">1</span>
                                <input type="hidden" name="cantidad_boletos" id="cantidad_boletos_input" value="1">
                                <button type="button" id="increase">+</button>
                            </div>
                        </div>
    
                        <div class="section-card">
                            <h2>Opciones de Parqueadero</h2>
                            <div class="switch-container">
                                <span>¿Necesitas un puesto de parqueadero?</span>
                                <label class="switch">
                                    <input type="checkbox" id="parking-switch" name="quiere_parqueadero">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div id="parking-details" style="display: none;">
                                <p style="color: #66ff66; text-align: center; margin-top: 10px;">
                                    El servicio de parqueadero tiene un costo adicional de <span
                                        id="parking-price">$5.00</span>.
                                </p>
                                <div class="parking-options">
                                    <p>Selecciona un parqueadero disponible:</p>
                                    <div id="parking-grid" class="parking-grid">
                                        <?php if (!empty($parqueaderos)): ?>
                                            <?php foreach ($parqueaderos as $parqueadero): ?>
                                                <button type="button" class="option-button"
                                                    data-id="<?php echo htmlspecialchars($parqueadero['par_id']); ?>"
                                                    data-name="<?php echo htmlspecialchars($parqueadero['par_nom']); ?>">
                                                    <?php echo htmlspecialchars($parqueadero['par_nom']); ?>
                                                </button>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p>No hay parqueaderos disponibles en este momento.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="parking-options">
                                    <p>Elige tu puesto:</p>
                                    <div id="puesto-grid" class="parking-grid">
                                        <?php if (!empty($puestos)): ?>
                                            <?php foreach ($puestos as $puesto): ?>
                                                <button type="button" class="option-button"
                                                    data-pue-id="<?php echo htmlspecialchars($puesto['pue_id']); ?>"
                                                    data-par-id="<?php echo htmlspecialchars($puesto['pue_id_par']); ?>"
                                                    style="display: none;">
                                                    <?php echo htmlspecialchars($puesto['pue_num']); ?>
                                                </button>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p>No hay puestos de parqueadero disponibles.</p>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" name="puesto_parqueadero" id="puesto_parqueadero_input">
                                </div>
                            </div>
                        </div>
    
                        <div class="section-card">
                            <h2>Selecciona tu Método de Pago</h2>
                            <div class="payment-grid">
                                <button type="button" class="option-button" data-method="transferencia"
                                    id="btn-transferencia">Transferencia Bancaria</button>
                                <button type="button" class="option-button" data-method="pago_en_lugar"
                                    id="btn-pago-lugar">Pagar en el Lugar</button>
                            </div>
                            <input type="hidden" name="metodo_pago" id="metodo_pago_input">
                            <div id="transfer-info" style="display: none; margin-top: 15px;">
                                <div
                                    style="background-color: white; padding: 15px; border-radius: 10px; text-align: center;">
                                    <img src="../imagenes/Pichincha.png" alt="Banco Pichincha"
                                        style="height: 40px; margin-bottom: 10px;">
                                    <p style="color: black; margin: 0;">
                                        <span style="font-weight: bold;">Número de Cuenta:</span> 220202020 (Banco
                                        Pichincha)
                                    </p>
                                    <p style="color: black; margin: 5px 0 0;">
                                        Por favor, realiza la transferencia y luego ingresa el número de referencia.
                                    </p>
                                    <input type="text" id="referencia-transferencia" name="referencia_transferencia"
                                        placeholder="Número de referencia de transferencia"
                                        style="width: 80%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; margin-top: 10px;">
                                </div>
                            </div>
                        </div>
    
                        <div class="section-card" id="total-card-original-position">
                            <div class="total-section">
                                <span>Total a Pagar:</span>
                                <span class="total-price"
                                    id="total-price">$<?php echo number_format($evento['evt_pre'], 2); ?></span>
                            </div>
                            <button type="submit" class="purchase-btn">Confirmar y Pagar</button>
                            <br><br>
                            <a href="../../index.php" class="purchase-btn">
                                Cancelar 
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="line-divider"></div>
            <h2 class="section-title">Nuestros Patrocinadores</h2>
            <div class="sponsors-container">
                <div class="sponsor-item">
                    <img src="https://orquideatech.com/wp-content/uploads/2021/07/nws-julio1-26.png"
                        alt="Logo de Empresa A">
                    <p>Empresa A</p>
                </div>
                <div class="sponsor-item">
                    <img src="https://orquideatech.com/wp-content/uploads/2021/07/nws-julio1-26.png"
                        alt="Logo de Comercio B">
                    <p>Comercio B</p>
                </div>
                <div class="sponsor-item">
                    <img src="https://orquideatech.com/wp-content/uploads/2021/07/nws-julio1-26.png"
                        alt="Logo de Tienda C">
                    <p>Tienda C</p>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Variables PHP a JS
        const PRECIO_BOLETO = <?php echo json_encode($evento['evt_pre']); ?>;
        const BOLETOS_DISPONIBLES = <?php echo json_encode($evento['evt_disponibles']); ?>;
        const EVENTO_ID = <?php echo json_encode($evento_id); ?>;
        const PARQUEADERO_PRECIO = 5.00;

        // Elementos del DOM
        const quantityEl = document.getElementById('quantity');
        const quantityInput = document.getElementById('cantidad_boletos_input');
        const increaseBtn = document.getElementById('increase');
        const decreaseBtn = document.getElementById('decrease');
        const totalEl = document.getElementById('total-price');
        const purchaseForm = document.getElementById('purchase-form');
        const parkingSwitch = document.getElementById('parking-switch');
        const parkingDetails = document.getElementById('parking-details');
        const parkingButtons = document.querySelectorAll('#parking-grid .option-button');
        const puestoButtons = document.querySelectorAll('#puesto-grid .option-button');
        const puestoInput = document.getElementById('puesto_parqueadero_input');
        const methodButtons = document.querySelectorAll('.payment-grid .option-button');
        const methodInput = document.getElementById('metodo_pago_input');
        const transferInfo = document.getElementById('transfer-info');
        const countdownEl = document.getElementById('countdown');

        // Variables de estado
        let cantidadBoletos = 1;
        let quiereParqueadero = false;
        let parqueaderoSeleccionado = null;
        let puestoSeleccionado = null;
        let metodoPagoSeleccionado = null;

        // Actualiza el precio total
        function updateTotalPrice() {
            let total = cantidadBoletos * PRECIO_BOLETO;
            if (quiereParqueadero) {
                total += PARQUEADERO_PRECIO;
            }
            totalEl.textContent = `$${total.toFixed(2)}`;
        }

        // Lógica de cantidad de boletos
        increaseBtn.addEventListener('click', () => {
            if (cantidadBoletos < BOLETOS_DISPONIBLES) {
                cantidadBoletos++;
                quantityEl.textContent = cantidadBoletos;
                quantityInput.value = cantidadBoletos;
                updateTotalPrice();
            } else {
                alert('¡Atención! Has alcanzado el número máximo de boletos disponibles.');
            }
        });

        decreaseBtn.addEventListener('click', () => {
            if (cantidadBoletos > 1) {
                cantidadBoletos--;
                quantityEl.textContent = cantidadBoletos;
                quantityInput.value = cantidadBoletos;
                updateTotalPrice();
            }
        });

        // Lógica del switch de parqueadero
        parkingSwitch.addEventListener('change', (e) => {
            quiereParqueadero = e.target.checked;
            parkingDetails.style.display = quiereParqueadero ? 'block' : 'none';

            if (!quiereParqueadero) {
                parqueaderoSeleccionado = null;
                puestoSeleccionado = null;
                puestoInput.value = '';
                parkingButtons.forEach(btn => btn.classList.remove('selected'));
                puestoButtons.forEach(btn => btn.classList.remove('selected'));
                puestoButtons.forEach(btn => btn.style.display = 'none');
            } else {
                if (parkingButtons.length > 0) {
                    parkingButtons[0].click();
                } else {
                    alert('No hay parqueaderos disponibles en este momento. Intenta de nuevo más tarde.');
                    parkingSwitch.checked = false;
                    quiereParqueadero = false;
                }
            }
            updateTotalPrice();
        });

        // Lógica de selección de parqueadero
        parkingButtons.forEach(button => {
            button.addEventListener('click', () => {
                parkingButtons.forEach(btn => btn.classList.remove('selected'));
                button.classList.add('selected');
                parqueaderoSeleccionado = button.dataset.id;
                puestoSeleccionado = null;
                puestoInput.value = '';
                updateTotalPrice();
                puestoButtons.forEach(btn => {
                    btn.classList.remove('selected');
                    if (btn.dataset.parId === parqueaderoSeleccionado) {
                        btn.style.display = 'inline-block';
                    } else {
                        btn.style.display = 'none';
                    }
                });
            });
        });

        // Lógica de selección de puesto
        puestoButtons.forEach(button => {
            button.addEventListener('click', () => {
                puestoButtons.forEach(btn => btn.classList.remove('selected'));
                button.classList.add('selected');
                puestoSeleccionado = button.dataset.pueId;
                puestoInput.value = puestoSeleccionado;
                updateTotalPrice();
            });
        });

        // Lógica de selección de método de pago
        methodButtons.forEach(button => {
            button.addEventListener('click', () => {
                methodButtons.forEach(btn => btn.classList.remove('selected'));
                button.classList.add('selected');
                metodoPagoSeleccionado = button.dataset.method;
                methodInput.value = metodoPagoSeleccionado;
                transferInfo.style.display = metodoPagoSeleccionado === 'transferencia' ? 'block' : 'none';
            });
        });

        // Manejo del formulario de compra
        purchaseForm.addEventListener('submit', (e) => {
            e.preventDefault();

            // Validación de datos
            if (metodoPagoSeleccionado === null) {
                alert("Por favor, selecciona un método de pago para continuar.");
                return;
            }
            if (quiereParqueadero && puestoSeleccionado === null) {
                alert("Si elegiste parqueadero, por favor, selecciona un puesto disponible.");
                return;
            }
            const referenciaTransferencia = document.getElementById('referencia-transferencia').value.trim();
            if (metodoPagoSeleccionado === 'transferencia' && referenciaTransferencia === '') {
                alert("Por favor, ingresa el número de referencia de tu transferencia.");
                return;
            }
            
            // Si todo está bien, le pide iniciar sesión
            alert("Para continuar con la compra, por favor, inicia sesión o regístrate.");
            window.location.href = "../../sesion/login.php";
        });
        
        // --- Lógica del Contador Regresivo ---
        const eventDate = new Date('<?php echo $evento['evt_fec']; ?>').getTime();

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = eventDate - now;

            if (distance < 0) {
                countdownEl.innerHTML = "<p>¡El evento ya ha comenzado o ha finalizado!</p>";
                clearInterval(countdownInterval);
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            countdownEl.innerHTML = `
            <div>${days}<span class="label">Días</span></div>
            <div>${hours}<span class="label">Horas</span></div>
            <div>${minutes}<span class="label">Minutos</span></div>
            <div>${seconds}<span class="label">Segundos</span></div>
        `;
        }

        const countdownInterval = setInterval(updateCountdown, 1000);
        updateCountdown();

        // Inicializa el precio total y los puestos de parqueadero
        updateTotalPrice();
        puestoButtons.forEach(btn => btn.style.display = 'none');
    </script>
</body>

</html>