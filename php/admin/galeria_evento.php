<?php
session_start();
// Validación de sesión y rol de administrador
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

require_once '../../conexion.php'; // Usa require_once para evitar múltiples inclusiones

// ---------------------- Funciones de Seguridad y Utilidad ----------------------

/**
 * Escapa y sanitiza una cadena de texto para prevenir inyección SQL.
 * @param mixed $conn Conexión a la base de datos.
 * @param string $data La cadena a sanitizar.
 * @return string La cadena sanitizada.
 */
function sanitize_input($conn, $data) {
    return htmlspecialchars(trim(pg_escape_string($conn, $data)));
}

// ---------------------- Rutas y Parámetros Iniciales ----------------------

$evento_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($evento_id <= 0) {
    echo "ID de evento no válido.";
    exit;
}

$upload_dir = 'imagenes/imagenes_galeria/';
$full_upload_dir = '../../' . $upload_dir;

// ---------------------- Gestión de Peticiones AJAX ----------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    
    header('Content-Type: application/json'); // Asegura que la respuesta sea JSON

    try {
        switch ($_POST['accion']) {
            case 'crear':
                handle_crear($conn, $evento_id, $upload_dir, $full_upload_dir);
                break;
            case 'listar':
                handle_listar($conn);
                break;
            case 'eliminar':
                handle_eliminar($conn, $full_upload_dir);
                break;
            case 'obtener':
                handle_obtener($conn);
                break;
            case 'actualizar':
                handle_actualizar($conn, $full_upload_dir);
                break;
            default:
                throw new Exception("Acción no válida.");
        }
    } catch (Exception $e) {
        echo json_encode(["success" => false, "mensaje" => "Error: " . $e->getMessage()]);
    }
    exit;
}

// ---------------------- Funciones para Acciones CRUD ----------------------

function handle_crear($conn, $evento_id, $upload_dir, $full_upload_dir) {
    if (!isset($_FILES['imagenes']) || empty($_FILES['imagenes']['name'][0])) {
        echo json_encode(["success" => false, "mensaje" => "¡Ups! Parece que olvidaste seleccionar una imagen. Por favor, elige una para subir."]);
        return;
    }

    if (!is_dir($full_upload_dir)) {
        mkdir($full_upload_dir, 0777, true);
    }
    
    $descripcion = sanitize_input($conn, $_POST['gal_des'] ?? '');
    $id_admin = intval($_SESSION['id']);
    
    $subidas_exitosas = 0;
    
    foreach ($_FILES['imagenes']['name'] as $key => $name) {
        $file_error = $_FILES['imagenes']['error'][$key];
        
        if ($file_error !== UPLOAD_ERR_OK) {
            continue; // Saltar si hay un error en el archivo
        }

        $tmp_name = $_FILES['imagenes']['tmp_name'][$key];
        $nombre_archivo = time() . "_" . basename($name);
        $ruta_destino = $full_upload_dir . $nombre_archivo;

        if (move_uploaded_file($tmp_name, $ruta_destino)) {
            $imagen_ruta_db = $upload_dir . $nombre_archivo;
            $query = "INSERT INTO tbl_galeria (gal_id_evt, gal_id_adm, gal_url, gal_des) VALUES ($1, $2, $3, $4)";
            $result = pg_query_params($conn, $query, [$evento_id, $id_admin, $imagen_ruta_db, $descripcion]);
            
            if ($result) {
                $subidas_exitosas++;
            } else {
                error_log("Error al insertar en DB: " . pg_last_error($conn));
            }
        }
    }
    
    if ($subidas_exitosas > 0) {
        echo json_encode(["success" => true, "mensaje" => "¡Genial! Se subieron $subidas_exitosas imagen(es) a la galería."]);
    } else {
        echo json_encode(["success" => false, "mensaje" => "No pudimos subir las imágenes. Por favor, inténtalo de nuevo."]);
    }
}

