<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

// Directorios base para las imágenes. 
$base_dir_imagenes_db = 'apis/imagenes/pagina/';
$dir_logos = $base_dir_imagenes_db . 'logos/';
$dir_nosotros = $base_dir_imagenes_db . 'nosotros/';
$dir_patrocinadores = $base_dir_imagenes_db . 'patrocinadores/';
$dir_redes = $base_dir_imagenes_db . 'redes/'; // Directorio para imágenes de redes

// ================== FUNCIONES DE UTILIDAD ==================

/**
 * Guarda el archivo subido en la ruta destino y devuelve la ruta relativa para la DB.
 * Elimina la imagen anterior si se proporciona.
 */
function manejar_subida_imagen($file_data, $directorio_destino_db, $ruta_anterior_db = null) {
    // La ruta absoluta de la raíz del proyecto, hasta htdocs/IP_Eventos
    $raiz_proyecto = realpath(__DIR__ . '/../../'); 

    if (isset($file_data) && $file_data['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = time() . "_" . basename($file_data['name']);
        // Ruta absoluta donde se guardará el archivo en el sistema de archivos
        $ruta_destino_absoluta = $raiz_proyecto . '/' . $directorio_destino_db . $nombreArchivo;
        
        // Crear directorio si no existe
        $directorio_absoluto = dirname($ruta_destino_absoluta);
        if (!is_dir($directorio_absoluto)) {
            mkdir($directorio_absoluto, 0777, true);
        }

        if (move_uploaded_file($file_data['tmp_name'], $ruta_destino_absoluta)) {
            // Eliminar imagen anterior si existe y se subió una nueva (solo si es edición)
            if ($ruta_anterior_db) {
                // No eliminamos la imagen si es la misma ruta, solo si es una nueva subida.
                // Esta lógica se maneja mejor en el CRUD para decidir si se elimina la vieja.
            }
            // Retorna la ruta relativa al directorio raíz del proyecto para la BD
            return $directorio_destino_db . $nombreArchivo;
        }
    }
    return null;
}

/**
 * Elimina un archivo de imagen si existe, utilizando la ruta relativa de la BD.
 * NOTA: Esta función ya no se usa en el CRUD de LOGOS/PATROCINADORES/REDES
 * para evitar la pérdida total, solo se usa en las imágenes de Nosotros.
 */
function eliminar_imagen($ruta_relativa_db) {
    if ($ruta_relativa_db) {
        $ruta_completa_imagen = realpath(__DIR__ . '/../../') . '/' . $ruta_relativa_db;
        if (file_exists($ruta_completa_imagen)) {
            unlink($ruta_completa_imagen);
        }
    }
}


// ================== ACCIONES AJAX (Backend) ==================

// -------------------- CAMBIAR ESTADO (Único Activo: Logos) --------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'activar_logo_unico') {
    $id = intval($_POST['id'] ?? 0);
    
    pg_query($conn, "BEGIN");
    try {
        // 1. Desactivar todos los demás logos
        $query_desactivar = "UPDATE tbl_logos SET log_est='desactivado'";
        pg_query($conn, $query_desactivar);
        
        // 2. Activar el logo seleccionado
        $query_activar = "UPDATE tbl_logos SET log_est='activo', log_fec_edi=CURRENT_TIMESTAMP WHERE log_id=$id";
        $res_db = pg_query($conn, $query_activar);

        if ($res_db) {
            pg_query($conn, "COMMIT");
            echo json_encode(["success" => true, "mensaje" => "Logo ID $id activado correctamente. Los demás han sido desactivados."]);
        } else {
            pg_query($conn, "ROLLBACK");
            throw new Exception("Error al activar el logo.");
        }
    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        echo json_encode(["success" => false, "mensaje" => "Error: " . $e->getMessage()]);
    }
    exit;
}

// -------------------- CAMBIAR ESTADO (Individual: Patrocinadores/Redes) --------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estado_individual') {
    $tipo = $_POST['tipo'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $estado = pg_escape_string($conn, $_POST['estado'] ?? 'desactivado');
    $tabla = '';
    $id_col = '';
    $est_col = '';
    $fec_col = '';

    switch ($tipo) {
        case 'patrocinadores':
            $tabla = 'tbl_patrocinadores';
            $id_col = 'pat_id';
            $est_col = 'pat_est';
            $fec_col = 'pat_fec_edi';
            break;
        case 'redes':
            $tabla = 'tbl_redes_sociales';
            $id_col = 'red_id';
            $est_col = 'red_est';
            $fec_col = 'red_fec_edi';
            break;
        default:
            echo json_encode(["success" => false, "mensaje" => "Tipo de tabla no válido para cambiar estado."]);
            exit;
    }

    $query = "UPDATE $tabla SET $est_col='$estado', $fec_col=CURRENT_TIMESTAMP WHERE $id_col=$id";
    $res_db = pg_query($conn, $query);

    if ($res_db) {
        $accion = $estado === 'activo' ? 'activado' : 'desactivado';
        echo json_encode(["success" => true, "mensaje" => ucfirst($tipo) . " $id $accion correctamente."]);
    } else {
        echo json_encode(["success" => false, "mensaje" => "Error al cambiar estado: " . pg_last_error($conn)]);
    }
    exit;
}


