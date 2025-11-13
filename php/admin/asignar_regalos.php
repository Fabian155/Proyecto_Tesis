<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

// ================== ACCIONES AJAX ==================

// 1. Listar Premios Asignados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'listar_asignaciones_totales') {
    $query = "
        SELECT 
            pa.pre_asg_id,
            p.pre_id,
            p.pre_nom,
            e.evt_tit AS nombre_evento,
            pa.pre_asg_fec_ent,
            pa.pre_asg_recogido,
            
            -- Lógica para la columna 'Recoge' y 'Contacto'
            CASE 
                WHEN pa.pre_asg_id_com IS NOT NULL THEN u.usr_nom || ' ' || u.usr_ape 
                WHEN pa.pre_asg_id_ven IS NOT NULL THEN vc.ven_cor_cli -- Correo en Recoge para Cajero
                ELSE 'Administrador' 
            END AS nombre_usuario,
            
            CASE 
                WHEN pa.pre_asg_id_com IS NOT NULL THEN u.usr_cor
                WHEN pa.pre_asg_id_ven IS NOT NULL THEN vc.ven_tel_cli -- Teléfono en Contacto para Cajero
                ELSE 'N/A' 
            END AS contacto,

            CASE 
                WHEN pa.pre_asg_id_com IS NOT NULL THEN 'App'
                WHEN pa.pre_asg_id_ven IS NOT NULL THEN 'Cajero'
                ELSE 'Manual' 
            END AS tipo_compra,
            
            COALESCE(pa.pre_asg_id_com, pa.pre_asg_id_ven) AS id_transaccion
            
        FROM tbl_premio_asignado pa
        JOIN tbl_premio p ON pa.pre_asg_id_pre = p.pre_id
        JOIN tbl_evento e ON p.pre_id_evt = e.evt_id
        LEFT JOIN tbl_compras_boletos cb ON pa.pre_asg_id_com = cb.com_id
        LEFT JOIN tbl_usuario u ON cb.com_id_usr = u.usr_id
        LEFT JOIN tbl_ventas_cajero vc ON pa.pre_asg_id_ven = vc.ven_id
        ORDER BY pa.pre_asg_id DESC
    ";
    $res = pg_query($conn, $query);
    $asignaciones = pg_fetch_all($res) ?: [];
    echo json_encode($asignaciones);
    exit;
}

// 2. Marcar como Entregado (Recogido = TRUE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'marcar_entregado') {
    $pre_asg_id = intval($_POST['pre_asg_id']);

    $query = "UPDATE tbl_premio_asignado 
              SET pre_asg_recogido = TRUE, 
                  pre_asg_fec_ent = CURRENT_TIMESTAMP 
              WHERE pre_asg_id = $pre_asg_id AND pre_asg_recogido = FALSE"; // Evita actualizar si ya está TRUE
              
    $res = pg_query($conn, $query);

    echo json_encode($res ? ["success"=>true, "mensaje"=>"Premio marcado como Entregado."] : ["success"=>false, "mensaje"=>"Error al marcar como entregado: ".pg_last_error($conn)]);
    exit;
}

// ================== HTML/CSS/JAVASCRIPT ==================

