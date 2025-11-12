<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

// ---------------------- Funciones de Utilidad ----------------------

/**
 * Escapa y sanitiza una cadena de texto para prevenir inyección SQL y XSS.
 * @param mixed $conn Conexión a la base de datos.
 * @param string $data La cadena a sanitizar.
 * @return string La cadena sanitizada.
 */
function sanitize_input($conn, $data) {
    // Usamos pg_escape_string para PostgreSQL y htmlspecialchars para XSS
    // Nota: pg_escape_string necesita la conexión $conn
    return htmlspecialchars(trim(pg_escape_string($conn, $data)));
}

// ================== ACCIONES AJAX ==================

// Crear evento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $titulo 	= sanitize_input($conn, $_POST['evt_tit']);
    $descripcion = sanitize_input($conn, $_POST['evt_des']);
    $fecha 	= sanitize_input($conn, $_POST['evt_fec']);
    $lugar 	= sanitize_input($conn, $_POST['evt_lug']);
    $precio 	= floatval($_POST['evt_pre']);
    $capacidad 	= intval($_POST['evt_capacidad']);
    // Ya que 'disponibles' es igual a 'capacidad' en la creación
    $disponibles = intval($_POST['evt_disponibles']); 
    $id_admin 	= intval($_SESSION['id']);
    $estado 	= sanitize_input($conn, $_POST['evt_est']);

    $carpetaImagenes = "imagenes/imagenes_eventos/";
    $imagenRuta = null;

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = time() . "_" . basename($_FILES['imagen']['name']);
        $rutaDestino = '../../' . $carpetaImagenes . $nombreArchivo;

        if (!is_dir('../../' . $carpetaImagenes)) {
            // Asegurar permisos para crear el directorio
            mkdir('../../' . $carpetaImagenes, 0777, true);
        }

        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            $imagenRuta = $carpetaImagenes . $nombreArchivo;
        }
    }

    // *** IMPORTANTE: Usar comillas simples para todos los valores de texto y NULL ***
    $query = "INSERT INTO tbl_evento
                (evt_tit, evt_des, evt_fec, evt_lug, evt_pre, evt_capacidad, evt_disponibles, evt_id_adm, evt_est, evt_img)
                VALUES
                ('$titulo', '$descripcion', '$fecha', '$lugar', $precio, $capacidad, $disponibles, $id_admin, '$estado', " .
                ($imagenRuta ? "'$imagenRuta'" : "NULL") . ")";
    $result = pg_query($conn, $query);

    $response = $result ? ["success" => true, "mensaje" => "Evento creado correctamente"] : ["success" => false, "mensaje" => "Error al crear: " . pg_last_error($conn)];
    
    echo json_encode($response);
    exit;
}

