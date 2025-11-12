<?php
session_start();


// ELIMINADO: include 'plantilla/header.php'; 
// No es necesario incluirlo aquí si ya tienes la cabecera HTML en el archivo.
// De hecho, en el código que me pasaste antes, no estaba esa línea.
// La voy a mantener si está en tu versión local, pero la estructura de la cabecera (<header>...</header>) es la que se debe eliminar más abajo.

// Incluye la conexión a la base de datos
include '../../conexion.php';

// Verifica si se proporcionó un ID de evento en la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: menu.php");
    exit();
}

// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'usuario') {
    // Si no está logueado, lo redirige para que inicie sesión
    header("Location: ../../sesion/login.php");
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

// Si el header.php incluye la apertura de <html> y <body>, esta línea se omite.
// Asumo que tu header.php solo incluye el inicio del HTML, el <head> y el inicio del <body>.

// Inclusión de la plantilla header.php
include 'plantilla/header.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprar Entradas para <?php echo htmlspecialchars($evento['evt_tit']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* Estilos de la cartelera de eventos */
        :root {
            --primary-color: #fa4647;
            --secondary-color: #0078c7;
            --dark-bg: #1A1A1A;
            --card-bg: #2C2C2C;
            --text-color: #F8F9FA;
            --light-text: #B0B0B0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0e172b;
            color: var(--text-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-x: hidden;
        }

        /* --- INICIO DE ESTILOS DE CABECERA Y BOTONES ELIMINADOS --- */
        /* Mantengo los estilos por si la hoja de estilos principal los necesita,
           pero la estructura HTML de la cabecera repetida ya no está. */

        header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 50px;
            box-sizing: border-box;
            background-color: transparent;
            position: relative;
            z-index: 1000;
            height: 150px;
            overflow: hidden;
            animation: fadeInDown 1s ease;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
            opacity: 0.8;
            animation: zoomVideo 20s infinite alternate ease-in-out;
        }

        @keyframes zoomVideo {
            from { transform: scale(1); }
            to { transform: scale(1.1); }
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            height: 100%;
            position: relative;
            z-index: 1;
        }

        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 0 20px;
            transition: transform 0.5s ease;
        }

        .logo-container img {
            height: 135px;
            margin-bottom: 5px;
            filter: drop-shadow(0 0 8px rgba(255,255,255,0.5));
            transition: transform 0.5s ease, filter 0.5s ease;
        }
        
        .logo-container img:hover {
            transform: scale(1.1) rotate(-3deg);
            filter: drop-shadow(0 0 15px rgba(255,255,255,0.8));
        }

        .menu-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .menu-links a,
        .menu-links button {
            text-decoration: none;
            color: white;
            font-size: 1.1em;
            padding: 12px 20px;
            border-radius: 100px;
            background-color: rgba(31, 41, 58, 0.7);
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-weight: bold;
            white-space: nowrap;
            position: relative;
            overflow: hidden;
        }
        
        .menu-links a:hover,
        .menu-links button:hover {
            background-color: #e60029;
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(230,0,41,0.6);
        }

        .menu-links a:active,
        .menu-links button:active {
            transform: scale(0.95);
        }

        .menu-links a::after,
        .menu-links button::after {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: all 0.5s;
        }

        .menu-links a:hover::after,
        .menu-links button:hover::after {
            left: 100%;
        }
        /* --- FIN DE ESTILOS DE CABECERA Y BOTONES ELIMINADOS --- */

        /* Contenedor principal del cuerpo */
        .main-container {
            width: 100%;
            max-width: 1200px;
            padding: 15px;
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            overflow-x: hidden;
            font-size: 0.9em;
            flex-grow: 1;
        }

        /* Contenido de la página de compra */
        
        .content-section {
            display: flex;
            flex-direction: row;
            gap: 20px;
            justify-content: center;
            align-items: flex-start;
            flex-wrap: nowrap;
            width: 100%;
            margin-top: 20px;
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
            color: var(--primary-color);
            font-size: 1em;
            flex-shrink: 0;
            width: 20px;
            text-align: center;
        }

        .details-item span {
            font-weight: bold;
            color: var(--light-text);
            margin-right: 4px;
        }

        .details-item .info-value {
            color: var(--text-color);
        }

        .countdown-container {
            margin-top: 15px;
            background-color: rgba(249, 65, 68, 0.1);
            border: 1px solid var(--primary-color);
            padding: 10px;
            border-radius: 6px;
            text-align: center;
        }

        .countdown-container p {
            font-size: 0.9em;
            color: var(--light-text);
            margin-bottom: 8px;
        }

        .countdown {
            display: flex;
            justify-content: center;
            gap: 12px;
            font-size: 1.2em;
            font-weight: bold;
            color: var(--primary-color);
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
            color: var(--light-text);
            margin-top: 4px;
        }

        /* Estilo base para todas las tarjetas de sección */
        .section-card {
            background-color: var(--card-bg);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
            margin-bottom: 15px;
        }

        h2 {
            color: white;
            margin-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
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
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            font-size: 1em;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }

        .quantity-selector button:hover {
            background-color: var(--primary-color);
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
            background-color: var(--primary-color);
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
            background-color: var(--primary-color);
            border-color: white;
        }
        
        .option-button.occupied {
            background-color: #888;
            border-color: #555;
            cursor: not-allowed;
            opacity: 0.7;
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
            color: var(--primary-color);
        }

        .purchase-btn, .cancel-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 5px;
            font-size: 1em;
            font-weight: bold;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 15px;
        }

        .purchase-btn:hover {
            background-color: #0077c65f;
        }
        
        .cancel-btn {
            background-color: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            width: 100%;
            text-align: center;
            text-decoration: none;
            display: block;
        }

        .cancel-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* Estilos del footer */
        footer {
            width: 100%;
            padding: 30px 50px;
            box-sizing: border-box;
            margin-top: auto;
            position: relative;
            overflow: hidden;
            border-top: 2px solid #3d4a66;
            text-align: center;
        }

        .footer-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .section-title {
            color: var(--text-color);
            font-size: 2em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 25px;
            position: relative;
            display: inline-block;
            padding-bottom: 10px;
            animation: fadeIn 1.5s ease;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 70px;
            height: 4px;
            background-color: var(--primary-color);
            border-radius: 5px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .sponsors-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 252px;
            padding: 20px 0;
            margin-top: 25px;
            width: 100%;
        }

        .sponsor-item {
            text-align: center;
            animation: fadeInUp 1.5s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .sponsor-item img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0176c7;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s ease;
            filter: drop-shadow(0 0 8px rgba(255,255,255,0.5));
        }

        .sponsor-item img:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 0 15px rgba(255,255,255,0.8));
        }

        .sponsor-item p {
            font-size: 1em;
            font-weight: bold;
            color: #FFD700;
            margin-top: 10px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }
        
        .footer-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
            opacity: 0.8;
            animation: zoomVideo 20s infinite alternate ease-in-out;
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
    <main class="main-container">
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
                <form id="purchase-form" action="procesar_compra.php" method="POST">
                    <input type="hidden" name="evento_id" value="<?php echo htmlspecialchars($evento_id); ?>">
    
                    <div class="section-card">
                        <h2>Elige tus Entradas</h2>
                        <p style="color: var(--light-text); text-align: center;">Selecciona cuántas entradas deseas
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
                            <span>¿Necesitas un puesto de parqueadero? (Gratuito)</span>
                            <label class="switch">
                                <input type="checkbox" id="parking-switch" name="quiere_parqueadero">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div id="parking-details" style="display: none;">
                            <p style="color: #66ff66; text-align: center; margin-top: 10px;">
                                El servicio de parqueadero es **gratuito**.
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
                        <a href="menu.php" class="cancel-btn">
                            Cancelar Compra
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>


