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
    // Se usa htmlspecialchars para XSS y pg_escape_string para SQL Injection en PostgreSQL
    return htmlspecialchars(trim(pg_escape_string($conn, $data)));
}

/**
 * Elimina un archivo físico del servidor.
 * @param string $url La URL relativa del archivo en la DB.
 * @return bool True si se eliminó o no existía, false si falló la eliminación.
 */
function delete_physical_file($url) {
    $full_path = '../../' . $url;
    // Verificar si existe antes de intentar eliminar
    if ($url && file_exists($full_path)) {
        return unlink($full_path);
    }
    // Si no existe, consideramos que no hay problema
    return true; 
}


// ---------------------- Rutas y Parámetros Iniciales ----------------------

$evento_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$upload_dir = 'imagenes/imagenes_galeria/';
$full_upload_dir = '../../' . $upload_dir;

// Crear directorio si no existe
if (!is_dir($full_upload_dir)) {
    mkdir($full_upload_dir, 0777, true);
}


// ---------------------- Gestión de Peticiones AJAX (Carga de Imágenes) ----------------------

if (isset($_GET['action']) && $_GET['action'] == 'fetch_images' && $evento_id > 0) {
    // Modo AJAX para obtener imágenes de un evento específico
    $query = "SELECT gal_id, gal_url, gal_des FROM tbl_galeria WHERE gal_id_evt = $evento_id ORDER BY gal_fec_sub DESC";
    $result = pg_query($conn, $query);

    if ($result) {
        $imagenes = pg_fetch_all($result) ?: [];
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'imagenes' => $imagenes]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al cargar imágenes: ' . pg_last_error($conn)]);
        exit;
    }
}


