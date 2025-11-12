<?php
session_start();
// Activar reporte de errores temporalmente para diagnosticar
// error_reporting(E_ALL); 
// ini_set('display_errors', 1); 

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    // Ajusta la ruta si es necesario
    header("Location: ../../sesion/login.php"); 
    exit;
}

// Asegúrate de que la ruta de conexión sea correcta
include '../../conexion.php'; 

// Directorio base para las imágenes. 
$dir_logos = 'apis/imagenes/pagina/logos/';

// ================== FUNCIONES DE UTILIDAD ==================

/**
 * Maneja la subida de una imagen al servidor.
 * @param resource $conn Conexión a la base de datos.
 * @param array $file_data Array $_FILES['nombre_campo'].
 * @param string $directorio_destino_db Ruta relativa al proyecto para guardar en DB.
 * @param string|null $ruta_anterior_db Ruta de la imagen anterior para posible borrado.
 * @return string|false|null La nueva ruta relativa, 'false' si es error MIME, o 'null' si no se subió.
 */
function manejar_subida_imagen($conn, $file_data, $directorio_destino_db, $ruta_anterior_db = null) {
    $raiz_proyecto = realpath(__DIR__ . '/../../'); 
    
    // Bandera para determinar si la imagen anterior debe ser eliminada
    $eliminar_anterior = false;
    $imagenRuta = $ruta_anterior_db; // Mantener la ruta anterior por defecto
    
    // 1. Manejar la eliminación explícita de la imagen (marcada en edición)
    if (isset($_POST['eliminar_imagen']) && $_POST['eliminar_imagen'] === 'true') {
        $eliminar_anterior = true;
        $imagenRuta = null; // La nueva ruta es nula
    }

    // 2. Procesar la subida de una nueva imagen
    if (isset($file_data) && $file_data['error'] === UPLOAD_ERR_OK && $file_data['size'] > 0) {
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        // Validar tipo MIME
        if (!in_array($file_data['type'], $tipos_permitidos)) {
            return false; // Indica error de tipo MIME
        }

        $nombreArchivo = time() . "_" . basename($file_data['name']);
        $ruta_destino_absoluta = $raiz_proyecto . '/' . $directorio_destino_db . $nombreArchivo;
        $directorio_absoluto = dirname($ruta_destino_absoluta);
        
        // Crear directorio si no existe
        if (!is_dir($directorio_absoluto)) {
            mkdir($directorio_absoluto, 0777, true);
        }

        if (move_uploaded_file($file_data['tmp_name'], $ruta_destino_absoluta)) {
            $eliminar_anterior = true; // Si hay subida exitosa, eliminamos la anterior
            $imagenRuta = $directorio_destino_db . $nombreArchivo; // Nueva ruta
        } else {
            // Fallo en move_uploaded_file
            return null; 
        }
    }
    
    // 3. Eliminar la imagen anterior si se marcó la bandera y existe
    if ($eliminar_anterior && $ruta_anterior_db && $imagenRuta !== $ruta_anterior_db) {
        $ruta_completa_anterior = $raiz_proyecto . '/' . $ruta_anterior_db;
        if (file_exists($ruta_completa_anterior)) {
            @unlink($ruta_completa_anterior);
        }
    }

    return $imagenRuta; // Ruta final (nueva ruta, ruta anterior, o null)
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
            throw new Exception("Error al activar el logo: " . pg_last_error($conn));
        }
    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        echo json_encode(["success" => false, "mensaje" => "Error: " . $e->getMessage()]);
    }
    exit;
}

