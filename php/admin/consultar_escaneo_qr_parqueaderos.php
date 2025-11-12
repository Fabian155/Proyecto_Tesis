<?php
// Inicia la sesión
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

// Incluye la conexión a la base de datos
include '../../conexion.php';

// =====================
// ESCANEOS DE BOLETOS DE PARQUEADERO
// =====================
$query_esc_parq = "
SELECT esc.esc_id, esc.esc_fec AS hora_escaneo,
        rp.res_id, rp.res_id_com,
        cb.com_qr AS qr_evento, cb.com_id_usr,
        u.usr_nom, u.usr_ape,
        p.pue_num, par.par_nom,
        cb.com_ruta_qr_parq AS qr_parqueadero,
        caj.caj_nom AS cajero
FROM tbl_escaneosParqueaderos esc
JOIN tbl_reservas_parqueadero rp ON esc.esc_id_res = rp.res_id
JOIN tbl_compras_boletos cb ON rp.res_id_com = cb.com_id
JOIN tbl_usuario u ON cb.com_id_usr = u.usr_id
JOIN tbl_parqueadero par ON rp.res_id_par = par.par_id
JOIN tbl_puestos_parqueadero p ON rp.res_id_pue = p.pue_id
JOIN tbl_cajero caj ON esc.esc_id_caj = caj.caj_id
ORDER BY esc.esc_id DESC
";

$result_esc_parq = pg_query($conn, $query_esc_parq);

// =====================
// ASIGNACIÓN DEL CONTENIDO A LA VARIABLE $contenido
// =====================
ob_start();
?>

<h2>Escaneos de Boletos de Parqueadero</h2>

<style>
.tabla-peque, .data-table {
    font-size: 12px;       /* Tamaño de letra más pequeño */
    border-collapse: collapse;
    width: 100%;
}

.tabla-peque th, .tabla-peque td, .data-table th, .data-table td {
    padding: 5px 8px;      /* Menos espacio dentro de las celdas */
    text-align: left;
    border: 1px solid #ccc;
}

.tabla-peque img, .data-table img {
    max-width: 50px;       /* Reduce tamaño de las imágenes de QR */
    max-height: 50px;
}

/* Estilos de encabezado DataTables (para asegurar que se vea bien) */
.data-table th {
    background-color: #34495e;
    color: white;
    text-transform: uppercase;
    font-weight: bold;
}
</style>

<table class="tabla-peque data-table" id="table-escaneos-parqueadero">
    <thead>
        <tr>
            <th>ID Escaneo</th>
            <th>Dueño del Boleto</th>
            <th>Puesto</th>
            <th>Parqueadero</th>
            <th>QR Boleto Parqueadero</th>
            <th>QR Boleto Evento Relacionado</th>
            <th>Cajero que Escaneó</th>
            <th>Hora Escaneo</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = pg_fetch_assoc($result_esc_parq)): ?>
        <tr class="parqueadero">
            <td><?php echo $row['esc_id']; ?></td>
            <td><?php echo htmlspecialchars($row['usr_nom'].' '.$row['usr_ape']); ?></td>
            <td><?php echo htmlspecialchars($row['pue_num']); ?></td>
            <td><?php echo htmlspecialchars($row['par_nom']); ?></td>
            <td style="text-align: center;">
                <?php 
                    if(!empty($row['qr_parqueadero'])) {
                        $ruta_parq = "../../" . $row['qr_parqueadero'];
                        // Enlace para ver la imagen en grande
                        echo "<a href='$ruta_parq' target='_blank'><img src='$ruta_parq' alt='Boleto Parqueadero'></a>"; 
                    } else { echo "Sin imagen"; }
                ?>
            </td>
            <td style="text-align: center;">
                <?php 
                    if(!empty($row['qr_evento'])) {
                        $ruta_ev = "../../" . $row['qr_evento'];
                        // Enlace para ver la imagen en grande
                        echo "<a href='$ruta_ev' target='_blank'><img src='$ruta_ev' alt='Boleto Evento'></a>"; 
                    } else { echo "Sin imagen"; }
                ?>
            </td>
            <td><?php echo htmlspecialchars($row['cajero']); ?></td>
            <td><?php echo $row['hora_escaneo']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableElement = document.getElementById('table-escaneos-parqueadero');

        if (tableElement) {
            // Inicialización de DataTables
            new DataTable(tableElement, {
                paging: true,
                searching: true,
                info: true,
                ordering: true,
                // Ordenar por la columna "ID Escaneo" (columna 0) descendente por defecto
                order: [[0, 'desc']], 
                language: {
                    // Carga el idioma español
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' 
                }
            });
        }
    });
</script>

<?php
// Se obtiene el contenido del búfer y se asigna a la variable $contenido
$contenido = ob_get_clean();

// Se incluye la plantilla
include 'plantillaAdmin.php';
?>