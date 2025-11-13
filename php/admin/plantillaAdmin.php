<?php
// Inicia la sesión si aún no ha sido iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../../sesion/login.php");
    exit;
}

// Verifica si la sesión está activa y si el nombre de usuario existe
$nombreUsuario = isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Invitado';

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administracion</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet"
        crossorigin="anonymous" />
    <link href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.5.4/css/fileinput.min.css" rel="stylesheet"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            color: #333;
            display: flex;
            height: 100vh;
        }

        /* Estilo de la barra lateral (sidebar) */
        .barra-lateral {
            width: 260px;
            color: #fff;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            height: 100vh;
            flex-shrink: 0;
            background-image: url('https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRUHkRY7OpPVMOF5ZHFwAn6bwhple4mkfHb7E1hPhU-WnoZn5OlNvb6OIruFQnDVJn1fAA&usqp=CAU');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
        }

        /* Capa de color con opacidad en el menu lateral */
        .barra-lateral::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(14, 23, 43, 0.85);
            /* #0e172b con opacidad */
            z-index: 0;
        }

        /* Contenido de la barra lateral sobre la capa opaca */
        .contenido-barra {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .encabezado-barra {
            padding: 15px 10px; /* Reducción de padding para limpiar */
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px; /* Reducción de gap */
        }

        .encabezado-barra img {
            width: 180px; /* Tamaño ajustado del logo */
            height: auto;
        }

        .info-usuario-superior {
            display: flex;
            /* pone imagen y texto en linea */
            align-items: center;
            /* alinea verticalmente al centro */
            gap: 15px; /* Reducción de espacio entre la imagen y el texto */
            margin-top: 5px; /* Reducción de margen superior */
        }

        .info-usuario-superior img {
            width: 50px; /* Tamaño ajustado del avatar */
            height: 50px; /* Tamaño ajustado del avatar */
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
        }

        .info-usuario-superior p {
            margin: 0;
            font-weight: bold;
            font-size: 1.2em; /* Tamaño de fuente ajustado */
        }
        
        /* ESTILO AÑADIDO: Para que el nombre de usuario se vea como un enlace clickeable */
        .info-usuario-superior a {
            color: inherit; /* Hereda el color del texto blanco */
            text-decoration: none; /* Quita el subrayado por defecto */
            transition: color 0.3s;
        }

        .info-usuario-superior a:hover {
            color: #E4002B; /* Color al pasar el ratón */
            text-decoration: underline; /* Añade subrayado al pasar el ratón */
        }


        /* Lista de enlaces del menu */
        .menu-barra {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
            overflow-y: auto;
            /* Permite el scroll vertical si el contenido es muy largo */
            /* Oculta la barra de desplazamiento en navegadores WebKit (Chrome, Safari) */
            scrollbar-width: none;
            /* Firefox */
        }

        /* Oculta la barra de desplazamiento para navegadores WebKit */
        .menu-barra::-webkit-scrollbar {
            display: none;
        }

        .menu-barra .grupo-item-menu {
            padding: 10px 20px; /* Reducción de padding para limpiar los botones/enlaces */
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px; /* Reducción de gap */
            transition: background-color 0.3s;
        }

        .menu-barra .grupo-item-menu:hover {
            background-color: #44607c;
        }

        .menu-barra .grupo-item-menu i {
            font-size: 1em; /* Tamaño de ícono ajustado */
        }

        /* Contenedor de sub-menu */
        .submenu {
            list-style: none;
            padding: 0;
            margin: 0; /* Asegura que no haya márgenes externos */
            background-color: rgba(44, 62, 80, 0.7);
            /* Transparencia ajustada */
            display: none;
        }

        .submenu.activo {
            display: block;
        }

        .submenu a {
            display: block;
            padding: 5px 15px 5px 40px; /* Ajuste crítico: Reducción del padding vertical a 5px para juntar los elementos */
            margin: 0; /* Elimina cualquier margen residual */
            color: #fff;
            text-decoration: none;
            transition: background-color 0.3s;
            background-color: rgba(0, 0, 0, 0.1);
            /* Agrega un fondo semitransparente al enlace */
        }

        .submenu a:hover {
            background-color: rgba(59, 80, 102, 0.7);
            /* Fondo hover con transparencia */
        }

        .submenu a.enlace-activo {
            background-color: rgba(227, 0, 43, 0.7);
            /* Fondo del enlace activo con transparencia */
            color: #fff;
        }

        .grupo-item-menu.padre-activo {
            background-color: #44607c;
        }

        /* Seccion de boton al final del menu */
        .seccion-cerrar-sesion {
            padding: 10px 20px; /* Reducción de padding */
            text-align: center;
            border-top: 1px solid #5e5c68;
            /* Color de linea ajustado */
            flex-shrink: 0;
        }

        .btn-cerrar-sesion {
            width: 100%;
            background-color: #E4002B;
            /* mismo color que el header */
            color: #fff;
            border: none;
            padding: 8px; /* Reducción de padding */
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }


        .btn-cerrar-sesion:hover {
            background-color: #c0392b;
        }

        /* Estilo del contenedor principal */
        .contenedor-principal {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Contenedor del encabezado con la imagen de fondo y la capa roja */
        .envoltorio-encabezado-principal {
            background-image: url('https://medibangpaint.com/wp-content/uploads/2021/05/32.png');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            position: relative;
            /* La capa opaca va sobre esta seccion */
            min-height: 1.5cm;
        }

        .envoltorio-encabezado-principal::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(228, 0, 43, 0.6);
            /* #E4002B solido */
            z-index: 0;
        }


        .encabezado-principal {
            background: none;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
            position: relative;
            z-index: 1;
            /* Asegura que el titulo este encima de la capa opaca */
            height: 100%;
        }

        .encabezado-principal h2 {
            margin: 0;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
        }

        /* Contenido principal con fondo blanco y scroll */
        .contenido-principal {
            flex-grow: 1;
            padding: 20px;
            background-color: #fff;
            overflow-y: auto;
            /* Permite el scroll vertical si el contenido es muy largo */
        }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
</head>

<body>

    <div class="barra-lateral">
        <div class="contenido-barra">
            <div class="encabezado-barra">
                <img src="https://ipeventos.alwaysdata.net/php/imagenes/logoempresa.png" alt="Logo de la empresa">
                <div class="info-usuario-superior">
                    <img src="https://cdnl.iconscout.com/lottie/premium/thumb/user-profile-animation-gif-download-4644453.gif"
                        alt="Avatar del usuario">
                    <a href="perfil.php">
                        <p><?php echo $nombreUsuario; ?></p>
                    </a>
                </div>
            </div>

            <ul class="menu-barra">
                <li>
                    <a href="menu.php" class="grupo-item-menu">
                        <i class="fas fa-house"></i> Inicio
                    </a>
                </li>

                <li>
                    <a href="#" class="grupo-item-menu">
                        <i class="fas fa-calendar-check"></i> Eventos
                    </a>
                    <ul class="submenu">
                        <li><a href="crear_evento.php"><i class="fas fa-pencil-alt"></i> Gestionar Evento</a></li>
                        <li><a href="gestionar_galeria.php"><i class="fas fa-camera-retro"></i> Galeria de Eventos
                        </li>
                        <li><a href="consultar_boletosCU.php"><i class="fas fa-user"></i> Boletos Comprados
                                (Usuarios)</a></li>
                        <li><a href="consultar_boletosCC.php"><i class="fas fa-cash-register"></i> Boletos Vendidos
                                (Cajeros)</a></li>
                    </ul>
                </li>

                <li>
                    <a href="#" class="grupo-item-menu">
                        <i class="fas fa-parking"></i> Parqueadero
                    </a>
                    <ul class="submenu">
                        <li><a href="crear_parqueadero.php"><i class="fas fa-plus-square"></i> gestionar Parqueadero
                        </li>
                        <li><a href="consultar_alquilerPU.php"><i class="fas fa-user-check"></i> Puestos (Usuarios)</a>
                        </li>
                        <li><a href="consultar_alquilerPC.php"><i class="fas fa-user-tie"></i> Puestos (Cajeros)</a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="#" class="grupo-item-menu">
                        <i class="fas fa-user-cog"></i> Cajeros
                    </a>
                    <ul class="submenu">
                        <li><a href="gestion_cajero.php"><i class="fas fa-user-plus"></i> Gestionar Cajeros</a></li>
                        <li><a href="consultar_escaneo_qr_eventos.php"><i class="fas fa-qrcode"></i> Escaneo Eventos
                        </li>
                        <li><a href="consultar_escaneo_qr_parqueaderos.php"><i class="fas fa-qrcode"></i> Escaneo
                                Parqueadero</a></li>
                    </ul>
                </li>

                <li>
                    <a href="#" class="grupo-item-menu">
                        <i class="fas fa-gift"></i> Regalos
                    </a>
                    <ul class="submenu">
                        <li><a href="crear_regalos.php"><i class="fas fa-box-open"></i> Gestionar Regalos</a></li>
                        <li><a href="asignar_regalos.php"><i class="fas fa-handshake"></i> Registrar Regalos
                                Entregados</a></li>
                    </ul>
                </li>

                <li>
                    <a href="#" class="grupo-item-menu">
                        <i class="fas fa-edit"></i> Editar Información de la Página
                    </a>
                    <ul class="submenu">
                        <li><a href="gestionLogos.php"><i class="fas fa-image"></i> Gestionar Logos</a></li>
                        <li><a href="gestionPatrocinadores.php"><i class="fas fa-hand-holding-usd"></i> Gestionar
                                Patrocinadores</a></li>
                        <li><a href="gestionRedes.php"><i class="fas fa-share-alt"></i> Gestionar Redes Sociales</a>
                        </li>
                        <li><a href="gestionNosotros.php"><i class="fas fa-info-circle"></i> Información Nosotros</a>
                        </li>
                    </ul>
                </li>

            </ul>


            <div class="seccion-cerrar-sesion">
                <form method="post">
                    <button type="submit" name="logout" class="btn-cerrar-sesion">Cerrar Sesion</button>
                </form>
            </div>
        </div>
    </div>

    <div class="contenedor-principal">
        <div class="envoltorio-encabezado-principal">
            <header class="encabezado-principal">
                <h2>Panel de Administracion</h2>
            </header>
        </div>

        <div class="contenido-principal">
            <?php
            // Se verifica si la variable $contenido existe antes de mostrarla para evitar errores.
            if (isset($contenido)) {
                echo $contenido;
            } else {
                echo '<h3>Bienvenido al Panel de Administracion</h3>
                    <p>Usa el menu de la izquierda para navegar.</p>';
            }
            ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js"
        integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="//cdn.datatables.net/2.3.1/js/dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/localization/messages_es.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.5.4/js/fileinput.min.js"
        crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.5.0/js/locales/es.min.js"></script>
    <script async
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDHTzZtvgXf4s3rTjS5rYzCIrBr2mF72qA&callback=initMap"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const itemsMenu = document.querySelectorAll('.grupo-item-menu');
            const enlacesSubMenu = document.querySelectorAll('.submenu a');

            function limpiarClasesActivas() {
                itemsMenu.forEach(item => item.classList.remove('padre-activo'));
                const todosSubmenus = document.querySelectorAll('.submenu');
                todosSubmenus.forEach(submenu => submenu.classList.remove('activo'));
                enlacesSubMenu.forEach(link => link.classList.remove('enlace-activo'));
            }

            function guardarEstadoMenu(padreEnlace, hijoEnlace) {
                if (padreEnlace) {
                    localStorage.setItem('padreActivo', padreEnlace);
                } else {
                    localStorage.removeItem('padreActivo');
                }
                if (hijoEnlace) {
                    localStorage.setItem('hijoActivo', hijoEnlace);
                } else {
                    localStorage.removeItem('hijoActivo');
                }
            }

            // Funcion para restaurar el estado desde localStorage
            function restaurarEstadoMenu() {
                const hijoActivoEnlace = localStorage.getItem('hijoActivo');
                const rutaActual = window.location.pathname.split('/').pop();

                limpiarClasesActivas();

                let encontrado = false;

                // 1. Prioriza la coincidencia con el enlace de submenu activo (hijoActivo)
                if (hijoActivoEnlace) {
                    const enlaceHijoActivo = document.querySelector(`.submenu a[href="${hijoActivoEnlace}"]`);
                    if (enlaceHijoActivo) {
                        enlaceHijoActivo.classList.add('enlace-activo');
                        const submenuPadre = enlaceHijoActivo.closest('.submenu');
                        if (submenuPadre) {
                            submenuPadre.classList.add('activo');
                            const itemMenuPadre = submenuPadre.previousElementSibling;
                            if (itemMenuPadre) {
                                itemMenuPadre.classList.add('padre-activo');
                                encontrado = true;
                            }
                        }
                    }
                }

                // 2. Si no hay coincidencia con hijoActivo, o si el usuario navego directamente,
                //    busca una coincidencia con la URL actual de la pagina.
                if (!encontrado) {
                    const enlaceActual = document.querySelector(`.menu-barra a[href="${rutaActual}"]`);
                    if (enlaceActual) {
                        const submenuPadre = enlaceActual.closest('.submenu');
                        if (submenuPadre) {
                            // Es un enlace de submenu
                            enlaceActual.classList.add('enlace-activo');
                            submenuPadre.classList.add('activo');
                            const itemMenuPadre = submenuPadre.previousElementSibling;
                            if (itemMenuPadre) {
                                itemMenuPadre.classList.add('padre-activo');
                            }
                        } else {
                            // Es un enlace de menu principal sin submenu
                            enlaceActual.classList.add('padre-activo');
                        }
                    }
                }
            }

            // Logica para manejar el clic en los menus principales
            itemsMenu.forEach(item => {
                item.addEventListener('click', function (event) {
                    const submenu = item.nextElementSibling;
                    const esSubmenu = submenu && submenu.classList.contains('submenu');

                    if (esSubmenu) {
                        event.preventDefault();
                        const estabaActivo = submenu.classList.contains('activo');
                        limpiarClasesActivas();
                        if (!estabaActivo) {
                            submenu.classList.add('activo');
                            item.classList.add('padre-activo');
                            guardarEstadoMenu(item.href, null);
                        } else {
                            guardarEstadoMenu(null, null);
                        }
                    } else {
                        limpiarClasesActivas();
                        item.classList.add('padre-activo');
                        guardarEstadoMenu(item.href, null);
                    }
                });
            });

            // Logica para manejar el clic en los enlaces del submenu
            enlacesSubMenu.forEach(link => {
                link.addEventListener('click', function () {
                    limpiarClasesActivas();
                    this.classList.add('enlace-activo');

                    const submenuPadre = this.closest('.submenu');
                    if (submenuPadre) {
                        submenuPadre.classList.add('activo');
                        const itemMenuPadre = submenuPadre.previousElementSibling;
                        if (itemMenuPadre) {
                            itemMenuPadre.classList.add('padre-activo');
                        }
                    }

                    const enlacePadreHref = submenuPadre ? submenuPadre.previousElementSibling.href : null;
                    guardarEstadoMenu(enlacePadreHref, this.href);
                });
            });

            restaurarEstadoMenu();
        });
    </script>
</body>

</html>