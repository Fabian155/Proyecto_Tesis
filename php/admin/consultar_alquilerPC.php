<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

// ================== CONSULTA DE ASIGNACIONES DE CAJEROS ==================
$query = "
SELECT 
    a.asi_id,
    pa.par_nom AS parqueadero,
    p.pue_num AS puesto,
    c.caj_nom AS cajero,
    a.asi_precio,
    a.asi_caj_correo AS correo_cliente,
    a.asi_caj_tel AS telefono_cliente,
    a.asi_fec_cre,
    a.asi_fec_edi
FROM tbl_cajero_asignacion_parqueadero a
JOIN tbl_parqueadero pa ON a.asi_id_par = pa.par_id
JOIN tbl_puestos_parqueadero p ON a.asi_id_pue = p.pue_id
JOIN tbl_cajero c ON a.asi_id_caj = c.caj_id
ORDER BY a.asi_fec_cre DESC
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
    <h2 class="page-title">Asignaciones de Cajeros a Parqueaderos</h2>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Parqueadero</th>
                    <th>Puesto</th>
                    <th>Cajero</th>
                    <th>Precio ($)</th>
                    <th>Correo Cliente</th>
                    <th>Teléfono Cliente</th>
                    <th>Fecha Creación</th>
                    <th>Fecha Edición</th>
                </tr>
            </thead>
            <tbody>
                <?php if (pg_num_rows($result) > 0): ?>
                    <?php while ($row = pg_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['asi_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['parqueadero']); ?></td>
                            <td><?php echo htmlspecialchars($row['puesto']); ?></td>
                            <td><?php echo htmlspecialchars($row['cajero']); ?></td>
                            <td><?php echo number_format($row['asi_precio'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['correo_cliente']); ?></td>
                            <td><?php echo htmlspecialchars($row['telefono_cliente']); ?></td>
                            <td><?php echo $row['asi_fec_cre']; ?></td>
                            <td><?php echo $row['asi_fec_edi']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="no-results">No se encontraron asignaciones de parqueadero.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$contenido = ob_get_clean(); // Guarda el contenido del búfer en la variable $contenido
include 'plantillaAdmin.php'; // Incluye la plantilla que contendrá y mostrará la variable $contenido
?>