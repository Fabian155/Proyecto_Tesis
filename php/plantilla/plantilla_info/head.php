<?php
// ========================================
// BASE URL Y CONEXIÓN
// ========================================
$baseURL = include __DIR__ . '/../../../ruta_Api.php';

require_once __DIR__ . '/../../../conexion.php';

// Página actual
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');

// ========================================
// LOGO ACTIVO (tbl_logos)
// ========================================
$logoURL = $baseURL . 'php/imagenes/logoempresa.png';
$qLogo = "SELECT log_rut FROM tbl_logos WHERE log_est = 'activo' LIMIT 1";
$rLogo = @pg_query($conn, $qLogo);
if ($rLogo && pg_num_rows($rLogo) > 0) {
    $ruta = trim(pg_fetch_result($rLogo, 0, 'log_rut'));
    if ($ruta !== '') {
        $logoURL = preg_match('#^https?://#i', $ruta)
            ? $ruta
            : rtrim($baseURL, '/') . '/' . ltrim($ruta, '/');
    }
}

// ========================================
// SOLO CELULAR (tbl_nosotros)
// ========================================
$telefono = '';
$qCel = "SELECT nos_cel FROM tbl_nosotros WHERE nos_est = 'activo' 
         AND nos_cel IS NOT NULL AND nos_cel <> '' 
         ORDER BY nos_id DESC LIMIT 1";
$rCel = @pg_query($conn, $qCel);
if ($rCel && pg_num_rows($rCel) > 0) {
    $telefono = trim(pg_fetch_result($rCel, 0, 'nos_cel'));
}

// ========================================
// REDES SOCIALES (tbl_redes_sociales)
// ========================================
// *** CONSULTA MODIFICADA: Ahora selecciona red_ico_clase ***
$qRedes = "SELECT red_nom, red_url, red_ico_clase, red_dim
           FROM tbl_redes_sociales
           WHERE red_est = 'activo'
           ORDER BY red_id ASC";
$rRedes = @pg_query($conn, $qRedes);

