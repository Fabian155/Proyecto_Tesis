<?php
session_start();
// Verificar sesión y tipo
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../sesion/login.php");
    exit;
}

include '../../conexion.php';

$adm_id = intval($_SESSION['id']);

// ================== ACTUALIZAR PERFIL ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $nombre = pg_escape_string($conn, $_POST['adm_nom']);
    $usuario = pg_escape_string($conn, $_POST['adm_usr']);
    $correo = pg_escape_string($conn, $_POST['adm_ema']);
    $contrasena = isset($_POST['adm_con']) && $_POST['adm_con'] !== '' ? $_POST['adm_con'] : null;

    $update = "UPDATE tbl_admin 
               SET adm_nom='$nombre', adm_usr='$usuario', adm_ema='$correo', adm_fec_edi=NOW()";

    if ($contrasena) {
        // Hashear la nueva contraseña en binario
        $hash_bin = pg_escape_bytea(hash('sha256', $contrasena, true));
        $update .= ", adm_con='{$hash_bin}'";
    }

    $update .= " WHERE adm_id=$adm_id";

    $res = pg_query($conn, $update);
    echo json_encode($res ? ["success"=>true,"mensaje"=>"Perfil actualizado correctamente"] : ["success"=>false,"mensaje"=>"Error: ".pg_last_error($conn)]);
    exit;
}

// ================== OBTENER DATOS DEL ADMIN ==================
$query = "SELECT adm_nom, adm_usr, adm_ema, adm_fec_cre, adm_fec_edi 
          FROM tbl_admin WHERE adm_id=$adm_id LIMIT 1";
$res = pg_query($conn, $query);
$admin = pg_fetch_assoc($res);

ob_start();
?>

<div class="container py-4">
    <h2 class="text-center mb-4">Mi Perfil de Administrador</h2>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Editar Perfil</h5>
                </div>
                <div class="card-body">
                    <form id="formPerfil">
                        <div class="mb-3">
                            <label for="adm_nom" class="form-label">Nombre:</label>
                            <input type="text" class="form-control" id="adm_nom" name="adm_nom" 
                                   value="<?= htmlspecialchars($admin['adm_nom']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="adm_usr" class="form-label">Usuario:</label>
                            <input type="text" class="form-control" id="adm_usr" name="adm_usr" 
                                   value="<?= htmlspecialchars($admin['adm_usr']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="adm_ema" class="form-label">Correo:</label>
                            <input type="email" class="form-control" id="adm_ema" name="adm_ema" 
                                   value="<?= htmlspecialchars($admin['adm_ema']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="adm_con" class="form-label">Contraseña (dejar en blanco si no desea cambiar):</label>
                            <input type="password" class="form-control" id="adm_con" name="adm_con">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">Actualizar Perfil</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-muted">
                    <small>Creado: <?= $admin['adm_fec_cre'] ?> | Última edición: <?= $admin['adm_fec_edi'] ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.getElementById('formPerfil').addEventListener('submit', function(e){
    e.preventDefault();
    let formData = new FormData(this);
    formData.append('accion','actualizar');

    fetch('', {method:'POST', body:formData})
    .then(res=>res.json())
    .then(data=>{
        Swal.fire({
            icon: data.success ? 'success' : 'error',
            title: data.success ? 'Éxito' : 'Error',
            text: data.mensaje,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    })
    .catch(err=>{
        Swal.fire({icon:'error',title:'Error',text:'No se pudo actualizar el perfil'});
    });
});
</script>

<?php
$contenido = ob_get_clean();
include 'plantillaAdmin.php';
