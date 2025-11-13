<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

$carpetaImagenes = "imagenes/premios/";
$imagenRuta = null;

// Función para obtener la lista de eventos para el select
function obtenerEventos($conn) {
    $eventos = pg_query($conn, "SELECT evt_id, evt_tit FROM tbl_evento ORDER BY evt_tit");
    return pg_fetch_all($eventos);
}

$eventos = obtenerEventos($conn);

// ================== FUNCIONES DE UTILIDAD DE IMAGEN ==================

function manejar_subida_imagen($conn, $file_data, $directorio_destino, $ruta_anterior_db = null) {
    // Definimos la raíz del proyecto asumiendo que el archivo de conexión está fuera de admin/
    $raiz_proyecto = realpath(__DIR__ . '/../../'); 
    
    if (isset($file_data) && $file_data['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = time() . "_" . basename($file_data['name']);
        // Ruta absoluta donde se guardará el archivo
        $ruta_destino_absoluta = $raiz_proyecto . '/' . $directorio_destino . $nombreArchivo;
        $directorio_absoluto = dirname($ruta_destino_absoluta);
        
        if (!is_dir($directorio_absoluto)) {
            mkdir($directorio_absoluto, 0777, true);
        }

        if (move_uploaded_file($file_data['tmp_name'], $ruta_destino_absoluta)) {
            // Devolver la ruta relativa para la base de datos
            return $directorio_destino . $nombreArchivo;
        } else {
             // Error al mover el archivo
             return false;
        }
    }
    return null; // No se subió archivo o hubo un error no UPLOAD_ERR_OK
}

// Función para eliminar el archivo del servidor
function eliminar_imagen_servidor($ruta_relativa) {
    if ($ruta_relativa) {
        // Asumiendo que esta función se llama desde el script actual, 
        // la ruta relativa necesita el prefijo '../../' para alcanzar la raíz del proyecto.
        $ruta_absoluta = '../../' . $ruta_relativa;
        if (file_exists($ruta_absoluta)) {
            unlink($ruta_absoluta);
        }
    }
}

// ================== ACCIONES AJAX ==================

// Crear regalo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $nombre = pg_escape_string($conn, $_POST['pre_nom']);
    $descripcion = pg_escape_string($conn, $_POST['pre_des']);
    $cantidad = intval($_POST['pre_can']);
    $id_evento = intval($_POST['pre_id_evt']);
    $id_admin = intval($_SESSION['id']);

    $imagenRuta = null;
    if (isset($_FILES['pre_img']) && $_FILES['pre_img']['error'] === UPLOAD_ERR_OK) {
        $imagenRuta = manejar_subida_imagen($conn, $_FILES['pre_img'], $carpetaImagenes);
    }
    
    // Si la imagen es obligatoria en la creación y no se subió
    if (!$imagenRuta && empty($_POST['pre_img_anterior'])) {
        echo json_encode(["success" => false, "mensaje" => "Error: La imagen es obligatoria al crear un regalo."]);
        exit;
    }
    
    // Se inserta con pre_est por defecto TRUE (activo)
    $query = "INSERT INTO tbl_premio
              (pre_nom, pre_des, pre_can, pre_img, pre_id_adm, pre_id_evt)
              VALUES
              ('$nombre', '$descripcion', $cantidad, '$imagenRuta', $id_admin, $id_evento)";
    $result = pg_query($conn, $query);

    echo json_encode($result ? ["success" => true, "mensaje" => "Regalo creado correctamente"] : ["success" => false, "mensaje" => "Error: " . pg_last_error($conn)]);
    exit;
}