ob_start();
?>
<style>
    /* Estilos de botones de filtro */
    .filter-buttons-row {
        display: flex; flex-wrap: wrap; justify-content: center; gap: 10px;
        margin: 20px 0 30px; padding: 15px; border-radius: 8px;
        background-color: #f7f9fb; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .tab-button { 
        padding: 10px 15px; border: 1px solid #ccc; border-radius: 6px; 
        background-color: #ecf0f1; cursor: pointer; font-weight: bold; 
        transition: all 0.3s; display: flex; align-items: center; gap: 5px;
    }
    .tab-button:hover { background-color: #e0e6e8; }
    .tab-button.active { 
        background-color: #34495e; color: white; border-color: #34495e;
    }
    
    /* Botones de Acción Masiva */
    .btn-massive {
        padding: 10px 15px; border: none; border-radius: 6px; color: white;
        cursor: pointer; transition: background-color 0.2s; font-weight: bold;
        display: flex; align-items: center; gap: 5px; margin-left: 10px;
    }
    .btn-massive.collect { background-color: #2ecc71; }
    .btn-massive.collect:hover { background-color: #27ad60; }
    
    /* Estilos de Tabla */
    /* **ATENCIÓN: Se añade la clase 'data-table' para DataTables** */
    .data-table { width: 100%; border-collapse: collapse; min-width: 900px; margin: 20px auto; font-size: 13px; }
    .data-table th, .data-table td { border: 1px solid #f0f0f0; padding: 10px; text-align: left; }
    .data-table th { 
        background-color: #34495e; color: white; font-weight: 600;
        text-transform: uppercase; font-size: 12px;
    }
    .data-table tr:nth-child(even) { background-color: #f9f9f9; }
    .data-table tr:hover { background-color: #f1f7fc; }
    .tabla-wrapper { overflow-x: auto; max-width: 1200px; margin: 20px auto; }
    
    /* Estados y Botones de Alternancia */
    .status-label { 
        padding: 5px 10px; border-radius: 4px; font-weight: bold; 
        text-align: center; display: inline-block;
    }
    .status-si { background-color: #d4edda; color: #155724; } /* Verde claro: Entregado */
    .status-no { background-color: #f8d7da; color: #721c24; } /* Rojo claro: Pendiente */
    
    .btn-marcar-entregado { 
        background-color: #2ecc71; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold;
    }
    .btn-marcar-entregado:hover { background-color: #27ad60; }

</style>

<div style="text-align:center;">
    <h1>Panel de Confirmación de Entrega <i class="fas fa-check-circle" style="color:#2ecc71;"></i></h1>
    <p style="color:#555; margin-bottom: 30px;">
        Utiliza el botón **Entregado** para confirmar que el premio fue retirado físicamente por el cliente.
    </p>

    <div id="mensaje" style="min-height:20px; color:#27ae60; margin:10px 0; font-weight:bold;"></div>

    
    <div style="margin:25px 0;">
        <p style="font-weight:bold;"><i class="fas fa-filter"></i> 1. Filtra por Estado y Origen:</p>
        <div id="filter_buttons" class="filter-buttons-row">
            
            <button class="tab-button active" onclick="filtrarAsignaciones('Todos', 'PENDIENTE')">
                <i class="fas fa-clock"></i> **Pendientes** de Retirar
            </button>
            <button class="tab-button" onclick="filtrarAsignaciones('Todos', 'RECOGIDO')">
                <i class="fas fa-check-circle"></i> **Ya Entregados**
            </button>
            <button class="tab-button" onclick="filtrarAsignaciones('Todos', 'TODOS')">
                <i class="fas fa-list"></i> Mostrar Todos
            </button>
            <div style="border-left: 1px solid #ccc; height: 30px;"></div> <button class="tab-button" onclick="filtrarAsignaciones('App', null)">
                <i class="fas fa-mobile-alt"></i> Compra en **APP**
            </button>
            <button class="tab-button" onclick="filtrarAsignaciones('Cajero', null)">
                <i class="fas fa-cash-register"></i> Compra en **SITIO**
            </button>
        </div>
    </div>
    
    <div id="massive_actions" class="filter-buttons-row" style="margin-top: 5px; justify-content: flex-end;">
        <p style="font-weight:bold; margin: 0 10px;"><i class="fas fa-bolt"></i> 2. Acción Masiva (Pendientes visibles):</p>
        
        <button id="btn_marcar_todos_recogidos" class="btn-massive collect" style="display:none;" onclick="accionMasiva('marcar_entregado')">
            <i class="fas fa-dolly"></i> Marcar **TODOS** Entregados
        </button>
    </div>

    <div class="tabla-wrapper">
        <h3 id="titulo_tabla_recogido">Premios Asignados (Cargando...)</h3>
        <table class="data-table" id="tabla-premios-asignados">
            <thead>
                <tr>
                    <th style="width: 5%;">ID Asg.</th>
                    <th style="width: 15%;">Evento</th>
                    <th style="width: 15%;">Premio</th>
                    <th style="width: 15%;">Recoge (Nombre/Email)</th>
                    <th style="width: 10%;">Contacto (Email/Tel)</th>
                    <th style="width: 10%;">Origen</th>
                    <th style="width: 10%;">Estado Actual</th>
                    <th style="width: 20%;">Acción</th>
                </tr>
            </thead>
            <tbody id="tabla_asignados_pendientes">
                <tr><td colspan="8">Cargando premios...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let asignacionesMaestra = []; // Lista completa de todos los premios asignados
let asignacionesFiltradas = []; // Lista actualmente mostrada en la tabla
let dataTablePremios = null; // **Variable para la instancia de DataTables**
let currentFilter = {
    origen: 'Todos',
    estado: 'PENDIENTE' 
};

// =================================== LÓGICA DE CARGA Y FILTRO ===================================

function cargarAsignacionesMaestra() {
    document.getElementById('mensaje').innerHTML = '<i class="fas fa-sync fa-spin"></i> Cargando todos los premios asignados...';
    
    let form = new FormData();
    form.append('accion', 'listar_asignaciones_totales');
    
    fetch('', {method: 'POST', body: form})
        .then(res => res.json())
        .then(data => {
            asignacionesMaestra = data; 
            document.getElementById('mensaje').innerHTML = `<i class="fas fa-list-alt"></i> Premios asignados cargados: **${data.length}**.`;
            // **IMPORTANTE: Llamar a filtrarAsignaciones que llama a renderizarTabla**
            filtrarAsignaciones(null, null); 
        })
        .catch(error => {
            console.error('Error al cargar asignaciones:', error);
            document.getElementById('mensaje').innerHTML = '<i class="fas fa-times-circle" style="color:red;"></i> Error al cargar las asignaciones.';
        });
}

function filtrarAsignaciones(origen, estado) {
    // 1. Actualizar filtros
    if (origen) currentFilter.origen = origen;
    if (estado) currentFilter.estado = estado;

    // 2. Filtrar la lista maestra
    let filteredList = asignacionesMaestra.filter(row => {
        const matchesOrigen = currentFilter.origen === 'Todos' || row.tipo_compra === currentFilter.origen;
        
        let matchesEstado = true;
        if (currentFilter.estado === 'PENDIENTE') {
            matchesEstado = row.pre_asg_recogido === 'f'; 
        } else if (currentFilter.estado === 'RECOGIDO') {
            matchesEstado = row.pre_asg_recogido === 't';
        }
        
        return matchesOrigen && matchesEstado;
    });

    asignacionesFiltradas = filteredList;
    renderizarTabla(asignacionesFiltradas);
    actualizarBotonesFiltro();
    actualizarBotonesMasivos();
}

function actualizarBotonesFiltro() {
    // 3. Actualizar la interfaz de filtros
    document.querySelectorAll('#filter_buttons .tab-button').forEach(btn => btn.classList.remove('active'));
    
    // Activar botón de ESTADO (filtrar por el valor del estado)
    document.querySelector(`#filter_buttons button[onclick*="'${currentFilter.estado}'"]`)?.classList.add('active');
    
    // Activar botón de ORIGEN (filtrar por el valor del origen si no es 'Todos')
    if (currentFilter.origen !== 'Todos') {
        document.querySelector(`#filter_buttons button[onclick*="'${currentFilter.origen}', null"]`)?.classList.add('active');
    }
}


function renderizarTabla(data) {
    const tablaElement = document.getElementById('tabla-premios-asignados');
    const tablaBody = document.getElementById('tabla_asignados_pendientes');
    let html = '';
    const totalMostrado = data.length;
    
    let estadoDisplay = currentFilter.estado === 'PENDIENTE' ? 'Pendientes de Retirar' : (currentFilter.estado === 'RECOGIDO' ? 'Entregados' : 'Todos');
    let origenDisplay = currentFilter.origen === 'Todos' ? 'Todos' : (currentFilter.origen === 'App' ? 'App' : 'Sitio');

    document.getElementById('titulo_tabla_recogido').innerText = `${estadoDisplay} (${origenDisplay}) - Total: ${totalMostrado}`;

    // 1. **Destruir DataTables si ya existe**
    if (dataTablePremios) {
        dataTablePremios.destroy();
        dataTablePremios = null; // Limpiar la variable
    }


    if (totalMostrado > 0) {
        data.forEach(row => {
            const esRecogido = row.pre_asg_recogido === 't';
            let estadoHtml = '';
            let accionHtml = '';
            
            if (esRecogido) {
                // Si ya fue recogido, solo mostramos el label del estado finalizado
                estadoHtml = `<span class="status-label status-si">ENTREGADO</span>`;
                accionHtml = `<span style="color:#2ecc71; font-weight:bold;"><i class="fas fa-check"></i> FINALIZADO</span>`;
            } else {
                // Si está pendiente, mostramos el label y el botón de acción
                estadoHtml = `<span class="status-label status-no">PENDIENTE</span>`;
                accionHtml = `
                    <button onclick="manejarAccion(${row.pre_asg_id}, 'marcar_entregado', this)" class="btn-marcar-entregado" title="Marcar como entregado al cliente">
                        <i class="fas fa-clipboard-check"></i> Entregado
                    </button>
                `;
            }

            html += `
                <tr id="row_asignacion_${row.pre_asg_id}" data-id="${row.pre_asg_id}" data-recogido="${esRecogido}">
                    <td>${row.pre_asg_id}</td>
                    <td>${row.nombre_evento || 'N/A'}</td>
                    <td class="premio-asignado">${row.pre_nom}</td>
                    <td>${row.nombre_usuario || 'N/A'}</td>
                    <td>${row.contacto || 'N/A'}</td>
                    <td>${row.tipo_compra} (ID: ${row.id_transaccion || 'N/A'})</td>
                    <td id="estado_col_${row.pre_asg_id}">${estadoHtml}</td>
                    <td id="accion_col_${row.pre_asg_id}">${accionHtml}</td>
                </tr>
            `;
        });
    } else {
        html = `<tr><td colspan="8" style="text-align:center; color:#888; font-weight:bold;">No se encontraron premios con los filtros aplicados.</td></tr>`;
    }

    tablaBody.innerHTML = html;
    
    // 2. **Inicializar DataTables**
    if (totalMostrado > 0) {
        dataTablePremios = new DataTable(tablaElement, {
            paging: true,
            searching: true,
            info: true,
            ordering: true, // Se deja el ordenamiento
            order: [[0, 'desc']], // Ordenar por ID de asignación (columna 0) descendente
            language: {
                url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' 
            }
        });
    }
}

function actualizarBotonesMasivos() {
    const btnRecogido = document.getElementById('btn_marcar_todos_recogidos');
    
    // Contar solo los pendientes visibles para la acción masiva
    const pendientes_visibles = asignacionesFiltradas.filter(a => a.pre_asg_recogido === 'f').length;

    // Botón para Marcar Entregado (sobre pendientes)
    if (pendientes_visibles > 0 && currentFilter.estado === 'PENDIENTE') {
        btnRecogido.style.display = 'flex';
        btnRecogido.innerHTML = `<i class="fas fa-dolly"></i> Marcar **${pendientes_visibles}** Entregados`;
    } else {
        btnRecogido.style.display = 'none';
    }
}


// =================================== ACCIONES (INDIVIDUAL Y MASIVA) ===================================

function manejarAccion(pre_asg_id, accion, buttonElement) {
    // En esta versión, 'accion' solo puede ser 'marcar_entregado'
    if (accion !== 'marcar_entregado') return; 

    const confirmMsg = `¿Confirmar la entrega física y marcar ID ${pre_asg_id} como ENTREGADO?`;

    // **Usar SweetAlert si está disponible**
    if (window.Swal) {
        Swal.fire({
            title: 'Confirmar Entrega',
            text: confirmMsg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2ecc71',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, Entregado',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Proceder con la acción
                executeAccionIndividual(pre_asg_id, accion, buttonElement);
            }
        });
    } else {
         if (confirm(confirmMsg)) {
            executeAccionIndividual(pre_asg_id, accion, buttonElement);
        }
    }
}

function executeAccionIndividual(pre_asg_id, accion, buttonElement) {
    const actionCol = document.getElementById(`accion_col_${pre_asg_id}`);
    const originalHTML = actionCol.innerHTML;
    actionCol.innerHTML = '<i class="fas fa-sync fa-spin"></i> Procesando...';

    let form = new FormData();
    form.append('accion', accion);
    form.append('pre_asg_id', pre_asg_id);
    
    fetch('', {method: 'POST', body: form})
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('mensaje').innerHTML = `<i class="fas fa-check-circle" style="color:green;"></i> ${data.mensaje}`;
                
                // Actualizar la lista Maestra local
                const index = asignacionesMaestra.findIndex(a => a.pre_asg_id == pre_asg_id);
                if (index !== -1) {
                    asignacionesMaestra[index].pre_asg_recogido = 't';
                }
                // Refrescar la vista: DataTables se destruye y se vuelve a aplicar
                filtrarAsignaciones(null, null);

            } else {
                document.getElementById('mensaje').innerHTML = `<i class="fas fa-times-circle" style="color:red;"></i> Error al procesar ID ${pre_asg_id}: ${data.mensaje}`;
                actionCol.innerHTML = originalHTML; // Restaurar botón
            }
        })
        .catch(error => {
             document.getElementById('mensaje').innerHTML = `<i class="fas fa-times-circle" style="color:red;"></i> Error de red al procesar ID ${pre_asg_id}.`;
             actionCol.innerHTML = originalHTML; // Restaurar botón
        });
}


async function accionMasiva(accion) {
    if (accion !== 'marcar_entregado') return; // Solo acción de entrega

    const targetState = 'Entregados';
    
    // Obtener IDs de los elementos PENDIENTES visibles
    let ids_a_procesar = asignacionesFiltradas
        .filter(a => a.pre_asg_recogido === 'f')
        .map(a => a.pre_asg_id);

    if (ids_a_procesar.length === 0) {
        alert(`No hay premios pendientes de recoger en la lista para marcar como ${targetState}.`);
        return;
    }

    // **Usar SweetAlert para confirmación masiva**
    let confirmation = true;
    if (window.Swal) {
        const result = await Swal.fire({
            title: 'Confirmar Acción Masiva',
            text: `¿Desea marcar los ${ids_a_procesar.length} premios pendientes visibles como **${targetState}**? Esta acción es irreversible.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2ecc71',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, Marcar Todos',
            cancelButtonText: 'Cancelar'
        });
        confirmation = result.isConfirmed;
    } else {
        confirmation = confirm(`¿Desea marcar los ${ids_a_procesar.length} premios pendientes visibles como **${targetState}**?`);
    }

    if (!confirmation) {
        return;
    }

    const btnMasivo = document.getElementById('btn_marcar_todos_recogidos');
    btnMasivo.disabled = true;
    
    let procesadosCount = 0;
    
    for (const pre_asg_id of ids_a_procesar) {
        const accionCol = document.getElementById(`accion_col_${pre_asg_id}`);
        if (accionCol) accionCol.innerHTML = '<i class="fas fa-sync fa-spin"></i> Procesando...';

        let form = new FormData();
        form.append('accion', accion);
        form.append('pre_asg_id', pre_asg_id);

        try {
            const res = await fetch('', {method: 'POST', body: form});
            const data = await res.json();

            if (data.success) {
                procesadosCount++;
                document.getElementById('mensaje').innerHTML = `<i class="fas fa-check-circle" style="color:green;"></i> Proceso masivo... ${procesadosCount} de ${ids_a_procesar.length} completados.`;
                
                // Actualizar la lista Maestra local
                const index = asignacionesMaestra.findIndex(a => a.pre_asg_id == pre_asg_id);
                if (index !== -1) {
                    asignacionesMaestra[index].pre_asg_recogido = 't';
                }
            } else {
                if (accionCol) accionCol.innerHTML = `<span style="color:red;">Error</span>`;
            }
        } catch (error) {
            if (accionCol) accionCol.innerHTML = `<span style="color:red;">Error de red</span>`;
        }
    }
    
    btnMasivo.disabled = false;
    document.getElementById('mensaje').innerHTML = `<i class="fas fa-check-circle" style="color:green;"></i> Proceso masivo finalizado. **${procesadosCount} premios marcados como ${targetState}**.`;
    
    // Refrescar la vista: DataTables se destruye y se vuelve a aplicar
    filtrarAsignaciones(null, null);
}


// Carga inicial al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    cargarAsignacionesMaestra(); 
});
</script>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
?>