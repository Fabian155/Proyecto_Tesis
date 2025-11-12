<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include '../../conexion.php';

// Hora local Ecuador
date_default_timezone_set('America/Guayaquil');

// Preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['ventas']) || !is_array($input['ventas'])) {
    echo json_encode(["success" => false, "message" => "No se recibieron ventas"]);
    exit;
}

$respuestas = [];

pg_query($conn, "BEGIN");

try {
    foreach ($input['ventas'] as $venta) {
        // Validar campos obligatorios
        if (!isset(
            $venta['ven_id_evt'],
            $venta['ven_id_caj'],
            $venta['ven_cor_cli'],
            $venta['ven_tel_cli'],
            $venta['ven_can_bol']
        )) {
            $respuestas[] = ["success" => false, "message" => "Venta incompleta", "data" => $venta];
            continue;
        }

        $evento   = intval($venta['ven_id_evt']);
        $cajero   = intval($venta['ven_id_caj']);
        $correo   = pg_escape_string($conn, $venta['ven_cor_cli']);
        $telefono = pg_escape_string($conn, $venta['ven_tel_cli']);
        $cantidad = intval($venta['ven_can_bol']);

        $premioAsignadoId = 'NULL';
        $premioAsignado = null;

        // Validar evento
        $qEvento = "SELECT evt_tit, evt_pre, evt_disponibles, evt_id_adm
                    FROM tbl_evento 
                    WHERE evt_id=$evento AND evt_est='activo'";
        $rEvento = pg_query($conn, $qEvento);
        if (!$rEvento || pg_num_rows($rEvento) == 0) {
            $respuestas[] = ["success" => false, "message" => "Evento no encontrado", "data" => $venta];
            continue;
        }
        $eventoData = pg_fetch_assoc($rEvento);
        $adminId = $eventoData['evt_id_adm'];

        if ($eventoData['evt_disponibles'] < $cantidad) {
            $respuestas[] = ["success" => false, "message" => "Boletos insuficientes", "data" => $venta];
            continue;
        }

        // Buscar premio disponible
        $qPremio = "SELECT pre_id, pre_nom FROM tbl_premio WHERE pre_id_evt=$evento AND pre_can > 0 LIMIT 1";
        $rPremio = pg_query($conn, $qPremio);

        if ($rPremio && pg_num_rows($rPremio) > 0) {
            $premioData = pg_fetch_assoc($rPremio);
            $premioId = $premioData['pre_id'];

            $qAsignacion = "INSERT INTO tbl_premio_asignado (pre_asg_id_pre, pre_asg_id_adm) 
                            VALUES ($premioId, $adminId) RETURNING pre_asg_id";
            $rAsignacion = pg_query($conn, $qAsignacion);

            if ($rAsignacion) {
                $premioAsignadoId = pg_fetch_assoc($rAsignacion)['pre_asg_id'];
                $premioAsignado = $premioData['pre_nom'];

                pg_query($conn, "UPDATE tbl_premio SET pre_can = pre_can - 1 WHERE pre_id=$premioId");
            }
        }

        // Calcular precio total
        $precioTotal = floatval($eventoData['evt_pre']) * $cantidad;

        // Evitar duplicados (por correo + teléfono + evento + cantidad + total)
        $qDup = "SELECT ven_id FROM tbl_ventas_cajero 
                 WHERE ven_id_evt=$evento AND ven_cor_cli='$correo' AND ven_tel_cli='$telefono' 
                 AND ven_can_bol=$cantidad AND ven_pre_tot=$precioTotal";
        $rDup = pg_query($conn, $qDup);

        if ($rDup && pg_num_rows($rDup) > 0) {
            $respuestas[] = ["success" => false, "message" => "Venta duplicada", "data" => $venta];
            continue;
        }

        // Insertar venta
        $qVenta = "INSERT INTO tbl_ventas_cajero 
                   (ven_id_evt, ven_id_caj, ven_cor_cli, ven_tel_cli, ven_can_bol, ven_pre_tot, ven_id_pre_asg) 
                   VALUES ($evento, $cajero, '$correo', '$telefono', $cantidad, $precioTotal, $premioAsignadoId)
                   RETURNING ven_id";
        $rVenta = pg_query($conn, $qVenta);

        if ($rVenta) {
            $ventaId = pg_fetch_assoc($rVenta)['ven_id'];

            if ($premioAsignadoId != 'NULL') {
                pg_query($conn, "UPDATE tbl_premio_asignado SET pre_asg_id_ven=$ventaId WHERE pre_asg_id=$premioAsignadoId");
            }

            pg_query($conn, "UPDATE tbl_evento SET evt_disponibles = evt_disponibles - $cantidad WHERE evt_id=$evento");

            $respuestas[] = [
                "success" => true,
                "venta_id" => $ventaId,
                "evento" => $eventoData['evt_tit'],
                "cantidad_boletos" => $cantidad,
                "precio_total" => $precioTotal,
                "cliente_correo" => $correo,
                "cliente_telefono" => $telefono,
                "premio_asignado" => $premioAsignado
            ];
        } else {
            $respuestas[] = ["success" => false, "message" => "Error al registrar venta", "data" => $venta];
        }
    }

    pg_query($conn, "COMMIT");
    echo json_encode(["success" => true, "resultados" => $respuestas], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

pg_close($conn);
?>