<?php
include '../plantilla/plantilla_info/footer.php';
?>
    
    <script>
        // Variables PHP a JS
        const PRECIO_BOLETO = parseFloat('<?php echo $evento['evt_pre']; ?>');
        const BOLETOS_DISPONIBLES = parseInt('<?php echo $evento['evt_disponibles']; ?>');
        const EVENTO_ID = '<?php echo $evento_id; ?>';
        const PUESTOS_DATA = JSON.parse('<?php echo json_encode($puestos); ?>');
        const PARQUEADERO_PRECIO = 0.00;

        // --- NUEVA CONSTANTE PARA LOCALSTORAGE ---
        const STORAGE_KEY = 'compraDataPreLogin';
        // --- FIN NUEVA CONSTANTE ---

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

        // Función para actualizar el precio total
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
                    if (!parqueaderoSeleccionado) {
                        parkingButtons[0].click(); 
                    }
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
                // Previene seleccionar un puesto ocupado (si la lógica de ocupación estuviera implementada)
                if (button.classList.contains('occupied')) return; 

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
                updateTotalPrice();
            });
        });

        // Manejo del formulario de compra
        purchaseForm.addEventListener('submit', (e) => {
            // Validación de datos
            if (metodoPagoSeleccionado === null) {
                e.preventDefault();
                alert("Por favor, selecciona un método de pago para continuar.");
                return;
            }
            if (quiereParqueadero && puestoSeleccionado === null) {
                e.preventDefault();
                alert("Si elegiste parqueadero, por favor, selecciona un puesto disponible.");
                return;
            }
            const referenciaTransferencia = document.getElementById('referencia-transferencia').value.trim();
            if (metodoPagoSeleccionado === 'transferencia' && referenciaTransferencia === '') {
                e.preventDefault();
                alert("Por favor, ingresa el número de referencia de tu transferencia.");
                return;
            }
            // Si todo está correcto, el formulario se enviará al action (procesar_compra.php)
        });

        // --- Lógica para cargar los datos guardados de compra pre-login (MODIFICADA) ---
        function loadSavedData() {
            const data = JSON.parse(localStorage.getItem(STORAGE_KEY));

            if (data) {
                // Cargar variables
                cantidadBoletos = data.cantidadBoletos || 1;
                quiereParqueadero = data.quiereParqueadero || false;
                parqueaderoSeleccionado = data.parqueaderoSeleccionado || null;
                puestoSeleccionado = data.puestoSeleccionado || null;
                metodoPagoSeleccionado = data.metodoPagoSeleccionado || null;
                // NUEVA LÓGICA: Cargar referencia de transferencia
                const referenciaTransferencia = data.referenciaTransferencia || ''; 

                // 1. Actualizar el DOM y variables básicas
                quantityEl.textContent = cantidadBoletos;
                quantityInput.value = cantidadBoletos;
                parkingSwitch.checked = quiereParqueadero;
                parkingDetails.style.display = quiereParqueadero ? 'block' : 'none';
                
                // 2. Aplicar selecciones de parqueadero con simulación de click para actualizar el estado visual y lógico
                if (parqueaderoSeleccionado) {
                    parkingButtons.forEach(btn => {
                        if (btn.dataset.id === parqueaderoSeleccionado) btn.click();
                    });
                }       

                // 3. Aplicar selección de puesto
                if (puestoSeleccionado) {
                    puestoButtons.forEach(btn => {
                        if (btn.dataset.pueId === puestoSeleccionado) {
                            btn.classList.add('selected');
                            puestoInput.value = puestoSeleccionado;
                        }
                    });
                }

                // 4. Aplicar selección de método de pago
                if (metodoPagoSeleccionado) {
                    methodButtons.forEach(btn => {
                        if (btn.dataset.method === metodoPagoSeleccionado) btn.click();
                    });
                }
                
                // 5. NUEVA LÓGICA: Aplicar la referencia de transferencia si se seleccionó ese método
                const referenciaTransferenciaEl = document.getElementById('referencia-transferencia');
                if (metodoPagoSeleccionado === 'transferencia' && referenciaTransferenciaEl) {
                    transferInfo.style.display = 'block';
                    referenciaTransferenciaEl.value = referenciaTransferencia;
                }

                updateTotalPrice();
                localStorage.removeItem(STORAGE_KEY); // Limpiar datos de localStorage después de cargar
            } else {
                // Si no hay datos guardados, inicializa el total
                updateTotalPrice(); 
            }
        }
        loadSavedData();
        // --- FIN Lógica para cargar los datos guardados de compra pre-login ---

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

    </script>
</body>

</html>