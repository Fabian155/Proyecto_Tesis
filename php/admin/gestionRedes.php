<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

$base_dir_imagenes_db = 'apis/imagenes/pagina/';
$dir_redes = $base_dir_imagenes_db . 'redes/';

// ================== FUNCIONES DE UTILIDAD ==================

/**
 * Maneja la subida de una imagen al servidor, elimina la anterior si es necesario.
 * (Ajustada para devolver un array estructurado para la gestión AJAX).
 * @param resource $conn Conexión a la base de datos.
 * @param array $file_data Array $_FILES['nombre_campo'].
 * @param string $directorio_destino_db Ruta relativa al proyecto para guardar en DB.
 * @param string|null $ruta_anterior_db Ruta de la imagen anterior para posible borrado.
 * @return array Resultado de la operación: success (bool), mensaje (string), ruta_db (string|null).
 */
function manejar_subida_imagen($conn, $file_data, $directorio_destino_db, $ruta_anterior_db = null) {
    $raiz_proyecto = realpath(__DIR__ . '/../../'); 
    $imagenRuta = $ruta_anterior_db;

    // 1. Procesar la nueva imagen si existe
    if (isset($file_data) && $file_data['error'] === UPLOAD_ERR_OK && $file_data['size'] > 0) {
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        
        if (!in_array($file_data['type'], $tipos_permitidos)) {
             return ["success" => false, "mensaje" => "Tipo de archivo no permitido. Solo se aceptan JPG, PNG, GIF, WEBP y SVG."];
        }

        $nombreArchivo = time() . "_" . basename($file_data['name']);
        $ruta_destino_absoluta = $raiz_proyecto . '/' . $directorio_destino_db . $nombreArchivo;
        $directorio_absoluto = dirname($ruta_destino_absoluta);
        
        if (!is_dir($directorio_absoluto)) {
            mkdir($directorio_absoluto, 0777, true);
        }
        
        if (move_uploaded_file($file_data['tmp_name'], $ruta_destino_absoluta)) {
            $imagenRuta = $directorio_destino_db . $nombreArchivo;
            
            // Eliminar la imagen anterior si se subió una nueva
            if ($ruta_anterior_db && $imagenRuta !== $ruta_anterior_db) {
                $ruta_completa_anterior = $raiz_proyecto . '/' . $ruta_anterior_db;
                if (file_exists($ruta_completa_anterior)) {
                    unlink($ruta_completa_anterior);
                }
            }
        } else {
             return ["success" => false, "mensaje" => "Error al mover el nuevo archivo de imagen al servidor."];
        }
    } else if (isset($_POST['eliminar_imagen']) && $_POST['eliminar_imagen'] === 'true') {
        // Lógica para eliminar la imagen
        if ($ruta_anterior_db) {
            $ruta_completa_anterior = $raiz_proyecto . '/' . $ruta_anterior_db;
            if (file_exists($ruta_completa_anterior)) {
                unlink($ruta_completa_anterior);
            }
        }
        $imagenRuta = null;
    }
    
    return ["success" => true, "ruta_db" => $imagenRuta];
}

// ================== ACCIONES AJAX ==================

// Crear Red Social
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $nombre = pg_escape_string($conn, $_POST['red_nom']);
    $enlace = pg_escape_string($conn, $_POST['red_enl']);
    
    $resultado_img = manejar_subida_imagen($conn, $_FILES['imagen'] ?? null, $dir_redes);
    if (!$resultado_img['success']) {
        echo json_encode($resultado_img);
        exit;
    }
    $imagenRuta = $resultado_img['ruta_db'];

    if (!$imagenRuta) {
         echo json_encode(["success" => false, "mensaje" => "El icono de la red social es obligatorio."]);
         exit;
    }

    $query = "INSERT INTO tbl_red_social (red_nom, red_img, red_enl, red_est) 
              VALUES ('$nombre', '$imagenRuta', '$enlace', TRUE)";

    $result = pg_query($conn, $query);

    $response = $result 
        ? ["success" => true, "mensaje" => "Red Social creada correctamente"] 
        : ["success" => false, "mensaje" => "Error al crear: " . pg_last_error($conn)];
    
    echo json_encode($response);
    exit;
}

