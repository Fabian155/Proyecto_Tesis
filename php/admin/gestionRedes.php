<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

// ================== ACCIONES AJAX ==================

// Crear Red Social
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $nombre = pg_escape_string($conn, $_POST['red_nom']);
    $enlace_url = pg_escape_string($conn, $_POST['red_url']);
    $icono_clase = pg_escape_string($conn, $_POST['red_ico_clase']);

    if (empty($icono_clase)) {
        echo json_encode(["success" => false, "mensaje" => "La clase del icono de la red social es obligatoria."]);
        exit;
    }

    $query = "INSERT INTO tbl_redes_sociales (red_nom, red_url, red_ico_clase, red_est)
              VALUES ('$nombre', '$enlace_url', '$icono_clase', 'activo')";

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
    $enlace_url = pg_escape_string($conn, $_POST['red_url']);
    $icono_clase = pg_escape_string($conn, $_POST['red_ico_clase']);

    if (empty($icono_clase)) {
        echo json_encode(["success" => false, "mensaje" => "La red social debe tener una clase de icono."]);
        exit;
    }

    $query = "UPDATE tbl_redes_sociales SET
                red_nom='$nombre', red_ico_clase='$icono_clase', red_url='$enlace_url'
                WHERE red_id=$id";

    $res = pg_query($conn, $query);

    echo json_encode($res ? ["success" => true, "mensaje" => "Red Social actualizada"] : ["success" => false, "mensaje" => "Error al actualizar: " . pg_last_error($conn)]);
    exit;
}

// Cambiar Estado (Activar/Desactivar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estado') {
    $id = intval($_POST['id']);
    $estado = $_POST['estado'] === 'activar' ? 'activo' : 'desactivado';
    $estado_mensaje = $_POST['estado'] === 'activado' ? 'activado' : 'desactivado';

    $query = "UPDATE tbl_redes_sociales SET red_est='$estado' WHERE red_id=$id";
    $result = pg_query($conn, $query);

    $response = $result
        ? ["success" => true, "mensaje" => "Red Social $id $estado_mensaje correctamente"]
        : ["success" => false, "mensaje" => "Error al cambiar estado: " . pg_last_error($conn)];
    
    echo json_encode($response);
    exit;
}

// Listar Redes Sociales ACTIVAS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'listar') {
    $query = "SELECT * FROM tbl_redes_sociales WHERE red_est = 'activo' ORDER BY red_id DESC";
              
    $redes = pg_query($conn, $query);
    $html = "";
    
    while ($row = pg_fetch_assoc($redes)) {
        $id = $row['red_id'];
        $estado_texto = '<span class="badge-success">Activa</span>';
        $btn_accion_texto = 'Desactivar';
        $btn_accion_clase = 'btn-danger-custom'; 

        $icono_clase = htmlspecialchars($row['red_ico_clase'] ?? '', ENT_QUOTES);
        $img_html = "<td>";
        $img_html .= $icono_clase ? "<i class='{$icono_clase}' style='font-size:24px;'></i>" : "<span>Sin icono</span>";
        $img_html .= "</td>";

        $data_attributes = " data-nom='" . htmlspecialchars($row['red_nom'], ENT_QUOTES) . "'";
        $data_attributes .= " data-url='" . htmlspecialchars($row['red_url'], ENT_QUOTES) . "'"; 
        $data_attributes .= " data-ico='" . $icono_clase . "'"; 

        $html .= "<tr id='fila-{$id}' {$data_attributes}>
                      <td>{$id}</td>
                      <td>" . htmlspecialchars($row['red_nom']) . "</td>
                      <td>" . htmlspecialchars($row['red_url']) . "</td>
                      {$img_html}
                      <td>{$estado_texto}</td>
                      <td>
                          <button class='btn btn-warning-custom btn-sm' onclick='cargarDatosEdicion({$id})'>Editar</button>
                          <button class='btn {$btn_accion_clase} btn-sm' onclick='cambiarEstado({$id}, \"desactivar\", \"principal\")'>{$btn_accion_texto}</button>
                      </td>
                  </tr>";
    }
    echo $html;
    exit;
}