// Listar eventos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'listar') {
    $eventos = pg_query($conn, "SELECT * FROM tbl_evento ORDER BY evt_id DESC");
    $html = "";
    
    while ($row = pg_fetch_assoc($eventos)) {
        $id = $row['evt_id'];
        $estado = htmlspecialchars($row['evt_est']);

        // Opciones para Data Attributes (mantenidos para la edición por formulario)
        $data_attributes = "";
        $data_attributes .= " data-tit='" . htmlspecialchars($row['evt_tit'], ENT_QUOTES) . "'";
        $data_attributes .= " data-des='" . htmlspecialchars($row['evt_des'], ENT_QUOTES) . "'";
        $data_attributes .= " data-fec='" . str_replace(' ', 'T', $row['evt_fec']) . "'"; // Formato datetime-local
        $data_attributes .= " data-lug='" . htmlspecialchars($row['evt_lug'], ENT_QUOTES) . "'";
        $data_attributes .= " data-pre='" . htmlspecialchars($row['evt_pre'], ENT_QUOTES) . "'";
        $data_attributes .= " data-cap='" . htmlspecialchars($row['evt_capacidad'], ENT_QUOTES) . "'";
        $data_attributes .= " data-disp='" . htmlspecialchars($row['evt_disponibles'], ENT_QUOTES) . "'";
        $data_attributes .= " data-est='" . $estado . "'";
        $data_attributes .= " data-img='" . htmlspecialchars($row['evt_img'] ?? '', ENT_QUOTES) . "'"; // Asegurar que no sea null

        // Botón Finalizar solo si el estado es 'activo' o 'pendiente'
        $btn_finalizar = '';
        if ($estado === 'activo' || $estado === 'pendiente') {
             $btn_finalizar = "<button class='btn btn-success btn-sm' onclick='finalizarEvento({$id})'>Finalizar</button>";
        }

        // --- Generación de la celda de imagen ---
        $img_html = "<td>";
        if ($row['evt_img']) {
            $img_html .= "<img src='../../" . htmlspecialchars($row['evt_img'], ENT_QUOTES) . "' style='max-width:80px; max-height:80px; cursor:pointer;' onclick=\"verImagen('../../" . htmlspecialchars($row['evt_img'], ENT_QUOTES) . "')\">";
        } else {
            $img_html .= "<span>Sin imagen</span>";
        }
        $img_html .= "</td>";


        $html .= "<tr id='fila-{$id}' {$data_attributes}>
                    <td>{$id}</td>
                    <td>" . htmlspecialchars($row['evt_tit']) . "</td>
                    <td>" . htmlspecialchars($row['evt_des']) . "</td>
                    <td>" . htmlspecialchars($row['evt_fec']) . "</td>
                    <td>" . htmlspecialchars($row['evt_lug']) . "</td>
                    <td>" . htmlspecialchars($row['evt_pre']) . "</td>
                    <td>" . htmlspecialchars($row['evt_capacidad']) . "</td>
                    <td>" . htmlspecialchars($row['evt_disponibles']) . "</td>
                    {$img_html}
                    <td>" . ucfirst($estado) . "</td>
                    <td>
                        <button class='btn btn-warning btn-sm' onclick='confirmarEdicion({$id})'>Editar</button>
                        {$btn_finalizar}
                    </td>
                </tr>";
    }
    echo $html;
    exit;
}

// Eliminar evento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $id = intval($_POST['id']);
    $query_imagen = "SELECT evt_img FROM tbl_evento WHERE evt_id = $id";
    $res_imagen = pg_query($conn, $query_imagen);
    $imagen_data = pg_fetch_assoc($res_imagen);
    $ruta_imagen = isset($imagen_data['evt_img']) ? $imagen_data['evt_img'] : null;
    
    pg_query($conn, "BEGIN");
    $res_db = pg_query($conn, "DELETE FROM tbl_evento WHERE evt_id=$id");
    
    if ($res_db) {
        if ($ruta_imagen) {
            // Se necesita __DIR__ o __FILE__ para obtener la ruta absoluta del script actual
            $ruta_completa_imagen = realpath(__DIR__ . '/../../' . $ruta_imagen);
            if (file_exists($ruta_completa_imagen)) {
                unlink($ruta_completa_imagen);
            }
        }
        pg_query($conn, "COMMIT");
        echo json_encode(["success" => true, "mensaje" => "Evento y su imagen eliminados correctamente"]);
    } else {
        pg_query($conn, "ROLLBACK");
        echo json_encode(["success" => false, "mensaje" => "Error: " . pg_last_error($conn)]);
    }
    exit;
}