// ---------------------- Gestión de CRUD (Creación, Actualización, Eliminación) por AJAX POST ----------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $evento_id > 0 && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    $action = $_POST['action'];
    $gal_id = isset($_POST['gal_id']) ? intval($_POST['gal_id']) : 0;
    $admin_id = intval($_SESSION['id']); 
    $descripcion = isset($_POST['gal_des']) ? sanitize_input($conn, $_POST['gal_des']) : '';

    // Función para manejar la subida o reemplazo de un archivo (interna)
    $handle_file_upload = function($file, $current_url = null) use ($full_upload_dir, $upload_dir) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Error de subida: Código ' . $file['error']];
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            return ['success' => false, 'message' => 'Tipo de archivo no permitido. Solo JPG, PNG, GIF.'];
        }

        // Si es un reemplazo, intentamos eliminar el archivo antiguo primero
        if ($current_url) {
            if (!delete_physical_file($current_url)) {
                 error_log("Fallo al eliminar archivo antiguo: $current_url");
                 // Se sigue adelante con el nuevo upload, ya que el fallo de unlink no es crítico
            }
        }
        
        $filename = uniqid('gal_') . '-' . basename($file['name']);
        $target_file = $full_upload_dir . $filename;
        $url_db = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return ['success' => true, 'url' => $url_db, 'message' => "Archivo $filename subido."];
        } else {
            return ['success' => false, 'message' => 'Error al mover el archivo subido.'];
        }
    };
    
    // ==============================================
    // 1. CREACIÓN (UPLOAD NEW)
    // ==============================================
    if ($action === 'upload_new') {
        // $_FILES['gal_url'] es un array porque el input en HTML tiene 'multiple' y '[]'
        $files = $_FILES['gal_url'];
        $messages = [];
        $all_success = true;

        // Itera sobre los archivos subidos (incluso si es uno solo)
        $file_array = is_array($files['name']) ? $files['name'] : [$files['name']];
        
        foreach (array_keys($file_array) as $key) {
             if ($files['error'][$key] === UPLOAD_ERR_NO_FILE) continue; // Saltar si no hay archivo
             
             $file = [
                 'name' => $files['name'][$key],
                 'type' => $files['type'][$key],
                 'tmp_name' => $files['tmp_name'][$key],
                 'error' => $files['error'][$key],
                 'size' => $files['size'][$key]
             ];
             
             $upload_result = $handle_file_upload($file);
             $messages[] = $upload_result['message'];

             if ($upload_result['success']) {
                 $url_db = $upload_result['url'];
                 $query = "INSERT INTO tbl_galeria (gal_id_evt, gal_id_adm, gal_url, gal_des) 
                           VALUES ($evento_id, $admin_id, '$url_db', '$descripcion')";
                
                 if (!pg_query($conn, $query)) {
                     // Si falla la DB, eliminar el archivo subido
                     delete_physical_file($url_db);
                     error_log("Error DB al insertar galería: " . pg_last_error($conn));
                     $messages[] = 'Error al guardar en la base de datos para la imagen ' . $file['name'];
                     $all_success = false;
                 }
             } else {
                 $all_success = false;
             }
        }
        
        $response['success'] = $all_success;
        $response['message'] = implode('<br>', $messages);

    // ==============================================
    // 2. ACTUALIZACIÓN (UPDATE EXISTING) - Permite reemplazar archivo
    // ==============================================
    } elseif ($action === 'update_existing' && $gal_id > 0) {
        // $_FILES['gal_url']['error'][0] se usa porque en modo edición, solo esperamos 1 archivo (o ninguno)
        $has_file = (isset($_FILES['gal_url']) && $_FILES['gal_url']['error'][0] !== UPLOAD_ERR_NO_FILE);
        $update_query = "";

        if ($has_file) {
            // A. Caso 1: Actualización de archivo y descripción (Reemplazo)
            
            // 1. Obtener URL actual para eliminar el archivo viejo
            $query_url = "SELECT gal_url FROM tbl_galeria WHERE gal_id = $gal_id";
            $result_url = pg_query($conn, $query_url);
            $row = pg_fetch_assoc($result_url);
            $current_url = $row ? $row['gal_url'] : null;

            // 2. Subir el nuevo archivo (maneja eliminación del antiguo)
            // Se pasa el primer (y único) archivo del array de files
            $file_to_upload = [
                'name' => $_FILES['gal_url']['name'][0],
                'type' => $_FILES['gal_url']['type'][0],
                'tmp_name' => $_FILES['gal_url']['tmp_name'][0],
                'error' => $_FILES['gal_url']['error'][0],
                'size' => $_FILES['gal_url']['size'][0]
            ];
            
            $upload_result = $handle_file_upload($file_to_upload, $current_url);

            if ($upload_result['success']) {
                $new_url = $upload_result['url'];
                $update_query = "UPDATE tbl_galeria SET 
                                 gal_des = '$descripcion', 
                                 gal_url = '$new_url', 
                                 gal_id_adm = $admin_id, 
                                 gal_fec_edi = CURRENT_TIMESTAMP 
                                 WHERE gal_id = $gal_id AND gal_id_evt = $evento_id";
                $response['message'] = 'Imagen y descripción actualizadas correctamente.';
            } else {
                $response['message'] = $upload_result['message'];
                echo json_encode($response);
                exit; // Fallo al subir el archivo, no continuar
            }
        } else {
            // B. Caso 2: Actualización de solo descripción (Sin archivo)
            $update_query = "UPDATE tbl_galeria SET 
                             gal_des = '$descripcion', 
                             gal_id_adm = $admin_id, 
                             gal_fec_edi = CURRENT_TIMESTAMP 
                             WHERE gal_id = $gal_id AND gal_id_evt = $evento_id";
            $response['message'] = 'Descripción actualizada correctamente.';
        }

        if (pg_query($conn, $update_query)) {
            $response['success'] = true;
        } else {
            $response['message'] = 'Error al actualizar: ' . pg_last_error($conn);
        }

    // ==============================================
    // 3. ELIMINACIÓN (DELETE IMAGE)
    // ==============================================
    } elseif ($action === 'delete_image' && $gal_id > 0) {
        
        // 1. Obtener la URL del archivo para eliminarlo físicamente
        $query_url = "SELECT gal_url FROM tbl_galeria WHERE gal_id = $gal_id AND gal_id_evt = $evento_id";
        $result_url = pg_query($conn, $query_url);
        $row = pg_fetch_assoc($result_url);
        $file_url = $row ? $row['gal_url'] : null;

        // 2. Eliminar el registro de la DB
        $query_del = "DELETE FROM tbl_galeria WHERE gal_id = $gal_id AND gal_id_evt = $evento_id";

        if (pg_query($conn, $query_del)) {
            // 3. Eliminar el archivo físico
            if (delete_physical_file($file_url)) {
                $response['message'] = 'Imagen y registro eliminados correctamente.';
            } else {
                $response['message'] = 'Registro eliminado, pero falló la eliminación del archivo físico.';
            }
            $response['success'] = true;
        } else {
            $response['message'] = 'Error al eliminar el registro de la base de datos: ' . pg_last_error($conn);
        }
    } else {
        $response['message'] = 'Acción inválida o ID de galería faltante.';
    }

    echo json_encode($response);
    exit;
}


