<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

$base_dir_imagenes_db = 'apis/imagenes/pagina/';
$dir_nosotros = $base_dir_imagenes_db . 'nosotros/';

// ================== FUNCIONES DE UTILIDAD (SIN CAMBIOS) ==================

/**
 * Maneja la subida de un archivo de imagen al servidor.
 * Devuelve la ruta relativa para la DB o NULL si falla o no se sube un archivo nuevo.
 */
function manejar_subida_imagen($conn, $file_data, $directorio_destino_db, $ruta_anterior_db = null) {
    // Usamos el root del proyecto para construir la ruta absoluta
    $raiz_proyecto = realpath(__DIR__ . '/../../'); 
    
    if (isset($file_data) && $file_data['error'] === UPLOAD_ERR_OK) {
        // Validación de tipo MIME (opcional pero recomendado)
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file_data['type'], $tipos_permitidos)) {
            return null; 
        }

        // Generar un nombre único para el archivo
        $nombreArchivo = time() . "_" . basename($file_data['name']);
        $ruta_destino_absoluta = $raiz_proyecto . '/' . $directorio_destino_db . $nombreArchivo;
        $directorio_absoluto = dirname($ruta_destino_absoluta);
        
        // Crear el directorio si no existe
        if (!is_dir($directorio_absoluto)) {
            mkdir($directorio_absoluto, 0777, true);
        }

        if (move_uploaded_file($file_data['tmp_name'], $ruta_destino_absoluta)) {
            // Eliminar imagen anterior
            if ($ruta_anterior_db) {
                $ruta_ant_absoluta = $raiz_proyecto . '/' . $ruta_anterior_db;
                if (file_exists($ruta_ant_absoluta)) {
                    @unlink($ruta_ant_absoluta); 
                }
            }
            // Devolver la ruta relativa para la base de datos
            return $directorio_destino_db . $nombreArchivo;
        }
        return null;
    }
    return $ruta_anterior_db; 
}

// -------------------- LECTURA (Listar - Solo obtiene el registro único) (SIN CAMBIOS) --------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'listar') {
    $query = "SELECT * FROM tbl_nosotros LIMIT 1";
    $res = pg_query($conn, $query);
    $data = pg_fetch_assoc($res);
    echo json_encode($data ? $data : ["success" => false, "mensaje" => "No existe registro 'Nosotros'."]);
    exit;
}