// Actualizar evento (FORMULARIO ÚNICO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $id 				= intval($_POST['evt_id']);
    $titulo 			= sanitize_input($conn, $_POST['evt_tit']);
    $descripcion 		= sanitize_input($conn, $_POST['evt_des']);
    $fecha 			= sanitize_input($conn, $_POST['evt_fec']);
    $lugar 			= sanitize_input($conn, $_POST['evt_lug']);
    $precio 			= floatval($_POST['evt_pre']);
    $capacidad 		= intval($_POST['evt_capacidad']);
    // Mantenemos 'disponibles' tal cual se recibió del formulario (que ahora será el valor actual guardado)
    $disponibles 		= intval($_POST['evt_disponibles']); 
    $estado 			= sanitize_input($conn, $_POST['evt_est']);

    // 1. Obtener la imagen anterior
    $query_old_img = "SELECT evt_img FROM tbl_evento WHERE evt_id = $id";
    $res_old_img = pg_query($conn, $query_old_img);
    $old_img_data = pg_fetch_assoc($res_old_img);
    $ruta_imagen_anterior = isset($old_img_data['evt_img']) ? $old_img_data['evt_img'] : null;

    $imagenRuta = $ruta_imagen_anterior; // Mantener la ruta anterior por defecto
    $carpetaImagenes = "imagenes/imagenes_eventos/";

    // 2. Procesar la nueva imagen si existe
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK && $_FILES['imagen']['size'] > 0) {
        $nombreArchivo = time() . "_" . basename($_FILES['imagen']['name']);
        $rutaDestino = '../../' . $carpetaImagenes . $nombreArchivo;
        
        if (!is_dir('../../' . $carpetaImagenes)) mkdir('../../' . $carpetaImagenes, 0777, true);
        
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            $imagenRuta = $carpetaImagenes . $nombreArchivo;
        } else {
            echo json_encode(["success" => false, "mensaje" => "Error al mover el nuevo archivo de imagen."]);
            exit;
        }
    }

    // 3. Query de actualización
    $query = "UPDATE tbl_evento SET
                    evt_tit='$titulo', evt_des='$descripcion', evt_fec='$fecha',
                    evt_lug='$lugar', evt_pre=$precio, evt_capacidad=$capacidad,
                    evt_disponibles=$disponibles, evt_est='$estado', evt_img=" . ($imagenRuta ? "'$imagenRuta'" : "NULL") . "
                    WHERE evt_id=$id";

    pg_query($conn, "BEGIN");
    $res = pg_query($conn, $query);
    
    if ($res) {
        // 4. Eliminar la imagen anterior si se subió una nueva y existía una anterior
        if ($imagenRuta && $ruta_imagen_anterior && $imagenRuta !== $ruta_imagen_anterior) {
            $ruta_completa_anterior = realpath(__DIR__ . '/../../' . $ruta_imagen_anterior);
            if (file_exists($ruta_completa_anterior)) {
                unlink($ruta_completa_anterior);
            }
        }
        pg_query($conn, "COMMIT");

        $response = ["success" => true, "mensaje" => "Evento actualizado"];
        // Redirección si cambia a 'finalizado'
        if ($estado === 'finalizado') {
            $response['redirect'] = "galeria_evento.php?id=" . $id;
        }
        echo json_encode($response);
    } else {
        pg_query($conn, "ROLLBACK");
        echo json_encode(["success" => false, "mensaje" => "Error al actualizar: " . pg_last_error($conn)]);
    }
    exit;
}

// NUEVA ACCIÓN: actualizar_estado (para el botón Finalizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_estado') {
    $id = intval($_POST['id']);
    $nuevo_estado = sanitize_input($conn, $_POST['estado']);

    $query = "UPDATE tbl_evento SET evt_est='$nuevo_estado' WHERE evt_id=$id";
    $res = pg_query($conn, $query);

    if ($res) {
        $response = ["success" => true, "mensaje" => "Estado del evento actualizado a " . ucfirst($nuevo_estado)];
        if ($nuevo_estado === 'finalizado') {
            $response['redirect'] = "galeria_evento.php?id=" . $id;
        }
        echo json_encode($response);
    } else {
        echo json_encode(["success" => false, "mensaje" => "Error al actualizar estado: " . pg_last_error($conn)]);
    }
    exit;
}

ob_start();
?>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<link href="//cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput/5.5.3/css/fileinput.min.css" media="all" rel="stylesheet" type="text/css" />
<script src="//cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput/5.5.3/js/fileinput.min.js"></script>
<script src="//cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput/5.5.3/js/locales/es.js"></script>