// ---------------------- Modo de Visualización ----------------------

// Si no hay evento ID, mostramos el selector
if ($evento_id <= 0) {

    // Función para obtener todos los eventos (usada solo en modo selector)
    function obtenerEventosSelector($conn) {
        // Obtenemos eventos que no estén 'finalizados'
        $query = "SELECT evt_id, evt_tit, evt_fec, evt_lug FROM tbl_evento 
                  WHERE evt_est IN ('pendiente', 'activo') 
                  ORDER BY evt_fec DESC";
        $result = pg_query($conn, $query);
        if (!$result) {
            error_log("Error al obtener eventos para selector: " . pg_last_error($conn));
            return [];
        }
        return pg_fetch_all($result) ?: [];
    }

    $eventos = obtenerEventosSelector($conn);
    $evento_titulo = "Seleccione un Evento"; // Título para la plantilla

} else {
    // Si hay evento ID, obtenemos el título para el encabezado
    $query_evt = "SELECT evt_tit FROM tbl_evento WHERE evt_id = $evento_id";
    $result_evt = pg_query($conn, $query_evt);
    if ($result_evt && pg_num_rows($result_evt) > 0) {
        $evento_data = pg_fetch_assoc($result_evt);
        $evento_titulo = "Galería: " . htmlspecialchars($evento_data['evt_tit']);
    } else {
        // El ID no existe o no es válido, redirigimos al selector
        header("Location: gestionar_galeria.php");
        exit;
    }
}


// ---------------------- Preparación del Contenido HTML ----------------------
ob_start();
?>

<div class="container-fluid">
    <h1 class="mt-4 mb-4"><i class="fas fa-camera-retro"></i> <?= htmlspecialchars($evento_titulo) ?></h1>

    <?php if ($evento_id <= 0): ?>
        <!-- ============================================== -->
        <!-- MODO 1: SELECTOR DE EVENTOS (SIN CAMBIOS) -->
        <!-- ============================================== -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-primary text-white">
                <h6 class="m-0 font-weight-bold">Selecciona un Evento</h6>
            </div>
            <div class="card-body">
                <?php if (empty($eventos)): ?>
                    <div class="alert alert-warning" role="alert">
                        No hay eventos activos o pendientes para gestionar su galería. Por favor, cree uno primero.
                    </div>
                <?php else: ?>
                    <p>Elige un evento de la lista para comenzar a gestionar su galería de imágenes.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col">Título</th>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Lugar</th>
                                    <th scope="col">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventos as $evento): 
                                    $id = htmlspecialchars($evento['evt_id']);
                                    $titulo = htmlspecialchars($evento['evt_tit']);
                                    $fecha = date('d/m/Y H:i', strtotime($evento['evt_fec']));
                                    $lugar = htmlspecialchars($evento['evt_lug']);
                                ?>
                                    <tr>
                                        <td><?= $titulo ?></td>
                                        <td><?= $fecha ?></td>
                                        <td><?= $lugar ?></td>
                                        <td>
                                            <!-- Botón que lleva al modo Gestión con el ID del evento -->
                                            <a href="gestionar_galeria.php?id=<?= $id ?>" class="btn btn-sm btn-info" 
                                               title="Gestionar Galería de <?= $titulo ?>">
                                                <i class="fas fa-images"></i> Gestionar Galería
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- ============================================== -->
        <!-- MODO 2: GESTIÓN DE GALERÍA -->
        <!-- ============================================== -->

        <!-- Botón para regresar al selector -->
        <a href="gestionar_galeria.php" class="btn btn-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Cambiar Evento
        </a>

        <!-- Formulario para Agregar/Editar Imágenes (Oculto por defecto) -->
        <div class="card shadow mb-4" id="formularioContainer" style="display: none;">
            <div class="card-header py-3 bg-success text-white">
                <h6 class="m-0 font-weight-bold" id="formTitle">Subir Nueva Imagen</h6>
            </div>
            <div class="card-body">
                <!-- Se usa enctype="multipart/form-data" para subir archivos (necesario aunque se use AJAX) -->
                <form id="formImagen" method="POST" enctype="multipart/form-data">
                    
                    <!-- Campo oculto para el ID de Galería (para edición) -->
                    <input type="hidden" name="gal_id" id="gal_id" value="">
                    <!-- Campo oculto para la acción de AJAX -->
                    <input type="hidden" name="action" id="formAction" value="upload_new">
                    
                    <div class="mb-3">
                        <label for="gal_des" class="form-label">Descripción (Opcional)</label>
                        <input type="text" class="form-control" id="gal_des" name="gal_des" maxlength="255">
                        <small class="form-text text-muted">Máximo 255 caracteres. Se usará como texto alternativo.</small>
                    </div>

                    <div class="mb-3" id="fileInputContainer">
                        <label for="gal_url" class="form-label" id="fileInputLabel">Seleccionar Imagen(es)</label>
                        <!-- Usamos el plugin fileinput.js. Permitimos múltiples en creación/reemplazo en edición -->
                        <input id="gal_url" name="gal_url[]" type="file" multiple class="file-loading">
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-danger me-2" id="btnCancelarForm">
                            <i class="fas fa-times-circle"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> Guardar Imagen(es)
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Botón para mostrar el formulario de subida (Opcion subir otra, se queda aquí) -->
        <div class="d-flex justify-content-between mb-4">
            <div id="btnOpcionVolver">
                <!-- Enlace para volver al selector de eventos -->
                <a href="gestionar_galeria.php" class="btn btn-secondary me-2">
                    <i class="fas fa-list"></i> Volver a Eventos
                </a>
            </div>
            <button class="btn btn-success" id="btnAgregarImagen">
                <i class="fas fa-plus"></i> Subir Otra Imagen
            </button>
        </div>
        
        <!-- Contenedor de la Galería de Imágenes -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-info text-white">
                <h6 class="m-0 font-weight-bold">Imágenes de la Galería</h6>
            </div>
            <div class="card-body">
                <div id="galeria-grid" class="row row-cols-1 row-cols-md-3 g-4">
                    <!-- Las imágenes se cargarán aquí vía JavaScript (AJAX) -->
                    <div class="col text-center" id="loading-spinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando imágenes...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Botón para subir otra imagen en el footer (según solicitud) -->
        <div class="d-flex justify-content-end mb-4">
            <button class="btn btn-success" id="btnAgregarImagenFooter">
                <i class="fas fa-plus"></i> Subir Otra Imagen
            </button>
        </div>

    <?php endif; ?>

