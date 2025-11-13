<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

// Validar que se reciba un ID de parqueadero válido
if (!isset($_GET['par_id']) || !is_numeric($_GET['par_id'])) {
    // Redirigir si no hay un ID de parqueadero válido
    header("Location: crear_parqueadero.php");
    exit;
}

$id_parqueadero = intval($_GET['par_id']);

// ================== ACCIONES AJAX ==================

// Crear puesto (Formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $numero_puesto = pg_escape_string($conn, $_POST['pue_num']);
    $estado          = pg_escape_string($conn, $_POST['pue_est']);
    $id_admin        = intval($_SESSION['id']);
    $id_parqueadero_post = intval($_POST['pue_id_par']);

    if ($id_parqueadero_post !== $id_parqueadero) {
        echo json_encode(["success" => false, "mensaje" => "Error de seguridad: ID de parqueadero no coincide."]);
        exit;
    }

    // El campo pue_act se establece a TRUE por defecto en la tabla
    $query = "INSERT INTO tbl_puestos_parqueadero
              (pue_id_par, pue_num, pue_est, pue_id_adm)
              VALUES
              ($id_parqueadero, '$numero_puesto', '$estado', $id_admin)";
    $result = pg_query($conn, $query);

    echo json_encode($result ? ["success" => true, "mensaje" => "Puesto de parqueadero creado correctamente"] : ["success" => false, "mensaje" => "Error: " . pg_last_error($conn)]);
    exit;
}

// Listar puestos (Simplificada y limpia para DataTables)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'listar') {
    $puestos = pg_query($conn, "SELECT * FROM tbl_puestos_parqueadero WHERE pue_id_par = $id_parqueadero ORDER BY pue_id DESC");
    $html = "";
    while ($row = pg_fetch_assoc($puestos)) {
        $id = $row['pue_id'];
        
        // Lógica de Estado (pue_act)
        $estado_activo = $row['pue_act'] === 't';
        $estado_texto = $estado_activo ? '<span class="badge-success">Activo</span>' : '<span class="badge-danger">Desactivado</span>';
        $btn_estado_clase = $estado_activo ? 'btn-danger' : 'btn-success';
        $btn_estado_texto = $estado_activo ? 'Desactivar' : 'Activar';

        // Codificamos los datos para pasarlos de forma segura a la función JS
        $pue_num_js = htmlspecialchars($row['pue_num'], ENT_QUOTES);
        $pue_est_js = htmlspecialchars($row['pue_est'], ENT_QUOTES);
        
        $html .= "<tr id='fila-{$id}'>
                    <td>{$id}</td>
                    <td>" . htmlspecialchars($row['pue_num']) . "</td>
                    <td>" . ucfirst(htmlspecialchars($row['pue_est'])) . "</td>
                    <td>{$estado_texto}</td>
                    <td>{$row['pue_fec_cre']}</td>
                    <td>{$row['pue_fec_edi']}</td>
                    <td>
                        <button class='btn btn-warning btn-sm' onclick='cargarDatosEdicion({$id}, \"{$pue_num_js}\", \"{$pue_est_js}\")'>Editar</button>
                        <button class='btn {$btn_estado_clase} btn-sm' onclick='cambiarEstadoPuesto({$id}, \"{$btn_estado_texto}\")'>{$btn_estado_texto}</button>
                    </td>
                </tr>";
    }
    echo $html;
    exit;
}

// Acción CAMBIAR ESTADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiarEstado') {
    $id = intval($_POST['id']);
    // 'Activar' o 'Desactivar' viene del JS y se mapea a TRUE/FALSE en la DB
    $nuevo_estado = $_POST['estado'] === 'Activar' ? 'TRUE' : 'FALSE';
    $mensaje_estado = $_POST['estado'] === 'Activar' ? 'activado' : 'desactivado';

    $query = "UPDATE tbl_puestos_parqueadero SET pue_act={$nuevo_estado}, pue_fec_edi=CURRENT_TIMESTAMP WHERE pue_id=$id AND pue_id_par=$id_parqueadero";
    $res = pg_query($conn, $query);

    if ($res) {
        echo json_encode(["success" => true, "mensaje" => "Puesto {$mensaje_estado} correctamente."]);
    } else {
        echo json_encode(["success" => false, "mensaje" => "Error al cambiar el estado: " . pg_last_error($conn)]);
    }
    exit;
}