function handle_listar($conn) {
    $evento_id = intval($_POST['gal_id_evt']);
    $query = "SELECT g.*, e.evt_tit, a.adm_nom FROM tbl_galeria g JOIN tbl_evento e ON g.gal_id_evt = e.evt_id JOIN tbl_admin a ON g.gal_id_adm = a.adm_id WHERE g.gal_id_evt = $1 ORDER BY g.gal_id DESC";
    $result = pg_query_params($conn, $query, [$evento_id]);
    
    $html = "";
    if (pg_num_rows($result) === 0) {
        $html = "<tr><td colspan='9' style='text-align:center;'>Aún no hay imágenes en la galería de este evento. ¡Sé el primero en subir una!</td></tr>";
    } else {
        while ($row = pg_fetch_assoc($result)) {
            $html .= "<tr>
                        <td>" . htmlspecialchars($row['gal_id']) . "</td>
                        <td>" . htmlspecialchars($row['gal_id_evt']) . "</td>
                        <td>" . htmlspecialchars($row['evt_tit']) . "</td>
                        <td>" . htmlspecialchars($row['gal_id_adm']) . "</td>
                        <td>" . htmlspecialchars($row['adm_nom']) . "</td>
                        <td><img src='../../" . htmlspecialchars($row['gal_url']) . "' style='max-width:150px;'></td>
                        <td>" . htmlspecialchars($row['gal_des']) . "</td>
                        <td>" . htmlspecialchars($row['gal_fec_sub']) . "</td>
                        <td>
                            <button class='btn btn-warning btn-sm' onclick='editarImagen(" . htmlspecialchars($row['gal_id']) . ")'>Editar</button>
                            <button class='btn btn-danger btn-sm' onclick='eliminarImagen(" . htmlspecialchars($row['gal_id']) . ")'>Eliminar</button>
                        </td>
                    </tr>";
        }
    }
    echo json_encode(["html" => $html]); // Devuelve HTML como parte de un objeto JSON
}

function handle_eliminar($conn, $full_upload_dir) {
    $id = intval($_POST['id']);
    $query_url = "SELECT gal_url FROM tbl_galeria WHERE gal_id = $1";
    $res_url = pg_query_params($conn, $query_url, [$id]);
    $url_data = pg_fetch_assoc($res_url);
    $ruta_imagen = $url_data['gal_url'] ?? null;

    pg_query($conn, "BEGIN");
    $res_db = pg_query_params($conn, "DELETE FROM tbl_galeria WHERE gal_id = $1", [$id]);

    if ($res_db) {
        if ($ruta_imagen && file_exists($full_upload_dir . basename($ruta_imagen))) {
            unlink($full_upload_dir . basename($ruta_imagen));
        }
        pg_query($conn, "COMMIT");
        echo json_encode(["success" => true, "mensaje" => "¡Éxito! La imagen ha sido eliminada."]);
    } else {
        pg_query($conn, "ROLLBACK");
        throw new Exception(pg_last_error($conn));
    }
}

function handle_obtener($conn) {
    $id = intval($_POST['id']);
    $res = pg_query_params($conn, "SELECT * FROM tbl_galeria WHERE gal_id = $1", [$id]);
    echo json_encode(pg_fetch_assoc($res));
}

function handle_actualizar($conn, $full_upload_dir) {
    $id = intval($_POST['gal_id']);
    $descripcion = sanitize_input($conn, $_POST['gal_des']);
    $imagen_ruta_db = null;

    $query_url = "SELECT gal_url FROM tbl_galeria WHERE gal_id = $1";
    $res_url = pg_query_params($conn, $query_url, [$id]);
    $row = pg_fetch_assoc($res_url);
    $ruta_actual = $row['gal_url'] ?? null;
    $imagen_ruta_db = $ruta_actual;

    if (isset($_FILES['imagenes']) && $_FILES['imagenes']['error'][0] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['imagenes']['tmp_name'][0];
        $file_name = time() . "_" . basename($_FILES['imagenes']['name'][0]);
        $ruta_destino = $full_upload_dir . $file_name;

        if (move_uploaded_file($file_tmp_name, $ruta_destino)) {
            if ($ruta_actual && file_exists($full_upload_dir . basename($ruta_actual))) {
                unlink($full_upload_dir . basename($ruta_actual));
            }
            $imagen_ruta_db = 'imagenes/imagenes_galeria/' . $file_name;
        } else {
            throw new Exception("Error al mover el archivo de imagen.");
        }
    }

    $query = "UPDATE tbl_galeria SET gal_des = $1, gal_url = $2, gal_fec_edi = CURRENT_TIMESTAMP WHERE gal_id = $3";
    $res = pg_query_params($conn, $query, [$descripcion, $imagen_ruta_db, $id]);

    echo json_encode($res ? ["success" => true, "mensaje" => "¡Genial! La imagen y su descripción han sido actualizadas con éxito."] : ["success" => false, "mensaje" => "Error al actualizar. Por favor, revisa la información."]);
}

