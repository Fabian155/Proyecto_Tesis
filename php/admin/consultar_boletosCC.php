<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

// ================== CONSULTA DE VENTAS POR CAJERO ==================
$query = "
SELECT 
    vc.ven_id,
    e.evt_tit AS evento,
    c.caj_nom AS cajero,
    vc.ven_cor_cli,
    vc.ven_tel_cli,
    vc.ven_can_bol,
    vc.ven_pre_tot,
    vc.ven_fec
FROM tbl_ventas_cajero vc
JOIN tbl_evento e ON vc.ven_id_evt = e.evt_id
JOIN tbl_cajero c ON vc.ven_id_caj = c.caj_id
ORDER BY vc.ven_fec DESC
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
    /* Clase y estilo de tabla DataTables */
    table.data-table {
        border-collapse: collapse;
        width: 100%;
        min-width: 800px;
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
    .no-results {
        text-align: center;
        padding: 20px;
        color: #777;
    }
</style>

<div class="content-wrapper">
    <h2 class="page-title">Ventas Registradas por Cajero</h2>

    <div class="table-container">
        <table class="data-table" id="table-ventas-cajero"> 
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Evento</th>
                    <th>Cajero</th>
                    <th>Correo Cliente</th>
                    <th>Teléfono Cliente</th>
                    <th>Cantidad Boletos</th>
                    <th>Total ($)</th>
                    <th>Fecha Venta</th>
                </tr>
            </thead>
            <tbody>
                <?php if (pg_num_rows($result) > 0): ?>
                    <?php while ($row = pg_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['ven_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['evento']); ?></td>
                            <td><?php echo htmlspecialchars($row['cajero']); ?></td>
                            <td><?php echo htmlspecialchars($row['ven_cor_cli']); ?></td>
                            <td><?php echo htmlspecialchars($row['ven_tel_cli']); ?></td>
                            <td><?php echo $row['ven_can_bol']; ?></td>
                            <td><?php echo number_format($row['ven_pre_tot'], 2); ?></td>
                            <td><?php echo $row['ven_fec']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="no-results">No se encontraron ventas por cajero.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const tableElement = document.getElementById('table-ventas-cajero');
    const tbody = tableElement.querySelector('tbody');
    const firstRow = tbody ? tbody.querySelector('tr') : null;
    const tdCount = firstRow ? firstRow.querySelectorAll('td').length : 0;

    // Inicializa DataTables solo si la primera fila tiene las 8 celdas (hay datos reales)
    if (tdCount === 8) {
        new DataTable(tableElement, {
            paging: true,
            searching: true,
            info: true,
            ordering: true,
            order: [[7, 'desc']],
            language: {
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