// -------------------- LECTURA (Listar) --------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'listar') {
    $tipo = $_POST['tipo'] ?? '';
    $html = "";
    $query = "";

    switch ($tipo) {
        case 'logos':
            $query = "SELECT * FROM tbl_logos ORDER BY log_id DESC";
            $result = pg_query($conn, $query);
            while ($row = pg_fetch_assoc($result)) {
                // Botón para Activar/Desactivar Logo. Solo uno puede estar activo.
                $es_activo = $row['log_est'] === 'activo';
                $btn_clase = $es_activo ? 'btn-success' : 'btn-secondary';
                $btn_texto = $es_activo ? 'Activo (Único)' : 'Activar';

                $html .= "<tr>
                            <td>{$row['log_id']}</td>
                            <td>" . htmlspecialchars($row['log_nom']) . "</td>
                            <td><img src='../../{$row['log_rut']}' style='max-width:100px;'></td>
                            <td>
                                <button class='btn $btn_clase btn-sm' onclick='activarLogoUnico({$row['log_id']}, \"{$row['log_est']}\")'>$btn_texto</button>
                            </td>
                            <td>
                                <button class='btn btn-warning btn-sm' onclick='obtenerItem({$row['log_id']}, \"logos\")'>Editar</button>
                            </td>
                        </tr>";
            }
            break;

        case 'patrocinadores':
            $query = "SELECT * FROM tbl_patrocinadores ORDER BY pat_id DESC";
            $result = pg_query($conn, $query);
            while ($row = pg_fetch_assoc($result)) {
                // Botón para Activar/Desactivar Patrocinador (Individual)
                $es_activo = $row['pat_est'] === 'activo';
                $btn_clase = $es_activo ? 'btn-success' : 'btn-secondary';
                $btn_texto = $es_activo ? 'Desactivar' : 'Activar';
                $nuevo_estado = $es_activo ? 'desactivado' : 'activo';

                $html .= "<tr>
                            <td>{$row['pat_id']}</td>
                            <td>" . htmlspecialchars($row['pat_nom']) . "</td>
                            <td><img src='../../{$row['pat_img']}' style='max-width:100px;'></td>
                            <td>
                                <button class='btn $btn_clase btn-sm' onclick='cambiarEstado({$row['pat_id']}, \"patrocinadores\", \"$nuevo_estado\")'>$btn_texto</button>
                            </td>
                            <td>
                                <button class='btn btn-warning btn-sm' onclick='obtenerItem({$row['pat_id']}, \"patrocinadores\")'>Editar</button>
                            </td>
                        </tr>";
            }
            break;

        case 'redes':
            $query = "SELECT * FROM tbl_redes_sociales ORDER BY red_id DESC";
            $result = pg_query($conn, $query);
            while ($row = pg_fetch_assoc($result)) {
                // Botón para Activar/Desactivar Red Social (Individual)
                $es_activo = $row['red_est'] === 'activo';
                $btn_clase = $es_activo ? 'btn-success' : 'btn-secondary';
                $btn_texto = $es_activo ? 'Desactivar' : 'Activar';
                $nuevo_estado = $es_activo ? 'desactivado' : 'activo';

                $html .= "<tr>
                            <td>{$row['red_id']}</td>
                            <td>" . htmlspecialchars($row['red_nom']) . "</td>
                            <td>" . htmlspecialchars($row['red_url']) . "</td>
                            <td>";
                if ($row['red_ico']) {
                    $html .= "<img src='../../{$row['red_ico']}' style='max-width:50px;'>";
                } else {
                    $html .= "Sin ícono";
                }
                $html .= "</td>
                            <td>
                                <button class='btn $btn_clase btn-sm' onclick='cambiarEstado({$row['red_id']}, \"redes\", \"$nuevo_estado\")'>$btn_texto</button>
                            </td>
                            <td>
                                <button class='btn btn-warning btn-sm' onclick='obtenerItem({$row['red_id']}, \"redes\")'>Editar</button>
                            </td>
                        </tr>";
            }
            break;
        
        case 'nosotros':
            $query = "SELECT * FROM tbl_nosotros LIMIT 1";
            $res = pg_query($conn, $query);
            $data = pg_fetch_assoc($res);
            echo json_encode($data ? $data : ["success" => false]);
            exit;
    }

    echo $html;
    exit;
}

// -------------------- OBTENER (Para editar) --------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'obtener') {
    $tipo = $_POST['tipo'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $res = null;

    switch ($tipo) {
        case 'logos':
            $res = pg_query($conn, "SELECT * FROM tbl_logos WHERE log_id=$id");
            break;
        case 'patrocinadores':
            $res = pg_query($conn, "SELECT * FROM tbl_patrocinadores WHERE pat_id=$id");
            break;
        case 'redes':
            $res = pg_query($conn, "SELECT * FROM tbl_redes_sociales WHERE red_id=$id");
            break;
        case 'nosotros':
            $res = pg_query($conn, "SELECT * FROM tbl_nosotros WHERE nos_id=$id LIMIT 1");
            break;
    }
    
    echo json_encode($res ? pg_fetch_assoc($res) : null);
    exit;
}

