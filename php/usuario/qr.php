<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: ../../index.php");
    exit;
}

$qr_boleto_url = $_GET['qr_boleto'] ?? null;
$qr_parqueadero_url = $_GET['qr_parqueadero'] ?? null;

if (!$qr_boleto_url) {
    echo "No se encontró el código QR del boleto.";
    exit;
}

// Se define la ruta base relativa del proyecto para las imágenes.
$base_url_relativa_imagenes = '../../';

$full_boleto_url = $base_url_relativa_imagenes . $qr_boleto_url;
$full_parqueadero_url = $qr_parqueadero_url ? $base_url_relativa_imagenes . $qr_parqueadero_url : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Compra Exitosa! - ViveCultura</title>
    <link rel="stylesheet" href="../../css/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0F172A 0%, #1D263B 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }
        .container {
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }
        .qr-card {
            background-color: rgba(29, 38, 59, 0.9);
            border-radius: 25px;
            padding: 1rem 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .qr-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(255,255,255,0.05) 0%, transparent 70%);
            transform: rotate(45deg);
            z-index: 0;
            pointer-events: none;
        }
        .qr-content {
            position: relative;
            z-index: 1;
        }
        .success-icon {
            font-size: 2.2rem;
            color: #28a745;
            margin-bottom: 8px;
            animation: bounceIn 0.8s ease-out;
        }
        h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: #F94144;
            text-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        .main-description {
            font-size: 0.85rem;
            line-height: 1.3;
            margin-bottom: 0.8rem !important;
        }
        .qr-type-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-top: 0.4rem;
            margin-bottom: 0.4rem;
            color: #0178c6;
        }
        .text-white-50 {
            color: rgba(255, 255, 255, 0.75) !important;
            font-size: 0.85rem;
            line-height: 1.3;
            margin-bottom: 0.5rem !important;
        }
        .highlight-text {
            color: #0178c6 !important;
            font-weight: 600;
        }

        /* Contenedor principal para la disposición: imagen | texto | boton */
        .main-qr-layout {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }
        @media (min-width: 768px) {
            .main-qr-layout {
                flex-direction: row;
                justify-content: center;
                align-items: flex-start;
                text-align: left;
                gap: 1.5rem;
            }
            .qr-image-column {
                flex-shrink: 0;
                text-align: center;
            }
            .qr-text-column {
                flex-grow: 1;
                min-width: 250px;
            }
            .qr-buttons-column {
                flex-shrink: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
                margin-top: 0;
            }
        }

        .qr-image-container {
            background-color: white;
            padding: 0.6rem;
            border-radius: 15px;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
            margin-bottom: 0;
            max-width: 100%;
        }
        .qr-image {
            width: 100%;
            max-width: 160px;
            height: auto;
            display: block;
        }

        .btn-custom, .btn-secondary-custom {
            padding: 7px 16px;
            font-size: 0.9rem;
            border-radius: 50px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-custom {
            background-color: #F94144;
            border-color: #F94144;
        }
        .btn-custom:hover {
            background-color: #0178c6;
            border-color: #0178c6;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        .btn-secondary-custom {
            background-color: #2C2C2C;
            border-color: #2C2C2C;
            color: white;
        }
        .btn-secondary-custom:hover {
            background-color: #444;
            border-color: #444;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        .btn-group {
            margin-top: 10px;
            display: flex;
            justify-content: center;
        }
        .btn-group .btn {
            border-radius: 50px !important;
            margin: 0 3px;
        }
        .btn-menu-volver {
            background-color: #F94144;
            border: none;
            color: white;
            padding: 9px 22px;
            font-size: 0.95rem;
            font-weight: bold;
            border-radius: 50px;
            margin-top: 15px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
        }
        .btn-menu-volver:hover {
            background-color: #0178c6;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
        }
        @keyframes bounceIn {
            0% { transform: scale(0.1); opacity: 0; }
            60% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .qr-image-container img {
            animation: fadeIn 1s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="qr-card">
                    <div class="qr-content">
                        <i class="fas fa-check-circle success-icon"></i>
                        <h1>¡Compra Exitosa!</h1>
                        <p class="text-white-50 main-description">
                            ¡Felicidades por tu adquisición en ViveCultura! Aquí tienes tus códigos QR. Guárdalos de forma segura y tenlos listos para el evento.
                        </p>
                        
                        <h2 id="qr-type-title" class="qr-type-title"></h2>

                        <div id="main-qr-layout" class="main-qr-layout">
                            <div class="qr-image-column">
                                <div class="qr-image-container">
                                    <img id="qrImage" src="" alt="Código QR" class="qr-image">
                                </div>
                            </div>
                            
                            <div class="qr-text-column">
                                <p id="qr-instructions" class="text-white-50"></p>
                                <p class="text-white-50 mt-2">
                                    También puedes <span class="highlight-text">consultar y descargar tu boleto</span> en la sección <span class="highlight-text">"Boletos"</span> de la app o página web.
                                </p>
                            </div>
                            
                            <div class="qr-buttons-column">
                                <button id="downloadBtn" class="btn btn-custom">
                                    <i class="fas fa-download me-2"></i> Guardar QR
                                </button>
                            </div>
                        </div>

                        <?php if ($full_parqueadero_url): ?>
                            <div class="mt-3">
                                <div class="btn-group" role="group">
                                    <button id="toggleBoleto" class="btn btn-custom">Boleto</button>
                                    <button id="toggleParqueo" class="btn btn-secondary-custom">Parqueadero</button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3">
                            <button id="acceptAndMenuBtn" class="btn btn-menu-volver">
                                Aceptar y Volver al Menú
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const qrBoletoUrl = "<?php echo $full_boleto_url; ?>";
        const qrParqueaderoUrl = "<?php echo $full_parqueadero_url; ?>";

        const qrImage = document.getElementById('qrImage');
        const qrTypeTitleEl = document.getElementById('qr-type-title');
        const instructionsEl = document.getElementById('qr-instructions');
        const downloadBtn = document.getElementById('downloadBtn');
        const acceptAndMenuBtn = document.getElementById('acceptAndMenuBtn');
        const toggleBoletoBtn = document.getElementById('toggleBoleto');
        const toggleParqueoBtn = document.getElementById('toggleParqueo');
        
        const hasParking = qrParqueaderoUrl && qrParqueaderoUrl !== "";
        let currentQrUrl = qrBoletoUrl;

        function showQr(url, isBoleto = true) {
            qrImage.src = url;
            if (isBoleto) {
                qrTypeTitleEl.textContent = 'Tu boleto para el evento';
                instructionsEl.innerHTML = 'Este es tu pase de acceso. <span class="highlight-text">Muéstralo en la entrada principal del evento</span> para que sea escaneado.';
                if (hasParking) {
                    toggleBoletoBtn.classList.remove('btn-secondary-custom');
                    toggleBoletoBtn.classList.add('btn-custom');
                    toggleParqueoBtn.classList.remove('btn-custom');
                    toggleParqueoBtn.classList.add('btn-secondary-custom');
                }
            } else {
                qrTypeTitleEl.textContent = 'Tu boleto para el parqueadero';
                instructionsEl.innerHTML = 'Usa este código para estacionar tu vehículo. <span class="highlight-text">Muéstralo en la entrada del parqueadero</span> y te guiarán a tu espacio.';
                if (hasParking) {
                    toggleParqueoBtn.classList.remove('btn-secondary-custom');
                    toggleParqueoBtn.classList.add('btn-custom');
                    toggleBoletoBtn.classList.remove('btn-custom');
                    toggleBoletoBtn.classList.add('btn-secondary-custom');
                }
            }
        }

        function downloadImage(url) {
            const link = document.createElement('a');
            link.href = url;
            link.download = url.split('/').pop() || 'qr_vivecultura.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        downloadBtn.addEventListener('click', () => downloadImage(currentQrUrl));

        if (hasParking) {
            toggleBoletoBtn.addEventListener('click', () => {
                currentQrUrl = qrBoletoUrl;
                showQr(currentQrUrl, true);
            });
            toggleParqueoBtn.addEventListener('click', () => {
                currentQrUrl = qrParqueaderoUrl;
                showQr(currentQrUrl, false);
            });
        }

        acceptAndMenuBtn.addEventListener('click', () => {
            window.location.href = 'menu.php';
        });

        // Mostrar el QR del boleto por defecto al cargar la página
        showQr(currentQrUrl);
    </script>
</body>
</html>