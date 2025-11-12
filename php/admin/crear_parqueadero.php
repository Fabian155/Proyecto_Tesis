<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

// ================== ACCIONES AJAX ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // Acción CREAR
    if ($accion === 'crear') {
        $nombre = pg_escape_string($conn, $_POST['par_nom']);
        $ubicacion = pg_escape_string($conn, $_POST['par_ubi']);
        $capacidad = intval($_POST['par_cap']);
        $id_admin = intval($_SESSION['id']);

        // Se agrega 'par_est' = TRUE por defecto (ya lo tienes en la definición de la tabla, pero se incluye por claridad)
        $query = "INSERT INTO tbl_parqueadero
                    (par_nom, par_ubi, par_cap, par_id_adm, par_est)
                    VALUES
                    ('$nombre', '$ubicacion', $capacidad, $id_admin, TRUE)";
        $result = pg_query($conn, $query);

        echo json_encode($result ? ["success" => true, "mensaje" => "Parqueadero creado correctamente"] : ["success" => false, "mensaje" => "Error: " . pg_last_error($conn)]);
        exit;
    }

    // Acción LISTAR (Simplificada y limpia para DataTables)
    if ($accion === 'listar') {
        $parqueaderos = pg_query($conn, "SELECT * FROM tbl_parqueadero ORDER BY par_id DESC");
        $html = "";
        while ($row = pg_fetch_assoc($parqueaderos)) {
            $id = $row['par_id'];
            $estado_texto = $row['par_est'] === 't' ? '<span class="badge-success">Activo</span>' : '<span class="badge-danger">Desactivado</span>';
            $btn_estado_clase = $row['par_est'] === 't' ? 'btn-danger' : 'btn-success';
            $btn_estado_texto = $row['par_est'] === 't' ? 'Desactivar' : 'Activar';

            // Pasamos los datos directamente a la función de JS para cargar el formulario
            $html .= "<tr id='fila-{$id}'>
                        <td>{$id}</td>
                        <td>" . htmlspecialchars($row['par_nom']) . "</td>
                        <td>" . htmlspecialchars($row['par_ubi']) . "</td>
                        <td>{$row['par_cap']}</td>
                        <td>{$estado_texto}</td>
                        <td>{$row['par_fec_cre']}</td>
                        <td>{$row['par_fec_edi']}</td>
                        <td>
                            <button class='btn btn-warning btn-sm' onclick='cargarDatosEdicion({$id}, \"".htmlspecialchars($row['par_nom'], ENT_QUOTES)."\", \"".htmlspecialchars($row['par_ubi'], ENT_QUOTES)."\", {$row['par_cap']})'>Editar</button>
                            <button class='btn {$btn_estado_clase} btn-sm' onclick='cambiarEstadoParqueadero({$id}, \"{$btn_estado_texto}\")'>{$btn_estado_texto}</button>
                            <a href='crear_puestos.php?par_id={$row['par_id']}' class='btn btn-info btn-sm'>Puestos</a>
                        </td>
                    </tr>";
        }
        echo $html;
        exit;
    }

    // Acción CAMBIAR ESTADO
    if ($accion === 'cambiarEstado') {
        $id = intval($_POST['id']);
        $nuevo_estado = $_POST['estado'] === 'Activar' ? 'TRUE' : 'FALSE';
        $mensaje_estado = $_POST['estado'] === 'Activar' ? 'activado' : 'desactivado';

        $query = "UPDATE tbl_parqueadero SET par_est={$nuevo_estado}, par_fec_edi=CURRENT_TIMESTAMP WHERE par_id=$id";
        $res = pg_query($conn, $query);

        if ($res) {
            echo json_encode(["success" => true, "mensaje" => "Parqueadero {$mensaje_estado} correctamente."]);
        } else {
            echo json_encode(["success" => false, "mensaje" => "Error al cambiar el estado: " . pg_last_error($conn)]);
        }
        exit;
    }

    // Acción ACTUALIZAR (Desde el formulario)
    if ($accion === 'actualizar') {
        $id = intval($_POST['par_id']);
        $nombre = pg_escape_string($conn, $_POST['par_nom']);
        $ubicacion = pg_escape_string($conn, $_POST['par_ubi']);
        $capacidad = intval($_POST['par_cap']);

        $query = "UPDATE tbl_parqueadero SET
                  par_nom='$nombre', par_ubi='$ubicacion', par_cap=$capacidad, par_fec_edi=CURRENT_TIMESTAMP
                  WHERE par_id=$id";

        $res = pg_query($conn, $query);

        echo json_encode($res ? ["success" => true, "mensaje" => "Parqueadero actualizado"] : ["success" => false, "mensaje" => "Error: " . pg_last_error($conn)]);
        exit;
    }
}

