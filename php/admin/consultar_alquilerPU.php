<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

// ================== CONSULTA DE RESERVAS DE PARQUEADERO ==================
$query = "
SELECT 
    r.res_id,
    u.usr_nom || ' ' || u.usr_ape AS usuario,
    e.evt_tit AS evento,
    p.pue_num AS puesto,
    pa.par_nom AS parqueadero,
    r.res_fec,
    r.res_est,
    cb.com_ruta_qr_parq
FROM tbl_reservas_parqueadero r
JOIN tbl_compras_boletos cb ON r.res_id_com = cb.com_id
JOIN tbl_usuario u ON cb.com_id_usr = u.usr_id
JOIN tbl_evento e ON cb.com_id_evt = e.evt_id
JOIN tbl_puestos_parqueadero p ON r.res_id_pue = p.pue_id
JOIN tbl_parqueadero pa ON r.res_id_par = pa.par_id
ORDER BY r.res_fec DESC
";

$result = pg_query($conn, $query);

ob_start(); // Inicia el almacenamiento en búfer de la salida
?>

<style>
    /* Estilos específicos para esta página */
    .content-wrapper {
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    .page-title {
        text-align: center;
        color: #c0392b;
        margin-bottom: 20px;
    }
    .table-container {
        overflow-x: auto;
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    /* Estilos de tabla DataTables */
    table.data-table {
        border-collapse: collapse;
        width: 100%;
        min-width: 800px; /* Para asegurar que la tabla no se vea demasiado pequeña en móviles */
    }
    .data-table th, .data-table td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
        transition: background-color 0.3s ease;
    }
    .data-table th {
        background-color: #34495e;
        color: white;
        text-transform: uppercase;
        font-weight: bold;
        position: sticky;
        top: 0;
    }
    .data-table tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    .data-table tr:hover {
        background-color: #e8f0fe;
    }
    .data-table td img.qr {
        max-width: 80px;
        height: auto;
        display: block;
        margin: 0 auto;
        transition: transform 0.2s ease;
    }
    .data-table td img.qr:hover {
        transform: scale(1.1);
    }
    .data-table a {
        color: #3498db;
        text-decoration: none;
    }
    .data-table a:hover {
        text-decoration: underline;
    }
    .no-results {
        text-align: center;
        padding: 20px;
        color: #777;
    }
</style>

<div class="content-wrapper">
    <h2 class="page-title">Reservas de Parqueadero</h2>
    
    <div class="table-container">
        <table class="data-table" id="table-reservas-parqueadero">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Evento</th>
                    <th>Puesto</th>
                    <th>Parqueadero</th>
                    <th>Fecha Reserva</th>
                    <th>Estado</th>
                    <th>QR Parqueadero</th>
                </tr>
            </thead>
            <tbody>
                <?php if (pg_num_rows($result) > 0): ?>
                    <?php while ($row = pg_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['res_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['usuario']); ?></td>
                            <td><?php echo htmlspecialchars($row['evento']); ?></td>
                            <td><?php echo htmlspecialchars($row['puesto']); ?></td>
                            <td><?php echo htmlspecialchars($row['parqueadero']); ?></td>
                            <td>
                                <?php 
                                    $fecha = new DateTime($row['res_fec']);
                                    echo $fecha->format('Y-m-d H:i');
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['res_est']); ?></td>
                            <td>
                                <?php if (!empty($row['com_ruta_qr_parq'])): ?>
                                    <?php $ruta_qr = "../../" . $row['com_ruta_qr_parq']; ?>
                                    <a href="<?php echo htmlspecialchars($ruta_qr); ?>" target="_blank">
                                        <img class="qr" src="<?php echo htmlspecialchars($ruta_qr); ?>" alt="QR Parqueadero">
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="no-results">No se encontraron reservas de parqueadero.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableElement = document.getElementById('table-reservas-parqueadero');

        if (tableElement && tableElement.getElementsByTagName('tbody')[0].children.length > 0 && 
            tableElement.getElementsByTagName('tbody')[0].children[0].className !== 'no-results') 
        {
            // Inicialización de DataTables
            new DataTable(tableElement, {
                paging: true,
                searching: true,
                info: true,
                ordering: true,
                // Ordenar por la columna "Fecha Reserva" (columna 5) descendente por defecto
                order: [[5, 'desc']], 
                language: {
                    // Carga el idioma español
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' 
                }
            });
        }
    });
</script>

<?php
$contenido = ob_get_clean(); // Guarda el contenido del búfer en la variable $contenido
include 'plantillaAdmin.php'; // Incluye la plantilla que contendrá y mostrará la variable $contenido
?>