// Listar regalos (Preparado para DataTables)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'listar') {
    // Se seleccionan todos para mostrar su estado, no se filtra por pre_est
    $query = "SELECT p.*, e.evt_tit 
              FROM tbl_premio p
              JOIN tbl_evento e ON p.pre_id_evt = e.evt_id
              ORDER BY p.pre_id DESC";
              
    $regalos = pg_query($conn, $query);
    $html = "";

    while ($row = pg_fetch_assoc($regalos)) {
        $id = $row['pre_id'];
        $imgRuta = $row['pre_img'];
        $imgHtml = $imgRuta ? "<img src='../../{$imgRuta}' width='50'>" : "Sin imagen";
        $estado = $row['pre_est'] === 't' ? 'Activo' : 'Desactivado'; // 't' es el valor de TRUE en PostgreSQL
        $estadoClase = $row['pre_est'] === 't' ? 'text-success' : 'text-danger';
        
        // Botón de acción: Desactivar si está activo, Activar si está desactivado
        if ($row['pre_est'] === 't') {
            $accionBtn = "<button class='btn btn-danger btn-sm' onclick='cambiarEstadoRegalo({$id}, \"desactivar\")'>Desactivar</button>";
        } else {
            $accionBtn = "<button class='btn btn-success btn-sm' onclick='cambiarEstadoRegalo({$id}, \"activar\")'>Activar</button>";
        }
        
        // Se añade la ruta de la imagen en un atributo de datos para JS
        $html .= "<tr id='fila-{$id}' data-img-ruta='{$imgRuta}'>
                    <td>{$id}</td>
                    <td>" . htmlspecialchars($row['pre_nom']) . "</td>
                    <td>" . htmlspecialchars($row['pre_des']) . "</td>
                    <td>{$row['pre_can']}</td>
                    <td>" . htmlspecialchars($row['evt_tit']) . "</td>
                    <td>{$imgHtml}</td>
                    <td><span class='{$estadoClase}'>{$estado}</span></td>
                    <td>{$row['pre_fec_cre']}</td>
                    <td>{$row['pre_fec_edi']}</td>
                    <td>
                        <button class='btn btn-warning btn-sm' onclick='cargarDatosEdicion({$id})'>Editar</button>
                        {$accionBtn}
                    </td>
                </tr>";
    }
    echo $html;
    exit;
}

// Obtener datos para edición (opcional, pero buena práctica si se requiere más data)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'obtener') {
    $id = intval($_POST['id']);
    $query = "SELECT pre_id, pre_nom, pre_des, pre_can, pre_id_evt, pre_img FROM tbl_premio WHERE pre_id = $id";
    $result = pg_query($conn, $query);
    $data = pg_fetch_assoc($result);
    // Asegurar que la ruta es accesible desde el cliente (ajustar si es necesario)
    if ($data && $data['pre_img']) {
        // Asumiendo que las imágenes están dos niveles arriba del script actual (../../)
        $data['pre_img_url'] = '../../' . $data['pre_img'];
    } else {
        $data['pre_img_url'] = null;
    }

    echo json_encode($data);
    exit;
}

// Actualizar regalo (Formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $id = intval($_POST['pre_id']);
    $nombre = pg_escape_string($conn, $_POST['pre_nom']);
    $descripcion = pg_escape_string($conn, $_POST['pre_des']);
    $cantidad = intval($_POST['pre_can']);
    $id_evento = intval($_POST['pre_id_evt']);
    
    $setImagen = "";
    
    // 1. Obtener la ruta de la imagen anterior
    $query_old_img = pg_query($conn, "SELECT pre_img FROM tbl_premio WHERE pre_id=$id");
    $old_img_data = pg_fetch_assoc($query_old_img);
    $ruta_anterior = $old_img_data['pre_img'] ?? null;

    // 2. Manejar la subida de la nueva imagen
    if (isset($_FILES['pre_img']) && $_FILES['pre_img']['error'] === UPLOAD_ERR_OK) {
        $imagenRuta = manejar_subida_imagen($conn, $_FILES['pre_img'], $carpetaImagenes);
        
        if ($imagenRuta) {
            $setImagen = ", pre_img='$imagenRuta'";
            // Eliminar la imagen anterior si existe y se subió una nueva
            if ($ruta_anterior) {
                eliminar_imagen_servidor($ruta_anterior);
            }
        } else {
            echo json_encode(["success" => false, "mensaje" => "Error al subir la nueva imagen."]);
            exit;
        }
    } 
    // 3. Si se borró la imagen en el FileInput (se usa el campo oculto 'imagen_anterior' con el marcador 'ELIMINAR_IMAGEN')
    else if (isset($_POST['pre_img_anterior']) && $_POST['pre_img_anterior'] === 'ELIMINAR_IMAGEN') {
        $setImagen = ", pre_img=NULL";
        eliminar_imagen_servidor($ruta_anterior);
    } 


    $query = "UPDATE tbl_premio SET
              pre_nom='$nombre', pre_des='$descripcion', pre_can=$cantidad, pre_id_evt=$id_evento, pre_fec_edi=CURRENT_TIMESTAMP
              $setImagen
              WHERE pre_id=$id";
    $res = pg_query($conn, $query);
    echo json_encode($res ? ["success" => true, "mensaje" => "Regalo actualizado"] : ["success" => false, "mensaje" => "Error: " . pg_last_error($conn)]);
    exit;
}