// Helper de activo para el menú
function isActive($file, $current){ return $current === $file ? 'active' : ''; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Iron Producciones</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />

<style>
:root{
    --nav:#171b34;
    --nav-2:#20254a;
    --acento:#e60029;
    --txt:#e7e9f1;
    --txt-m:#a8afbf;
}
*{box-sizing:border-box}
html,body{height:100%}
body{
    margin:0;
    background:#fff;
    color:#222;
    font-family:"Poppins",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    overflow-x:hidden;
}

/* ===== HERO ===== */
.hero{
    position:relative;
    width:100%;
    height:160px;
    overflow:hidden;
    background:var(--nav);
    display:flex;
    align-items:center;
    justify-content:center;
}
.hero video{
    position:absolute;
    top:50%;
    left:50%;
    min-width:100%;
    min-height:100%;
    width:auto;
    height:auto;
    transform:translate(-50%,-50%);
    object-fit:contain;
}
.hero-inner{
    position:relative;
    z-index:1;
    text-align:center;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:10px;
}
.brand img{
    height:100px;
    max-width:300px;
    display:block;
    filter:drop-shadow(0 6px 14px rgba(0,0,0,.4));
}
.telefono{
    color:#fff;
    font-size:1rem;
    background:rgba(255,255,255,0.1);
    padding:6px 14px;
    border-radius:20px;
    border:1px solid rgba(255,255,255,0.25);
}

/* ===== NAVBAR ===== */
.navbar{
    position:sticky;
    top:0;
    z-index:10;
    background:var(--nav);
    box-shadow:0 3px 10px rgba(0,0,0,0.2);
}
.nav-wrap{
    max-width:1200px;
    margin:0 auto;
    padding:14px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

/* Menú a la izquierda */
nav ul{
    list-style:none;
    margin:0;
    padding:0;
    display:flex;
    gap:30px;
    align-items:center;
}
nav a{
    color:var(--txt);
    text-decoration:none;
    font-size:1rem;
    padding:8px 16px;
    border-radius:30px;
    transition:all 0.3s ease;
}
nav a:hover{ background:var(--nav-2); }
nav a.active{
    background:var(--acento);
    color:#fff;
    font-weight:bold;
}

/* Redes y botón a la derecha */
.right-side{
    display:flex;
    align-items:center;
    gap:25px;
}
.nav-social{
    display:flex;
    align-items:center;
    gap:10px;
}
/* Estilo para los íconos (etiqueta <i>) */
.nav-social a i{
    font-size: 24px; /* Ajusta el tamaño base de los íconos */
    color: var(--txt); /* Color por defecto */
    opacity:.9;
    transition:transform .2s ease,opacity .2s ease, color .2s ease;
    width: 39px; /* Mantiene la misma zona de clic */
    height: 39px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.nav-social a i:hover{
    opacity:1;
    transform:scale(1.1);
    color: var(--acento); /* Color al pasar el ratón */
}
/* Estilos para <img> deben ser removidos o ajustados si no se usan más */
/* .nav-social a img{
    width: 39px !important;
    opacity:.9;
    transition:transform .2s ease,opacity .2s ease;
}
.nav-social a img:hover{
    opacity:1;
    transform:scale(1.1);
} */
.login-btn{
    color:#fff;
    background:var(--acento);
    padding:6px 14px;
    border-radius:25px;
    text-decoration:none;
    font-weight:600;
    transition:.3s;
}
.login-btn:hover{ background:#b80022; }

/* RESPONSIVE */
@media (max-width:900px){
    .brand img{height:65px;}
    nav a{font-size:0.95rem;}
}
@media (max-width:600px){
    .brand img{height:55px;}
    nav ul{gap:18px;}
}
</style>
</head>
<body>

<section class="hero">
    <video autoplay muted loop playsinline>
        <source src="<?= $baseURL ?>php/imagenes/video/fondoinfo.mp4" type="video/mp4">
    </video>
    <div class="hero-inner">
        <div class="brand">
            <a href="<?= $baseURL ?>index.php">
                <img src="<?= htmlspecialchars($logoURL) ?>" alt="Logo Empresa">
            </a>
        </div>

    </div>
</section>

<header class="navbar">
    <div class="nav-wrap">
        <nav>
            <ul>
                <?php if ($currentPage !== 'index.php'): ?>
                    <li><a href="<?= $baseURL ?>index.php" class="<?= isActive('index.php', $currentPage) ?>">Inicio</a></li>
                <?php endif; ?>
                <li><a href="<?= $baseURL ?>php/info/galeria.php" class="<?= isActive('galeria.php', $currentPage) ?>">Galería</a></li>
                <li><a href="<?= $baseURL ?>php/info/nosotros.php" class="<?= isActive('nosotros.php', $currentPage) ?>">Nosotros</a></li>

            </ul>
        </nav>

        <div class="right-side">
            <?php if ($rRedes && pg_num_rows($rRedes) > 0): ?>
              <div class="nav-social">
                <?php while ($red = pg_fetch_assoc($rRedes)): ?>
                  <a href="<?= htmlspecialchars($red['red_url']) ?>" target="_blank" rel="noopener"
                     title="<?= htmlspecialchars($red['red_nom']) ?>">
                    <i class="<?= htmlspecialchars($red['red_ico_clase']) ?>" 
                       style="<?= !empty($red['red_dim']) ? 'font-size:' . htmlspecialchars($red['red_dim']) : '' ?>">
                    </i>
                  </a>
                <?php endwhile; ?>
              </div>
            <?php endif; ?>

            <a href="<?= $baseURL ?>sesion/login.php"
               class="login-btn <?= $currentPage == 'login.php' ? 'active' : '' ?>">
               Iniciar Sesión
            </a>
        </div>
    </div>
</header>