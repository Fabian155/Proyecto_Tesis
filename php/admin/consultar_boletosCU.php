<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

// ================== LÓGICA PARA CAMBIAR EL ESTADO DE VERIFICACIÓN (Avanzada) ==================
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 1. ACCIÓN: VALIDAR TODOS PENDIENTES
    if ($_POST['action'] === 'validar_todos') {
        $update_query = "
            UPDATE tbl_compras_boletos 
            SET com_est_verif = 'validado', com_fec_edi = CURRENT_TIMESTAMP 
            WHERE com_met = 'transferencia' AND com_est_verif = 'por_validar'
        ";
        $update_result = pg_query($conn, $update_query);

        if ($update_result) {
            $num_rows = pg_affected_rows($update_result);
            $success_message = "¡Éxito! Se han validado $num_rows transferencias pendientes.";
        } else {
            $error_message = "Error al validar todas las transferencias: " . pg_last_error($conn);
        }
    } 
    // 2. ACCIÓN: VALIDACIÓN INDIVIDUAL
    elseif ($_POST['action'] === 'validar_individual' && isset($_POST['com_id'], $_POST['nuevo_estado'])) {
        $com_id = $_POST['com_id'];
        $nuevo_estado = $_POST['nuevo_estado'];

        $update_com_act_clause = '';
        $query_params = [$nuevo_estado, $com_id]; 

        if (in_array($nuevo_estado, ['validado', 'transferencia_no_valida']) && is_numeric($com_id)) {
            
            // Si se marca como no válida, se inactiva el boleto (com_act = 'f')
            if ($nuevo_estado === 'transferencia_no_valida') {
                $update_com_act_clause = ', com_act = $3'; 
                // Parámetros: $1=nuevo_estado, $2=com_id, $3='f' (false)
                $query_params = [$nuevo_estado, $com_id, 'f']; 
            }
            
            $update_query = "
                UPDATE tbl_compras_boletos 
                SET com_est_verif = $1, com_fec_edi = CURRENT_TIMESTAMP " . $update_com_act_clause . "
                WHERE com_id = $2 AND com_met = 'transferencia'
            ";

            $update_result = pg_query_params($conn, $update_query, $query_params);

            if ($update_result && pg_affected_rows($update_result) > 0) {
                $status_text = ($nuevo_estado === 'validado') ? 'Validado' : 'Inválido';
                $success_message = "¡Éxito! El boleto ID $com_id se marcó como $status_text.";
                if ($nuevo_estado === 'transferencia_no_valida') {
                    $success_message .= " y se **inhabilitó** para su uso (Usado).";
                }
            } else {
                $error_message = "Error al actualizar el boleto ID $com_id: " . pg_last_error($conn);
            }
        } else {
            $error_message = "Error: Estado o ID de boleto no válido.";
        }
    }

    // Redirección para evitar reenvío y volver a la pestaña de transferencias
    if ($success_message || $error_message) {
        $redirect_url = $_SERVER['PHP_SELF'] . "?tab=transferencias";
        if ($success_message) $redirect_url .= "&success=" . urlencode($success_message);
        if ($error_message) $redirect_url .= "&error=" . urlencode($error_message);
        header("Location: " . $redirect_url);
        exit;
    }
}
// ====================================================================================


// ================== CONSULTA DE BOLETOS ==================
$query = "
SELECT 
    cb.com_id,
    u.usr_nom || ' ' || u.usr_ape AS usuario,
    e.evt_tit AS evento,
    cb.com_met,
    cb.com_can_bol,
    cb.com_pre_tot,
    cb.com_fec,
    cb.com_uso_con,
    cb.com_act,
    cb.com_num_transf,
    cb.com_qr,
    cb.com_ruta_qr_parq,
    cb.com_id_res_par,
    cb.com_est_verif
FROM tbl_compras_boletos cb
JOIN tbl_usuario u ON cb.com_id_usr = u.usr_id
JOIN tbl_evento e ON cb.com_id_evt = e.evt_id
ORDER BY cb.com_fec DESC
";

$result = pg_query($conn, $query);

// Pre-separar los resultados en dos arrays para las tablas
$pagos_lugar = [];
$transferencias = [];

if (pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        if ($row['com_met'] === 'pago_en_lugar') {
            $pagos_lugar[] = $row;
        } elseif ($row['com_met'] === 'transferencia') {
            $transferencias[] = $row;
        }
    }
}

// Mensajes después de la redirección
if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}


// Función para formatear el estado de verificación
function format_verif_state($estado) {
    $clase = 'verif-' . $estado;
    $texto = ucwords(str_replace('_', ' ', $estado));
    return "<span class=\"$clase\">$texto</span>";
}


ob_start();
?>

