<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../../conexion.php';

// Leer JSON de entrada
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['com_id'])) {
    echo json_encode(["error" => "Falta el ID de la compra (com_id)."]);
    exit;
}

$compraId = intval($input['com_id']);

try {
    // Consulta con JOIN para traer datos completos
    $qCompra = "
        SELECT 
            c.com_id,
            c.com_can_bol,
            c.com_uso_con,
            c.com_act,
            c.com_pre_tot,
            c.com_met,
            c.com_qr,
            c.com_num_transf,
            c.com_ruta_qr_parq,
            c.com_fec,
            c.com_est_verif,
            u.usr_id,
            u.usr_nom,
            u.usr_ape,
            e.evt_id,
            e.evt_tit
        FROM tbl_compras_boletos c
        JOIN tbl_usuario u ON c.com_id_usr = u.usr_id
        JOIN tbl_evento e ON c.com_id_evt = e.evt_id
        WHERE c.com_id = $compraId
    ";

    $rCompra = pg_query($conn, $qCompra);

    if (!$rCompra || pg_num_rows($rCompra) == 0) {
        throw new Exception("Compra no encontrada.");
    }

    $compra = pg_fetch_assoc($rCompra);

    // Determinar estado del boleto
    if ($compra['com_act'] === 't' && intval($compra['com_uso_con']) >= 1) {
        $estado = 'activo';
    } else {
        $estado = 'desactivado';
    }

    // Evaluar el estado de la transferencia
    switch ($compra['com_est_verif']) {
        case 'por_validar':
            $estado_transferencia = 'Pendiente de validacion';
            break;
        case 'transferencia_no_valida':
            $estado_transferencia = 'Transferencia no valida';
            break;
        case 'validado':
            $estado_transferencia = 'Transferencia validada';
            break;
        default:
            $estado_transferencia = 'Estado desconocido';
    }

    // Preparar respuesta JSON
    echo json_encode([
        "com_id"                => intval($compra['com_id']),
        "cantidad"              => intval($compra['com_can_bol']),
        "usados"                => intval($compra['com_uso_con']),
        "estado"                => $estado,
        "precio_total"          => floatval($compra['com_pre_tot']),
        "metodo_pago"           => $compra['com_met'],
        "qr"                    => $compra['com_qr'],
        "num_transf"            => $compra['com_num_transf'],
        "ruta_qr_parq"          => $compra['com_ruta_qr_parq'],
        "fecha_compra"          => $compra['com_fec'],
        "transferencia_valida"  => $estado_transferencia,
        "usuario"               => [
            "id"        => intval($compra['usr_id']),
            "nombre"    => $compra['usr_nom'],
            "apellido"  => $compra['usr_ape']
        ],
        "evento"                => [
            "id"        => intval($compra['evt_id']),
            "titulo"    => $compra['evt_tit']
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}

pg_close($conn);
?>