ob_start();
?>

<div style="text-align: center;">
    <a href="crear_evento.php" style="text-decoration: none;"><button class="back-button">← Volver a Eventos</button></a>
    
    <h2 style="margin-bottom:15px;">Galería de Imágenes para Evento #<span id="evento_id_display"><?php echo htmlspecialchars($evento_id); ?></span></h2>
    
    <div style="margin-bottom:20px;">
        <button id="btnAgregarImagen" class="btn btn-primary">Agregar Nueva Imagen</button>
    </div>

    <div id="formularioContainer" style="display: none; margin: 0 auto; width: fit-content;">
        <form id="formImagen" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:8px; width:350px; margin:0 auto 20px auto;">
            <input type="hidden" id="gal_id" name="gal_id">
            <input type="hidden" id="gal_id_evt" name="gal_id_evt" value="<?php echo htmlspecialchars($evento_id); ?>">
            
            <label>Descripción de las imágenes:</label>
            <textarea id="descripcion" name="gal_des" rows="3" placeholder="Ej: Fotos del evento de lanzamiento" style="padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;"></textarea>
            
            <label>Subir imagen(es):</label>
            <input type="file" id="imagen" name="imagenes[]" accept="image/*" multiple required style="padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">
            
            <button type="submit" id="submitBtn" style="padding:8px; border-radius:4px; border:none; background:#4CAF50; color:white; cursor:pointer;">Guardar Imagen(es)</button>
            <button type="button" id="btnCancelarForm" style="padding:8px; border-radius:4px; border:none; background:#f44336; color:white; cursor:pointer;">Cancelar</button>
        </form>
    </div>

    <div class="mensaje" id="mensaje" style="color:green; margin:10px 0; font-size:12px; text-align:center;"></div>

    <h3 style="margin-top:25px;">Imágenes del Evento</h3>
    <div class="table-container" style="overflow-x:auto;">
        <table style="border-collapse:collapse; width:100%; font-size:12px; margin:0 auto;">
            <thead>
                <tr>
                    <th>ID Galería</th>
                    <th>ID Evento</th>
                    <th>Título Evento</th>
                    <th>ID Admin</th>
                    <th>Nombre Admin</th>
                    <th>Vista Previa</th>
                    <th>Descripción</th>
                    <th>Fecha de Subida</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaGaleria"></tbody>
        </table>
    </div>
</div>