// Actualizar puesto (Formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $id              = intval($_POST['pue_id']);
    $numero_puesto   = pg_escape_string($conn, $_POST['pue_num']);
    $estado          = pg_escape_string($conn, $_POST['pue_est']);
    $id_parqueadero_post = intval($_POST['pue_id_par']);

    if ($id_parqueadero_post !== $id_parqueadero) {
        echo json_encode(["success" => false, "mensaje" => "Error de seguridad: ID de parqueadero no coincide."]);
        exit;
    }

    $query = "UPDATE tbl_puestos_parqueadero SET
              pue_num='$numero_puesto', pue_est='$estado', pue_fec_edi=CURRENT_TIMESTAMP
              WHERE pue_id=$id AND pue_id_par=$id_parqueadero";

    $res = pg_query($conn, $query);

    echo json_encode($res ? ["success" => true, "mensaje" => "Puesto actualizado"] : ["success" => false, "mensaje" => "Error: " . pg_last_error($conn)]);
    exit;
}

ob_start();
?>

<div style="text-align: center;">
    <a href="crear_parqueadero.php" style="text-decoration: none;"><button class="back-button">← Volver a Parqueaderos</button></a>
    
    <h2 style="margin-bottom:15px;">Gestionar Puestos para Parqueadero #<?php echo htmlspecialchars($id_parqueadero); ?></h2>
    
    <div style="margin-bottom:20px;">
        <button id="btnAgregarPuesto" class="btn btn-primary">➕ Agregar Nuevo Puesto</button>
    </div>

    <div id="formularioContainer" style="display: none; margin: 0 auto; width: 350px; border: 1px solid #ccc; padding: 20px; border-radius: 5px; text-align: left;">
        <h3 id="formTitle" style="margin-top:0;">Crear Puesto</h3>
        <form id="formPuesto" style="display:flex; flex-direction:column; gap:8px;">
            <input type="hidden" id="pue_id" name="pue_id">
            <input type="hidden" id="pue_id_par" name="pue_id_par" value="<?php echo htmlspecialchars($id_parqueadero); ?>">
            
            <label>Número de Puesto:</label>
            <input type="text" id="numero" name="pue_num" required style="padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">
            
            <label>Estado (Lógico):</label>
            <select id="estado" name="pue_est" style="padding:8px; border-radius:4px; border:1px solid #ccc; font-size:12px;">
                <option value="disponible">Disponible</option>
                <option value="ocupado">Ocupado</option>
                <option value="reservado">Reservado</option>
            </select>
            
            <button type="submit" id="btnGuardar" class="btn btn-success" style="margin-top:10px;">Guardar Puesto</button>
            <button type="button" id="btnCancelarForm" class="btn btn-danger">Cancelar</button>
        </form>
    </div>

    <div class="mensaje" id="mensaje" style="margin:10px 0; font-size:12px; text-align:center;"></div>

    <h3 style="margin-top:25px;">Puestos Registrados</h3>
    <div class="table-container" style="overflow-x:auto;">
        <table id="tablaRegistros" class="display" style="border-collapse:collapse; width:100%; font-size:12px; margin:0 auto;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Número</th>
                    <th>Estado (Lógico)</th>
                    <th>Estado (Act/Des)</th>
                    <th>Fecha Creación</th>
                    <th>Fecha Edición</th>
                    <th data-dt-order="disable">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaPuestos"></tbody>
        </table>
    </div>
</div>