<style>
    /* Estilos base */
    .content-wrapper { padding: 20px; max-width: 1600px; margin: 0 auto; }
    .page-title { text-align: center; color: #c0392b; margin-bottom: 20px; }
    .table-container { overflow-x: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
    .tab-buttons { display: flex; justify-content: center; margin-bottom: 20px; }
    .tab-button { padding: 10px 20px; border: none; background-color: #ecf0f1; cursor: pointer; font-weight: bold; transition: background-color 0.3s; border-radius: 5px 5px 0 0; margin: 0 5px; }
    .tab-button.active { background-color: #34495e; color: white; }
    .tab-content { border-top: 2px solid #34495e; padding-top: 20px; }
    .tab-pane { display: none; }
    .tab-pane.active { display: block; }
    
    /* Nota: Usamos la clase 'dataTable' para DataTables */
    table.data-table { border-collapse: collapse; width: 100%; min-width: 1200px; font-size: 14px; }
    .data-table th, .data-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    .data-table th { background-color: #34495e; color: white; text-transform: uppercase; font-weight: bold; position: sticky; top: 0; z-index: 10; }
    .data-table tr:nth-child(even) { background-color: #f8f9fa; }
    .data-table td img.qr { max-width: 60px; height: auto; display: block; margin: 0 auto; }
    .estado-activo { color: #fff; background-color: #28a745; padding: 3px 7px; border-radius: 4px; display: inline-block; font-weight: bold; }
    .estado-usado { color: #fff; background-color: #dc3545; padding: 3px 7px; border-radius: 4px; display: inline-block; font-weight: bold; }
    /* Estilos para los 4 estados de verificación */
    .verif-por_validar { color: #333; background-color: #ffc107; padding: 3px 7px; border-radius: 4px; display: inline-block; font-weight: bold; }
    .verif-validado { color: #fff; background-color: #17a2b8; padding: 3px 7px; border-radius: 4px; display: inline-block; font-weight: bold; }
    .verif-transferencia_no_valida { color: #fff; background-color: #dc3545; padding: 3px 7px; border-radius: 4px; display: inline-block; font-weight: bold; }
    .verif-no_aplica { color: #34495e; background-color: #ecf0f1; padding: 3px 7px; border-radius: 4px; display: inline-block; font-weight: bold; }
    .no-results { text-align: center; padding: 20px; color: #777; }
    
    /* Controles de Validación (Modo Transferencias) */
    .validation-controls { 
        display: flex; 
        justify-content: flex-end; /* Alinear a la derecha el botón de validar todos */
        align-items: center;
        margin-bottom: 15px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .validation-controls button#btn-validar-todos {
        padding: 10px 20px;
        background-color: #28a745; /* Verde para acción masiva */
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.3s;
    }
    .validation-controls button#btn-validar-todos:hover {
        background-color: #1e7e34;
    }

    /* Estilos de los botones de acción individual */
    .action-buttons-wrapper {
        display: flex;
        gap: 5px;
        justify-content: center;
        align-items: center;
    }
    .btn-validate-individual {
        padding: 5px 8px;
        background-color: #17a2b8; /* Azul claro para Validar */
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        font-size: 13px;
        transition: background-color 0.3s;
    }
    .btn-validate-individual:hover {
        background-color: #117a8b;
    }
    .btn-invalidate-individual {
        padding: 5px 8px;
        background-color: #dc3545; /* Rojo para Inválido */
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        font-size: 13px;
        transition: background-color 0.3s;
    }
    .btn-invalidate-individual:hover {
        background-color: #c82333;
    }

    .editable-verif-col {
        min-width: 170px;
        vertical-align: middle;
        text-align: center !important;
    }
</style>

<div class="content-wrapper">
    <h2 class="page-title">Boletos Registrados por Tipo de Pago</h2>

    <?php if ($success_message): ?>
        <p style="color: green; text-align: center; font-weight: bold;"><?php echo $success_message; ?></p>
    <?php elseif ($error_message): ?>
        <p style="color: red; text-align: center; font-weight: bold;"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <div class="tab-buttons">
        <button class="tab-button" onclick="openTab(event, 'pago_en_lugar')">Pagos en Lugar (<?php echo count($pagos_lugar); ?>)</button>
        <button class="tab-button" onclick="openTab(event, 'transferencias')">Transferencias (<?php echo count($transferencias); ?>)</button>
    </div>

    <div class="table-container tab-content">
        
        <div id="pago_en_lugar" class="tab-pane">
            <h3>Boletos Pagados en Lugar</h3>
            <table class="data-table" id="table-pagos-lugar">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Evento</th>
                        <th>Cantidad</th>
                        <th>Total ($)</th>
                        <th>Fecha Compra</th>
                        <th>Estado Uso</th>
                        <th>QR Evento</th>
                        <th>QR Parqueadero</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pagos_lugar) > 0): ?>
                        <?php foreach ($pagos_lugar as $row): ?>
                            <tr>
                                <td><?php echo $row['com_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['usuario']); ?></td>
                                <td><?php echo htmlspecialchars($row['evento']); ?></td>
                                <td><?php echo $row['com_can_bol']; ?></td>
                                <td><?php echo number_format($row['com_pre_tot'], 2); ?></td>
                                <td><?php echo $row['com_fec']; ?></td>
                                <td>
                                    <?php if($row['com_act'] === 't'): ?>
                                        <span class="estado-activo">Activo</span>
                                    <?php else: ?>
                                        <span class="estado-usado">Usado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['com_qr'])): ?>
                                        <?php $ruta_evento = "../../" . $row['com_qr']; ?>
                                        <a href="<?php echo htmlspecialchars($ruta_evento); ?>" target="_blank">
                                            <img class="qr" src="<?php echo htmlspecialchars($ruta_evento); ?>" alt="QR Evento">
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['com_ruta_qr_parq'])): ?>
                                        <?php $ruta_parq = "../../" . $row['com_ruta_qr_parq']; ?>
                                        <a href="<?php echo htmlspecialchars($ruta_parq); ?>" target="_blank">
                                            <img class="qr" src="<?php echo htmlspecialchars($ruta_parq); ?>" alt="QR Parqueadero">
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="no-results">No se encontraron boletos con pago en lugar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="transferencias" class="tab-pane">
            <h3>Boletos con Pago por Transferencia (Requiere Validación)</h3>
            
            <form id="validation-form-individual" method="POST" action="">
                <input type="hidden" name="action" value="validar_individual">
                <input type="hidden" name="com_id" id="individual_com_id">
                <input type="hidden" name="nuevo_estado" id="individual_nuevo_estado">
            </form>

            <div class="validation-controls">
                <form method="POST" action="" onsubmit="return confirmValidarTodos()">
                    <input type="hidden" name="action" value="validar_todos">
                    <button type="submit" id="btn-validar-todos">
                        Validar Todos Pendientes
                    </button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="data-table" id="table-transferencias">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th class="editable-verif-col">Estado Verificación</th>
                            <th>Usuario</th>
                            <th>Evento</th>
                            <th>Cantidad</th>
                            <th>Total ($)</th>
                            <th>Fecha Compra</th>
                            <th>No. Transacción</th>
                            <th>Método Pago</th>
                            <th>Estado Uso</th>
                            <th>QR Evento</th>
                            <th>QR Parqueadero</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($transferencias) > 0): ?>
                            <?php foreach ($transferencias as $row): 
                                $com_id = $row['com_id'];
                                $estado_verif = $row['com_est_verif'];
                            ?>
                                <tr data-com-id="<?php echo $com_id; ?>" data-estado="<?php echo $estado_verif; ?>">
                                    <td><?php echo $com_id; ?></td>
                                    <td class="editable-verif-col">
                                        <?php if ($estado_verif === 'por_validar'): ?>
                                            <div class="action-buttons-wrapper">
                                                <button type="button" 
                                                    class="btn-validate-individual" 
                                                    onclick="confirmIndividualValidation(<?php echo $com_id; ?>, 'validado')">
                                                    Validar
                                                </button>
                                                <button type="button" 
                                                    class="btn-invalidate-individual" 
                                                    onclick="confirmIndividualValidation(<?php echo $com_id; ?>, 'transferencia_no_valida')">
                                                    Inválido
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <?php echo format_verif_state($estado_verif); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['usuario']); ?></td>
                                    <td><?php echo htmlspecialchars($row['evento']); ?></td>
                                    <td><?php echo $row['com_can_bol']; ?></td>
                                    <td><?php echo number_format($row['com_pre_tot'], 2); ?></td>
                                    <td><?php echo $row['com_fec']; ?></td>
                                    <td><?php echo htmlspecialchars($row['com_num_transf']); ?></td>
                                    <td><?php echo htmlspecialchars($row['com_met']); ?></td>
                                    <td>
                                        <?php if($row['com_act'] === 't'): ?>
                                            <span class="estado-activo">Activo</span>
                                        <?php else: ?>
                                            <span class="estado-usado">Usado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['com_qr'])): ?>
                                            <?php $ruta_evento = "../../" . $row['com_qr']; ?>
                                            <a href="<?php echo htmlspecialchars($ruta_evento); ?>" target="_blank">
                                                <img class="qr" src="<?php echo htmlspecialchars($ruta_evento); ?>" alt="QR Evento">
                                            </a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['com_ruta_qr_parq'])): ?>
                                            <?php $ruta_parq = "../../" . $row['com_ruta_qr_parq']; ?>
                                            <a href="<?php echo htmlspecialchars($ruta_parq); ?>" target="_blank">
                                                <img class="qr" src="<?php echo htmlspecialchars($ruta_parq); ?>" alt="QR Parqueadero">
                                            </a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="no-results">No se encontraron boletos con pago por transferencia.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    // Variables para almacenar las instancias de DataTables
    let dataTablePagosLugar = null;
    let dataTableTransferencias = null;

    // =================================== LÓGICA DE PESTAÑAS ===================================
    function openTab(evt, tabName) {
        let tabcontent = document.getElementsByClassName("tab-pane");
        for (let i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }

        let tablinks = document.getElementsByClassName("tab-button");
        for (let i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }

        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
        
        // Actualiza la URL sin recargar la página
        history.pushState(null, null, "?tab=" + tabName);
        
        // Inicializa o redibuja la tabla activa
        initializeDataTable(tabName);
    }
    
    function initializeDataTable(tabName) {
        const defaultOptions = {
            paging: true,
            searching: true,
            info: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' 
            }
        };

        if (tabName === 'pago_en_lugar') {
            const tableElement = document.getElementById('table-pagos-lugar');
            if (tableElement) {
                if (dataTablePagosLugar) {
                    // Si ya existe, solo la redibuja
                    dataTablePagosLugar.columns.adjust().draw();
                } else {
                    // Si no existe, la inicializa
                    dataTablePagosLugar = new DataTable(tableElement, {
                        ...defaultOptions,
                        ordering: true // El ordenamiento sí se puede dejar activo para esta tabla
                    });
                }
            }
        } 
        
        if (tabName === 'transferencias') {
            const tableElement = document.getElementById('table-transferencias');
            if (tableElement) {
                if (dataTableTransferencias) {
                    // Si ya existe, solo la redibuja
                    dataTableTransferencias.columns.adjust().draw();
                } else {
                    // Si no existe, la inicializa
                    dataTableTransferencias = new DataTable(tableElement, {
                        ...defaultOptions,
                        ordering: false // Desactivamos el ordenamiento en columnas con botones
                    });
                }
            }
        }
    }

    function setInitialTab() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab') || 'pago_en_lugar';
        
        const initialButton = document.querySelector(`.tab-button[onclick*='${tab}']`);

        if(initialButton) {
            // Activa visualmente la pestaña
            let tabcontent = document.getElementsByClassName("tab-pane");
            for (let i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }

            let tablinks = document.getElementsByClassName("tab-button");
            for (let i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            
            document.getElementById(tab).style.display = "block";
            initialButton.className += " active";
        }
        
        // Inicializar DataTables para la pestaña activa
        initializeDataTable(tab);
    }
    
    // =================================== LÓGICA DE VALIDACIÓN INDIVIDUAL (CONFIRMACIÓN) ===================================

    function confirmIndividualValidation(comId, estado) {
        let confirmText;
        if (estado === 'validado') {
            confirmText = `¿Está seguro de VALIDAR el boleto ID ${comId}?\n\nLa transferencia se considera verificada y válida.`;
        } else {
            confirmText = `¿Está seguro de marcar como INVÁLIDO el boleto ID ${comId}?\n\nADVERTENCIA: Esto también lo marcará como "Usado/Inactivo" (com_act = 'f') para que no pueda ser utilizado.`;
        }

        // Usamos SweetAlert
        if (window.Swal) {
            Swal.fire({
                title: 'Confirmar Acción',
                text: confirmText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, proceder',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeIndividualValidation(comId, estado);
                }
            });
        } else {
             if (confirm(confirmText)) {
                executeIndividualValidation(comId, estado);
            }
        }
    }

    function executeIndividualValidation(comId, nuevoEstado) {
        document.getElementById('individual_com_id').value = comId;
        document.getElementById('individual_nuevo_estado').value = nuevoEstado;
        document.getElementById('validation-form-individual').submit();
    }


    // =================================== LÓGICA DE VALIDACIÓN MASIVA (VALIDAR TODOS) ===================================

    function confirmValidarTodos() {
        const confirmText = "¿Está seguro de validar TODAS las transferencias pendientes (estado 'por validar')? Esta acción es masiva e inmediata.";
        
        if (window.Swal) {
            return Swal.fire({
                title: 'Confirmar Validación Masiva',
                text: confirmText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Validar Todo',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                // Para el formulario, devolvemos true/false si el usuario confirmó
                return result.isConfirmed; 
            });
        }
        
        // Fallback al confirm nativo
        return confirm(confirmText);  
    }

    document.addEventListener('DOMContentLoaded', () => {
        setInitialTab();
    });

</script>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
?>