// ================== CONTENIDO PARA PLANTILLA ==================
ob_start();
?>

<div style="text-align: center;">
    <h2 style="margin-bottom:15px;">Gestión de Parqueaderos</h2>
    
    <div style="margin-bottom:20px;">
        <button id="btnAgregarParqueadero" class="btn btn-primary">➕ Agregar Nuevo Parqueadero</button>
    </div>

    <div class="modal fade" id="parqueaderoModal" tabindex="-1" aria-labelledby="formTitle" aria-hidden="true">
        <div class="modal-dialog"> 
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formTitle">Crear Nuevo Parqueadero</h5> 
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formParqueadero" style="display:flex; flex-direction:column; gap:8px;">
                        <input type="hidden" id="par_id" name="par_id">
                        <label>Nombre:</label>
                        <input type="text" id="nombre" name="par_nom" required class="form-control form-control-sm" style="font-size:12px;">
                        <label>Ubicación:</label>
                        <input type="text" id="ubicacion" name="par_ubi" required class="form-control form-control-sm" style="font-size:12px;">
                        <label>Capacidad:</label>
                        <input type="number" id="capacidad" name="par_cap" required min="1" class="form-control form-control-sm" style="font-size:12px;">
                    </form>
                </div>
                <div class="modal-footer" style="display: flex; gap: 10px;">
                    <button type="submit" form="formParqueadero" id="btnGuardar" class="btn btn-success btn-sm">Guardar Parqueadero</button>
                    <button type="button" id="btnCancelarForm" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    <h3 style="margin-top:25px;">Parqueaderos Registrados</h3>
    <div class="table-container" style="overflow-x:auto;">
        <table id="tablaRegistros" class="display" style="border-collapse:collapse; width:100%; font-size:12px; margin:0 auto;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Ubicación</th>
                    <th>Capacidad</th>
                    <th>Estado</th> <th>Fecha Creación</th>
                    <th>Fecha Edición</th>
                    <th data-dt-order="disable">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaParqueaderos"></tbody>
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

    /* Estilos para badges de estado */
    .badge-success { background-color: #4CAF50; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; }
    .badge-danger { background-color: #f44336; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; }
    
    .form-control, .form-select { font-size: 12px !important; } /* Añadido para consistencia con bootstrap/modal */

    table th, table td { border: 1px solid #ccc; padding: 6px; text-align: center; vertical-align: middle; }
    th { background: #f2f2f2; }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
    let dataTable = null; 
    let parqueaderoModal = null; // Variable para la instancia del Modal de Bootstrap

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

    function cargarParqueaderos() {
        // 1. Destruir la instancia de DataTables si existe
        if (dataTable) {
            dataTable.destroy();
            dataTable = null; 
        }
        
        let form = new FormData();
        form.append("accion", "listar");
        const tablaBody = document.getElementById("tablaParqueaderos");
        tablaBody.innerHTML = '<tr><td colspan="8">Cargando...</td></tr>'; 
        
        fetch("", { method: "POST", body: form })
            .then(res => res.text())
            .then(html => {
                tablaBody.innerHTML = html;
                
                // 2. Inicializar DataTables después de cargar el contenido
                // Nota: La inicialización se hace después del fetch exitoso, que es la mejor práctica.
                // Usamos el constructor de DataTables directamente sobre el elemento.
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
                 mostrarAlerta({ success: false, mensaje: 'Error al cargar la lista de parqueaderos.' });
                 tablaBody.innerHTML = '<tr><td colspan="8">Error al cargar la lista.</td></tr>';
            });
    }
    
    // ============== LÓGICA DE EDICIÓN EN FORMULARIO (MODAL) ==============

    window.cargarDatosEdicion = function(id, nombre, ubicacion, capacidad) {
        // 1. Configurar el formulario para edición
        document.getElementById('par_id').value = id;
        document.getElementById('nombre').value = nombre;
        document.getElementById('ubicacion').value = ubicacion;
        document.getElementById('capacidad').value = capacidad;
        
        // 2. Actualizar textos de formulario para EDICIÓN
        document.getElementById('formTitle').innerText = 'Editar Parqueadero ID: ' + id;
        document.getElementById('btnGuardar').innerText = 'Actualizar Parqueadero';
        
        // 3. Mostrar el modal
        if (parqueaderoModal) {
            parqueaderoModal.show();
        }
    };

    // ============== LÓGICA DE ACTIVAR/DESACTIVAR (Cambiar Estado) ==============
    window.cambiarEstadoParqueadero = function(id, estadoActual) {
        const estadoNuevo = estadoActual === 'Activar' ? 'Activar' : 'Desactivar';
        const icono = estadoActual === 'Activar' ? 'question' : 'warning';
        const color = estadoActual === 'Activar' ? '#3085d6' : '#d33';

        Swal.fire({
            title: `¿Estás seguro de ${estadoNuevo} el parqueadero?`,
            text: `El parqueadero será marcado como ${estadoNuevo.toLowerCase()}.`,
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
                        if (data.success) cargarParqueaderos();
                    })
                    .catch(error => {
                        mostrarAlerta({ success: false, mensaje: 'Error de red al cambiar el estado.' });
                    });
            }
        });
    }

    // ============== LÓGICA DE CREACIÓN Y ACTUALIZACIÓN (Form Submit) ==============

    document.getElementById("btnAgregarParqueadero").addEventListener("click", function() {
        // Modo CREACIÓN
        document.getElementById("formParqueadero").reset();
        document.getElementById("par_id").value = ""; // Indica modo CREACIÓN
        document.getElementById("formTitle").innerText = 'Crear Nuevo Parqueadero';
        document.getElementById("btnGuardar").innerText = "Guardar Parqueadero";
        
        // Mostrar el modal
        if (parqueaderoModal) {
            parqueaderoModal.show();
        }
    });

    // El botón 'Cancelar' tiene data-bs-dismiss="modal" y cierra el modal
    document.getElementById("btnCancelarForm").addEventListener("click", function() {
        // Limpiar formulario al cerrar, aunque el modal lo cierra por sí mismo, es buena práctica
        document.getElementById("formParqueadero").reset();
        document.getElementById("par_id").value = "";
    });

    document.getElementById("formParqueadero").addEventListener("submit", function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let parqueaderoId = document.getElementById("par_id").value;
        let accion = parqueaderoId ? "actualizar" : "crear";
        formData.append("accion", accion);

        if (parseInt(document.getElementById('capacidad').value) < 1) {
            mostrarAlerta({ success: false, mensaje: 'La capacidad debe ser mayor a cero.' });
            return;
        }

        const confirmar = () => {
             enviarFormulario(formData);
        };

        if (accion === "actualizar") {
             Swal.fire({
                 title: "¿Estás seguro?",
                 text: "Deseas actualizar este parqueadero.",
                 icon: "warning",
                 showCancelButton: true,
                 confirmButtonText: "Sí, actualizar",
                 cancelButtonText: "Cancelar",
                 confirmButtonColor: "#ff9800", // Cambiado a warning/amarillo
                 cancelButtonColor: "#6c757d"
            }).then((result) => {
                 if (result.isConfirmed) {
                     confirmar();
                 }
            });
        } else {
            // Modo CREAR
            confirmar();
        }
    });

    function enviarFormulario(formData) {
        fetch("", { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                mostrarAlerta(data);
                if (data.success) {
                    document.getElementById("formParqueadero").reset();
                    document.getElementById("par_id").value = "";
                    
                    // Ocultar el modal en lugar de manipular el estilo del div
                    if (parqueaderoModal) {
                        parqueaderoModal.hide();
                    }
                    
                    cargarParqueaderos();
                }
            })
            .catch(() => {
                mostrarAlerta({ success: false, mensaje: "Error al conectar con el servidor." });
            });
    }

    // Cargar parqueaderos e inicializar el modal al iniciar
    document.addEventListener("DOMContentLoaded", function() {
        // Inicializar el objeto Modal de Bootstrap
        // Necesita la librería de Bootstrap JS para funcionar.
        // Asumiendo que 'plantillaAdmin.php' incluye Bootstrap JS.
        // Si no, debes agregarlo al código.
        parqueaderoModal = new bootstrap.Modal(document.getElementById('parqueaderoModal'), {});

        cargarParqueaderos();
    });
</script>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
?>