<style>
    /* Estilos base */
    .btn { padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; margin: 2px; }
    .btn-primary { background: #2196F3; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-success { background: #4CAF50; color: white; }
    .btn-danger { background: #f44336; color: white; }
    .btn-warning { background: #ff9800; color: white; }
    .btn-info { background: #00bcd4; color: white; }
    .back-button { background-color: #f44336; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; margin-bottom: 20px; }
    
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
    const ID_PARQUEADERO = <?php echo json_encode($id_parqueadero); ?>;
    let dataTable = null; 

    /**
     * Muestra una alerta usando SweetAlert2 para una mejor UX.
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
    
    function cargarPuestos() {
        // 1. Destruir la instancia de DataTables si existe
        if (dataTable) {
            dataTable.destroy();
            dataTable = null; 
        }
        
        let form = new FormData();
        form.append('accion', 'listar');
        const tablaBody = document.getElementById('tablaPuestos');
        tablaBody.innerHTML = '<tr><td colspan="7">Cargando...</td></tr>'; // Cambié colspan a 7

        fetch('', { method: 'POST', body: form })
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
                 mostrarAlerta({ success: false, mensaje: 'Error al cargar la lista de puestos.' });
                 tablaBody.innerHTML = '<tr><td colspan="7" style="color:red;">Error al cargar la lista.</td></tr>';
            });
    }

    // ============== LÓGICA DE EDICIÓN EN FORMULARIO ==============

    window.cargarDatosEdicion = function(id, numero, estado) {
        // 1. Configurar el formulario para edición
        document.getElementById('pue_id').value = id;
        document.getElementById('numero').value = numero;
        document.getElementById('estado').value = estado;
        
        // 2. Actualizar textos de formulario para EDICIÓN
        document.getElementById('formTitle').innerText = 'Editar Puesto ID: ' + id;
        document.getElementById('btnGuardar').innerText = 'Actualizar Puesto';
        
        // 3. Mostrar el formulario
        document.getElementById('formularioContainer').style.display = 'block';
        document.documentElement.scrollTop = 0; // Subir al inicio para ver el formulario
    };

    // ============== LÓGICA de ACTIVAR/DESACTIVAR (Cambiar Estado) ==============
    window.cambiarEstadoPuesto = function(id, estadoActual) {
        const estadoNuevo = estadoActual === 'Activar' ? 'Activar' : 'Desactivar';
        const icono = estadoActual === 'Activar' ? 'question' : 'warning';
        const color = estadoActual === 'Activar' ? '#3085d6' : '#d33';

        Swal.fire({
            title: `¿Estás seguro de ${estadoNuevo} el puesto?`,
            text: `El puesto será marcado como ${estadoNuevo.toLowerCase()}.`,
            icon: icono,
            showCancelButton: true,
            confirmButtonText: `Sí, ${estadoNuevo.toLowerCase()}`,
            cancelButtonText: "Cancelar",
            confirmButtonColor: color,
            cancelButtonColor: "#6c757d"
        }).then((result) => {
            if (result.isConfirmed) {
                let form = new FormData();
                form.append("accion", "cambiarEstado");
                form.append("id", id);
                form.append("estado", estadoActual); // Enviamos el texto del botón: 'Activar' o 'Desactivar'

                fetch("", { method: "POST", body: form })
                    .then(res => res.json())
                    .then(data => {
                        mostrarAlerta(data);
                        if (data.success) cargarPuestos();
                    })
                    .catch(error => {
                        mostrarAlerta({ success: false, mensaje: 'Error de red al cambiar el estado.' });
                    });
            }
        });
    }

    // ============== LÓGICA DE CREACIÓN Y ACTUALIZACIÓN (Form Submit) ==============

    document.getElementById("btnAgregarPuesto").addEventListener("click", function() {
        document.getElementById("formularioContainer").style.display = "block";
        document.getElementById("formPuesto").reset();
        document.getElementById("pue_id").value = ""; // Indica modo CREACIÓN
        document.getElementById("formTitle").innerText = 'Crear Nuevo Puesto';
        document.getElementById("btnGuardar").innerText = "Guardar Puesto";
        document.documentElement.scrollTop = 0;
    });

    document.getElementById("btnCancelarForm").addEventListener("click", function() {
        document.getElementById("formPuesto").reset();
        document.getElementById("pue_id").value = "";
        document.getElementById("formularioContainer").style.display = "none";
    });

    document.getElementById("formPuesto").addEventListener("submit", function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let puestoId = document.getElementById("pue_id").value;
        let accion = puestoId ? "actualizar" : "crear";
        formData.append("accion", accion);

        const confirmar = () => {
             enviarFormulario(formData);
        };

        if (accion === "actualizar") {
             Swal.fire({
                 title: "¿Estás seguro?",
                 text: "Deseas actualizar este puesto.",
                 icon: "warning",
                 showCancelButton: true,
                 confirmButtonText: "Sí, actualizar",
                 cancelButtonText: "Cancelar",
                 confirmButtonColor: "#3085d6",
                 cancelButtonColor: "#d33"
             }).then((result) => {
                 if (result.isConfirmed) {
                     confirmar();
                 }
             });
        } else {
            confirmar();
        }
    });

    function enviarFormulario(formData) {
        fetch("", { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                mostrarAlerta(data);
                if (data.success) {
                    document.getElementById("formPuesto").reset();
                    document.getElementById("pue_id").value = "";
                    document.getElementById("formularioContainer").style.display = "none";
                    cargarPuestos();
                }
            })
            .catch(() => {
                mostrarAlerta({ success: false, mensaje: "Error al conectar con el servidor." });
            });
    }

    // Cargar puestos al iniciar
    document.addEventListener("DOMContentLoaded", cargarPuestos);
</script>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
?>