// Actualizar Red Social
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $id = intval($_POST['red_id']);
    $nombre = pg_escape_string($conn, $_POST['red_nom']);
    $enlace = pg_escape_string($conn, $_POST['red_enl']);
    $imagen_anterior_db = $_POST['imagen_anterior'] ?? null;
    
    $resultado_img = manejar_subida_imagen($conn, $_FILES['imagen'] ?? null, $dir_redes, $imagen_anterior_db);
    if (!$resultado_img['success']) {
        echo json_encode($resultado_img);
        exit;
    }
    $imagenRuta = $resultado_img['ruta_db'];

    if (!$imagenRuta) {
         echo json_encode(["success" => false, "mensaje" => "La red social debe tener un icono."]);
         exit;
    }

    $query = "UPDATE tbl_red_social SET
                red_nom='$nombre', red_img='$imagenRuta', red_enl='$enlace'
                WHERE red_id=$id";

    $res = pg_query($conn, $query);

    echo json_encode($res ? ["success" => true, "mensaje" => "Red Social actualizada"] : ["success" => false, "mensaje" => "Error al actualizar: " . pg_last_error($conn)]);
    exit;
}

// Eliminar Red Social
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $id = intval($_POST['id']);
    
    $query_imagen = "SELECT red_img FROM tbl_red_social WHERE red_id = $id";
    $res_imagen = pg_query($conn, $query_imagen);
    $imagen_data = pg_fetch_assoc($res_imagen);
    $ruta_imagen = isset($imagen_data['red_img']) ? $imagen_data['red_img'] : null;
    $raiz_proyecto = realpath(__DIR__ . '/../../');

    pg_query($conn, "BEGIN");
    $res_db = pg_query($conn, "DELETE FROM tbl_red_social WHERE red_id=$id");
    
    if ($res_db) {
        if ($ruta_imagen) {
            $ruta_completa_imagen = $raiz_proyecto . '/' . $ruta_imagen;
            if (file_exists($ruta_completa_imagen)) {
                unlink($ruta_completa_imagen);
            }
        }
        pg_query($conn, "COMMIT");
        echo json_encode(["success" => true, "mensaje" => "Red Social y su icono eliminados correctamente"]);
    } else {
        pg_query($conn, "ROLLBACK");
        echo json_encode(["success" => false, "mensaje" => "Error: " . pg_last_error($conn)]);
    }
    exit;
}

// Cambiar Estado (Activar/Desactivar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estado') {
    $id = intval($_POST['id']);
    $estado = $_POST['estado'] === 'activar' ? 'TRUE' : 'FALSE';
    $estado_mensaje = $_POST['estado'] === 'activar' ? 'activado' : 'desactivado';

    $query = "UPDATE tbl_red_social SET red_est=$estado WHERE red_id=$id";
    $result = pg_query($conn, $query);

    $response = $result 
        ? ["success" => true, "mensaje" => "Red Social $id $estado_mensaje correctamente"] 
        : ["success" => false, "mensaje" => "Error al cambiar estado: " . pg_last_error($conn)];
    
    echo json_encode($response);
    exit;
}

// Listar Redes Sociales (para DataTables)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'listar') {
    $query = "SELECT * FROM tbl_red_social ORDER BY red_id DESC";
              
    $redes = pg_query($conn, $query);
    $html = "";
    
    while ($row = pg_fetch_assoc($redes)) {
        $id = $row['red_id'];
        $estado = $row['red_est'] === 't';
        $estado_texto = $estado ? '<span class="badge-success">Activa</span>' : '<span class="badge-danger">Inactiva</span>';
        $btn_accion_texto = $estado ? 'Desactivar' : 'Activar';
        $btn_accion_clase = $estado ? 'btn-danger' : 'btn-success';

        // --- Generación de la celda de imagen ---
        $img_html = "<td>";
        $img_ruta = htmlspecialchars($row['red_img'] ?? '', ENT_QUOTES);
        $img_display_ruta = $img_ruta ? "../../" . $img_ruta : '';

        if ($img_ruta) {
            $img_html .= "<img src='{$img_display_ruta}' style='max-width:40px; max-height:40px; cursor:pointer;' onclick=\"verImagen('{$img_display_ruta}')\">";
        } else {
            $img_html .= "<span>Sin icono</span>";
        }
        $img_html .= "</td>";

        // Preparamos los datos para la edición (usando data attributes)
        $data_attributes = "";
        $data_attributes .= " data-nom='" . htmlspecialchars($row['red_nom'], ENT_QUOTES) . "'";
        $data_attributes .= " data-enl='" . htmlspecialchars($row['red_enl'], ENT_QUOTES) . "'";
        $data_attributes .= " data-img='" . $img_ruta . "'";

        $html .= "<tr id='fila-{$id}' {$data_attributes}>
                    <td>{$id}</td>
                    <td>" . htmlspecialchars($row['red_nom']) . "</td>
                    <td>" . htmlspecialchars($row['red_enl']) . "</td>
                    {$img_html}
                    <td>{$estado_texto}</td>
                    <td>
                        <button class='btn btn-warning btn-sm' onclick='cargarDatosEdicion({$id})'>Editar</button>
                        <button class='btn {$btn_accion_clase} btn-sm' onclick='cambiarEstado({$id}, \"{$btn_accion_texto}\")'>{$btn_accion_texto}</button>
                        <button class='btn btn-danger btn-sm' onclick='eliminarRedSocial({$id})'>Eliminar</button>
                    </td>
                </tr>";
    }
    echo $html;
    exit;
}


