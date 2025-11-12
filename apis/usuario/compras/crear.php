<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include '../../../conexion.php';
require '../../../librerias/qr/phpqrcode/qrlib.php';

// 🔹 Establecer la hora local de Ecuador (Guayaquil)
date_default_timezone_set('America/Guayaquil');


$input = json_decode(file_get_contents("php://input"), true);

if (
    !$input || 
    !isset($input['com_id_usr'], $input['com_id_evt'], $input['com_met'], $input['com_can_bol'])
) {
    echo json_encode(["error" => "Faltan datos obligatorios."]);
    exit;
}

$usuario        = intval($input['com_id_usr']);
$evento         = intval($input['com_id_evt']);
$metodo         = pg_escape_string($conn, $input['com_met']);
$cantidad       = intval($input['com_can_bol']);
$reservaPue     = isset($input['pue_id']) ? intval($input['pue_id']) : null;
$parqueaderoId  = isset($input['par_id']) ? intval($input['par_id']) : null;
$numTransf      = isset($input['com_num_transf']) ? pg_escape_string($conn, $input['com_num_transf']) : null;

// 🔹 Nuevo campo: estado de verificación según el método
$estadoVerif = ($metodo === "transferencia") ? "por_validar" : "no_aplica";

if ($metodo === "transferencia" && empty($numTransf)) {
    echo json_encode(["error" => "Debe enviar el número de transferencia para pagos por transferencia."]);
    exit;
}

// Si el método no es transferencia, forzar "No aplica"
if ($metodo !== "transferencia") {
    $numTransf = "No aplica";
}

// Inicializa variables para la lógica de premios
$premioAsignadoId = 'NULL';
$premioAsignado = null;

// Inicia transacción
pg_query($conn, "BEGIN");