// -------------------- CREAR / ACTUALIZAR --------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'crear' || ($_POST['accion'] ?? '') === 'actualizar')) {
    $accion = $_POST['accion'];
    $tipo = $_POST['tipo'] ?? '';
    $id_key = substr($tipo, 0, 3) . '_id';
    $id = intval($_POST[$id_key] ?? 0); 
    $res_db = false;
    $mensaje = "";

    pg_query($conn, "BEGIN");

    try {
        switch ($tipo) {
            // LOGOS
            case 'logos':
                $nombre = pg_escape_string($conn, $_POST['log_nom']);
                // El estado siempre será desactivado al crear/actualizar, 
                // la activación se maneja con el botón específico (activarLogoUnico)
                $estado = 'desactivado'; 
                $ruta_anterior = null;
                $imagenRuta = null;

                if ($accion === 'actualizar') {
                    $query_old_img = "SELECT log_rut, log_est FROM tbl_logos WHERE log_id = $id";
                    $res_old_img = pg_query($conn, $query_old_img);
                    $old_img_data = pg_fetch_assoc($res_old_img);
                    $ruta_anterior = $old_img_data['log_rut'] ?? null;
                    $estado = $old_img_data['log_est'] ?? 'desactivado'; // Mantiene el estado anterior
                }

                if (isset($_FILES['log_rut']) && $_FILES['log_rut']['error'] === UPLOAD_ERR_OK) {
                    $imagenRuta = manejar_subida_imagen($_FILES['log_rut'], $dir_logos, $ruta_anterior);
                }

                if ($accion === 'crear') {
                    if (!$imagenRuta) throw new Exception("Debe subir una imagen para crear un logo.");
                    $query = "INSERT INTO tbl_logos (log_nom, log_rut, log_est) VALUES ('$nombre', '$imagenRuta', '$estado')";
                    $res_db = pg_query($conn, $query);
                    $mensaje = "Logo creado correctamente. Recuerde activarlo para que sea visible.";
                } else {
                    $query = "UPDATE tbl_logos SET log_nom='$nombre', log_fec_edi=CURRENT_TIMESTAMP";
                    if ($imagenRuta) {
                        $query .= ", log_rut='$imagenRuta'";
                    }
                    $query .= " WHERE log_id=$id";
                    $res_db = pg_query($conn, $query);
                    $mensaje = "Logo actualizado correctamente.";
                }
                break;
            
            // PATROCINADORES
            case 'patrocinadores':
                $nombre = pg_escape_string($conn, $_POST['pat_nom']);
                $estado = pg_escape_string($conn, $_POST['pat_est']);
                $ruta_anterior = null;
                $imagenRuta = null;

                if ($accion === 'actualizar') {
                    $query_old_img = "SELECT pat_img FROM tbl_patrocinadores WHERE pat_id = $id";
                    $res_old_img = pg_query($conn, $query_old_img);
                    $old_img_data = pg_fetch_assoc($res_old_img);
                    $ruta_anterior = $old_img_data['pat_img'] ?? null;
                }

                if (isset($_FILES['pat_img']) && $_FILES['pat_img']['error'] === UPLOAD_ERR_OK) {
                    $imagenRuta = manejar_subida_imagen($_FILES['pat_img'], $dir_patrocinadores, $ruta_anterior);
                }

                if ($accion === 'crear') {
                    if (!$imagenRuta) throw new Exception("Debe subir una imagen para crear un patrocinador.");
                    $query = "INSERT INTO tbl_patrocinadores (pat_nom, pat_img, pat_est) VALUES ('$nombre', '$imagenRuta', '$estado')";
                    $res_db = pg_query($conn, $query);
                    $mensaje = "Patrocinador creado correctamente.";
                } else {
                    $query = "UPDATE tbl_patrocinadores SET pat_nom='$nombre', pat_est='$estado', pat_fec_edi=CURRENT_TIMESTAMP";
                    if ($imagenRuta) {
                        $query .= ", pat_img='$imagenRuta'";
                    }
                    $query .= " WHERE pat_id=$id";
                    $res_db = pg_query($conn, $query);
                    $mensaje = "Patrocinador actualizado correctamente.";
                }
                break;

            // REDES SOCIALES
            case 'redes':
                $nombre = pg_escape_string($conn, $_POST['red_nom']);
                $url = pg_escape_string($conn, $_POST['red_url']);
                $estado = pg_escape_string($conn, $_POST['red_est']);
                $ruta_anterior = null;
                $imagenRuta = null;

                if ($accion === 'actualizar') {
                    $query_old_img = "SELECT red_ico FROM tbl_redes_sociales WHERE red_id = $id";
                    $res_old_img = pg_query($conn, $query_old_img);
                    $old_img_data = pg_fetch_assoc($res_old_img);
                    $ruta_anterior = $old_img_data['red_ico'] ?? null;
                }

                if (isset($_FILES['red_ico']) && $_FILES['red_ico']['error'] === UPLOAD_ERR_OK) { 
                    $imagenRuta = manejar_subida_imagen($_FILES['red_ico'], $dir_redes, $ruta_anterior);
                } else if ($accion === 'crear') {
                    throw new Exception("Debe subir una imagen (ícono) para crear una red social.");
                }

                if ($accion === 'crear') {
                    $query = "INSERT INTO tbl_redes_sociales (red_nom, red_url, red_ico, red_est) VALUES ('$nombre', '$url', '$imagenRuta', '$estado')";
                    $res_db = pg_query($conn, $query);
                    $mensaje = "Red social creada correctamente.";
                } else {
                    $query = "UPDATE tbl_redes_sociales SET red_nom='$nombre', red_url='$url', red_est='$estado', red_fec_edi=CURRENT_TIMESTAMP";
                    if ($imagenRuta) {
                        $query .= ", red_ico='$imagenRuta'"; 
                    }
                    $query .= " WHERE red_id=$id";
                    $res_db = pg_query($conn, $query);
                    $mensaje = "Red social actualizada correctamente.";
                }
                break;

            // NOSOTROS (Solo Actualizar/Crear el registro único)
            case 'nosotros':
                $nombre_emp = pg_escape_string($conn, $_POST['nos_nom_emp']);
                $historia = pg_escape_string($conn, $_POST['nos_hist']);
                $mision = pg_escape_string($conn, $_POST['nos_mis']);
                $vision = pg_escape_string($conn, $_POST['nos_vis']);
                $celular = pg_escape_string($conn, $_POST['nos_cel']);
                $direccion = pg_escape_string($conn, $_POST['nos_dir']);
                $correo = pg_escape_string($conn, $_POST['nos_correo']);
                $mensaje_ini = pg_escape_string($conn, $_POST['nos_men_ini']);
                $link_app = pg_escape_string($conn, $_POST['nos_link_app']);
                $num_cuenta = pg_escape_string($conn, $_POST['nos_num_cuenta']);
                $nom_banco = pg_escape_string($conn, $_POST['nos_nom_banco']);
                $estado = pg_escape_string($conn, $_POST['nos_est']);

                $query_old_img = "SELECT nos_img_hist, nos_img_mis, nos_img_vis, nos_logo_banco FROM tbl_nosotros WHERE nos_id = $id";
                $res_old_img = pg_query($conn, $query_old_img);
                $old_img_data = pg_fetch_assoc($res_old_img);

                // Manejo de las 4 imágenes de Nosotros
                $img_hist = isset($_FILES['nos_img_hist']) ? manejar_subida_imagen($_FILES['nos_img_hist'], $dir_nosotros, $old_img_data['nos_img_hist'] ?? null) : null;
                $img_mis = isset($_FILES['nos_img_mis']) ? manejar_subida_imagen($_FILES['nos_img_mis'], $dir_nosotros, $old_img_data['nos_img_mis'] ?? null) : null;
                $img_vis = isset($_FILES['nos_img_vis']) ? manejar_subida_imagen($_FILES['nos_img_vis'], $dir_nosotros, $old_img_data['nos_img_vis'] ?? null) : null;
                $logo_banco = isset($_FILES['nos_logo_banco']) ? manejar_subida_imagen($_FILES['nos_logo_banco'], $dir_nosotros, $old_img_data['nos_logo_banco'] ?? null) : null;

                // Construcción dinámica de la consulta
                $query_update_parts = [
                    "nos_nom_emp='$nombre_emp'", "nos_hist='$historia'", "nos_mis='$mision'", "nos_vis='$vision'",
                    "nos_cel='$celular'", "nos_dir='$direccion'", "nos_correo='$correo'", "nos_men_ini='$mensaje_ini'",
                    "nos_link_app='$link_app'", "nos_num_cuenta='$num_cuenta'", "nos_nom_banco='$nom_banco'", "nos_est='$estado'",
                    "nos_fec_edi=CURRENT_TIMESTAMP"
                ];

                if ($img_hist) $query_update_parts[] = "nos_img_hist='$img_hist'";
                if ($img_mis) $query_update_parts[] = "nos_img_mis='$img_mis'";
                if ($img_vis) $query_update_parts[] = "nos_img_vis='$img_vis'";
                if ($logo_banco) $query_update_parts[] = "nos_logo_banco='$logo_banco'";

                $query_update = "UPDATE tbl_nosotros SET " . implode(', ', $query_update_parts) . " WHERE nos_id=$id";
                
                // Si es un nuevo registro (ID 0 o no encontrado)
                if ($id === 0 || pg_num_rows(pg_query($conn, "SELECT nos_id FROM tbl_nosotros WHERE nos_id=$id")) === 0) {
                    $query_insert = "INSERT INTO tbl_nosotros (nos_nom_emp, nos_hist, nos_img_hist, nos_mis, nos_img_mis, nos_vis, nos_img_vis, nos_cel, nos_dir, nos_correo, nos_men_ini, nos_link_app, nos_num_cuenta, nos_nom_banco, nos_logo_banco, nos_est)
                    VALUES ('$nombre_emp', '$historia', " . ($img_hist ? "'$img_hist'" : "NULL") . ", '$mision', " . ($img_mis ? "'$img_mis'" : "NULL") . ", '$vision', " . ($img_vis ? "'$img_vis'" : "NULL") . ", '$celular', '$direccion', '$correo', '$mensaje_ini', '$link_app', '$num_cuenta', '$nom_banco', " . ($logo_banco ? "'$logo_banco'" : "NULL") . ", '$estado')";
                    $res_db = pg_query($conn, $query_insert);
                    $mensaje = "Información 'Nosotros' creada correctamente.";
                } else {
                    $res_db = pg_query($conn, $query_update);
                    $mensaje = "Información 'Nosotros' actualizada correctamente.";
                }
                break;

            default:
                throw new Exception("Tipo de acción no válido.");
        }

        if ($res_db) {
            pg_query($conn, "COMMIT");
            echo json_encode(["success" => true, "mensaje" => $mensaje]);
        } else {
            pg_query($conn, "ROLLBACK");
            throw new Exception("Error al ejecutar la consulta en la base de datos: " . pg_last_error($conn));
        }

    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        echo json_encode(["success" => false, "mensaje" => "Error: " . $e->getMessage()]);
    }
    exit;
}

