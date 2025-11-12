<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

// =====================
// ESCANEOS DE BOLETOS DE EVENTO
// =====================
$query_esc_boletos = "
SELECT esc.esc_id, esc.esc_fec AS hora_escaneo, esc.esc_boletosusado,
       cb.com_id, cb.com_qr, cb.com_met, cb.com_num_transf,
       u.usr_nom, u.usr_ape, u.usr_cor, u.usr_cel,
       e.evt_tit,
       caj.caj_nom AS cajero
FROM tbl_escaneosBoletos esc
JOIN tbl_compras_boletos cb ON esc.esc_id_com = cb.com_id
JOIN tbl_usuario u ON cb.com_id_usr = u.usr_id
JOIN tbl_evento e ON cb.com_id_evt = e.evt_id
JOIN tbl_cajero caj ON esc.esc_id_caj = caj.caj_id
ORDER BY esc.esc_id DESC
";

$result_esc_boletos = pg_query($conn, $query_esc_boletos);

// =====================
// ASIGNACIÓN DEL CONTENIDO A LA VARIABLE $contenido
// =====================
ob_start();
?>

<h2>Escaneos de Boletos de Evento</h2>

<style>
/* Se mantiene la clase original pero también se añade .data-table para consistencia */
.tabla-evento, .data-table { 
    font-size: 12px;       /* Letras más pequeñas */
    border-collapse: collapse;
    width: 100%;
}

.tabla-evento th, .tabla-evento td, .data-table th, .data-table td {
    padding: 5px 8px;      /* Menos espacio */
    text-align: left;
    border: 1px solid #ccc;
}

.tabla-evento img, .data-table img {
    max-width: 50px;       /* QR más pequeño */
    max-height: 50px;
}

/* Estilos de encabezado DataTables (opcional, si no están definidos globalmente) */
.data-table th {
    background-color: #34495e;
    color: white;
    text-transform: uppercase;
    font-weight: bold;
}
</style>

<table class="tabla-evento data-table" id="table-escaneos-boletos">
    <thead>
        <tr>
            <th>ID Escaneo</th>
            <th>Dueño del Boleto</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Evento</th>
            <th>QR Boleto</th>
            <th>Tipo de Pago</th>
            <th>Número de Transferencia</th>
            <th>Cajero que Escaneó</th>
            <th>Hora Escaneo</th>
            <th>Boletos Usados</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = pg_fetch_assoc($result_esc_boletos)): ?>
        <tr class="evento">
            <td><?php echo $row['esc_id']; ?></td>
            <td><?php echo htmlspecialchars($row['usr_nom'].' '.$row['usr_ape']); ?></td>
            <td><?php echo htmlspecialchars($row['usr_cor']); ?></td>
            <td><?php echo htmlspecialchars($row['usr_cel']); ?></td>
            <td><?php echo htmlspecialchars($row['evt_tit']); ?></td>
            <td style="text-align: center;">
                <?php 
                    if(!empty($row['com_qr'])) {
                        $ruta = "../../" . $row['com_qr'];
                        // Se agrega target="_blank" para abrir la imagen en otra pestaña
                        echo "<a href='$ruta' target='_blank'><img src='$ruta' alt='Boleto'></a>"; 
                    } else { echo "Sin imagen"; }
                ?>
            </td>
            <td><?php echo htmlspecialchars($row['com_met']); ?></td>
            <td><?php echo !empty($row['com_num_transf']) ? htmlspecialchars($row['com_num_transf']) : '-'; ?></td>
            <td><?php echo htmlspecialchars($row['cajero']); ?></td>
            <td><?php echo $row['hora_escaneo']; ?></td>
            <td><?php echo $row['esc_boletosusado']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableElement = document.getElementById('table-escaneos-boletos');

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