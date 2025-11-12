<?php
include '../plantilla/plantilla_info/head.php';
include '../../conexion.php';

$baseURL = include __DIR__ . '/../../ruta_Api.php';


// Consulta de redes sociales activas
$query = "SELECT red_nom, red_url, red_ico, red_dim 
          FROM tbl_redes_sociales 
          WHERE red_est = 'activo' 
          ORDER BY red_id ASC";

$result = pg_query($conn, $query);

$redes = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $redes[] = $row;
    }
}
?>

<!-- 🎨 Estilos específicos para esta página -->
<style>
body, html {
    margin: 0;
    padding: 0;
    height: 100%;
    width: 100%;
    overflow-x: hidden;
    font-family: Arial, sans-serif;
}

.content-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    padding: 20px;
    color: white;
}

/* Línea divisoria decorativa */
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
.line-divider::before, .line-divider::after {
    content: '';
    width: 12px;
    height: 12px;
    background-color: white;
    border-radius: 50%;
}

/* Título de sección */
.section-title {
    font-size: 2em;
    margin: 15px 0 5px 0;
    text-align: center;
}

/* 📞 Fila de íconos */
.icon-row {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 100px;
    padding: 40px;
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

/* Tarjetas de redes sociales */
.icon-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    text-decoration: none;
    color: white;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.icon-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px #e60029;
}
.icon-card img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #0176c7;
    transition: border-color 0.3s ease;
}
.icon-card:hover img {
    border-color: #e60029;
}
.icon-card p {
    margin-top: 15px;
    font-size: 1.1em;
    font-weight: bold;
    color: #eff2f4ff;
}

/* 💡 Responsive */
@media (max-width: 900px) {
    .icon-row {
        flex-direction: column;
        gap: 30px;
        padding: 20px;
    }
}
</style>

<!-- 📞 Contenido principal -->
<div class="content-wrapper">
    <h2 class="section-title">Contáctanos</h2>
    <div class="line-divider"></div>

    <div class="icon-row">
        <?php if (!empty($redes)): ?>
            <?php foreach ($redes as $red): ?>
                <?php
                    $imgRuta = $red['red_ico'] ? $baseURL . $red['red_ico'] : $baseURL . "php/imagenes/default-icon.png";
                    $dim = $red['red_dim'] ?: "150x150";
                    list($w, $h) = explode("x", $dim);
                ?>
                <a href="<?= htmlspecialchars($red['red_url']) ?>" target="_blank" class="icon-card">
                    <img src="<?= $imgRuta ?>" alt="<?= htmlspecialchars($red['red_nom']) ?>" width="<?= $w ?>" height="<?= $h ?>">
                    <p><?= htmlspecialchars($red['red_nom']) ?></p>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; color:#ccc;">No hay redes sociales activas.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../plantilla/plantilla_info/footer.php'; ?>