// ================== CAMBIO DE ESTADO (Desactivar/Activar) ==================

// Desactivar regalo (antes era eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'desactivar') {
    $id = intval($_POST['id']);
    
    // Simplemente actualizamos el estado a FALSE
    $res_db = pg_query($conn, "UPDATE tbl_premio SET pre_est=FALSE, pre_fec_edi=CURRENT_TIMESTAMP WHERE pre_id=$id");
    
    echo json_encode($res_db ? ["success" => true, "mensaje" => "Regalo desactivado correctamente"] : ["success" => false, "mensaje" => "Error: " . pg_last_error($conn)]);
    exit;
}

// Nueva acción: Activar regalo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'activar') {
    $id = intval($_POST['id']);
    
    // Simplemente actualizamos el estado a TRUE
    $res_db = pg_query($conn, "UPDATE tbl_premio SET pre_est=TRUE, pre_fec_edi=CURRENT_TIMESTAMP WHERE pre_id=$id");
    
    echo json_encode($res_db ? ["success" => true, "mensaje" => "Regalo activado correctamente"] : ["success" => false, "mensaje" => "Error: " . pg_last_error($conn)]);
    exit;
}

ob_start();
?>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

<div class="container" style="max-width: 1000px; margin: 20px auto; text-align: center;">
    <h2>Gestión de Regalos</h2>
    <div style="margin:10px 0; font-size:14px; text-align:center;">
        <button id="btnAgregarRegalo" class="btn btn-primary" style="margin-bottom: 15px;" data-toggle="modal" data-target="#regaloModal">➕ Agregar Nuevo Regalo</button>
    </div>

    <div class="modal fade" id="regaloModal" tabindex="-1" role="dialog" aria-labelledby="regaloModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="formTitle">Crear Nuevo Regalo</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form id="formRegalo" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:8px; margin:0 auto;">
                <input type="hidden" id="pre_id" name="pre_id">
                <input type="hidden" id="imagen_anterior" name="pre_img_anterior">
                
                <label>Nombre:</label>
                <input type="text" id="nombre" name="pre_nom" required class="form-control">
                
                <label>Descripción:</label>
                <textarea id="descripcion" name="pre_des" required class="form-control"></textarea>
                
                <label>Cantidad:</label>
                <input type="number" id="cantidad" name="pre_can" required min="1" class="form-control">
                
                <label for="pre_id_evt">Evento:</label>
                <select id="pre_id_evt_form" name="pre_id_evt" required class="form-control">
                    <option value="">Seleccione un evento</option>
                    <?php foreach ($eventos as $evento): ?>
                        <option value="<?= $evento['evt_id'] ?>"><?= htmlspecialchars($evento['evt_tit']) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <label id="labelImagen">Imagen (obligatoria al crear):</label>
                <div id="kv-error-1" class="text-danger" style="display:none"></div> 
                <input type="file" id="imagen" name="pre_img" accept="image/*">
                </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
            <button type="submit" id="btnGuardar" class="btn btn-success" form="formRegalo">Guardar Regalo</button>
          </div>
        </div>
      </div>
    </div>
    <h3 style="margin-top:25px;">Regalos Registrados</h3>
    <div class="table-container" style="overflow-x:auto;">
        <table id="tablaRegistros" class="display" style="border-collapse:collapse; width:100%; font-size:12px; margin:0 auto;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Evento</th> 
                    <th data-dt-order="disable">Imagen</th>
                    <th>Estado</th> <th>Creación</th>
                    <th>Edición</th>
                    <th data-dt-order="disable">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaRegalos"></tbody>
        </table>
    </div>
</div>