// ================== HTML y JAVASCRIPT (Frontend) ==================

ob_start();
?>

<div style="text-align: center;">
    <h2 style="margin-bottom:20px;">Gestión de Información de la Página</h2>
    <div style="margin-bottom:30px;">
        <button id="btnGestionLogos" class="btn btn-primary" onclick="mostrarSeccion('logos')">Logos</button>
        <button id="btnGestionPatrocinadores" class="btn btn-primary" onclick="mostrarSeccion('patrocinadores')">Patrocinadores</button>
        <button id="btnGestionRedes" class="btn btn-primary" onclick="mostrarSeccion('redes')">Redes Sociales</button>
        <button id="btnGestionNosotros" class="btn btn-info" onclick="mostrarSeccion('nosotros')">Información Nosotros</button>
    </div>

    <div class="mensaje" id="mensaje" style="color:green; margin:10px 0; font-size:12px; text-align:center;"></div>

    <div id="formularioContainer" style="display: none; margin: 0 auto; width: fit-content;">
        <h3 id="formTitle"></h3>
        <form id="formItem" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:8px; width:350px; margin:0 auto 20px auto;"></form>
    </div>

    <div id="tablaContainer" class="table-container" style="overflow-x:auto;">
        <h3 id="tableTitle"></h3>
        <button id="btnAgregar" class="btn btn-success" style="margin-bottom: 15px; display: none;"></button>
        <table style="border-collapse:collapse; width:100%; font-size:12px; margin:0 auto;">
            <thead>
                <tr id="tableHeaders"></tr>
            </thead>
            <tbody id="tableBody"></tbody>
        </table>
    </div>

    <div id="nosotrosContainer" style="display: none; margin: 0 auto; width: 600px;">
        <h3>Editar Información de "Nosotros"</h3>
        <form id="formNosotros" enctype="multipart/form-data" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin:20px 0;">
            <input type="hidden" id="nos_id" name="nos_id">
            <input type="hidden" name="tipo" value="nosotros">
            
            <label style="grid-column: 1 / 3;">Nombre de la Empresa:</label>
            <input type="text" id="nos_nom_emp" name="nos_nom_emp" required style="grid-column: 1 / 3; padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">

            <label style="grid-column: 1 / 3;">Mensaje de Inicio:</label>
            <textarea id="nos_men_ini" name="nos_men_ini" required style="grid-column: 1 / 3; padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;"></textarea>

            <label>Historia:</label>
            <textarea id="nos_hist" name="nos_hist" style="padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;"></textarea>
            <label>Imagen Historia (Dejar vacío para mantener):</label>
            <input type="file" id="nos_img_hist" name="nos_img_hist" accept="image/*" style="padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">

            <label>Misión:</label>
            <textarea id="nos_mis" name="nos_mis" style="padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;"></textarea>
            <label>Imagen Misión (Dejar vacío para mantener):</label>
            <input type="file" id="nos_img_mis" name="nos_img_mis" accept="image/*" style="padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">

            <label>Visión:</label>
            <textarea id="nos_vis" name="nos_vis" style="padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;"></textarea>
            <label>Imagen Visión (Dejar vacío para mantener):</label>
            <input type="file" id="nos_img_vis" name="nos_img_vis" accept="image/*" style="padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">

            <label style="grid-column: 1 / 3;">Celular:</label>
            <input type="text" id="nos_cel" name="nos_cel" style="grid-column: 1 / 3; padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">
            <label style="grid-column: 1 / 3;">Dirección:</label>
            <input type="text" id="nos_dir" name="nos_dir" style="grid-column: 1 / 3; padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">
            <label style="grid-column: 1 / 3;">Correo:</label>
            <input type="email" id="nos_correo" name="nos_correo" style="grid-column: 1 / 3; padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">
            <label style="grid-column: 1 / 3;">Link App:</label>
            <input type="url" id="nos_link_app" name="nos_link_app" style="grid-column: 1 / 3; padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">

            <label>Número de Cuenta:</label>
            <input type="text" id="nos_num_cuenta" name="nos_num_cuenta" style="padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">
            <label>Nombre del Banco:</label>
            <input type="text" id="nos_nom_banco" name="nos_nom_banco" style="padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">
            <label style="grid-column: 1 / 3;">Logo del Banco (Dejar vacío para mantener):</label>
            <input type="file" id="nos_logo_banco" name="nos_logo_banco" accept="image/*" style="grid-column: 1 / 3; padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">
            
            <label style="grid-column: 1 / 3;">Estado (General de la sección Nosotros):</label>
            <select id="nos_est" name="nos_est" style="grid-column: 1 / 3; padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">
                <option value="activo">Activo</option>
                <option value="desactivado">Desactivado</option>
            </select>
            
            <button type="submit" class="btn btn-success" style="grid-column: 1 / 3; padding:10px;">Guardar Información</button>
        </form>
    </div>