ob_start();
?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<link href="//cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput/5.5.3/css/fileinput.min.css" media="all" rel="stylesheet" type="text/css" />
<script src="//cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput/5.5.3/js/fileinput.min.js"></script>
<script src="//cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput/5.5.3/js/locales/es.js"></script>


<div class="container-fluid" style="padding: 20px; text-align: center;">
    <h2 style="margin-bottom:15px;">Gestión de Redes Sociales</h2>
    
    <div style="margin-bottom:20px; text-align: left;">
        <button id="btnAgregarRedSocial" class="btn btn-primary">➕ Agregar Nueva Red Social</button>
    </div>

    <div class="modal fade" id="redSocialModal" tabindex="-1" aria-labelledby="formTitle" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formTitle">Crear Nueva Red Social</h5> 
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formRedSocial" enctype="multipart/form-data">
                        <input type="hidden" id="red_id" name="red_id">
                        <input type="hidden" id="imagen_anterior" name="imagen_anterior" value="">
                        <input type="hidden" id="eliminar_imagen_input" name="eliminar_imagen" value="false">

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="nombre" class="form-label">Nombre de la Red Social:</label>
                                <input type="text" id="nombre" name="red_nom" required class="form-control form-control-sm">
                            </div>
                            
                            <div class="col-12">
                                <label for="enlace" class="form-label">Enlace/URL de la Red:</label>
                                <input type="url" id="enlace" name="red_enl" required class="form-control form-control-sm">
                            </div>

                            <div class="col-12">
                                <label for="imagen" class="form-label">Icono de la Red Social:</label>
                                <input type="file" id="imagen" name="imagen" accept="image/*" class="form-control">
                                <div id="kv-error-1" style="width:100%; margin-top:5px;"></div> 
                                <small class="text-muted">La imagen es obligatoria. Formatos: JPG, PNG, GIF, WEBP, SVG. Max 2MB.</small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="display: flex; gap: 10px;">
                    <button type="submit" form="formRedSocial" id="btnGuardar" class="btn btn-success btn-sm">Guardar Red</button>
                    <button type="button" id="btnCancelarForm" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    <h3 style="margin-top:25px;">Redes Sociales Registradas</h3>
    <div class="table-container" style="overflow-x:auto;">
        <table id="tablaRegistros" class="display" style="border-collapse:collapse; width:100%; font-size:12px; margin:0 auto;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Enlace</th>
                    <th>Icono</th>
                    <th>Estado</th>
                    <th data-dt-order="disable">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaRedes"></tbody>
        </table>
    </div>
</div>