// Listar Redes Sociales DESACTIVADAS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'listar_desactivadas') {
    $query = "SELECT * FROM tbl_redes_sociales WHERE red_est = 'desactivado' ORDER BY red_id DESC";
              
    $redes = pg_query($conn, $query);
    $html = "";
    
    while ($row = pg_fetch_assoc($redes)) {
        $id = $row['red_id'];
        $estado_texto = '<span class="badge-danger">Inactiva</span>';
        $btn_accion_texto = 'Activar';
        $btn_accion_clase = 'btn-success-custom'; 

        $icono_clase = htmlspecialchars($row['red_ico_clase'] ?? '', ENT_QUOTES);
        $img_html = "<td>";
        $img_html .= $icono_clase ? "<i class='{$icono_clase}' style='font-size:24px;'></i>" : "<span>Sin icono</span>";
        $img_html .= "</td>";

        $html .= "<tr id='fila-desactivada-{$id}'>
                      <td>{$id}</td>
                      <td>" . htmlspecialchars($row['red_nom']) . "</td>
                      <td>" . htmlspecialchars($row['red_url']) . "</td>
                      {$img_html}
                      <td>{$estado_texto}</td>
                      <td>
                          <button class='btn {$btn_accion_clase} btn-sm' onclick='cambiarEstado({$id}, \"activar\", \"modal\")'>{$btn_accion_texto}</button>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_es.min.js"></script>


<div class="main-content-area-fixed"> 
    
    <div class="header-area">
        <h3 style="margin-top:5px; margin-bottom: 5px;">Redes Sociales ACTIVAS</h3>
    </div>
    
    <div class="content-scroll-area">
        <div class="table-container">
            <table id="tablaRegistros" class="display responsive-table" style="font-size:12px;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Enlace (red_url)</th>
                        <th>Icono</th>
                        <th>Estado</th>
                        <th data-dt-order="disable">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaRedes"></tbody>
            </table>
        </div>
    </div>
    
    <div class="fixed-bottom-bar d-flex justify-content-between align-items-center">
        <button id="btnAgregarRedSocial" class="btn btn-primary-custom btn-lg">➕ Agregar Nueva Red Social</button>
        <button id="btnVerDesactivadas" class="btn btn-info-custom btn-lg">🚫 Redes Sociales Desactivadas</button>
    </div>

    <div class="modal fade modal-admin-adjusted" id="redSocialModal" tabindex="-1" aria-labelledby="formTitle" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header header-custom">
                    <h5 class="modal-title" id="formTitle">Crear Nueva Red Social</h5> 
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formRedSocial" enctype="multipart/form-data">
                        <input type="hidden" id="red_id" name="red_id">

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="nombre" class="form-label">Nombre de la Red Social:</label>
                                <input type="text" id="nombre" name="red_nom" required class="form-control form-control-sm">
                            </div>
                            
                            <div class="col-12">
                                <label for="enlace" class="form-label">Enlace/URL de la Red (red_url):</label>
                                <input type="url" id="enlace" name="red_url" required class="form-control form-control-sm">
                            </div>

                            <div class="col-12">
                                <label for="icono_clase" class="form-label">Clase de Icono (ej: fab fa-facebook):</label>
                                <input type="text" id="icono_clase" name="red_ico_clase" required class="form-control form-control-sm" list="iconoClaseSugerencias">
                                <small class="text-muted">Introduce la clase de ícono (ej. Font Awesome). Es obligatorio.</small>
                            </div>
                            
                            <datalist id="iconoClaseSugerencias">
                                <option value="fab fa-facebook-f">Facebook</option>
                                <option value="fab fa-instagram">Instagram</option>
                                <option value="fab fa-twitter">Twitter (X)</option>
                                <option value="fab fa-linkedin-in">LinkedIn</option>
                                <option value="fab fa-youtube">Youtube</option>
                                <option value="fab fa-tiktok">TikTok</option>
                                <option value="fab fa-pinterest-p">Pinterest</option>
                                <option value="fab fa-whatsapp">WhatsApp</option>
                                <option value="fab fa-telegram-plane">Telegram</option>
                                <option value="fas fa-envelope">Email (sobre)</option>
                                <option value="fab fa-github">GitHub</option>
                                <option value="fab fa-snapchat-ghost">Snapchat</option>
                                <option value="fab fa-tumblr">Tumblr</option>
                            </datalist>
                            </div>
                    </form>
                </div>
                <div class="modal-footer" style="display: flex; gap: 10px;">
                    <button type="submit" form="formRedSocial" id="btnGuardar" class="btn btn-success-custom btn-sm">Guardar Red</button>
                    <button type="button" id="btnCancelarForm" class="btn btn-danger-custom btn-sm" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-admin-adjusted" id="redesDesactivadasModal" tabindex="-1" aria-labelledby="desactivadasTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl"> 
            <div class="modal-content">
                <div class="modal-header header-custom">
                    <h5 class="modal-title" id="desactivadasTitle">Redes Sociales DESACTIVADAS</h5> 
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="cargarItems();"></button> 
                </div>
                <div class="modal-body">
                    <div class="table-container" style="overflow-x:auto; overflow-y:auto; width: fit-content; max-width: 100%;">
                        <table id="tablaDesactivadas" class="display responsive-table" style="font-size:12px; margin:0 auto;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Enlace (red_url)</th>
                                    <th>Icono</th>
                                    <th>Estado</th>
                                    <th data-dt-order="disable">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaRedesDesactivadas"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* 1. LAYOUT PRINCIPAL Y SCROLL (Para usar el 100% del espacio disponible) */
    /* Asegura que la plantilla padre use 100% de alto y no tenga scroll global */
    html, body, .plantilla-padre, #content { 
        height: 100%;
        margin: 0;
        padding: 0;
        overflow: hidden; 
    }
    
    /* Contenedor principal del contenido de esta página */
    .main-content-area-fixed {
        height: 100%;
        display: flex;
        flex-direction: column;
        padding: 10px; 
        box-sizing: border-box;
    }

    .header-area {
        flex-shrink: 0;
        padding-bottom: 5px;
    }
    
    .content-scroll-area {
        flex-grow: 1; 
        overflow-y: auto; 
        overflow-x: hidden;
        margin-bottom: 10px; 
    }

    /* 2. BARRA DE BOTONES FIJA/FLEXIBLE */
    .fixed-bottom-bar {
        flex-shrink: 0; 
        padding: 15px 10px;
        background-color: #f8f9fa; 
        border-top: 2px solid #2e3643;
        width: 100%;
        box-sizing: border-box;
        margin-left: -10px; 
        margin-right: -10px; 
        padding-left: 20px; 
        padding-right: 20px; 
    }
    .btn-lg { padding: 10px 20px; font-size: 16px; }
    
    /* 3. COLORES Y ESTILOS */
    .btn-primary-custom, .btn-info-custom { background-color: #2e3643; color: white; border-color: #2e3643; }
    .btn-success-custom { background-color: #0475c7; color: white; border-color: #0475c7; }
    .btn-danger-custom { background-color: #db062b; color: white; border-color: #db062b; }
    .btn-warning-custom { background-color: #ffc107; color: #212529; border-color: #ffc107; }

    table.dataTable thead th { 
        background-color: #2e3643 !important; 
        color: white; 
        border-color: #1a1f26 !important;
    }
    table th, table td { border: 1px solid #ccc; padding: 6px; text-align: center; vertical-align: middle; }
    
    .table-container { 
        width: fit-content; 
        max-width: 100%;
        margin: 0 auto;
    }
    table.dataTable.responsive-table {
        width: auto !important; 
        min-width: 100%;
        box-sizing: border-box;
    }
    
    /* 4. AJUSTE CRÍTICO DE MODALES */
    /* Este CSS ajusta el modal para que respete el margen de la barra lateral izquierda */
    .modal-admin-adjusted {
        /* Asegura que el fondo del modal comience después de la barra lateral (asumiendo 250px de ancho para la barra) */
        left: 250px !important; 
        width: calc(100% - 250px) !important;
    }

    .modal-admin-adjusted .modal-dialog {
        /* Fuerza la alineación superior y ajusta el ancho dentro del nuevo espacio */
        align-items: flex-start;
        min-height: calc(100% - 20px); 
        /* Centraliza el modal dentro del área principal de contenido */
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Ajuste de margen superior e inferior para evitar que se pegue al borde */
    .modal-admin-adjusted .modal-dialog .modal-content {
        margin-top: 20px; 
        margin-bottom: 20px;
    }

    /* Estilos para JQUERY VALIDATION */
    label.error { color: #db062b; font-size: 10px; font-weight: bold; display: block; margin-top: 2px; }
    input.error, textarea.error, select.error { border: 1px solid #db062b !important; }
</style>


<script>
    let dataTable = null; 
    let dataTableDesactivadas = null; 
    let redSocialModal = null; 
    let redesDesactivadasModal = null; 

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
     * Carga la lista de redes sociales ACTIVAS y refresca la DataTables principal.
     */
    function cargarItems() {
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
                
                // Inicializar DataTables
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
                mostrarMensaje({ success: false, mensaje: 'Error al cargar la lista de redes sociales activas.' });
                tablaBody.innerHTML = '<tr><td colspan="6">Error al cargar la lista.</td></tr>';
            });
    }
    
    /**
     * Carga la lista de redes sociales DESACTIVADAS y refresca la DataTables del modal.
     */
    function cargarItemsDesactivados() {
        if (dataTableDesactivadas) {
            dataTableDesactivadas.destroy();
            dataTableDesactivadas = null; 
        }
        
        let form = new FormData();
        form.append("accion", "listar_desactivadas");
        const tablaBody = document.getElementById("tablaRedesDesactivadas");
        tablaBody.innerHTML = '<tr><td colspan="6">Cargando...</td></tr>'; 
        
        fetch("", { method: "POST", body: form })
            .then(res => res.text())
            .then(html => {
                tablaBody.innerHTML = html;
                
                // Inicializar DataTables
                dataTableDesactivadas = new DataTable('#tablaDesactivadas', {
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
                mostrarMensaje({ success: false, mensaje: 'Error al cargar la lista de redes sociales desactivadas.' });
                tablaBody.innerHTML = '<tr><td colspan="6">Error al cargar la lista.</td></tr>';
            });
    }

    window.cargarDatosEdicion = function(id) {
        const fila = document.getElementById(`fila-${id}`);
        if (!fila) {
            mostrarMensaje({success: false, mensaje: "Fila de red social no encontrada."});
            return;
        }

        $("#formRedSocial").validate().resetForm();
        $("#formRedSocial").find('.error').removeClass('error');

        document.getElementById('red_id').value = id;
        document.getElementById('nombre').value = fila.dataset.nom;
        document.getElementById('enlace').value = fila.dataset.url; 
        document.getElementById('icono_clase').value = fila.dataset.ico;
        
        document.getElementById('formTitle').innerText = 'Editar Red Social ID: ' + id;
        document.getElementById('btnGuardar').innerText = 'Actualizar Red Social';
        
        if (redSocialModal) {
            redSocialModal.show();
        }
    };
    
    function cancelarFormulario() {
        $("#formRedSocial").validate().resetForm();
        $("#formRedSocial").find('.error').removeClass('error');

        document.getElementById("formRedSocial").reset();
        document.getElementById("red_id").value = "";
        
        if (redSocialModal) {
            redSocialModal.hide();
        }
        cargarItems();
    }

    window.cambiarEstado = function(id, estadoNuevo, origen) {
        const icono = estadoNuevo === 'activar' ? 'question' : 'warning';
        const color = estadoNuevo === 'activar' ? '#0475c7' : '#db062b';
        const estadoTexto = estadoNuevo === 'activar' ? 'Activar' : 'Desactivar';

        Swal.fire({
            title: `¿Estás seguro de ${estadoTexto} la red social?`,
            text: `La red social será marcada como ${estadoNuevo}.`,
            icon: icono,
            showCancelButton: true,
            confirmButtonText: `Sí, ${estadoTexto}`,
            cancelButtonText: "Cancelar",
            confirmButtonColor: color,
            cancelButtonColor: "#2e3643"
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
                        if (data.success) {
                            if (origen === 'principal') {
                                cargarItems();
                            } else if (origen === 'modal') {
                                cargarItemsDesactivados(); 
                                cargarItems(); 
                            }
                        }
                    })
                    .catch(error => {
                        mostrarMensaje({ success: false, mensaje: 'Error de red al cambiar el estado.' });
                    });
            }
        });
    }

    // =========================================================
    // JQUERY VALIDATION y AJAX Submit
    // =========================================================

    // Asignar eventos a los botones de la barra fija
    document.getElementById("btnAgregarRedSocial").addEventListener("click", function() {
        document.getElementById("formRedSocial").reset();
        document.getElementById("red_id").value = ""; 
        
        $("#formRedSocial").validate().resetForm();
        $("#formRedSocial").find('.error').removeClass('error');

        document.getElementById("formTitle").innerText = 'Crear Nueva Red Social';
        document.getElementById("btnGuardar").innerText = "Guardar Red Social";
        
        if (redSocialModal) {
            redSocialModal.show();
        }
    });

    document.getElementById("btnVerDesactivadas").addEventListener("click", function() {
        cargarItemsDesactivados();
        if (redesDesactivadasModal) {
            redesDesactivadasModal.show();
        }
    });
    
    document.getElementById("btnCancelarForm").addEventListener("click", function() {
        cancelarFormulario();
    });

    $.validator.setDefaults({
        ignore: []
    });

    $("#formRedSocial").validate({
        rules: {
            "red_nom": { required: true, minlength: 3, maxlength: 50 },
            "red_url": { required: true, url: true },
            "red_ico_clase": { required: true }
        },
        messages: {
            "red_nom": {
                required: "Ingrese un nombre",
                minlength: "El nombre debe tener al menos 3 caracteres", 
                maxlength: "El nombre no debe exceder los 50 caracteres"
            },
            "red_url": {
                required: "Ingrese el Enlace/URL",
                url: "Ingrese un formato de URL válido (ej. https://example.com)"
            },
            "red_ico_clase": {
                required: "Ingrese la clase de icono (obligatorio)"
            }
        },
        submitHandler: function(form) {
            let formData = new FormData(form);
            let redId = document.getElementById('red_id').value;
            let accion = redId ? 'actualizar' : 'crear'; 

            formData.append('accion',accion);

            const confirmTitle = accion === 'actualizar' ? "¿Confirmar Actualización?" : "¿Crear Nueva Red Social?";
            const confirmText = accion === 'actualizar' ? "Se guardarán los cambios de la red social." : "¿Está seguro de querer crear esta red social?";
            const confirmButtonText = accion === 'actualizar' ? "Sí, Actualizar" : "Sí, Crear";
            const confirmColor = accion === 'actualizar' ? "#2e3643" : "#0475c7"; 

            Swal.fire({
                title: confirmTitle,
                text: confirmText,
                icon: "question",
                showCancelButton: true,
                confirmButtonText: confirmButtonText,
                cancelButtonText: "Cancelar",
                confirmButtonColor: confirmColor,
                cancelButtonColor: "#db062b"
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
                            cancelarFormulario(); 
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        mostrarMensaje({ success: false, mensaje: 'Error de red en la operación: ' + error.message });
                    });
                }
            });
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
        // Inicializar los modales
        redSocialModal = new bootstrap.Modal(document.getElementById('redSocialModal'), {});
        redesDesactivadasModal = new bootstrap.Modal(document.getElementById('redesDesactivadasModal'), {
            backdrop: 'static', 
            keyboard: true 
        });
        
        cargarItems();
    });
</script>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
?>