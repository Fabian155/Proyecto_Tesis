<?php
session_start();
// Verifica si el usuario está logueado y es administrador
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

// Incluye la conexión a la base de datos
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
    return htmlspecialchars(trim(pg_escape_string($conn, $data)));
}

// ================== ACCIONES AJAX ==================

/**
 * Endpoint para crear un nuevo cajero.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $nombre = sanitize_input($conn, $_POST['caj_nom']);
    $usuario = sanitize_input($conn, $_POST['caj_usr']);
    $contrasena = $_POST['caj_con']; // Se deja sin sanitizar hasta el hash
    $correo = sanitize_input($conn, $_POST['caj_ema']);
    $id_admin = intval($_SESSION['id']);

    if (empty($contrasena)) {
         echo json_encode(["success"=>false,"mensaje"=>"La contraseña es obligatoria para un nuevo cajero."]);
         exit;
    }

    // Hasheo de la contraseña
    // Nota: Es más seguro usar pg_escape_bytea(hash('sha256', $contrasena, true)) para almacenar el binario.
    // Mantengo la lógica original (hex2bin) por coherencia con tu código, pero es recomendable revisar el método de hash.
    $contrasena_hash = bin2hex(hash('sha256', $contrasena, true));

    // Se asume estado activo (TRUE) por defecto
    $query = "INSERT INTO tbl_cajero 
              (caj_nom, caj_usr, caj_con, caj_ema, caj_id_adm, caj_est) 
              VALUES 
              ('$nombre', '$usuario', '$contrasena_hash', '$correo', $id_admin, TRUE)";
    
    $result = pg_query($conn, $query);

    $response = $result ? ["success" => true, "mensaje" => "Cajero '$nombre' creado correctamente"] : ["success" => false, "mensaje" => "Error al crear: " . pg_last_error($conn)];
    
    echo json_encode($response);
    exit;
}

/**
 * Endpoint para actualizar un cajero existente.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $id = intval($_POST['caj_id']);
    $nombre = sanitize_input($conn, $_POST['caj_nom']);
    $usuario = sanitize_input($conn, $_POST['caj_usr']);
    $contrasena = $_POST['caj_con']; // Se deja sin sanitizar hasta el hash
    $correo = sanitize_input($conn, $_POST['caj_ema']);

    $update_fields = "caj_nom='$nombre', caj_usr='$usuario', caj_ema='$correo'";
    
    // Si se proporciona una nueva contraseña, la hashea y la añade a la consulta
    if (!empty($contrasena)) {
        $contrasena_hash = bin2hex(hash('sha256', $contrasena, true));
        $update_fields .= ", caj_con='$contrasena_hash'";
    }

    $query = "UPDATE tbl_cajero SET $update_fields WHERE caj_id=$id";
    $result = pg_query($conn, $query);

    $response = $result ? ["success" => true, "mensaje" => "Cajero actualizado correctamente"] : ["success" => false, "mensaje" => "Error al actualizar: " . pg_last_error($conn)];
    
    echo json_encode($response);
    exit;
}

/**
 * Endpoint para cambiar el estado (activar/desactivar) de un cajero.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estado') {
    $id = intval($_POST['caj_id']);
    $nuevo_estado = $_POST['estado'] === 'activar' ? 'TRUE' : 'FALSE';
    $estado_mensaje = $_POST['estado'] === 'activar' ? 'activado' : 'desactivado';

    $query = "UPDATE tbl_cajero SET caj_est=$nuevo_estado WHERE caj_id=$id";
    $result = pg_query($conn, $query);

    $response = $result ? ["success" => true, "mensaje" => "Cajero $id $estado_mensaje correctamente"] : ["success" => false, "mensaje" => "Error al cambiar estado: " . pg_last_error($conn)];
    
    echo json_encode($response);
    exit;
}

/**
 * Endpoint para listar los cajeros.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'listar') {
    $cajeros = pg_query($conn, "SELECT * FROM tbl_cajero ORDER BY caj_id DESC");
    $html = "";
    
    while ($row = pg_fetch_assoc($cajeros)) {
        $id = $row['caj_id'];
        $estado = $row['caj_est'] === 't';
        $estado_texto = $estado ? '<span class="badge-success">Activo</span>' : '<span class="badge-danger">Desactivado</span>';
        $btn_accion_texto = $estado ? 'Desactivar' : 'Activar';
        $btn_accion_clase = $estado ? 'btn-danger' : 'btn-success';

        // Pasamos los datos directamente a la función de JS para cargar el formulario
        $html .= "<tr id='fila-{$id}'>
                    <td>{$id}</td>
                    <td>" . htmlspecialchars($row['caj_nom']) . "</td>
                    <td>" . htmlspecialchars($row['caj_usr']) . "</td>
                    <td>" . htmlspecialchars($row['caj_ema']) . "</td>
                    <td>{$estado_texto}</td>
                    <td>
                        <button class='btn btn-warning btn-sm' onclick='cargarDatosEdicion({$id}, \"".htmlspecialchars($row['caj_nom'], ENT_QUOTES)."\", \"".htmlspecialchars($row['caj_usr'], ENT_QUOTES)."\", \"".htmlspecialchars($row['caj_ema'], ENT_QUOTES)."\")'>Editar</button>
                        <button class='btn {$btn_accion_clase} btn-sm' onclick='cambiarEstado({$id}, \"{$btn_accion_texto}\")'>{$btn_accion_texto}</button>
                    </td>
                </tr>";
    }
    echo $html;
    exit;
}


ob_start();
?>

<div class="container-fluid" style="padding: 20px;">
    <h2 class="text-center" style="margin-bottom:20px;">Gestión de Cajeros</h2>

    <div class="row">
        <div id="tablaPrincipalCol" class="col-lg-12">
            <div style="margin-bottom:20px; text-align: left;">
                <button id="btnAgregarCajero" class="btn btn-primary">➕ Agregar Nuevo Cajero</button>
            </div>
            
            <h3 style="margin-top:25px;">Cajeros Registrados</h3>
            <div class="table-container" style="overflow-x:auto;">
                <table id="tablaRegistros" class="display" style="border-collapse:collapse; width:100%; font-size:12px;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>Estado</th>
                            <th data-dt-order="disable">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaCajeros"></tbody>
                </table>
            </div>
        </div>
        
        </div>
</div>

<div class="modal fade" id="cajeroModal" tabindex="-1" aria-labelledby="formTitle" aria-hidden="true">
    <div class="modal-dialog"> 
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formTitle">Crear Nuevo Cajero</h5> 
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formCajero" style="display:flex; flex-direction:column; gap:8px;">
                    <input type="hidden" id="caj_id" name="caj_id">
                    
                    <label for="caj_nom_form" class="form-label">Nombre:</label>
                    <input type="text" id="caj_nom_form" name="caj_nom" required class="form-control form-control-sm">
                    
                    <label for="caj_usr_form" class="form-label">Usuario:</label>
                    <input type="text" id="caj_usr_form" name="caj_usr" required class="form-control form-control-sm">
                    
                    <label for="caj_ema_form" class="form-label">Correo:</label>
                    <input type="email" id="caj_ema_form" name="caj_ema" required class="form-control form-control-sm">
                    
                    <label for="caj_con_form" class="form-label" id="contrasena_label">Contraseña (Obligatoria al crear, opcional al editar):</label>
                    <input type="password" id="caj_con_form" name="caj_con" class="form-control form-control-sm">
                </form>
            </div>
            <div class="modal-footer" style="display: flex; gap: 10px;">
                <button type="submit" form="formCajero" id="btnGuardar" class="btn btn-success btn-sm">Guardar Cajero</button>
                <button type="button" id="btnCancelarForm" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
<style>
    /* Estilos base */
    .btn { padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; margin: 2px; }
    .btn-primary { background: #2196F3; color: white; }
    .btn-success { background: #4CAF50; color: white; }
    .btn-danger { background: #f44336; color: white; }
    .btn-warning { background: #ff9800; color: white; }
    
    .form-control, .form-select { font-size: 12px !important; }
    .form-label { font-size: 12px; margin-bottom: 2px; }

    /* Estilos para badges de estado */
    .badge-success { background-color: #4CAF50; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; }
    .badge-danger { background-color: #f44336; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; }
    
    table th, table td { border: 1px solid #ccc; padding: 6px; text-align: center; vertical-align: middle; }
    th { background: #f2f2f2; }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
    let dataTable = null; 
    let cajeroModal = null; // Instancia del Modal de Bootstrap

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

    /**
     * Carga la lista de cajeros y refresca la DataTables.
     * CORRECCIÓN: Destruye y vuelve a inicializar DataTables correctamente.
     */
    function cargarCajeros() {
        // 1. Destruir la instancia de DataTables si existe
        if (dataTable) {
            dataTable.destroy();
            dataTable = null; 
        }
        
        let form = new FormData();
        form.append("accion", "listar");
        const tablaBody = document.getElementById("tablaCajeros");
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
                        url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
                    }
                });
            })
            .catch(err => {
                 mostrarAlerta({ success: false, mensaje: 'Error al cargar la lista de cajeros.' });
                 tablaBody.innerHTML = '<tr><td colspan="6">Error al cargar la lista.</td></tr>';
            });
    }
    
    // ============== LÓGICA DE EDICIÓN (MODAL) ==============

    window.cargarDatosEdicion = function(id, nombre, usuario, correo) {
        // 1. Configurar el formulario para edición
        document.getElementById('caj_id').value = id;
        document.getElementById('caj_nom_form').value = nombre;
        document.getElementById('caj_usr_form').value = usuario;
        document.getElementById('caj_ema_form').value = correo;
        document.getElementById('caj_con_form').value = ''; // Siempre limpiar contraseña en edición
        
        // 2. Actualizar textos de formulario
        document.getElementById('formTitle').innerText = 'Editar Cajero ID: ' + id;
        document.getElementById('btnGuardar').innerText = 'Actualizar Cajero';
        document.getElementById('contrasena_label').innerText = 'Contraseña (Opcional, dejar vacío para no cambiar):';
        
        // 3. Mostrar el modal
        if (cajeroModal) {
            cajeroModal.show();
        }
    };

    // ============== LÓGICA DE ACTIVAR/DESACTIVAR ==============
    window.cambiarEstado = function(id, estadoActual) {
        const estadoNuevo = estadoActual === 'Activar' ? 'activar' : 'desactivar';
        const icono = estadoNuevo === 'activar' ? 'question' : 'warning';
        const color = estadoNuevo === 'activar' ? '#3085d6' : '#d33';

        Swal.fire({
            title: `¿Estás seguro de ${estadoNuevo} el cajero?`,
            text: `El cajero será marcado como ${estadoNuevo}.`,
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
                form.append("caj_id", id);
                form.append("estado", estadoNuevo); 

                fetch("", { method: "POST", body: form })
                    .then(res => res.json())
                    .then(data => {
                        mostrarAlerta(data);
                        if (data.success) cargarCajeros();
                    })
                    .catch(error => {
                        mostrarAlerta({ success: false, mensaje: 'Error de red al cambiar el estado.' });
                    });
            }
        });
    }

    // ============== LÓGICA DE CREACIÓN Y ACTUALIZACIÓN (Form Submit) ==============

    document.getElementById("btnAgregarCajero").addEventListener("click", function() {
        // Modo CREACIÓN
        document.getElementById("formCajero").reset();
        document.getElementById("caj_id").value = ""; 
        document.getElementById("formTitle").innerText = 'Crear Nuevo Cajero';
        document.getElementById("btnGuardar").innerText = "Guardar Cajero";
        document.getElementById('contrasena_label').innerText = 'Contraseña (Obligatoria):';

        // Mostrar el modal
        if (cajeroModal) {
            cajeroModal.show();
        }
    });

    document.getElementById("formCajero").addEventListener("submit", function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let cajeroId = document.getElementById("caj_id").value;
        let accion = cajeroId ? "actualizar" : "crear";

        // Validación de contraseña si estamos creando
        if (accion === 'crear' && !document.getElementById('caj_con_form').value.trim()) {
            mostrarAlerta({ success: false, mensaje: 'La contraseña es obligatoria para un nuevo cajero.' });
            return;
        }

        formData.append('accion',accion);

        fetch('',{method:'POST',body:formData})
        .then(res=>res.json())
        .then(data=>{
            mostrarAlerta(data);

            if(data.success){
                // Reset y ocultar formulario, y cargar de nuevo
                document.getElementById('formCajero').reset();
                document.getElementById('caj_id').value = '';
                
                // Ocultar el modal
                if (cajeroModal) {
                    cajeroModal.hide();
                }

                cargarCajeros(); 
            }
        })
        .catch(error => {
            mostrarAlerta({ success: false, mensaje: 'Error de red en la operación.' });
        });
    });

    // Cargar cajeros e inicializar el modal al iniciar
    document.addEventListener("DOMContentLoaded", function() {
        // Inicializar el objeto Modal de Bootstrap
        // Asumiendo que 'plantillaAdmin.php' incluye Bootstrap JS.
        cajeroModal = new bootstrap.Modal(document.getElementById('cajeroModal'), {});
        
        cargarCajeros();
    });
</script>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
?>