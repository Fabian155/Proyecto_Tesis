<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Incluir conexión
include '../../conexion.php';

// Validar campos obligatorios
if (!isset($_POST['evt_tit'], $_POST['evt_des'], $_POST['evt_fec'], $_POST['evt_lug'], $_POST['evt_pre'], $_POST['evt_id_adm'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios"]);
    exit;
}

// Escapar variables
$titulo      = pg_escape_string($conn, $_POST['evt_tit']);
$descripcion = pg_escape_string($conn, $_POST['evt_des']);
$fecha       = pg_escape_string($conn, $_POST['evt_fec']);
$lugar       = pg_escape_string($conn, $_POST['evt_lug']);
$precio      = floatval($_POST['evt_pre']);
$id_admin    = intval($_POST['evt_id_adm']);
$estado      = isset($_POST['evt_est']) ? pg_escape_string($conn, $_POST['evt_est']) : 'pendiente';

// Carpeta para guardar imágenes
$carpetaImagenes = "../../imagenes/imagenes_eventos/";
$imagenRuta = null;

// Subida de imagen
if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK){
    $nombreArchivo = time() . "_" . basename($_FILES['imagen']['name']);
    $rutaDestino = $carpetaImagenes . $nombreArchivo;

    if(!is_dir($carpetaImagenes)){
        mkdir($carpetaImagenes, 0777, true); // crea la carpeta si no existe
    }

    if(move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)){
        $imagenRuta = "imagenes/imagenes_eventos/" . $nombreArchivo;
    }
}

// Insertar evento en la BD
$query = "INSERT INTO tbl_evento 
          (evt_tit, evt_des, evt_fec, evt_lug, evt_pre, evt_id_adm, evt_est, evt_img) 
          VALUES 
          ('$titulo', '$descripcion', '$fecha', '$lugar', $precio, $id_admin, '$estado', " .
          ($imagenRuta ? "'$imagenRuta'" : "NULL") . ")";

$result = pg_query($conn, $query);

if ($result) {
    echo json_encode(["success" => true, "message" => "Evento creado correctamente"]);
} else {
    echo json_encode(["success" => false, "message" => "Error al crear el evento: " . pg_last_error($conn)]);
}
?>