try {
    // 1️⃣ Validar usuario
    $qUsuario = "SELECT usr_nom, usr_ape FROM tbl_usuario WHERE usr_id=$usuario";
    $rUsuario = pg_query($conn, $qUsuario);
    if (!$rUsuario || pg_num_rows($rUsuario) == 0) throw new Exception("Usuario no encontrado.");
    $usuarioData = pg_fetch_assoc($rUsuario);
    $nombreUsuario = $usuarioData['usr_nom'] . " " . $usuarioData['usr_ape'];

    // 2️⃣ Validar evento y obtener datos necesarios, incluyendo el ID del administrador
    $qEvento = "SELECT evt_tit, evt_pre, evt_disponibles, evt_fec, evt_lug, evt_id_adm 
                FROM tbl_evento 
                WHERE evt_id=$evento AND evt_est='activo'";
    $rEvento = pg_query($conn, $qEvento);
    if (!$rEvento || pg_num_rows($rEvento) == 0) throw new Exception("Evento no encontrado o no activo.");
    $eventoData = pg_fetch_assoc($rEvento);
    $adminId = $eventoData['evt_id_adm']; // ID del administrador para la asignación del premio

    if ($eventoData['evt_disponibles'] < $cantidad) throw new Exception("No hay suficientes boletos disponibles.");

    // 3️⃣ Validar parqueadero y puesto si aplica
    if ($reservaPue && $parqueaderoId) {
        $qPuesto = "SELECT pue_est, pue_num FROM tbl_puestos_parqueadero 
                    WHERE pue_id=$reservaPue AND pue_id_par=$parqueaderoId";
        $rPuesto = pg_query($conn, $qPuesto);
        if (!$rPuesto || pg_num_rows($rPuesto) == 0) throw new Exception("El puesto no existe en el parqueadero seleccionado.");
        $puesto = pg_fetch_assoc($rPuesto);
        if ($puesto['pue_est'] !== 'disponible') throw new Exception("El puesto ya está ocupado.");
    }
    
    // 4️⃣ Lógica de Asignación de Premio
    $qPremio = "SELECT pre_id, pre_nom FROM tbl_premio WHERE pre_id_evt=$evento AND pre_can > 0 LIMIT 1";
    $rPremio = pg_query($conn, $qPremio);

    if ($rPremio && pg_num_rows($rPremio) > 0) {
        $premioData = pg_fetch_assoc($rPremio);
        $premioId = $premioData['pre_id'];

        // Insertar en tbl_premio_asignado
        $qAsignacion = "INSERT INTO tbl_premio_asignado (pre_asg_id_pre, pre_asg_id_adm) 
                        VALUES ($premioId, $adminId) RETURNING pre_asg_id";
        $rAsignacion = pg_query($conn, $qAsignacion);

        if (!$rAsignacion) throw new Exception("Error al asignar el premio.");
        
        $premioAsignadoId = pg_fetch_assoc($rAsignacion)['pre_asg_id'];
        $premioAsignado = $premioData['pre_nom'];

        // Reducir la cantidad de premios disponibles
        pg_query($conn, "UPDATE tbl_premio SET pre_can = pre_can - 1 WHERE pre_id=$premioId");
    }

    // 5️⃣ Insertar compra
    $precioTotal = floatval($eventoData['evt_pre']) * $cantidad;
    // La variable $premioAsignadoId es 'NULL' o el ID de la asignación.
    $qCompra = "INSERT INTO tbl_compras_boletos 
    (com_id_usr, com_id_evt, com_met, com_can_bol, com_pre_tot, com_num_transf, com_uso_con, com_est_verif, com_id_pre_asg) 
    VALUES ($usuario, $evento, '$metodo', $cantidad, $precioTotal, '$numTransf', $cantidad, '$estadoVerif', $premioAsignadoId) 
    RETURNING com_id";
    
    $rCompra = pg_query($conn, $qCompra);
    if (!$rCompra) throw new Exception("No se pudo registrar la compra.");
    $compra = pg_fetch_assoc($rCompra);
    $compraId = $compra['com_id'];

    // Si se asignó un premio, actualizamos el campo pre_asg_id_com
    if ($premioAsignadoId != 'NULL') {
        pg_query($conn, "UPDATE tbl_premio_asignado SET pre_asg_id_com=$compraId WHERE pre_asg_id=$premioAsignadoId");
    }

    $qrBoletoWebPath = null;
    $qrParqueaderoWebPath = null;

    // 6️⃣ Insertar reserva de parqueadero si aplica
    if ($reservaPue && $parqueaderoId) {
        $qReserva = "INSERT INTO tbl_reservas_parqueadero (res_id_com, res_id_pue, res_id_par) 
                     VALUES ($compraId, $reservaPue, $parqueaderoId) RETURNING res_id";
        $rReserva = pg_query($conn, $qReserva);
        if (!$rReserva) throw new Exception("No se pudo registrar la reserva de parqueadero.");
        $reservaId = pg_fetch_assoc($rReserva)['res_id'];

        // Actualizar puesto como ocupado
        pg_query($conn, "UPDATE tbl_puestos_parqueadero SET pue_est='ocupado' WHERE pue_id=$reservaPue");
        pg_query($conn, "UPDATE tbl_compras_boletos SET com_id_res_par=$reservaId WHERE com_id=$compraId");
    }

    // 7️⃣ Actualizar boletos disponibles
    pg_query($conn, "UPDATE tbl_evento SET evt_disponibles = evt_disponibles - $cantidad WHERE evt_id=$evento");

    // 8️⃣ Generar QR
    $boletoDir = "../../../imagenes/boleto/";
    $parqueaderoDir = "../../../imagenes/parqueadero/";
    if (!file_exists($boletoDir)) mkdir($boletoDir, 0777, true);
    if (!file_exists($parqueaderoDir)) mkdir($parqueaderoDir, 0777, true);

    $timestamp = time();
    $randomStr = bin2hex(random_bytes(4));

    // QR Boleto
    $nombreArchivoBoleto = "boleto_{$timestamp}_{$randomStr}.png";
    $qrBoletoPath = $boletoDir . $nombreArchivoBoleto;
    $qrTextoBoleto = "ID Compra: $compraId\n".
                     "Usuario: $nombreUsuario\n".
                     "Evento: {$eventoData['evt_tit']}\n".
                     "Fecha: {$eventoData['evt_fec']}\n".
                     "Lugar: {$eventoData['evt_lug']}\n".
                     "Cantidad: $cantidad\n".
                     "Total: $precioTotal\n".
                     "Método: $metodo\n".
                     "N° Transferencia: $numTransf";

    QRcode::png($qrTextoBoleto, $qrBoletoPath, QR_ECLEVEL_L, 4, 2);
    $qrBoletoWebPath = "imagenes/boleto/" . $nombreArchivoBoleto;
    pg_query($conn, "UPDATE tbl_compras_boletos SET com_qr='$qrBoletoWebPath' WHERE com_id=$compraId");

    // QR Parqueadero
    if (isset($reservaId)) {
        $qParqueadero = "SELECT par_nom FROM tbl_parqueadero WHERE par_id=$parqueaderoId";
        $rParqueadero = pg_query($conn, $qParqueadero);
        $nombreParqueadero = pg_fetch_assoc($rParqueadero)['par_nom'];

        $nombreArchivoParqueo = "parqueo_{$timestamp}_{$randomStr}.png";
        $qrParqueaderoPath = $parqueaderoDir . $nombreArchivoParqueo;
        $qrTextoParqueo = "ID Reserva: $reservaId\n".
                          "Parqueadero: $nombreParqueadero\n".
                          "Usuario: $nombreUsuario\n".
                          "Evento: {$eventoData['evt_tit']}\n".
                          "Puesto N°: {$puesto['pue_num']}\n".
                          "Fecha: {$eventoData['evt_fec']}";
        QRcode::png($qrTextoParqueo, $qrParqueaderoPath, QR_ECLEVEL_L, 4, 2);
        $qrParqueaderoWebPath = "imagenes/parqueadero/" . $nombreArchivoParqueo;

        // Guardar la ruta en la tabla compras
        pg_query($conn, "UPDATE tbl_compras_boletos SET com_ruta_qr_parq='$qrParqueaderoWebPath' WHERE com_id=$compraId");
    }

    pg_query($conn, "COMMIT");

    // 9️⃣ Respuesta
    $respuesta = [
        "compra_id" => $compraId,
        "evento" => $eventoData['evt_tit'],
        "usuario" => $nombreUsuario,
        "cantidad_boletos" => $cantidad,
        "precio_total" => $precioTotal,
        "qr_boleto" => $qrBoletoWebPath,
        "qr_parqueadero" => $qrParqueaderoWebPath,
        "estado_verificacion" => $estadoVerif
    ];

    if ($premioAsignado) {
        $respuesta['premio_asignado'] = [
            "id_asignacion" => intval($premioAsignadoId),
            "nombre" => $premioAsignado
        ];
    }

    echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    // Si se asignó un premio pero falló la compra, debemos revertir la cantidad.
    // Esto es un punto delicado, pero el ROLLBACK debería revertir todo (incluyendo la reducción de 'pre_can').
    // Si la conexión a la base de datos es robusta, el ROLLBACK se encarga de esto.

    echo json_encode(["error" => $e->getMessage()]);
}

pg_close($conn);
?>