<div style="text-align: center;">
    <h2 style="margin-bottom:15px;">Gestión de Eventos</h2>
    
    <div style="margin-bottom:20px;">
        <button id="btnAgregarEvento" class="btn btn-primary">➕ Agregar Nuevo Evento</button>
    </div>

    <div class="modal fade" id="eventoModal" tabindex="-1" aria-labelledby="formTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg"> <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formTitle">Crear Evento</h5> 
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEvento" enctype="multipart/form-data">
                        <input type="hidden" id="evt_id" name="evt_id">
                        <input type="hidden" id="imagen_anterior" name="imagen_anterior" value="">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="titulo" class="form-label">Título:</label>
                                <input type="text" id="titulo" name="evt_tit" required class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label for="lugar" class="form-label">Lugar:</label>
                                <input type="text" id="lugar" name="evt_lug" required class="form-control form-control-sm">
                            </div>

                            <div class="col-12">
                                <label for="descripcion" class="form-label">Descripción:</label>
                                <textarea id="descripcion" name="evt_des" required class="form-control form-control-sm"></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="fecha" class="form-label">Fecha (Fecha y Hora):</label>
                                <input type="datetime-local" id="fecha" name="evt_fec" required class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label for="precio" class="form-label">Precio:</label>
                                <input type="number" step="0.01" id="precio" name="evt_pre" required class="form-control form-control-sm">
                            </div>

                            <div class="col-md-4">
                                <label for="capacidad" class="form-label">Capacidad máxima:</label>
                                <input type="number" id="capacidad" name="evt_capacidad" required min="1" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label for="disponibles" class="form-label">Boletos disponibles:</label>
                                <input type="number" id="disponibles" name="evt_disponibles" required min="0" class="form-control form-control-sm" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="estadoSelect" class="form-label">Estado:</label>
                                <select id="estadoSelect" name="evt_est" class="form-select form-select-sm">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="activo">Activo</option>
                                    <option value="finalizado">Finalizado</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="imagen" class="form-label">Imagen del Evento (Opcional en edición):</label>
                                <input type="file" id="imagen" name="imagen" accept="image/*" class="form-control">
                                <div id="kv-error-1" style="width:100%; margin-top:5px;"></div> 
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="display: flex; gap: 10px;">
                    <button type="submit" form="formEvento" id="btnGuardar" class="btn btn-success btn-sm">Guardar Evento</button>
                    <button type="button" id="btnCancelarForm" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="mensaje" id="mensaje" style="margin:10px 0; font-size:12px; text-align:center;"></div>

    <h3 style="margin-top:25px;">Eventos Registrados</h3>
    <div class="table-container" style="overflow-x:auto;">
        <table id="tablaRegistros" class="display" style="border-collapse:collapse; width:100%; font-size:12px; margin:0 auto;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Lugar</th>
                    <th>Precio</th>
                    <th>Capacidad</th>
                    <th>Disp.</th>
                    <th>Imagen</th>
                    <th>Estado</th>
                    <th data-dt-order="disable">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaEventos"></tbody>
        </table>
    </div>
</div>