<style>
    /* Estilos Básicos de Botones */
    .btn { padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; margin: 2px; transition: all 0.3s ease; }
    .btn-primary { background: #2196F3; color: white; }
    .btn-success { background: #4CAF50; color: white; }
    .btn-warning { background: #ff9800; color: white; }
    .btn-danger { background: #f44336; color: white; }
    .text-success { color: #4CAF50; font-weight: bold; }
    .text-danger { color: #f44336; font-weight: bold; }
    /* Estilos de Tabla */
    table th, table td { border: 1px solid #ccc; padding: 6px; text-align: center; vertical-align: middle; }
    table th { background: #f2f2f2; }
    /* Reemplazado por form-control de Bootstrap */
    /* #formRegalo input, #formRegalo select, #formRegalo button, #formRegalo textarea { width: 100%; box-sizing: border-box; } */ 

    /* Fix para que DataTables y FileInput se vean bien con los estilos inline */
    .file-input-new .file-preview { margin-bottom: 0px !important; }

    /* ESTILOS AGREGADOS PARA JQUERY VALIDATION */
    .error {
        color: #f44336; /* Rojo para el mensaje de error */
        font-size: 11px;
        display: block; /* Asegura que cada error esté en una nueva línea/columna */
        margin-top: 2px;
        margin-bottom: 5px; 
        text-align: left;
    }
    .form-control.error {
        border: 1px solid #f44336 !important; /* Resalta el borde del input con error */
    }
    /* La validación para el select de FileInput se maneja con el elErrorContainer */
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_es.min.js"></script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<link href="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/css/fileinput.min.css" media="all" rel="stylesheet" type="text/css" />
<script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/plugins/buffer.min.js" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/plugins/piexif.min.js" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/plugins/sortable.min.js" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/fileinput.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/locales/es.js"></script>

<script>
    let dataTable = null; 
    let fileInputInitialized = false;

    // Configuración de validación para el formulario con jQuery Validate
    $(document).ready(function() {
        $("#formRegalo").validate({
            rules: {
                "pre_nom": {
                    required: true,
                    minlength: 3, 
                    maxlength: 100
                },
                "pre_des": {
                    required: true,
                    minlength: 10,
                    maxlength: 255
                },
                "pre_can": {
                    required: true,
                    digits: true,
                    min: 1
                },
                "pre_id_evt": {
                    required: true
                },
                // La validación de 'pre_img' se deja a FileInput, pero se puede añadir un 'required' condicional si se necesita redundancia.
                "pre_img": {
                     required: function(element) {
                        // Es requerido SOLO si estamos en modo "crear" (pre_id está vacío)
                        return $("#pre_id").val() === "";
                    },
                    // Opcional: Validación simple del tipo de archivo (redundancia)
                    accept: "image/jpeg,image/png,image/webp" 
                }
            },
            messages: {
                "pre_nom": {
                    required: "El nombre del regalo es obligatorio.",
                    minlength: "Mínimo 3 caracteres para el nombre.", 
                    maxlength: "Máximo 100 caracteres para el nombre."
                },
                "pre_des": {
                    required: "La descripción es obligatoria.",
                    minlength: "La descripción debe tener al menos 10 caracteres.",
                    maxlength: "La descripción no puede exceder 255 caracteres."
                },
                "pre_can": {
                    required: "La cantidad es obligatoria.",
                    digits: "Solo se permiten números enteros.",
                    min: "La cantidad debe ser al menos 1."
                },
                "pre_id_evt": {
                    required: "Debe seleccionar un evento."
                },
                "pre_img": {
                    required: "Debe seleccionar una imagen para crear el regalo.",
                    accept: "Solo se permiten imágenes JPEG, PNG o WEBP."
                }
            },
            // Deshabilita el submit automático de validate para manejarlo con fetch
            submitHandler: function(form) {
                // El manejador original del submit se encuentra al final
                manejarEnvioFormulario(form);
                return false; 
            },
            // Configuración para que el error se muestre después del input/select/textarea
            errorPlacement: function(error, element) {
                // FileInput ya maneja sus errores con #kv-error-1. No insertamos aquí.
                if (element.attr("name") === "pre_img") {
                    error.appendTo('#kv-error-1');
                } else {
                    error.insertAfter(element);
                }
            }
        });
    });

    // Función para inicializar o re-inicializar FileInput
    function inicializarFileInput(initialPreview = [], initialPreviewConfig = [], modoEdicion = false) {
        // Destruir si ya existe
        if (fileInputInitialized) {
            $('#imagen').fileinput('destroy');
        }
        
        // Determinar si es requerido basado en si estamos en modo edición o no
        // En modo edición (modoEdicion=true), el campo no es estrictamente requerido por el HTML/JS, 
        // ya que puede mantener la imagen anterior. En modo creación es requerido.
        const isRequired = !modoEdicion; 

        $('#imagen').fileinput({
            showUpload: false,
            dropZoneEnabled: !modoEdicion, 
            language: "es",
            allowedFileExtensions: ["jpg","jpeg","png","webp"],
            maxFileSize: 2048, 
            browseClass: "btn btn-outline-secondary btn-sm",
            msgPlaceholder: modoEdicion ? "Haga clic para cambiar la imagen (opcional)..." : "Seleccione imagen...",
            previewFileType: "image",
            elErrorContainer: "#kv-error-1",
            initialPreview: initialPreview,
            initialPreviewConfig: initialPreviewConfig,
            initialPreviewAsData: true,           
            overwriteInitial: true, 
            showRemove: true, 
            showCancel: false,
            // Establecer el REQUIRED a nivel de FileInput (para que muestre el error de FileInput)
            required: isRequired 
        }).on('filecleared', function() {
            // Cuando se limpia el input de archivo
            if (modoEdicion) {
                // Si estamos en modo edición y se borra la vista previa, marcamos el campo oculto 
                // para indicar al backend que debe eliminar la imagen existente.
                document.getElementById('imagen_anterior').value = 'ELIMINAR_IMAGEN'; 
            }
        }).on('fileloaded', function() {
            // Cuando se carga una nueva imagen
            if (modoEdicion) {
                // Si se carga una nueva imagen en edición, limpiamos el marcador de eliminación
                document.getElementById('imagen_anterior').value = '';
            }
        });
        fileInputInitialized = true;
    }

    /**
     * Muestra una alerta usando SweetAlert2.
     */
    function mostrarAlerta(data) {
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

    function cargarRegalos() {
        // 1. Destruir la instancia de DataTables si existe
        if (dataTable) {
            dataTable.destroy();
            dataTable = null; 
        }
        
        let form = new FormData();
        form.append('accion','listar');
        const tablaBody = document.getElementById('tablaRegalos');
        tablaBody.innerHTML = '<tr><td colspan="10">Cargando...</td></tr>'; 
        
        fetch('',{method:'POST',body:form})
        .then(res=>res.text())
        .then(html=>{
            tablaBody.innerHTML=html;
            
            // 2. Inicializar DataTables después de cargar el contenido
            dataTable = new DataTable('#tablaRegistros', {
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                responsive: true,
                columnDefs: [
                    // Deshabilitar ordenación y búsqueda en columna de imagen y acciones
                    { targets: [5, 9], searchable: false, orderable: false } 
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
                }
            });
        })
        .catch(err => {
             mostrarAlerta({ success: false, mensaje: 'Error al cargar la lista de regalos.' });
             tablaBody.innerHTML = '<tr><td colspan="10">Error al cargar la lista.</td></tr>';
        });
    }

    // ============== LÓGICA DE EDICIÓN EN FORMULARIO ==============

    window.cargarDatosEdicion = function(id) {
        
        // 1. Obtener la ruta de la imagen existente (se hace con una petición AJAX)
        let form = new FormData();
        form.append('accion','obtener');
        form.append('id',id);
        
        fetch('', {method: 'POST', body: form})
        .then(res => res.json())
        .then(data => {
            if (data) {
                let modoEdicion = true;
                let preview = [];
                let config = [];

                if (data.pre_img_url) {
                    preview = [data.pre_img_url];
                    config = [{
                        caption: data.pre_img.split('/').pop(), 
                        key: id, 
                        url: ''
                    }];
                }
                
                // Inicializar FileInput
                inicializarFileInput(preview, config, modoEdicion);
                
                // 3. Configurar el resto del formulario para edición
                document.getElementById('pre_id').value = id;
                document.getElementById('nombre').value = data.pre_nom;
                document.getElementById('descripcion').value = data.pre_des;
                document.getElementById('cantidad').value = data.pre_can;
                document.getElementById('pre_id_evt_form').value = data.pre_id_evt;
                document.getElementById('imagen_anterior').value = ''; // Limpiar el campo oculto
                
                // 4. Actualizar textos de formulario para EDICIÓN
                document.getElementById('formTitle').innerText = 'Editar Regalo ID: ' + id;
                document.getElementById('btnGuardar').innerText = 'Actualizar Regalo';
                document.getElementById('labelImagen').innerText = 'Nueva Imagen (Opcional):';
                
                // 5. Mostrar el modal
                $('#regaloModal').modal('show');
                
                // Limpiar la validación de jQuery Validate al abrir en modo edición
                const validator = $("#formRegalo").validate();
                validator.resetForm();

            } else {
                mostrarAlerta({ success: false, mensaje: 'Error al obtener datos para edición.' });
            }
        })
        .catch(error => {
            mostrarAlerta({ success: false, mensaje: 'Error al obtener datos para edición.' });
        });
    };

    // ============== LÓGICA DE CAMBIO DE ESTADO (Activar/Desactivar) ==============
    window.cambiarEstadoRegalo = function(id, nuevaAccion){
        const titulo = nuevaAccion === 'activar' ? '¿Quieres activar este regalo?' : '¿Quieres desactivar este regalo?';
        const texto = nuevaAccion === 'activar' ? "El regalo volverá a estar disponible." : "El regalo dejará de estar disponible, pero su registro se mantendrá.";
        const confirmText = nuevaAccion === 'activar' ? 'Sí, Activar' : 'Sí, Desactivar';
        
        Swal.fire({
            title: titulo,
            text: texto,
            icon: nuevaAccion === 'activar' ? 'info' : 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                let form = new FormData();
                form.append('accion', nuevaAccion); // 'activar' o 'desactivar'
                form.append('id', id);
                
                fetch('',{method:'POST',body:form})
                .then(res=>res.json())
                .then(data=>{
                    mostrarAlerta(data);
                    if(data.success){
                        cargarRegalos();
                    }
                })
                .catch(error => {
                    mostrarAlerta({ success: false, mensaje: 'Error de red al cambiar el estado.' });
                });
            }
        });
    }

    // ============== LÓGICA DE CREACIÓN Y ACTUALIZACIÓN (Form Submit) ==============

    document.getElementById('btnAgregarRegalo').addEventListener('click', function(){
        document.getElementById('formRegalo').reset();
        document.getElementById('pre_id').value = ''; // Indica modo CREACIÓN
        
        // Inicializar FileInput en modo CREACIÓN (modoEdicion=false)
        inicializarFileInput([], [], false);

        // Configurar para CREACIÓN
        document.getElementById('formTitle').innerText = 'Crear Nuevo Regalo';
        document.getElementById('btnGuardar').innerText = 'Guardar Regalo';
        document.getElementById('labelImagen').innerText = 'Imagen (Obligatoria al crear):';
        
        // Limpiar la validación de jQuery Validate al abrir en modo creación
        const validator = $("#formRegalo").validate();
        validator.resetForm();

        // El modal se abre con data-toggle="modal"
    });
    
    // Manejador del botón de cancelar del modal y del modal cerrado
    $('#regaloModal').on('hidden.bs.modal', function (e) {
        // Limpiar el formulario al cerrar el modal (si no se hizo antes)
        document.getElementById('formRegalo').reset();
        document.getElementById('pre_id').value = '';
        // Limpiar la validación de jQuery Validate al cerrar
        const validator = $("#formRegalo").validate();
        validator.resetForm();
    });

    // Función de envío de formulario, llamada por jQuery Validate (submitHandler)
    window.manejarEnvioFormulario = function(form){
        let formData = new FormData(form);
        let regaloId = document.getElementById('pre_id').value;
        let accion = regaloId ? 'actualizar' : 'crear'; 

        formData.append('accion',accion);

        fetch('',{method:'POST',body:formData})
        .then(res=>res.json())
        .then(data=>{
            mostrarAlerta(data);

            if(data.success){
                // Cerrar el modal al tener éxito
                $('#regaloModal').modal('hide');
                // La lógica de resetear y limpiar validación se realiza en el evento 'hidden.bs.modal'
                cargarRegalos(); 
            }
        })
        .catch(error => {
            mostrarAlerta({ success: false, mensaje: 'Error de red en la operación.' });
        });
    }


    // Cargar regalos al iniciar
    document.addEventListener("DOMContentLoaded", cargarRegalos);
</script>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
?>