<style>
    /* Estilos básicos */
    .form-label { margin-bottom: 2px; font-size: 12px; }
    .form-control, .form-select { font-size: 12px !important; }
    .btn { padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; margin: 2px; }
    .btn-primary { background: #2196F3; color: white; }
    .btn-success { background: #4CAF50; color: white; }
    .btn-danger { background: #f44336; color: white; }
    .btn-warning { background: #ff9800; color: white; }
    table th, table td { border: 1px solid #ccc; padding: 6px; text-align: center; vertical-align: middle; }
    th { background: #f2f2f2; }
    /* Estilos para badges de estado */
    .badge-success { background-color: #4CAF50; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; }
    .badge-danger { background-color: #f44336; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; }
</style>


<script>
    let dataTable = null; 
    let redSocialModal = null; 
    let fileInputInitialized = false;

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
     * Inicializa o re-inicializa el plugin FileInput (Kartik-v).
     */
    function inicializarFileInput(initialPreview = [], initialPreviewConfig = [], isUpdate = false) {
        if (fileInputInitialized) {
            $('#imagen').fileinput('destroy');
        }

        // La imagen es obligatoria.
        const required = true; 

        $('#imagen').fileinput({
            showUpload: false,
            language: "es",
            allowedFileExtensions: ["jpg","jpeg","png","gif","webp","svg"],
            maxFileSize: 2048, // KB (2MB)
            browseClass: "btn btn-outline-secondary btn-sm",
            msgPlaceholder: "Seleccione icono...",
            previewFileType: "any", // Permite SVG
            elErrorContainer: "#kv-error-1",
            initialPreview: initialPreview,
            initialPreviewConfig: initialPreviewConfig,
            initialPreviewAsData: true,           
            overwriteInitial: true, 
            showRemove: isUpdate, 
            showCancel: false,
            required: required,
            dropZoneEnabled: true, // Habilitar arrastrar y soltar
            showCaption: true
        }).on('filecleared', function() {
            // Si el usuario borra la imagen en modo edición, marcamos para eliminar en el backend
            if (isUpdate) {
                document.getElementById('eliminar_imagen_input').value = 'true';
            }
        });
        fileInputInitialized = true;
    }


    /**
     * Carga la lista de redes sociales y refresca la DataTables.
     */
    function cargarItems() {
        // 1. Destruir la instancia de DataTables si existe
        if (dataTable) {
            dataTable.destroy();
            dataTable = null; 
        }
        
        let form = new FormData();
        form.append("accion", "listar");
        const tablaBody = document.getElementById("tablaRedes");
        tablaBody.innerHTML = '<tr><td colspan="6">Cargando...</td></tr>'; 
        
        fetch("", { method: "POST", body: form })
            .then(res => res.text())
            .then(html => {
                tablaBody.innerHTML = html;
                
                // 2. Inicializar DataTables después de cargar el contenido
                dataTable = new DataTable('#tablaRegistros', {
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    responsive: true,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
                    }
                });
            })
            .catch(err => {
                 mostrarMensaje({ success: false, mensaje: 'Error al cargar la lista de redes sociales.' });
                 tablaBody.innerHTML = '<tr><td colspan="6">Error al cargar la lista.</td></tr>';
            });
    }
    
    // ============== LÓGICA DE EDICIÓN (MODAL) ==============

    window.cargarDatosEdicion = function(id) {
        const fila = document.getElementById(`fila-${id}`);
        if (!fila) {
            mostrarMensaje({success: false, mensaje: "Fila de red social no encontrada."});
            return;
        }

        // 1. Configurar el formulario para edición
        document.getElementById('red_id').value = id;
        document.getElementById('nombre').value = fila.dataset.nom;
        document.getElementById('enlace').value = fila.dataset.enl;
        
        // Configuración de imagen
        const imagenRuta = fila.dataset.img;
        document.getElementById('imagen_anterior').value = imagenRuta;
        document.getElementById('eliminar_imagen_input').value = 'false';
        
        const initialPreview = imagenRuta ? ["../../" + imagenRuta] : [];
        const initialPreviewConfig = imagenRuta
            ? [{ caption: imagenRuta.split('/').pop(), key: 1, type: "image", downloadUrl: "../../" + imagenRuta }]
            : [];

        // Inicializar FileInput en modo actualización (true)
        inicializarFileInput(initialPreview, initialPreviewConfig, true); 

        // 2. Actualizar textos de formulario
        document.getElementById('formTitle').innerText = 'Editar Red Social ID: ' + id;
        document.getElementById('btnGuardar').innerText = 'Actualizar Red Social';
        
        // 3. Mostrar el modal
        if (redSocialModal) {
            redSocialModal.show();
        }
    };
    
    // Función para limpiar el formulario y cerrar el modal
    function cancelarFormulario() {
        document.getElementById("formRedSocial").reset();
        document.getElementById("red_id").value = "";
        document.getElementById('imagen_anterior').value = '';
        document.getElementById('eliminar_imagen_input').value = 'false';
        
        // Destruir FileInput
        if (fileInputInitialized) {
            $('#imagen').fileinput('destroy');
            fileInputInitialized = false;
        }
        
        if (redSocialModal) {
            redSocialModal.hide();
        }
        cargarItems();
    }
    
    document.getElementById("btnCancelarForm").addEventListener("click", function() {
        cancelarFormulario();
    });
    
    // ============== LÓGICA DE CAMBIAR ESTADO ==============

    window.cambiarEstado = function(id, estadoActual) {
        const estadoNuevo = estadoActual === 'Activar' ? 'activar' : 'desactivar';
        const icono = estadoNuevo === 'activar' ? 'question' : 'warning';
        const color = estadoNuevo === 'activar' ? '#3085d6' : '#d33';

        Swal.fire({
            title: `¿Estás seguro de ${estadoNuevo} la red social?`,
            text: `La red social será marcada como ${estadoNuevo}.`,
            icon: icono,
            showCancelButton: true,
            confirmButtonText: `Sí, ${estadoNuevo}`,
            cancelButtonText: "Cancelar",
            confirmButtonColor: color,
            cancelButtonColor: "#6c757d"
        }).then((result) => {
            if (result.isConfirmed) {
                let form = new FormData();
                form.append("accion", "cambiar_estado");
                form.append("id", id);
                form.append("estado", estadoNuevo); 

                fetch("", { method: "POST", body: form })
                    .then(res => res.json())
                    .then(data => {
                        mostrarMensaje(data);
                        if (data.success) cargarItems();
                    })
                    .catch(error => {
                        mostrarMensaje({ success: false, mensaje: 'Error de red al cambiar el estado.' });
                    });
            }
        });
    }

    // ============== LÓGICA DE ELIMINAR =============

    window.eliminarRedSocial = function(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¡No podrás revertir esto! Se eliminará también el icono del servidor.',
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
                    .catch(() => mostrarMensaje({ success: false, mensaje: 'Error de red al eliminar.' }));
            }
        });
    }

    // ============== LÓGICA DE CREACIÓN Y ACTUALIZACIÓN (Form Submit) ==============

    document.getElementById("btnAgregarRedSocial").addEventListener("click", function() {
        // Modo CREACIÓN
        document.getElementById("formRedSocial").reset();
        document.getElementById("red_id").value = ""; 
        document.getElementById('imagen_anterior').value = '';
        document.getElementById('eliminar_imagen_input').value = 'false';
        
        // Inicializar FileInput en modo creación
        inicializarFileInput([], [], false); 

        document.getElementById("formTitle").innerText = 'Crear Nueva Red Social';
        document.getElementById("btnGuardar").innerText = "Guardar Red Social";
        
        // Mostrar el modal
        if (redSocialModal) {
            redSocialModal.show();
        }
    });

    document.getElementById('formRedSocial').addEventListener('submit', function(e){
        e.preventDefault();
        
        let formData = new FormData(this);
        let redId = document.getElementById('red_id').value;
        let accion = redId ? 'actualizar' : 'crear'; 

        formData.append('accion',accion);

        const confirmTitle = accion === 'actualizar' ? "¿Confirmar Actualización?" : "¿Crear Nueva Red Social?";
        const confirmText = accion === 'actualizar' ? "Se guardarán los cambios de la red social." : "¿Está seguro de querer crear esta red social?";
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
                    if (!res.ok) {
                        throw new Error(`Error HTTP: ${res.status}`);
                    }
                    return res.json();
                })
                .then(data=>{
                    mostrarMensaje(data);

                    if(data.success){
                        // Limpiar formulario y cerrar modal
                        cancelarFormulario(); 
                    }
                })
                .catch(error => {
                    console.error(error);
                    mostrarMensaje({ success: false, mensaje: 'Error de red en la operación: ' + error.message });
                });
            }
        });
    });

    // Cargar items e inicializar el modal al iniciar
    document.addEventListener("DOMContentLoaded", function() {
        // Inicializar el objeto Modal de Bootstrap
        redSocialModal = new bootstrap.Modal(document.getElementById('redSocialModal'), {});
        
        cargarItems();
        // Inicializar FileInput en modo creación
        inicializarFileInput([], [], false); 
    });
</script>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
?>