// -------------------- LECTURA (Listar) - Preparado para DataTables --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'listar') {
    $html = "";
    $query = "SELECT * FROM tbl_logos ORDER BY log_id DESC";
    $result = pg_query($conn, $query);
    
    // Verificar si la consulta fue exitosa
    if (!$result) {
        echo json_encode(["success" => false, "mensaje" => "Error al listar: " . pg_last_error($conn)]);
        exit;
    }

    while ($row = pg_fetch_assoc($result)) {
        $id = $row['log_id'];
        $es_activo = $row['log_est'] === 'activo';
        $btn_clase = $es_activo ? 'btn-success' : 'btn-secondary';
        $btn_texto = $es_activo ? 'Activo (Único)' : 'Activar';
        $nom_escapado = htmlspecialchars($row['log_nom'], ENT_QUOTES);
        $rut_escapado = htmlspecialchars($row['log_rut'], ENT_QUOTES);

        // Se añaden los data-attributes para cargar la edición directamente desde JS sin otra llamada AJAX
        $data_attributes = " data-id='{$id}' data-nom='{$nom_escapado}' data-rut='{$rut_escapado}'";

        $html .= "<tr id='fila-$id' {$data_attributes}>
                    <td>{$id}</td>
                    <td>
                        <span id='nombre-vista-$id'>{$nom_escapado}</span>
                    </td>
                    <td>
                        <img id='img-preview-$id' src='../../{$rut_escapado}' style='max-width:80px; max-height:40px; object-fit:contain; margin-bottom: 5px;'>
                    </td>
                    <td>
                        <button class='btn $btn_clase btn-sm' onclick='activarLogoUnico({$id}, \"{$row['log_est']}\")' " . ($es_activo ? 'disabled' : '') . ">$btn_texto</button>
                    </td>
                    <td>
                        <button class='btn btn-warning btn-sm' onclick='cargarDatosEdicion({$id})'>Editar</button>
                    </td>
                </tr>";
    }
    // Devolvemos HTML en formato JSON para que JS pueda manejar la inicialización de DataTables
    echo json_encode(["success" => true, "html" => $html]);
    exit;
}

// -------------------- OBTENER (No es necesario con data-attributes, pero se mantiene si se usa) --------------------
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'obtener') {
    // La lógica de obtener ha sido omitida/simplificada ya que la edición carga datos desde los data-attributes de la tabla
    // Si fuera necesario, se descomenta y se usa para una consulta más detallada
    exit;
}
*/