// -------------------- CREAR / ACTUALIZAR (SIN CAMBIOS) --------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'crear' || ($_POST['accion'] ?? '') === 'actualizar')) {
    $id = intval($_POST['nos_id'] ?? 0); 
    $res_db = false;
    $mensaje = "";

    pg_query($conn, "BEGIN");

    try {
        // Validación básica
        if (empty($_POST['nos_nom_emp']) || empty($_POST['nos_men_ini'])) {
            throw new Exception("El nombre de la empresa y el mensaje de inicio son obligatorios.");
        }
        
        // Limpieza de datos
        $nombre_emp = pg_escape_string($conn, $_POST['nos_nom_emp'] ?? '');
        $historia = pg_escape_string($conn, $_POST['nos_hist'] ?? '');
        $mision = pg_escape_string($conn, $_POST['nos_mis'] ?? '');
        $vision = pg_escape_string($conn, $_POST['nos_vis'] ?? '');
        $celular = pg_escape_string($conn, $_POST['nos_cel'] ?? '');
        $direccion = pg_escape_string($conn, $_POST['nos_dir'] ?? '');
        $correo = pg_escape_string($conn, $_POST['nos_correo'] ?? '');
        $mensaje_ini = pg_escape_string($conn, $_POST['nos_men_ini'] ?? '');
        $link_app = pg_escape_string($conn, $_POST['nos_link_app'] ?? '');
        $num_cuenta = pg_escape_string($conn, $_POST['nos_num_cuenta'] ?? '');
        $nom_banco = pg_escape_string($conn, $_POST['nos_nom_banco'] ?? '');
        $estado = pg_escape_string($conn, $_POST['nos_est'] ?? 'desactivado');
        $url_app_ios = pg_escape_string($conn, $_POST['nos_url_app_ios'] ?? '');
        
        // Obtener rutas de imágenes antiguas 
        $query_old_img = "SELECT nos_img_hist, nos_img_mis, nos_img_vis, nos_logo_banco, nos_url_img_android, nos_img_url_ios FROM tbl_nosotros WHERE nos_id = $id";
        $res_old_img = pg_query($conn, $query_old_img);
        $old_img_data = pg_fetch_assoc($res_old_img) ?: []; // Manejar si no hay datos
        

        // ================== MANEJO DE LAS 6 IMÁGENES ==================
        $img_hist = manejar_subida_imagen($conn, $_FILES['nos_img_hist'] ?? null, $dir_nosotros, $old_img_data['nos_img_hist'] ?? null);
        $img_mis = manejar_subida_imagen($conn, $_FILES['nos_img_mis'] ?? null, $dir_nosotros, $old_img_data['nos_img_mis'] ?? null);
        $img_vis = manejar_subida_imagen($conn, $_FILES['nos_img_vis'] ?? null, $dir_nosotros, $old_img_data['nos_img_vis'] ?? null);
        $logo_banco = manejar_subida_imagen($conn, $_FILES['nos_logo_banco'] ?? null, $dir_nosotros, $old_img_data['nos_logo_banco'] ?? null);
        $url_img_android_db = manejar_subida_imagen($conn, $_FILES['nos_url_img_android'] ?? null, $dir_nosotros, $old_img_data['nos_url_img_android'] ?? null);
        $img_url_ios_db = manejar_subida_imagen($conn, $_FILES['nos_img_url_ios'] ?? null, $dir_nosotros, $old_img_data['nos_img_url_ios'] ?? null);
        
        // =============================================================
        
        // Formatear rutas para la DB (NULL si no hay valor)
        $img_hist_db = $img_hist ? "'" . pg_escape_string($conn, $img_hist) . "'" : "NULL";
        $img_mis_db = $img_mis ? "'" . pg_escape_string($conn, $img_mis) . "'" : "NULL";
        $img_vis_db = $img_vis ? "'" . pg_escape_string($conn, $img_vis) . "'" : "NULL";
        $logo_banco_db = $logo_banco ? "'" . pg_escape_string($conn, $logo_banco) . "'" : "NULL";
        $url_img_android_sql = $url_img_android_db ? "'" . pg_escape_string($conn, $url_img_android_db) . "'" : "NULL";
        $img_url_ios_sql = $img_url_ios_db ? "'" . pg_escape_string($conn, $img_url_ios_db) . "'" : "NULL";
        
        // Determinar si es UPDATE o INSERT
        $existe_registro = ($id > 0 && pg_num_rows(pg_query($conn, "SELECT nos_id FROM tbl_nosotros WHERE nos_id=$id")) > 0);

        if ($existe_registro) {
            $query_update_parts = [
                "nos_nom_emp='$nombre_emp'", "nos_hist='$historia'", "nos_mis='$mision'", "nos_vis='$vision'",
                "nos_cel='$celular'", "nos_dir='$direccion'", "nos_correo='$correo'", "nos_men_ini='$mensaje_ini'",
                "nos_link_app='$link_app'", "nos_num_cuenta='$num_cuenta'", "nos_nom_banco='$nom_banco'", "nos_est='$estado'",
                "nos_img_hist=$img_hist_db", "nos_img_mis=$img_mis_db", "nos_img_vis=$img_vis_db", "nos_logo_banco=$logo_banco_db",
                "nos_url_img_android=$url_img_android_sql", 
                "nos_url_app_ios='$url_app_ios'", 
                "nos_img_url_ios=$img_url_ios_sql", 
                "nos_fec_edi=CURRENT_TIMESTAMP"
            ];
            
            $query = "UPDATE tbl_nosotros SET " . implode(', ', $query_update_parts) . " WHERE nos_id=$id";
            $res_db = pg_query($conn, $query);
            $mensaje = "Información 'Nosotros' actualizada correctamente.";
        } else {
            // INSERT (se asume que solo debe haber 1 registro)
            $query = "INSERT INTO tbl_nosotros (nos_nom_emp, nos_hist, nos_img_hist, nos_mis, nos_img_mis, nos_vis, nos_img_vis, nos_cel, nos_dir, nos_correo, nos_men_ini, nos_link_app, nos_num_cuenta, nos_nom_banco, nos_logo_banco, nos_est, nos_url_img_android, nos_url_app_ios, nos_img_url_ios)
            VALUES ('$nombre_emp', '$historia', $img_hist_db, '$mision', $img_mis_db, '$vision', $img_vis_db, '$celular', '$direccion', '$correo', '$mensaje_ini', '$link_app', '$num_cuenta', '$nom_banco', $logo_banco_db, '$estado', $url_img_android_sql, '$url_app_ios', $img_url_ios_sql)";
            
            $res_db = pg_query($conn, $query);
            $mensaje = "Información 'Nosotros' creada correctamente.";
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

// ================== HTML y JAVASCRIPT (Frontend con Plantilla) ==================

ob_start(); 
?>

<div class="container-fluid py-4">
    <h2 class="mb-4">Gestión de Información de "Nosotros"</h2>

    <form id="formNosotros" enctype="multipart/form-data" class="bg-white p-4 shadow-sm rounded">
        <input type="hidden" id="nos_id" name="nos_id" value="0">
        <input type="hidden" name="tipo" value="nosotros">
        
        <ul class="nav nav-tabs" id="nosotrosTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-tab-pane" type="button" role="tab" aria-controls="general-tab-pane" aria-selected="true">Info General</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="filosofia-tab" data-bs-toggle="tab" data-bs-target="#filosofia-tab-pane" type="button" role="tab" aria-controls="filosofia-tab-pane" aria-selected="false">Misión, Visión, Historia</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="contacto-app-tab" data-bs-toggle="tab" data-bs-target="#contacto-app-tab-pane" type="button" role="tab" aria-controls="contacto-app-tab-pane" aria-selected="false">Contacto y Apps</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bancario-tab" data-bs-toggle="tab" data-bs-target="#bancario-tab-pane" type="button" role="tab" aria-controls="bancario-tab-pane" aria-selected="false">Bancario</button>
            </li>
        </ul>

        <div class="tab-content pt-3" id="nosotrosTabsContent">
            
            <div class="tab-pane fade show active" id="general-tab-pane" role="tabpanel" aria-labelledby="general-tab" tabindex="0">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nos_nom_emp" class="form-label fw-bold">Nombre de la Empresa:</label>
                        <input type="text" id="nos_nom_emp" name="nos_nom_emp" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="nos_est" class="form-label fw-bold">Estado (General de la sección Nosotros):</label>
                        <select id="nos_est" name="nos_est" class="form-select">
                            <option value="activo">Activo</option>
                            <option value="desactivado">Desactivado</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="nos_men_ini" class="form-label fw-bold">Mensaje de Inicio (Banner Principal):</label>
                        <textarea id="nos_men_ini" name="nos_men_ini" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="filosofia-tab-pane" role="tabpanel" aria-labelledby="filosofia-tab" tabindex="0">
                <div class="row g-4">
                    <div class="col-md-12">
                        <label for="nos_hist" class="form-label fw-bold">Historia:</label>
                        <textarea id="nos_hist" name="nos_hist" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label for="nos_img_hist" class="form-label fw-bold">Imagen Historia:</label>
                        <input type="file" id="nos_img_hist" name="nos_img_hist" accept="image/*" class="file-input-nosotros">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="nos_mis" class="form-label fw-bold">Misión:</label>
                        <textarea id="nos_mis" name="nos_mis" class="form-control" rows="4"></textarea>
                        <label for="nos_img_mis" class="form-label mt-3 fw-bold">Imagen Misión:</label>
                        <input type="file" id="nos_img_mis" name="nos_img_mis" accept="image/*" class="file-input-nosotros">
                    </div>

                    <div class="col-md-6">
                        <label for="nos_vis" class="form-label fw-bold">Visión:</label>
                        <textarea id="nos_vis" name="nos_vis" class="form-control" rows="4"></textarea>
                        <label for="nos_img_vis" class="form-label mt-3 fw-bold">Imagen Visión:</label>
                        <input type="file" id="nos_img_vis" name="nos_img_vis" accept="image/*" class="file-input-nosotros">
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="contacto-app-tab-pane" role="tabpanel" aria-labelledby="contacto-app-tab" tabindex="0">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="nos_cel" class="form-label fw-bold">Celular:</label>
                        <input type="text" id="nos_cel" name="nos_cel" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="nos_correo" class="form-label fw-bold">Correo:</label>
                        <input type="email" id="nos_correo" name="nos_correo" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="nos_link_app" class="form-label fw-bold">Link Apps (URL general/Link árbol):</label>
                        <input type="url" id="nos_link_app" name="nos_link_app" class="form-control">
                    </div>
                    <div class="col-12">
                        <label for="nos_dir" class="form-label fw-bold">Dirección:</label>
                        <input type="text" id="nos_dir" name="nos_dir" class="form-control">
                    </div>

                    <hr class="mt-4 mb-4">
                    <h5 class="fw-bold">Configuración de Links/Imágenes de Apps Específicas</h5>
                    
                    <div class="col-md-6">
                        <label for="nos_url_app_ios" class="form-label fw-bold">URL de Descarga/Acceso para iOS:</label>
                        <input type="url" id="nos_url_app_ios" name="nos_url_app_ios" class="form-control mb-3">
                        <label for="nos_img_url_ios" class="form-label fw-bold">Imagen iOS (QR/Botón):</label>
                        <input type="file" id="nos_img_url_ios" name="nos_img_url_ios" accept="image/*" class="file-input-nosotros">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="nos_url_img_android" class="form-label fw-bold">Imagen Android (QR/Botón):</label>
                        <div class="mb-3"></div> 
                        <input type="file" id="nos_url_img_android" name="nos_url_img_android" accept="image/*" class="file-input-nosotros">
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="bancario-tab-pane" role="tabpanel" aria-labelledby="bancario-tab" tabindex="0">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nos_num_cuenta" class="form-label fw-bold">Número de Cuenta:</label>
                        <input type="text" id="nos_num_cuenta" name="nos_num_cuenta" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="nos_nom_banco" class="form-label fw-bold">Nombre del Banco:</label>
                        <input type="text" id="nos_nom_banco" name="nos_nom_banco" class="form-control">
                    </div>
                    <div class="col-12">
                        <label for="nos_logo_banco" class="form-label fw-bold">Logo del Banco:</label>
                        <input type="file" id="nos_logo_banco" name="nos_logo_banco" accept="image/*" class="file-input-nosotros">
                    </div>
                </div>
            </div>

        </div> <div class="row mt-4">
            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                    <i class="bi bi-save"></i> Guardar Toda la Información
                </button>
            </div>
        </div>
    </form>
</div>

<style>
    /* Estilos de espaciado y ajuste, usando clases de Bootstrap */
    .container-fluid { max-width: 900px; } /* Limitar el ancho para que no sea toda la pantalla */
    
    .file-input-nosotros + .file-input-container {
        margin-top: 5px; 
    }
    
    /* Mejoras visuales para el área de contenido */
    .tab-content {
        min-height: 400px; /* Asegurar espacio suficiente */
    }

    /* Ocultar los previews antiguos */
    .img-preview { display: none !important; }

    /* Estilo para el botón principal de guardar */
    #submitBtn {
        font-size: 1.1rem;
        padding: 10px 20px;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 

<script>
    // Configuración base para todos los fileinputs
    const fileInputConfig = {
        showUpload: false,
        dropZoneEnabled: false,
        language: "es",
        allowedFileExtensions: ["jpg", "jpeg", "png", "webp", "gif"],
        maxFileSize: 2048, // KB (2MB)
        browseClass: "btn btn-outline-secondary",
        msgPlaceholder: "Seleccionar imagen...",
        previewFileType: "image",
        removeClass: "btn btn-outline-danger",
        removeLabel: "Eliminar",
        removeIcon: '<i class="bi-trash"></i> ' // Asumo que tienes bootstrap icons
    };
    
    // Lista de IDs de los inputs de imagen
    const imageInputIds = [
        'nos_img_hist', 'nos_img_mis', 'nos_img_vis', 
        'nos_url_img_android', 'nos_img_url_ios', 'nos_logo_banco'
    ];
    
    // Función para inicializar todos los fileinputs
    function initializeFileInputs() {
        imageInputIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                // Destruir la instancia anterior si existe (importante para la recarga)
                try {
                    $(element).fileinput('destroy');
                } catch (e) { /* Ignorar error si no existe */ }

                // Inicializar el nuevo fileinput
                $(element).fileinput({
                    ...fileInputConfig,
                    initialPreview: [], 
                    initialPreviewConfig: [],
                    overwriteInitial: true,
                });
            }
        });
    }

    /**
     * Muestra mensajes de SweetAlert2 tipo toast (notificación)
     * @param {object} data - Objeto con { success: boolean, mensaje: string }
     */
    function mostrarMensaje(data) {
        Swal.fire({
            icon: data.success ? 'success' : 'error',
            title: data.success ? 'Éxito' : 'Error',
            text: data.mensaje,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000
        });
    }

    /**
     * Cargar datos de "Nosotros" (Equivalente al listar/obtener)
     */
    function cargarNosotros() {
        let form = new FormData();
        form.append('accion', 'listar');

        fetch('', { method: 'POST', body: form })
            .then(res => {
                if (!res.ok) throw new Error('Respuesta de red no satisfactoria');
                return res.json();
            })
            .then(data => {
                
                // 1. Inicializar o Re-inicializar fileinputs antes de cargar datos
                initializeFileInputs();

                if (data.nos_id) {
                    // Cargar ID para saber si es actualización
                    document.getElementById('nos_id').value = data.nos_id;
                    
                    // Asignación de valores a los campos
                    document.getElementById('nos_nom_emp').value = data.nos_nom_emp || '';
                    document.getElementById('nos_hist').value = data.nos_hist || '';
                    document.getElementById('nos_mis').value = data.nos_mis || '';
                    document.getElementById('nos_vis').value = data.nos_vis || '';
                    document.getElementById('nos_cel').value = data.nos_cel || '';
                    document.getElementById('nos_dir').value = data.nos_dir || '';
                    document.getElementById('nos_correo').value = data.nos_correo || '';
                    document.getElementById('nos_men_ini').value = data.nos_men_ini || '';
                    document.getElementById('nos_link_app').value = data.nos_link_app || '';
                    
                    document.getElementById('nos_url_app_ios').value = data.nos_url_app_ios || '';

                    document.getElementById('nos_num_cuenta').value = data.nos_num_cuenta || '';
                    document.getElementById('nos_nom_banco').value = data.nos_nom_banco || '';
                    document.getElementById('nos_est').value = data.nos_est || 'activo';
                    
                    // 2. Previsualización de las 6 Imágenes usando fileinput.js
                    const imagenFields = {
                        'nos_img_hist': data.nos_img_hist,
                        'nos_img_mis': data.nos_img_mis,
                        'nos_img_vis': data.nos_img_vis,
                        'nos_logo_banco': data.nos_logo_banco,
                        'nos_url_img_android': data.nos_url_img_android, 
                        'nos_img_url_ios': data.nos_img_url_ios 
                    };
                    
                    for (const id in imagenFields) {
                        const ruta = imagenFields[id];
                        const fileInput = $(`#${id}`);
                        
                        // Destruir la instancia antes de re-inicializar
                        fileInput.fileinput('destroy'); 

                        if (ruta) {
                            const fullUrl = `../../${ruta}`; // Ruta completa al archivo
                            
                            fileInput.fileinput({
                                ...fileInputConfig,
                                initialPreview: [fullUrl],
                                initialPreviewAsData: true,
                                initialPreviewConfig: [{ 
                                    caption: ruta.substring(ruta.lastIndexOf('/') + 1), 
                                    size: 0, 
                                    key: id 
                                }],
                                initialCaption: "Imagen actual cargada",
                            });
                        } else {
                            // Si no hay imagen, solo re-inicializar con la configuración base
                            fileInput.fileinput(fileInputConfig);
                        }
                    }

                } else {
                    document.getElementById('formNosotros').reset();
                    document.getElementById('nos_id').value = 0; 
                    // Re-inicializar en caso de que fuera "Actualizar" y se limpió
                    initializeFileInputs(); 
                    mostrarMensaje({ success: false, mensaje: "Cargando formulario para CREAR el primer registro 'Nosotros'." });
                }
            })
            .catch(error => {
                console.error('Error al cargar datos:', error);
                mostrarMensaje({ success: false, mensaje: 'Error de red o servidor al cargar datos iniciales.' });
            });
    }

    document.getElementById('formNosotros').addEventListener('submit', function (e) {
        e.preventDefault();
        const form = this;
        let formData = new FormData(form);
        const nos_id = document.getElementById('nos_id').value;
        const accion = nos_id && nos_id !== '0' ? 'actualizar' : 'crear'; 

        formData.append('accion', accion);
        
        // Bloquear el botón temporalmente
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

        fetch('', { method: 'POST', body: formData })
            .then(res => {
                if (!res.ok) throw new Error('Respuesta de red no satisfactoria');
                return res.json();
            })
            .then(data => {
                mostrarMensaje(data);
                if (data.success) {
                    cargarNosotros(); 
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-save"></i> Guardar Toda la Información';
            })
            .catch(error => {
                console.error('Error en el proceso de guardado:', error);
                mostrarMensaje({ success: false, mensaje: 'Error de red al guardar la información.' });
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-save"></i> Guardar Toda la Información';
            });
    });

    document.addEventListener("DOMContentLoaded", function() {
        // Ejecutamos la carga inicial que también inicializa los fileinputs
        cargarNosotros();
    });
</script>

<?php
$contenido = ob_get_clean(); 
include 'plantillaAdmin.php'; 
?>