<style>
    .btn { padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; margin: 2px; }
    .btn-primary { background: #2196F3; color: white; }
    .btn-primary:hover { background: #0b7dda; }
    .btn-success { background: #4CAF50; color: white; }
    .btn-success:hover { background: #45a049; }
    .btn-danger { background: #f44336; color: white; }
    .btn-danger:hover { background: #d32f2f; }
    .btn-warning { background: #ff9800; color: white; }
    .btn-warning:hover { background: #e68a00; }
    .btn-info { background: #00bcd4; color: white; }
    .btn-info:hover { background: #00a5bb; }
    .back-button { background-color: #f44336; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; margin-bottom: 20px; }

    .mensaje { transition: opacity 0.5s ease; }
    .alert-success { color: green; }
    .alert-danger { color: red; }

    table, th, td { border: 1px solid #ccc; padding: 6px; text-align: center; }
    th { background: #f2f2f2; }
</style>

<script>
    const eventoId = document.getElementById('gal_id_evt').value;
    const formImagen = document.getElementById('formImagen');
    const mensajeDiv = document.getElementById('mensaje');
    const tablaGaleria = document.getElementById('tablaGaleria');
    const imagenInput = document.getElementById('imagen');
    const galIdInput = document.getElementById('gal_id');
    const submitBtn = document.getElementById('submitBtn');
    const formularioContainer = document.getElementById('formularioContainer');

    function mostrarMensaje(data) {
        mensajeDiv.innerText = data.mensaje;
        mensajeDiv.className = `mensaje ${data.success ? 'success' : 'error'}`;
        mensajeDiv.style.display = 'block';
    }

    function cargarImagenes() {
        const formData = new FormData();
        formData.append('accion', 'listar');
        formData.append('gal_id_evt', eventoId);

        fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.html) {
                    tablaGaleria.innerHTML = data.html;
                } else {
                    mostrarMensaje({ success: false, mensaje: 'Error al cargar la lista de imágenes.' });
                }
            })
            .catch(err => {
                console.error('Error:', err);
                mostrarMensaje({ success: false, mensaje: 'Error de conexión al listar. Por favor, revisa tu conexión.' });
            });
    }

    formImagen.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const accion = galIdInput.value ? 'actualizar' : 'crear';
        formData.append('accion', accion);

        // Agrega una validación simple para el campo de imagen en el frontend para evitar envíos vacíos en "crear"
        if (accion === 'crear' && imagenInput.files.length === 0) {
            mostrarMensaje({ success: false, mensaje: '¡Ups! Por favor, selecciona al menos una imagen para subir.' });
            return;
        }

        fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                mostrarMensaje(data);
                if (data.success) {
                    formImagen.reset();
                    galIdInput.value = '';
                    imagenInput.required = true;
                    imagenInput.multiple = true;
                    submitBtn.innerText = 'Guardar Imagen(es)';
                    formularioContainer.style.display = 'none';
                    cargarImagenes();
                }
            })
            .catch(err => {
                console.error('Error:', err);
                mostrarMensaje({ success: false, mensaje: 'Error de conexión al procesar la solicitud. Por favor, inténtalo de nuevo más tarde.' });
            });
    });

    function eliminarImagen(id) {
        if (confirm('¿Estás seguro de que quieres eliminar esta imagen? ¡Esta acción no se puede deshacer!')) {
            const formData = new FormData();
            formData.append('accion', 'eliminar');
            formData.append('id', id);

            fetch('', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => { 
                    mostrarMensaje(data);
                    if (data.success) {
                        cargarImagenes(); 
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    mostrarMensaje({ success: false, mensaje: 'Error de conexión al eliminar. Por favor, inténtalo de nuevo.' });
                });
        }
    }

    function editarImagen(id) {
        const formData = new FormData();
        formData.append('accion', 'obtener');
        formData.append('id', id);

        fetch('', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                galIdInput.value = data.gal_id;
                document.getElementById('descripcion').value = data.gal_des;
                imagenInput.required = false;
                imagenInput.multiple = false;
                submitBtn.innerText = 'Actualizar Imagen';
                formularioContainer.style.display = 'block';
                document.documentElement.scrollTop = 0;
            });
    }

    document.getElementById('btnAgregarImagen').addEventListener('click', function() {
        formularioContainer.style.display = 'block';
        formImagen.reset();
        galIdInput.value = '';
        imagenInput.required = true;
        imagenInput.multiple = true;
        submitBtn.innerText = 'Guardar Imagen(es)';
    });

    document.getElementById('btnCancelarForm').addEventListener('click', function() {
        formImagen.reset();
        galIdInput.value = '';
        formularioContainer.style.display = 'none';
        imagenInput.required = true;
        imagenInput.multiple = true;
        submitBtn.innerText = 'Guardar Imagen(es)';
    });

    // Inicializar la carga de imágenes
    document.addEventListener("DOMContentLoaded", cargarImagenes);
</script>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
?>