</div>

<style>
    /* Estilos base */
    .btn { padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; margin: 2px; }
    .btn-primary { background: #2196F3; color: white; }
    .btn-success { background: #4CAF50; color: white; }
    .btn-secondary { background: #6c757d; color: white; } /* Nuevo para desactivado */
    .btn-danger { background: #f44336; color: white; }
    .btn-warning { background: #ff9800; color: white; }
    .btn-info { background: #00bcd4; color: white; }
    .mensaje { transition: opacity 0.5s ease; }
    .alert-success { color: green; }
    .alert-danger { color: red; }
    table, th, td { border: 1px solid #ccc; padding: 6px; text-align: center; }
    th { background: #f2f2f2; }
</style>

<script>
    let seccionActual = '';

    // Mapeo de configuración de secciones (Logos, Patrocinadores, Redes)
    const secciones = {
        logos: {
            titulo: 'Gestión de Logos',
            ruta_columna: 'log_rut', 
            // Quitamos log_est del formulario de edición/creación
            campos: [
                { id: 'log_id', type: 'hidden' },
                { id: 'log_nom', label: 'Nombre:', type: 'text', required: true },
                { id: 'log_rut', label: 'Imagen:', type: 'file', accept: 'image/*' }
            ],
            headers: ['ID', 'Nombre', 'Imagen', 'Estado', 'Acciones'] // Estado ya no es un campo de edición
        },
        patrocinadores: {
            titulo: 'Gestión de Patrocinadores',
            ruta_columna: 'pat_img',
            campos: [
                { id: 'pat_id', type: 'hidden' },
                { id: 'pat_nom', label: 'Nombre:', type: 'text', required: true },
                // pat_est se maneja con el botón directo en la tabla
                { id: 'pat_img', label: 'Imagen:', type: 'file', accept: 'image/*' }
            ],
            headers: ['ID', 'Nombre', 'Imagen', 'Estado', 'Acciones']
        },
        redes: {
            titulo: 'Gestión de Redes Sociales',
            ruta_columna: 'red_ico',
            campos: [
                { id: 'red_id', type: 'hidden' },
                { id: 'red_nom', label: 'Nombre:', type: 'text', required: true },
                { id: 'red_url', label: 'URL:', type: 'url', required: true },
                // red_est se maneja con el botón directo en la tabla
                { id: 'red_ico', label: 'Ícono (Imagen):', type: 'file', accept: 'image/*' }, 
            ],
            headers: ['ID', 'Nombre', 'URL', 'Ícono', 'Estado', 'Acciones'] 
        }
    };

    function mostrarMensaje(data) {
        const mensajeDiv = document.getElementById('mensaje');
        mensajeDiv.innerText = data.mensaje;
        mensajeDiv.style.display = 'block';
        mensajeDiv.classList.remove('alert-success', 'alert-danger');
        mensajeDiv.classList.add(data.success ? 'alert-success' : 'alert-danger');
        setTimeout(() => { mensajeDiv.innerText = ''; mensajeDiv.style.display = 'none'; }, 5000);
    }
    
    function cargarItems(tipo) {
        let form = new FormData();
        form.append('accion', 'listar');
        form.append('tipo', tipo);
        
        const tablaBody = document.getElementById('tableBody');
        tablaBody.innerHTML = '<tr><td colspan="10">Cargando...</td></tr>';

        fetch('', { method: 'POST', body: form })
            .then(res => res.text())
            .then(html => {
                tablaBody.innerHTML = html;
            })
            .catch(err => {
                tablaBody.innerHTML = '<tr><td colspan="10" class="alert-danger">Error al cargar la lista.</td></tr>';
            });
    }

    /**
     * Función para cambiar el estado individual de Patrocinadores y Redes.
     */
    window.cambiarEstado = function(id, tipo, nuevoEstado) {
        if (!confirm(`¿Estás seguro de ${nuevoEstado === 'activo' ? 'activar' : 'desactivar'} este ${tipo.slice(0, -1)}?`)) {
            return;
        }
        
        let form = new FormData();
        form.append('accion', 'cambiar_estado_individual');
        form.append('tipo', tipo);
        form.append('id', id);
        form.append('estado', nuevoEstado);

        fetch('', { method: 'POST', body: form })
            .then(res => res.json())
            .then(data => {
                mostrarMensaje(data);
                if (data.success) {
                    cargarItems(tipo);
                }
            });
    }

    /**
     * Función para activar un logo y desactivar todos los demás (Unicidad).
     */
    window.activarLogoUnico = function(id, estadoActual) {
        if (estadoActual === 'activo') {
            mostrarMensaje({ success: false, mensaje: 'Este logo ya es el único activo.' });
            return;
        }

        if (!confirm('¿Estás seguro de activar este logo? El logo actual activo será desactivado.')) {
            return;
        }

        let form = new FormData();
        form.append('accion', 'activar_logo_unico');
        form.append('id', id);

        fetch('', { method: 'POST', body: form })
            .then(res => res.json())
            .then(data => {
                mostrarMensaje(data);
                if (data.success) {
                    cargarItems('logos');
                }
            });
    }

    /**
     * Llena el formulario de edición/creación (Logos, Patrocinadores, Redes).
     */
    function llenarFormulario(data, tipo) {
        const config = secciones[tipo];
        const form = document.getElementById('formItem');
        form.innerHTML = ''; // Limpiar formulario anterior
        form.dataset.modo = data ? 'actualizar' : 'crear';
        
        config.campos.forEach(campo => {
            if (campo.type === 'hidden') {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.id = campo.id;
                input.name = campo.id;
                input.value = data ? data[campo.id] : '';
                form.appendChild(input);
            } else {
                const label = document.createElement('label');
                label.innerText = campo.label;
                form.appendChild(label);
                
                let input;
                if (campo.type === 'select') {
                    // (Lógica de Select se mantiene aquí por si lo volvemos a usar)
                    input = document.createElement('select');
                    campo.options.forEach(optionValue => {
                        const option = document.createElement('option');
                        option.value = optionValue;
                        option.innerText = optionValue.charAt(0).toUpperCase() + optionValue.slice(1);
                        input.appendChild(option);
                    });
                } else {
                    input = document.createElement('input');
                    input.type = campo.type;
                    if (campo.type === 'file') {
                        input.accept = campo.accept;
                    }
                    if (campo.required && campo.type !== 'file') input.required = true;
                }
                
                input.id = campo.id;
                input.name = campo.id;
                input.style = "padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;";

                // ASIGNAR VALOR DE EDICIÓN
                if (data && campo.type !== 'file') {
                    input.value = data[campo.id] || '';
                    if (campo.type === 'select') {
                        input.value = data[campo.id] || campo.options[0];
                    }
                }

                form.appendChild(input);

                // MOSTRAR DATOS ACTUALES (Previsualización de imagen)
                if (data && campo.type === 'file') {
                    const ruta = data[config.ruta_columna];

                    if (ruta) {
                        const imgPreview = document.createElement('div');
                        imgPreview.innerHTML = `
                            <small style="display:block;">Imagen actual (dejar vacío para mantener):</small>
                            <img src="../../${ruta}" style="max-width:100px; margin-bottom: 5px; border:1px solid #ccc; padding:3px;">
                        `;
                        form.appendChild(imgPreview);
                    } else if (form.dataset.modo === 'actualizar') {
                        const small = document.createElement('small');
                        small.innerText = 'No hay imagen actual.';
                        form.appendChild(small);
                    }
                }
            }
        });

        // Botones de guardar y cancelar
        form.innerHTML += `<input type="hidden" name="tipo" value="${tipo}">
            <button type="submit" id="btnGuardarForm" style="padding:8px; border-radius:4px; border:none; background:#4CAF50; color:white; cursor:pointer; margin-top:10px;">${data ? 'Actualizar' : 'Guardar'} ${config.titulo.split(' ')[2]}</button>
            <button type="button" id="btnCancelarForm" class="btn-danger" style="padding:8px; border-radius:4px; border:none; color:white; cursor:pointer;">Cancelar</button>`;

        document.getElementById('formTitle').innerText = (data ? 'Editar ' : 'Agregar ') + config.titulo.split(' ')[2];
        document.getElementById('formularioContainer').style.display = 'block';
        document.getElementById('tablaContainer').style.display = 'none';
        document.getElementById('nosotrosContainer').style.display = 'none';

        // Evento de cancelar
        document.getElementById('btnCancelarForm').addEventListener('click', function() {
            document.getElementById('formularioContainer').style.display = 'none';
            document.getElementById('tablaContainer').style.display = 'block';
            document.getElementById('formItem').reset();
            cargarItems(tipo); // Recargar la tabla
        });
    }

    // Lógica para obtener datos y abrir formulario de edición (Función de Carga de Datos)
    window.obtenerItem = function(id, tipo) {
        let form = new FormData();
        form.append('accion', 'obtener');
        form.append('tipo', tipo);
        form.append('id', id);

        fetch('', { method: 'POST', body: form })
            .then(res => res.json())
            .then(data => {
                if (data) {
                    llenarFormulario(data, tipo); 
                    document.documentElement.scrollTop = 0; 
                } else {
                    mostrarMensaje({ success: false, mensaje: 'Error: No se encontraron datos para editar.' });
                }
            })
            .catch(error => {
                mostrarMensaje({ success: false, mensaje: 'Error al obtener los datos para la edición.' });
                console.error('Error en obtenerItem:', error);
            });
    }

    // Lógica para mostrar las secciones
    window.mostrarSeccion = function(tipo) {
        seccionActual = tipo;
        document.getElementById('nosotrosContainer').style.display = 'none';
        document.getElementById('formularioContainer').style.display = 'none';
        document.getElementById('formItem').reset();
        
        const btnAgregar = document.getElementById('btnAgregar');

        if (tipo === 'nosotros') {
            document.getElementById('tablaContainer').style.display = 'none';
            document.getElementById('nosotrosContainer').style.display = 'block';
            cargarNosotros();
        } else {
            document.getElementById('tablaContainer').style.display = 'block';
            
            const config = secciones[tipo];
            document.getElementById('tableTitle').innerText = config.titulo;
            
            // Configurar cabeceras de tabla
            document.getElementById('tableHeaders').innerHTML = config.headers.map(h => `<th>${h}</th>`).join('');
            
            // Configurar botón de agregar
            btnAgregar.innerText = 'Agregar Nuevo ' + config.titulo.split(' ')[2];
            btnAgregar.style.display = 'block';
            btnAgregar.onclick = () => llenarFormulario(null, tipo);

            cargarItems(tipo);
        }
    }

    // Envío del formulario CRUD dinámico
    document.getElementById('formItem').addEventListener('submit', function (e) {
        e.preventDefault();
        const form = this;
        let formData = new FormData(form);
        const accion = form.dataset.modo;
        const tipo = formData.get('tipo');

        formData.append('accion', accion);

        fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                mostrarMensaje(data);
                if (data.success) {
                    form.reset();
                    document.getElementById('formularioContainer').style.display = 'none';
                    document.getElementById('tablaContainer').style.display = 'block';
                    cargarItems(tipo);
                }
            }).catch(err => {
                mostrarMensaje({ success: false, mensaje: 'Error al conectar con el servidor.' });
            });
    });

    // Envío del formulario Nosotros
    document.getElementById('formNosotros').addEventListener('submit', function (e) {
        e.preventDefault();
        const form = this;
        let formData = new FormData(form);
        const nos_id = document.getElementById('nos_id').value;
        const accion = nos_id && nos_id !== '0' ? 'actualizar' : 'crear'; 

        formData.append('accion', accion);
        
        fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                mostrarMensaje(data);
                if (data.success) {
                    cargarNosotros(); 
                }
            }).catch(err => {
                mostrarMensaje({ success: false, mensaje: 'Error al conectar con el servidor (Nosotros).' });
            });
    });

    /**
     * Cargar datos de "Nosotros" (Función de Carga de Datos específica para Nosotros)
     */
    function cargarNosotros() {
        let form = new FormData();
        form.append('accion', 'listar');
        form.append('tipo', 'nosotros');

        fetch('', { method: 'POST', body: form })
            .then(res => res.json())
            .then(data => {
                const formNosotros = document.getElementById('formNosotros');
                // Limpiar previsualizaciones antiguas
                formNosotros.querySelectorAll('.img-preview').forEach(el => el.remove()); 

                if (data.nos_id) {
                    // Asignación de valores a los campos de texto/select
                    document.getElementById('nos_id').value = data.nos_id;
                    document.getElementById('nos_nom_emp').value = data.nos_nom_emp || '';
                    document.getElementById('nos_hist').value = data.nos_hist || '';
                    document.getElementById('nos_mis').value = data.nos_mis || '';
                    document.getElementById('nos_vis').value = data.nos_vis || '';
                    document.getElementById('nos_cel').value = data.nos_cel || '';
                    document.getElementById('nos_dir').value = data.nos_dir || '';
                    document.getElementById('nos_correo').value = data.nos_correo || '';
                    document.getElementById('nos_men_ini').value = data.nos_men_ini || '';
                    document.getElementById('nos_link_app').value = data.nos_link_app || '';
                    document.getElementById('nos_num_cuenta').value = data.nos_num_cuenta || '';
                    document.getElementById('nos_nom_banco').value = data.nos_nom_banco || '';
                    document.getElementById('nos_est').value = data.nos_est || 'activo';
                    
                    // Previsualización de Imágenes de Nosotros
                    const imagenes = {
                        'nos_img_hist': data.nos_img_hist,
                        'nos_img_mis': data.nos_img_mis,
                        'nos_img_vis': data.nos_img_vis,
                        'nos_logo_banco': data.nos_logo_banco
                    };
                    
                    for (const id in imagenes) {
                        const ruta = imagenes[id];
                        if (ruta) {
                            const inputElement = document.getElementById(id);
                            if (inputElement) {
                                const previewDiv = document.createElement('div');
                                previewDiv.classList.add('img-preview');
                                // Mover la imagen debajo de la etiqueta para que no interfiera con el layout de 2 columnas
                                const labelElement = formNosotros.querySelector(`label[for="${id}"]`);
                                if (labelElement) {
                                    previewDiv.style.gridColumn = '1 / 3'; // Ocupar todo el ancho para una mejor visualización
                                    previewDiv.style.textAlign = 'left';
                                    
                                    previewDiv.innerHTML = `<small style="display:block;">Actual (Dejar vacío para mantener):</small><img src="../../${ruta}" style="max-width:100px; display:block; margin-top:5px; border:1px solid #ccc; padding:3px;">`;
                                    labelElement.parentNode.insertBefore(previewDiv, inputElement);
                                }
                            }
                        }
                    }

                } else {
                    document.getElementById('formNosotros').reset();
                    document.getElementById('nos_id').value = 0; 
                }
            });
    }

    // Cargar la primera sección por defecto al iniciar
    document.addEventListener("DOMContentLoaded", () => {
        mostrarSeccion('logos');
    });
</script>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
?>