<style>
    /* Estilos básicos para el nuevo formulario */
    .form-label { margin-bottom: 2px; font-size: 12px; }
    .form-control, .form-select { font-size: 12px !important; }
    
    /* Estilo para que el campo readonly no se vea idéntico al editable, si se desea */
    #disponibles[readonly] {
        background-color: #e9ecef; /* Un color gris claro */
        cursor: not-allowed;
    }
    
    .btn { padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; margin: 2px; }
    .btn-primary { background: #2196F3; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-success { background: #4CAF50; color: white; }
    .btn-danger { background: #f44336; color: white; }
    .btn-warning { background: #ff9800; color: white; }
    
    .alert-success { color: green; font-weight: bold; }
    .alert-danger { color: red; font-weight: bold; }

    table th, table td { border: 1px solid #ccc; padding: 6px; text-align: center; vertical-align: middle; }
    th { background: #f2f2f2; }
</style>


<script>
    let dataTable = null; 
    let fileInputInitialized = false; // Flag para inicialización
    let eventoModal = null; // Variable para la instancia del Modal de Bootstrap

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
    
    function cargarEventos() {
        if (dataTable) {
            dataTable.destroy();
            dataTable = null; 
        }
        
        let form = new FormData();
        form.append('accion', 'listar');
        
        const tablaBody = document.getElementById('tablaEventos');
        tablaBody.innerHTML = '<tr><td colspan="11"><div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p>Cargando eventos...</p></div></td></tr>';

        fetch('', { method: 'POST', body: form })
            .then(res => res.text())
            .then(html => {
                // Reemplazar el contenido con el HTML de las filas devueltas
                tablaBody.innerHTML = html;
                
                // Inicializar DataTables después de cargar el contenido
                dataTable = new DataTable('#tablaRegistros', {
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    responsive: true,
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
                    }
                });
            })
            .catch(err => {
                 mostrarAlerta({ success: false, mensaje: 'Error de red al cargar la lista de eventos.' });
                 tablaBody.innerHTML = '<tr><td colspan="11" style="color:red;">Error al cargar la lista. Verifique la conexión.</td></tr>';
            });
    }

    // Función para inicializar o re-inicializar FileInput
    function inicializarFileInput(initialPreview = [], initialPreviewConfig = [], readonly = false) {
        // Destruir si ya existe
        if (fileInputInitialized) {
            $('#imagen').fileinput('destroy');
        }

        $('#imagen').fileinput({
            showUpload: false,
            dropZoneEnabled: !readonly, // Deshabilitar dropzone en modo edición si es necesario
            language: "es",
            allowedFileExtensions: ["jpg","jpeg","png","webp"],
            maxFileSize: 2048, // KB (2MB)
            browseClass: "btn btn-outline-secondary btn-sm",
            msgPlaceholder: readonly ? "Haga clic para cambiar la imagen (opcional)..." : "Seleccione imagen...",
            previewFileType: "image",
            elErrorContainer: "#kv-error-1",
            initialPreview: initialPreview,
            initialPreviewConfig: initialPreviewConfig,
            initialPreviewAsData: true,           // ✅ importante para que cargue como imagen
            overwriteInitial: readonly ? true : false, // ✅ siempre sobreescribir o usar la inicial
            showRemove: readonly, // Mostrar botón remover si es edición (para borrar la existente)
            showCancel: false,
            required: !readonly // Obligatorio en creación, opcional en edición
        }).on('filecleared', function() {
            // Cuando se limpia el input de archivo
            if (readonly) {
                // Si estamos en modo edición y se borra, forzamos a que el campo 'imagen' no se envíe si no se seleccionó uno nuevo
                // O marcamos el campo oculto 'imagen_anterior' como vacío para forzar la eliminación en el backend
                document.getElementById('imagen_anterior').value = ''; 
            }
        });
        fileInputInitialized = true;
    }


    // ============== LÓGICA DE EDICIÓN EN FORMULARIO (VUELVE AL FORMULARIO) ==============

    /**
     * Función que carga directamente el formulario de edición sin pedir confirmación.
     * Se llama al pulsar el botón "Editar" de la tabla.
     * @param {number} id - ID del evento a editar.
     */
    window.confirmarEdicion = function(id) {
        const fila = document.getElementById(`fila-${id}`);
        if (!fila) {
            mostrarAlerta({success: false, mensaje: "Fila de evento no encontrada."});
            return;
        }

        cargarDatosEnFormulario(id, fila);
    };
    
    /**
     * Carga los datos de la fila seleccionada en el formulario de edición y realiza el scroll.
     * Se ajusta para USAR EL MODAL.
     * @param {number} id - ID del evento.
     * @param {HTMLElement} fila - La fila de la tabla (<tr>) que contiene los data-attributes.
     */
    function cargarDatosEnFormulario(id, fila) {
        // 1. Configurar el formulario para edición
        document.getElementById('evt_id').value = id;
        
        // 2. Llenar campos
        document.getElementById('titulo').value = fila.dataset.tit;
        document.getElementById('descripcion').value = fila.dataset.des;
        document.getElementById('fecha').value = fila.dataset.fec;
        document.getElementById('lugar').value = fila.dataset.lug;
        document.getElementById('precio').value = fila.dataset.pre;
        document.getElementById('capacidad').value = fila.dataset.cap;
        document.getElementById('disponibles').value = fila.dataset.disp; // Carga el valor actual de la BD
        
        document.getElementById('estadoSelect').value = fila.dataset.est; 
        document.getElementById('imagen_anterior').value = fila.dataset.img || '';
        
        // 3. Configurar FileInput para edición
        let imagenSrc = null;
        if (fila.dataset.img && fila.dataset.img.trim() !== "") {
            // La ruta en data-img es relativa al directorio raíz del proyecto (ej: 'imagenes/eventos/...')
            imagenSrc = "../../" + fila.dataset.img;
        }

        const initialPreview = imagenSrc ? [imagenSrc] : [];
        const initialPreviewConfig = imagenSrc
            ? [{
                  caption: fila.dataset.img.split('/').pop(), // solo el nombre del archivo
                  downloadUrl: imagenSrc, 
                  key: 1,
                  type: "image"
              }]
            : [];

        // Pasar true para modo edición (readonly)
        inicializarFileInput(initialPreview, initialPreviewConfig, true); 

        // 4. Actualizar textos de formulario
        document.getElementById('formTitle').innerText = 'Editar Evento ID: ' + id;
        document.getElementById('btnGuardar').innerText = 'Actualizar Evento';
        
        // 5. Mostrar el formulario (ahora el modal)
        if (eventoModal) {
            eventoModal.show();
        }
    }


    // ============== LÓGICA DE FINALIZAR EVENTO ==============

    window.finalizarEvento = function(id) {
        Swal.fire({
            title: "¿Desea finalizar este evento?",
            text: "El estado se cambiará a 'Finalizado'. Si es exitoso, será redirigido a la galería para subir fotos.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, Finalizar",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#4CAF50",
            cancelButtonColor: "#d33"
        }).then((result) => {
            if (result.isConfirmed) {
                let form = new FormData();
                form.append("accion", "actualizar_estado");
                form.append("id", id);
                form.append("estado", "finalizado"); 

                fetch('', { method: "POST", body: form })
                    .then(res => res.json())
                    .then(data => {
                        mostrarAlerta(data);
                        if (data.success) {
                            if (data.redirect) {
                                window.location.href = data.redirect; 
                            } else {
                                cargarEventos(); 
                            }
                        }
                    })
                    .catch(() => {
                        mostrarAlerta({ success: false, mensaje: 'Error de red al intentar finalizar el evento.' });
                    });
            }
        });
    }
    
    // ============== LÓGICA DE ELIMINAR ==============

    window.eliminarEvento = function(id) {
        Swal.fire({
            title: "¿Estás seguro de eliminar?",
            text: "Esta acción es irreversible y eliminará la imagen del servidor.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#f44336",
            cancelButtonColor: "#6c757d"
        }).then((result) => {
            if (result.isConfirmed) {
                let form = new FormData();
                form.append("accion", "eliminar");
                form.append("id", id);
                fetch("", { method: "POST", body: form })
                    .then(res => res.json())
                    .then(data => {
                        mostrarAlerta(data);
                        if (data.success) cargarEventos();
                    })
                    .catch(() => {
                        mostrarAlerta({ success: false, mensaje: 'Error de red al eliminar.' });
                    });
            }
        });
    }
    
    // Función para ver la imagen en grande
    window.verImagen = function(src) {
        Swal.fire({
            imageUrl: src,
            imageAlt: 'Imagen del Evento',
            showConfirmButton: false,
            showCloseButton: true,
            focusConfirm: false
        });
    }


    // ============== LÓGICA DE CREACIÓN Y ACTUALIZACIÓN (Form Submit) ==============

    document.getElementById("btnAgregarEvento").addEventListener("click", function() {
        // Modo CREACIÓN
        document.getElementById("formEvento").reset();
        document.getElementById("evt_id").value = ""; 
        document.getElementById("imagen_anterior").value = "";
        
        // Configurar FileInput para creación
        inicializarFileInput([], [], false); 

        document.getElementById("formTitle").innerText = 'Crear Nuevo Evento';
        document.getElementById("btnGuardar").innerText = 'Guardar Evento';
        
        // Ajustar el estado a 'pendiente' por defecto en creación
        document.getElementById('estadoSelect').value = 'pendiente';
        
        // Mostrar el modal
        if (eventoModal) {
            eventoModal.show();
        }
    });

    // Se mantiene la función de cancelar para la lógica interna de limpiar y destruir el fileinput
    // El botón 'Cancelar' ahora tiene el atributo data-bs-dismiss="modal" para cerrarlo
    document.getElementById("btnCancelarForm").addEventListener("click", function() {
        document.getElementById("formEvento").reset();
        document.getElementById("evt_id").value = "";
        // Destruir FileInput si se había inicializado
        if (fileInputInitialized) {
            $('#imagen').fileinput('destroy');
            fileInputInitialized = false;
        }
        // NOTA: El modal se cierra automáticamente por el data-bs-dismiss="modal"
    });

    // Evento para copiar el valor de Capacidad a Disponibles
    document.getElementById('capacidad').addEventListener('input', function() {
        // Obtenemos los valores actuales
        const capacidad = parseInt(this.value) || 0;
        const disponiblesField = document.getElementById('disponibles');
        const disponibles = parseInt(disponiblesField.value) || 0;
        const eventoId = document.getElementById("evt_id").value;
        
        // La lógica:
        // 1. Si estamos en modo CREACIÓN (no hay eventoId), siempre copiar.
        // 2. Si estamos en modo EDICIÓN (hay eventoId), solo copiar si 'disponibles' es igual a 'capacidad' (es decir, aún no se han vendido boletos).
        // 3. Si en edición la capacidad se reduce, ajustamos disponibles a la nueva capacidad (no puede haber más disponibles que capacidad).
        if (!eventoId) {
            // Modo CREACIÓN: siempre copia
            disponiblesField.value = capacidad;
        } else {
            // Modo EDICIÓN
            if (disponibles === capacidad) {
                 // Si aún no se han vendido boletos, copia el nuevo valor
                disponiblesField.value = capacidad;
            } else if (disponibles > capacidad) {
                 // Si la capacidad se redujo por debajo de los vendidos (ej: 10/10 a 5/10), ajustamos al máximo posible (5/5)
                 disponiblesField.value = capacidad;
                 mostrarAlerta({ success: false, mensaje: 'La capacidad se ha reducido, la cantidad de boletos disponibles no puede ser mayor a la capacidad máxima.' });
            }
            // Si disponibles < capacidad (ej: 5/10), no hacemos nada, mantenemos 5 disponibles.
        }
    });

    document.getElementById("formEvento").addEventListener("submit", function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let eventoId = document.getElementById("evt_id").value;
        let accion = eventoId ? "actualizar" : "crear";
        formData.append("accion", accion);
        
        const formElement = this;

        // --- Mantiene el SweetAlert al guardar/actualizar en el formulario ---
        let confirmTitle = accion === 'actualizar' ? "¿Confirmar Actualización?" : "¿Crear Nuevo Evento?";
        let confirmText = accion === 'actualizar' ? "Se guardarán los cambios en el evento ID " + eventoId + "." : "¿Está seguro de querer crear este evento?";
        let confirmButtonText = accion === 'actualizar' ? "Sí, Actualizar" : "Sí, Crear";
        let confirmColor = accion === 'actualizar' ? "#ff9800" : "#4CAF50";


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
                // Si el usuario confirma, procede con el envío del formulario
                
                fetch('', { 
                    method: 'POST', 
                    body: formData 
                })
                .then(res => {
                    if (!res.ok) {
                        // Manejar errores HTTP (404, 500, etc.)
                        throw new Error(`Error HTTP: ${res.status}`);
                    }
                    return res.json();
                })
                .then(data => {
                    mostrarAlerta(data);
                    if (data.success) {
                        formElement.reset();
                        document.getElementById("evt_id").value = "";
                        if (fileInputInitialized) {
                            $('#imagen').fileinput('destroy');
                            fileInputInitialized = false;
                        }
                        // Ocultar el modal en lugar de manipular el estilo del div
                        if (eventoModal) {
                            eventoModal.hide();
                        }
                        
                        cargarEventos(); // Recargar la tabla
                        
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    }
                })
                .catch(error => {
                    console.error(error);
                    mostrarAlerta({ success: false, mensaje: 'Error de comunicación con el servidor: ' + error.message });
                });
            }
        });
    });


    // Cargar eventos al iniciar
    document.addEventListener("DOMContentLoaded", function() {
        // Inicializar el objeto Modal de Bootstrap 
        // Se asume que estás usando Bootstrap 5 o superior.
        eventoModal = new bootstrap.Modal(document.getElementById('eventoModal'), {});
        
        cargarEventos();
        // Inicializar FileInput en estado vacío al cargar la página por primera vez (creación por defecto)
        inicializarFileInput([], [], false); 
    });
</script>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
?>