</div>

<!-- Scripts necesarios para el FileInput y la Galería (incluye SweetAlert2) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" 
    xintegrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8luMXOK+STx6/vF18+5sZ/uCWJ0nK0Rj/i/4g==" 
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.5.4/js/fileinput.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.5.4/js/plugins/piexif.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.5.4/js/themes/fa6/theme.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.5.4/js/locales/es.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.12.2/sweetalert2.all.min.js"></script>

<script>
    // Código JavaScript solo para el Modo Gestión
    <?php if ($evento_id > 0): ?>

    const EVENTO_ID = <?= $evento_id ?>;
    
    // Referencias a elementos del DOM
    const galeriaGrid = document.getElementById('galeria-grid');
    const formImagen = document.getElementById('formImagen');
    const formularioContainer = document.getElementById('formularioContainer');
    const galIdInput = document.getElementById('gal_id');
    const formActionInput = document.getElementById('formAction');
    const galDesInput = document.getElementById('gal_des');
    const fileInput = document.getElementById('gal_url');
    const fileInputLabel = document.getElementById('fileInputLabel');
    const formTitle = document.getElementById('formTitle');
    const submitBtn = document.getElementById('submitBtn');

    // ==============================================
    // Inicialización del FileInput
    // ==============================================

    /**
     * Inicializa el plugin fileinput para los modos 'crear' o 'editar'.
     * @param {string} mode 'crear' o 'editar'
     * @param {string[]} initialPreview URLs de la imagen actual (solo en editar)
     * @param {object[]} initialPreviewConfig Configuración de la previsualización (solo en editar)
     */
    function inicializarFileInput(mode, initialPreview = [], initialPreviewConfig = []) {
        // Destruir la instancia existente si la hay
        if ($(fileInput).data('fileinput')) {
            $(fileInput).fileinput('destroy');
        }

        const isCreate = (mode === 'crear');

        const configBase = {
            theme: 'fa6',
            language: 'es',
            uploadUrl: '#', 
            allowedFileExtensions: ['jpg', 'jpeg', 'png', 'gif'],
            maxFileCount: (isCreate ? 10 : 1), // Máximo 10 en creación, 1 en edición/reemplazo
            minFileCount: (isCreate ? 1 : 0), // 1 obligatorio en creación, 0 en edición
            showUpload: false, 
            showCaption: true,
            dropZoneEnabled: true,
            overwriteInitial: !isCreate, 
            browseLabel: 'Buscar',
            removeLabel: 'Quitar',
            uploadLabel: 'Subir',
            msgPlaceholder: isCreate ? 'Seleccione archivo(s)...' : 'Seleccione nuevo archivo para reemplazar (opcional)...',
            initialPreviewAsData: true,
            initialPreviewFileType: 'image',
            showRemove: true,
            showCancel: false,
        };

        if (isCreate) {
            // Modo CREAR (Múltiples archivos)
            fileInput.setAttribute('multiple', 'multiple');
            fileInput.setAttribute('name', 'gal_url[]');
            fileInputLabel.innerText = 'Seleccionar Imagen(es)';
            $(fileInput).fileinput({
                ...configBase,
                initialPreview: [],
                initialPreviewConfig: [],
            });
        } else { 
            // Modo EDITAR (Un solo archivo para reemplazar, o ninguno)
            fileInput.removeAttribute('multiple');
            // Se mantiene como array en el name 'gal_url[]' para unificar el manejo en PHP
            fileInput.setAttribute('name', 'gal_url[]'); 
            fileInputLabel.innerText = 'Seleccionar Nueva Imagen (Opcional)';
            $(fileInput).fileinput({
                ...configBase,
                minFileCount: 0,
                initialPreview: initialPreview,
                initialPreviewConfig: initialPreviewConfig,
                maxFileCount: 1, 
            });
        }
    }

    // ==============================================
    // Funciones de Carga y Renderizado
    // ==============================================

    /**
     * Carga las imágenes de la galería del evento desde el servidor (AJAX).
     */
    async function cargarImagenes() {
        galeriaGrid.innerHTML = `
            <div class="col text-center" id="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando imágenes...</p>
            </div>
        `;
        
        // Ocultar formulario al cargar la galería
        formularioContainer.style.display = 'none';

        try {
            const response = await fetch(`gestionar_galeria.php?action=fetch_images&id=${EVENTO_ID}`);
            const data = await response.json();

            galeriaGrid.innerHTML = ''; // Limpiar el spinner

            if (data.success && data.imagenes.length > 0) {
                data.imagenes.forEach(img => {
                    const html = crearTarjetaImagen(img.gal_id, img.gal_url, img.gal_des);
                    galeriaGrid.innerHTML += html;
                });
            } else {
                galeriaGrid.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-exclamation-circle fa-3x text-warning"></i>
                        <p class="mt-3 lead">No se encontraron imágenes en la galería para este evento.</p>
                        <p>Usa el botón "Subir Otra Imagen" para empezar.</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error al cargar la galería:', error);
            Swal.fire('Error', 'Error de conexión al cargar las imágenes.', 'error');
            galeriaGrid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-times-circle fa-3x text-danger"></i>
                    <p class="mt-3 lead">Error de conexión al cargar las imágenes.</p>
                </div>
            `;
        }
    }

    /**
     * Genera el HTML para una tarjeta de imagen.
     */
    function crearTarjetaImagen(id, url, des) {
        // La URL se genera relativa al index/plantilla, la ruta real es ../../url
        const fullUrl = `../../${url}`;
        const displayDes = des ? `Descripción: ${des}` : 'Sin descripción';

        return `
            <div class="col" data-gal-id="${id}">
                <div class="card h-100 shadow-sm border-0">
                    <img src="${fullUrl}" class="card-img-top" alt="${des}" 
                         onerror="this.onerror=null;this.src='https://placehold.co/600x400/CCCCCC/333333?text=Imagen+No+Encontrada';"
                         style="height: 200px; object-fit: cover; cursor: pointer;"
                         onclick="mostrarImagen('${fullUrl}')">
                    <div class="card-body p-3">
                        <p class="card-text text-muted small mb-1 overflow-hidden" style="max-height: 3em;">
                            ${displayDes}
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <button class="btn btn-sm btn-outline-primary" 
                                onclick="editarImagen(${id}, '${url}', '${des.replace(/'/g, "\\'")}')">
                                <i class="fas fa-edit"></i> Actualizar
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarImagen(${id})">
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // ==============================================
    // Lógica de Interacción
    // ==============================================

    /**
     * Prepara el formulario para editar la descripción y/o reemplazar la imagen.
     */
    function editarImagen(id, url, des) {
        formTitle.innerText = 'Actualizar Imagen y Descripción';
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
        
        // Modo ACTUALIZAR
        formImagen.reset(); // Limpiar inputs antes de rellenar
        galIdInput.value = id;
        formActionInput.value = 'update_existing';
        galDesInput.value = des;
        
        // Inicializar FileInput en modo edición
        const initialPreview = [`../../${url}`];
        const initialPreviewConfig = [{
            caption: 'Imagen actual',
            key: id,
            showRemove: false, 
            showDrag: false,
        }];

        inicializarFileInput('editar', initialPreview, initialPreviewConfig);
        
        formularioContainer.style.display = 'block';
        formularioContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /**
     * Envía la petición para eliminar una imagen.
     */
    function eliminarImagen(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción eliminará la imagen y no se puede revertir.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, ¡Eliminar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete_image');
                formData.append('gal_id', id);

                fetch('gestionar_galeria.php?id=' + EVENTO_ID, {
                    method: 'POST',
                    body: formData 
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('¡Eliminada!', data.message, 'success');
                        cargarImagenes(); 
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error de red:', error);
                    Swal.fire('Error de Conexión', 'No se pudo conectar con el servidor.', 'error');
                });
            }
        });
    }

    /**
     * Muestra una imagen en un modal de SweetAlert.
     */
    function mostrarImagen(src) {
        Swal.fire({
            title: 'Vista Previa',
            imageUrl: src,
            imageAlt: 'Imagen de la Galería',
            showConfirmButton: false,
            showCloseButton: true,
            focusConfirm: false
        });
    }

    /**
     * Resetea el formulario al modo 'Subir Nueva Imagen'
     */
    function resetFormularioSubida() {
        formTitle.innerText = 'Subir Nueva Imagen';
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Guardar Imagen(es)';
        
        formImagen.reset();
        galIdInput.value = '';
        formActionInput.value = 'upload_new';
        inicializarFileInput('crear');
        
        formularioContainer.style.display = 'block';
        formularioContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }


    // ==============================================
    // Eventos del formulario y botones
    // ==============================================

    // Botones para subir otra imagen (superior y footer)
    document.getElementById('btnAgregarImagen').addEventListener('click', resetFormularioSubida);
    document.getElementById('btnAgregarImagenFooter').addEventListener('click', resetFormularioSubida);
    
    document.getElementById('btnCancelarForm').addEventListener('click', function() {
        formImagen.reset();
        galIdInput.value = '';
        formActionInput.value = 'upload_new'; 
        formularioContainer.style.display = 'none';
        
        // Resetear FileInput al modo por defecto (Crear)
        inicializarFileInput('crear');
    });

    // Evento al enviar el formulario (maneja creación y actualización por AJAX)
    formImagen.addEventListener('submit', function(e) {
        e.preventDefault();

        const action = formActionInput.value;
        const fileCount = fileInput.files.length;
        
        // Validar que se seleccione un archivo en modo CREAR
        if (action === 'upload_new' && fileCount === 0) {
            Swal.fire('Error', 'Debe seleccionar al menos una imagen para subir.', 'warning');
            return;
        }

        // Crear objeto FormData para enviar datos y archivos
        const formData = new FormData(formImagen);
        formData.set('action', action); 
        // Nota: gal_url[] y gal_des ya están incluidos en formData

        Swal.fire({
            title: action === 'upload_new' ? 'Subiendo...' : 'Actualizando...',
            html: 'Por favor espera un momento.',
            didOpen: () => {
                Swal.showLoading();
            },
            allowOutsideClick: false,
            allowEscapeKey: false
        });

        fetch('gestionar_galeria.php?id=' + EVENTO_ID, {
            method: 'POST',
            body: formData 
        })
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.success) {
                Swal.fire(
                    action === 'upload_new' ? '¡Subida Exitosa!' : '¡Actualización Exitosa!', 
                    data.message, 
                    'success'
                );
                // Ocultar el formulario y recargar la galería
                formularioContainer.style.display = 'none';
                cargarImagenes();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.close();
            console.error('Error de red:', error);
            Swal.fire('Error de Conexión', 'No se pudo conectar con el servidor.', 'error');
        });
    });

    // Inicializar la carga de imágenes y el file input al cargar el DOM
    document.addEventListener("DOMContentLoaded", function() {
        cargarImagenes();
        inicializarFileInput('crear'); // Inicializa para el modo 'Crear' por defecto
    });
    
    <?php endif; // Fin del bloque JavaScript para Modo Gestión ?>
</script>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
?>