// -------------------- CREAR / ACTUALIZAR --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'crear' || ($_POST['accion'] ?? '') === 'actualizar')) {
    $accion = $_POST['accion'];
    $id = intval($_POST['log_id'] ?? 0); 
    $res_db = false;
    $mensaje = "";

    pg_query($conn, "BEGIN");

    try {
        // Sanitización simple
        $nombre = pg_escape_string($conn, $_POST['log_nom']);
        $estado = 'desactivado'; 
        $ruta_anterior = null;
        
        if ($accion === 'actualizar') {
            // Obtenemos la ruta anterior y el estado
            $query_old_data = "SELECT log_rut, log_est FROM tbl_logos WHERE log_id = $id";
            $res_old_data = pg_query($conn, $query_old_data);
            $old_data = pg_fetch_assoc($res_old_data);
            if (!$old_data) throw new Exception("Logo a actualizar no encontrado.");
            $ruta_anterior = $old_data['log_rut'] ?? null;
            $estado = $old_data['log_est']; // Mantenemos el estado actual al actualizar
        }

        // Manejar la subida de la imagen, incluyendo eliminación de la anterior si aplica
        $imagenRuta = manejar_subida_imagen($conn, $_FILES['log_rut'] ?? null, $dir_logos, $ruta_anterior);
        
        if ($imagenRuta === false) {
            throw new Exception("Error al subir la imagen. Tipo de archivo no permitido.");
        }
        // Se valida el campo `log_rut` en el frontend (jQuery Validation) y en el backend (aquí)
        if (is_null($imagenRuta) && $accion === 'crear') {
            throw new Exception("Debe subir una imagen válida para crear un logo.");
        }
        
        // Si es actualización y la ruta es nula (se borró la imagen), y se intentó acceder, se permite
        
        if ($accion === 'crear') {
            $query = "INSERT INTO tbl_logos (log_nom, log_rut, log_est) VALUES ('$nombre', '$imagenRuta', '$estado')";
            $res_db = pg_query($conn, $query);
            $mensaje = "Logo creado correctamente. Recuerde activarlo para que sea visible.";
        } else { // Actualizar
            
            // Usamos COALESCE para manejar el caso de que $imagenRuta sea null (se eliminó la imagen)
            $ruta_db_value = is_null($imagenRuta) ? "NULL" : "'$imagenRuta'";

            $query = "UPDATE tbl_logos SET log_nom='$nombre', log_rut=$ruta_db_value, log_fec_edi=CURRENT_TIMESTAMP WHERE log_id=$id";
            $res_db = pg_query($conn, $query);
            $mensaje = "Logo ID $id actualizado correctamente.";
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

// -------------------- ELIMINAR (Simple) - ELIMINADO SEGÚN REQUERIMIENTO DEL USUARIO --------------------
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $id = intval($_POST['id'] ?? 0);
    
    pg_query($conn, "BEGIN");
    try {
        // Obtener ruta para borrar físico
        $res_url = pg_query_params($conn, "SELECT log_rut FROM tbl_logos WHERE log_id = $1", [$id]);
        $url_data = pg_fetch_assoc($res_url);
        $ruta_imagen = $url_data['log_rut'] ?? null;
        
        // Uso de pg_query_params para DELETE
        $res_db = pg_query_params($conn, "DELETE FROM tbl_logos WHERE log_id = $1", [$id]);
        
        if ($res_db) {
            if ($ruta_imagen) {
                $raiz_proyecto = realpath(__DIR__ . '/../../');
                $ruta_completa_imagen = $raiz_proyecto . '/' . $ruta_imagen;
                if (file_exists($ruta_completa_imagen)) {
                    unlink($ruta_completa_imagen);
                }
            }
            pg_query($conn, "COMMIT");
            echo json_encode(["success" => true, "mensaje" => "Logo eliminado correctamente."]);
        } else {
            pg_query($conn, "ROLLBACK");
            throw new Exception("Error al eliminar en la base de datos: " . pg_last_error($conn));
        }
    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        echo json_encode(["success" => false, "mensaje" => $e->getMessage()]);
    }
    exit;
}
*/

ob_start(); // INICIO DEL BUFFER PARA CAPTURAR EL CONTENIDO
?>

<link href="//cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.3/css/fileinput.min.css" media="all" rel="stylesheet" type="text/css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/localization/messages_es.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="//cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.3/js/fileinput.min.js"></script>
<script src="//cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.3/js/locales/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>


<div class="container" style="max-width: 900px; margin: 20px auto; text-align: center;">
    <h2 style="margin-bottom:20px;">Gestión de Logos</h2>

    <div style="margin-bottom:20px; text-align: left;">
        <button id="btnAgregar" class="btn btn-primary">➕ Agregar Nuevo Logo</button>
    </div>
    
    <div class="modal fade" id="logoModal" tabindex="-1" aria-labelledby="logoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg"> <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoModalLabel">Crear Nuevo Logo</h5> 
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> 
                </div>
                <div class="modal-body">
                    <form id="formLogo" enctype="multipart/form-data">
                        <input type="hidden" id="log_id_modal" name="log_id">
                        <input type="hidden" id="eliminar_imagen_input" name="eliminar_imagen" value="false">
                        <div class="row g-3">
                            
                            <div class="col-12">
                                <label for="log_nom_modal" class="form-label d-block text-start">Nombre del Logo:</label>
                                <input type="text" id="log_nom_modal" name="log_nom" required class="form-control form-control-sm">
                                <small class="text-muted d-block mt-1 text-start" style="font-size:10px;">*El nombre es solo para identificarlo en la lista.</small>
                            </div>

                            <div class="col-12" style="text-align: center;">
                                <label for="log_rut_modal" class="form-label d-block text-start">Imagen del Logo:</label>
                                <input type="file" id="log_rut_modal" name="log_rut" accept="image/*" class="form-control">
                                <div id="kv-error-1" class="text-danger" style="width:100%; margin-top:5px; font-size:10px;"></div> 
                                <small class="text-muted d-block mt-1" style="font-size:10px;">En edición, puede eliminar o reemplazar.</small>
                            </div>
                            
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="display: flex; gap: 10px;">
                    <button type="submit" form="formLogo" id="btnGuardar" class="btn btn-success btn-sm">Guardar Logo</button>
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    <div id="tablaContainer" class="table-container" style="overflow-x:auto;">
        <table id="tablaRegistros" class="display" style="border-collapse:collapse; width:100%; font-size:12px; margin:0 auto; border: 1px solid #ccc;">
            <thead>
                <tr>
                    <th style="background:#f2f2f2; padding:8px;">ID</th>
                    <th style="background:#f2f2f2; padding:8px;">Nombre</th>
                    <th style="background:#f2f2f2; padding:8px;">Imagen</th>
                    <th style="background:#f2f2f2; padding:8px;">Estado</th>
                    <th style="background:#f2f2f2; padding:8px;">Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody"></tbody>
        </table>
    </div>
</div>

<style>
    /* Estilos base (mantenidos o simplificados) */
    .btn { padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; margin: 2px; transition: all 0.3s ease; }
    .btn-primary { background: #2196F3; color: white; }
    .btn-success { background: #4CAF50; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-warning { background: #ff9800; color: white; }
    .btn-danger { background: #f44336; color: white; }
    table th, table td { border: 1px solid #ccc; padding: 6px; text-align: center; vertical-align: middle; }
    
    /* Estilos del modal y fileinput (para el formato tarjeta) */
    .modal-body { 
        padding-top: 20px; 
        /* Añadir un ligero borde o sombra al contenido del modal si se desea un aspecto de tarjeta interna */
    }
    /* Estilos para que el FileInput se vea compacto */
    .file-input .file-caption {
        font-size: 12px;
    }
    .file-input .btn-file, .file-input .fileinput-remove {
        padding: 4px 8px;
        font-size: 11px;
    }
    .file-input .kv-file-remove, .file-input .kv-file-zoom {
        font-size: 10px;
    }
    .file-input .file-preview {
        padding: 5px;
    }
    .file-input .file-preview-frame {
        margin: 5px;
        border: 1px solid #ddd;
    }
    
    /* *** NUEVO: Estilo para la validación con borde rojo *** */
    .error {
        color: #dc3545; /* Color del texto de error */
        font-size: 10px;
        margin-top: 3px;
        display: block;
    }
    input.error, select.error, textarea.error {
        border: 1px solid #dc3545 !important; /* Borde rojo */
    }
    /* Estilo para el contenedor del FileInput en caso de error */
    .file-input.error .file-caption-name {
        border-color: #dc3545 !important;
    }
</style>

<script>
    let dataTable = null; 
    let logoModal = null; // Instancia del Modal de Bootstrap
    let fileInputInitialized = false;
    const RUTA_BASE_IMAGEN = '../../'; // Necesario para la vista previa del logo

    /**
    * Muestra una alerta usando SweetAlert2.
    */
    function mostrarMensaje(data) {
        Swal.fire({
            icon: data.success ? 'success' : 'error',
            title: data.success ? 'Éxito' : 'Error',
            text: data.mensaje,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }

    /**
     * Inicializa o re-inicializa el plugin FileInput.
     */
    function inicializarFileInput(initialPreview = [], initialPreviewConfig = [], required = true, isUpdate = false) {
        // 1. Destruir si ya existe
        if (fileInputInitialized) {
            $('#log_rut_modal').fileinput('destroy');
        }

        // 2. Inicializar
        const fileInput = $('#log_rut_modal').fileinput({
            showUpload: false,
            language: "es",
            allowedFileExtensions: ["jpg", "jpeg", "png", "webp", "gif"],
            maxFileSize: 2048, // KB (2MB)
            browseClass: "btn btn-outline-secondary btn-sm",
            msgPlaceholder: "Seleccione logo...",
            previewFileType: "image",
            elErrorContainer: "#kv-error-1",
            initialPreview: initialPreview,
            initialPreviewConfig: initialPreviewConfig,
            initialPreviewAsData: true, 
            overwriteInitial: true, // Siempre sobreescribir la previsualización
            showRemove: isUpdate, // Mostrar botón remover solo en edición
            showCancel: false,
            // required: required // SE ELIMINA para evitar la doble validación/mensaje de error con jQuery Validation
        });

        // 3. Manejo de eventos del FileInput
        fileInput.on('filecleared', function() {
            // Si el usuario borra la imagen en modo edición, marcamos para eliminar en el backend
            if (isUpdate) {
                document.getElementById('eliminar_imagen_input').value = 'true';
            }
            // Disparar la validación si el campo es requerido
            if (required) {
                $('#formLogo').validate().element('#log_rut_modal');
            }
        }).on('filepreupload', function() {
            // Si el usuario selecciona una nueva imagen, desmarcamos la eliminación
            document.getElementById('eliminar_imagen_input').value = 'false';
            // Disparar la validación para limpiar el error (si lo había)
            $('#formLogo').validate().element('#log_rut_modal');
        });

        fileInputInitialized = true;
    }
    
    function cargarItems() {
        if (dataTable) {
            dataTable.destroy();
            dataTable = null; 
        }
        
        let form = new FormData();
        form.append('accion', 'listar');
        
        const tablaBody = document.getElementById('tableBody');
        tablaBody.innerHTML = '<tr><td colspan="5">Cargando...</td></tr>';

        fetch('', { method: 'POST', body: form })
            .then(res => res.json()) 
            .then(data => {
                if (data.success && data.html) {
                    tablaBody.innerHTML = data.html;
                    // Inicializar DataTables después de cargar el contenido
                    dataTable = new DataTable('#tablaRegistros', {
                        paging: true,
                        searching: true,
                        ordering: true,
                        info: true,
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
                        }
                    });
                } else {
                    tablaBody.innerHTML = '<tr><td colspan="5">No hay logos registrados.</td></tr>';
                    if (data && data.mensaje) mostrarMensaje(data); 
                }
            })
            .catch((err) => {
                console.error("Error fetching data:", err);
                tablaBody.innerHTML = '<tr><td colspan="5" class="alert-danger">Error de conexión al cargar la lista.</td></tr>';
            });
    }

    // ============== LÓGICA DE ACTIVACIÓN ==============
    window.activarLogoUnico = function(id, estadoActual) {
        if (estadoActual === 'activo') {
            mostrarMensaje({ success: false, mensaje: 'Este logo ya es el único activo.' });
            return;
        }

        Swal.fire({
            title: '¿Estás seguro?',
            text: 'El logo actual activo será desactivado y este será el nuevo logo principal.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4CAF50',
            cancelButtonColor: '#f44336',
            confirmButtonText: 'Sí, activar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                let form = new FormData();
                form.append('accion', 'activar_logo_unico');
                form.append('id', id);

                fetch('', { method: 'POST', body: form })
                    .then(res => res.json())
                    .then(data => {
                        mostrarMensaje(data);
                        if (data.success) cargarItems();
                    })
                    .catch(() => mostrarMensaje({ success: false, mensaje: 'Error de red al activar logo.' }));
            }
        });
    }
    
    // ============== LÓGICA DEL MODAL (Crear/Editar) ==============

    document.getElementById("btnAgregar").addEventListener("click", function() {
        // Modo CREACIÓN
        document.getElementById("formLogo").reset();
        document.getElementById("log_id_modal").value = ""; 
        document.getElementById('eliminar_imagen_input').value = 'false';
        
        // Resetear la validación y limpiar errores visibles
        $("#formLogo").validate().resetForm(); 
        
        // Inicializar FileInput: Requerido en creación (true), modo actualización (false)
        inicializarFileInput([], [], true, false); 

        document.getElementById("logoModalLabel").innerText = 'Crear Nuevo Logo';
        document.getElementById("btnGuardar").innerText = "Guardar Logo";
        
        // Mostrar el modal
        if (logoModal) {
            logoModal.show();
        }
    });

    window.cargarDatosEdicion = function(id) {
        const fila = document.getElementById(`fila-${id}`);
        if (!fila) {
            mostrarMensaje({success: false, mensaje: "Fila de logo no encontrada."});
            return;
        }

        // Resetear la validación y limpiar errores visibles
        $("#formLogo").validate().resetForm();

        // 1. Obtener datos de los data-attributes
        const nombre = fila.dataset.nom;
        const imagenRuta = fila.dataset.rut; // Ruta relativa: apis/imagenes/pagina/logos/xxx.png
        
        // 2. Configurar el formulario para edición
        document.getElementById('log_id_modal').value = id;
        document.getElementById('log_nom_modal').value = nombre;
        document.getElementById('eliminar_imagen_input').value = 'false'; // Resetea la bandera de eliminación
        
        // 3. Configuración de Bootstrap FileInput
        const initialPreview = imagenRuta ? [RUTA_BASE_IMAGEN + imagenRuta] : [];
        const initialPreviewConfig = imagenRuta
            ? [{ caption: imagenRuta.split('/').pop(), key: 1, type: "image", downloadUrl: RUTA_BASE_IMAGEN + imagenRuta }]
            : [];

        // Inicializar FileInput: No requerido en edición (false), modo actualización (true)
        inicializarFileInput(initialPreview, initialPreviewConfig, false, true); 

        // 4. Actualizar textos de formulario
        document.getElementById('logoModalLabel').innerText = 'Editar Logo ID: ' + id;
        document.getElementById('btnGuardar').innerText = 'Actualizar Logo';
        
        // 5. Mostrar el modal
        if (logoModal) {
            logoModal.show();
        }
    };
    
    // Manejador de submit del formulario (se llama si la validación jQuery es exitosa)
    function handleFormSubmit(form) {
        
        // La validación se hace a través del plugin, el preventDefault lo maneja él.
        // Aquí form es el elemento DOM del formulario
        
        const logoId = document.getElementById('log_id_modal').value;
        const esCrear = !logoId;
        
        let formData = new FormData(form);
        let accion = esCrear ? 'crear' : 'actualizar'; 
        formData.append('accion', accion);

        const confirmTitle = accion === 'actualizar' ? "¿Confirmar Actualización?" : "¿Crear Nuevo Logo?";
        const confirmText = accion === 'actualizar' ? "Se guardarán los cambios." : "¿Está seguro de querer crear este logo?";
        const confirmButtonText = accion === 'actualizar' ? "Sí, Actualizar" : "Sí, Crear";
        const confirmColor = accion === 'actualizar' ? "#ff9800" : "#4CAF50";

        Swal.fire({
            title: confirmTitle,
            text: confirmText,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: confirmButtonText,
            cancelButtonText: "Cancelar",
            confirmButtonColor: confirmColor,
            cancelButtonColor: "#6c757d"
        }).then((result) => {
            if (result.isConfirmed) {
                
                fetch('',{method:'POST',body:formData})
                .then(res=>{
                    return res.text();
                })
                .then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error("Respuesta no es JSON. Error PHP o fatal:", text);
                        mostrarMensaje({ success: false, mensaje: 'Error de servidor (PHP): Respuesta inválida. Revisa la consola.' });
                        return;
                    }
                    
                    mostrarMensaje(data);

                    if(data.success){
                        // Ocultar el modal
                        if (logoModal) {
                            logoModal.hide();
                        }
                        // Limpiar y destruir FileInput
                        if (fileInputInitialized) {
                            $('#log_rut_modal').fileinput('destroy');
                            fileInputInitialized = false;
                        }
                        
                        cargarItems(); 
                    }
                })
                .catch(error => {
                    console.error("Error de red o procesamiento:", error);
                    mostrarMensaje({ success: false, mensaje: 'Error de red en la operación: ' + error.message });
                });
            }
        });
    }

    // ============== LÓGICA DE ELIMINAR (ELIMINADA) ==============
    /*
    window.eliminarLogo = function(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¡No podrás revertir esto! Se recomienda no eliminar logos activos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f44336',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                let form = new FormData();
                form.append('accion', 'eliminar');
                form.append('id', id);

                fetch('', { method: 'POST', body: form })
                    .then(res => res.json())
                    .then(data => {
                        mostrarMensaje(data);
                        if (data.success) cargarItems();
                    })
                    .catch(() => mostrarMensaje({ success: false, mensaje: 'Error de red al eliminar logo.' }));
            }
        });
    }
    */


    // Cargar la tabla e inicializar el modal al inicio
    document.addEventListener("DOMContentLoaded", function() {
        // Inicializar el objeto Modal de Bootstrap
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
             logoModal = new bootstrap.Modal(document.getElementById('logoModal'), {});
        } else {
             console.warn("Bootstrap JS no está cargado. El modal no funcionará correctamente.");
        }
        
        cargarItems();
        // Inicializar FileInput por defecto en modo creación (por si se abre sin pasar por el botón)
        inicializarFileInput([], [], true, false); 
    });

    // ============== JQUERY VALIDATION PLUGIN ==============
    // Este código se debe ejecutar después de que jQuery y el plugin de validación estén cargados
    $(document).ready(function() {
        
        // Define un método de validación personalizado para el FileInput.
        $.validator.addMethod("checkLogoFile", function(value, element) {
            const esCrear = !$("#log_id_modal").val();
            const fileCount = element.files.length;
            
            // En modo crear, debe haber al menos un archivo.
            if (esCrear) {
                return fileCount > 0;
            }
            // En modo actualizar, no es estrictamente requerido, confiamos en el backend para la validación final.
            return true; 
        }, "Debe seleccionar un archivo de logo.");
        
        $("#formLogo").validate({
            rules: {
                "log_nom": {
                    required: true,
                    minlength: 3, 
                    maxlength: 100
                },
                "log_rut": {
                    checkLogoFile: true // Usamos la regla personalizada
                }
            },
            messages: {
                "log_nom": {
                    required: "El nombre del logo es obligatorio.",
                    minlength: "Mínimo 3 caracteres para el nombre.", 
                    maxlength: "Máximo 100 caracteres para el nombre."
                },
                "log_rut": {
                    // El mensaje de error para 'log_rut' viene de la regla 'checkLogoFile'
                }
            },
            errorPlacement: function(error, element) {
                // Coloca el mensaje de error de forma personalizada
                if (element.attr("name") == "log_rut") {
                    // Para el FileInput, usamos el contenedor de error definido por él (#kv-error-1)
                    error.appendTo("#kv-error-1");
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass(errorClass).removeClass(validClass);
                // Si es el file input, también marcamos su contenedor
                if ($(element).attr("name") === "log_rut") {
                    $(element).closest('.file-input').addClass(errorClass);
                }
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass(errorClass).addClass(validClass);
                // Si es el file input, también desmarcamos su contenedor
                if ($(element).attr("name") === "log_rut") {
                    $(element).closest('.file-input').removeClass(errorClass);
                }
            },
            submitHandler: function(form) {
                // Cuando la validación es exitosa, llama a nuestra función de manejo de envío
                handleFormSubmit(form);
            }
        });
    });

</script>

<?php
$contenido = ob_get_clean(); 
// Asegúrate de que esta ruta sea la correcta para tu plantilla de administrador
include 'plantillaAdmin.php'; 
?>