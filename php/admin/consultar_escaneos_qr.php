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
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Consultar Escaneos QR</title>
<style>
    body { font-family: Arial; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 40px; }
    th, td { border:1px solid #ccc; padding:10px; text-align:left; }
    th { background: #f0f0f0; }
    tr.evento { background-color: #d1f7d6; }
    tr.parqueadero { background-color: #f7e7d1; }
    img { max-width: 100px; }
</style>
</head>
<body>

<h2>Escaneos de Boletos de Evento</h2>
<table>
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
            <td>
                <?php 
                    if(!empty($row['com_qr'])) {
                        $ruta = "../../" . $row['com_qr'];
                        echo "<img src='$ruta' alt='Boleto'>";
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

<h2>Escaneos de Boletos de Parqueadero</h2>
<table>
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
            <td>
                <?php 
                    if(!empty($row['qr_parqueadero'])) {
                        $ruta_parq = "../../" . $row['qr_parqueadero'];
                        echo "<img src='$ruta_parq' alt='Boleto Parqueadero'>";
                    } else { echo "Sin imagen"; }
                ?>
            </td>
            <td>
                <?php 
                    if(!empty($row['qr_evento'])) {
                        $ruta_ev = "../../" . $row['qr_evento'];
                        echo "<img src='$ruta_ev' alt='Boleto Evento'>";
                    } else { echo "Sin imagen"; }
                ?>
            </td>
            <td><?php echo htmlspecialchars($row['cajero']); ?></td>
            <td><?php echo $row['hora_escaneo']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
