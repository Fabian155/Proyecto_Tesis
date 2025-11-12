<?php
session_start();

// El archivo de conexión a la base de datos (PostgreSQL)
include __DIR__ . '/../../conexion.php'; 

$error = "";

/**
 * Función mejorada para el inicio de sesión.
 * Utiliza password_verify() para una verificación segura.
 * * NOTA IMPORTANTE: Si las contraseñas en la base de datos aún están 
 * almacenadas como SHA-256 binario, la verificación será:
 * 1. Si la base de datos usa 'bytea' (almacenamiento binario de SHA-256):
 * if (pg_escape_bytea(hash('sha256', $contrasena, true)) === $row[$pass_field]) { ... }
 * 2. Si la base de datos usa 'text'/'varchar' (almacenamiento hexadecimal de SHA-256):
 * if (hash('sha256', $contrasena) === $row[$pass_field]) { ... }
 * * El código original usaba la primera opción (SHA-256 binario).
 * Recomiendo encarecidamente migrar las contraseñas a password_hash().
 * * Por ahora, mantendremos la lógica original para la retrocompatibilidad:
 * function sha256_bin($string) { return hex2bin(hash('sha256', $string)); }
 */
function sha256_bin($string) {
    return hex2bin(hash('sha256', $string));
}

/**
 * Realiza el chequeo de login contra las diferentes tablas de usuario.
 * @param resource $conn Conexión a la base de datos.
 * @param string $correo Correo electrónico del usuario.
 * @param string $contrasena Contraseña en texto plano.
 * @return array Un array con los datos del usuario o un error.
 */
function check_login($conn, $correo, $contrasena) {
    // Escapa y convierte la contraseña a binario SHA-256 para comparación
    $hash_bin_input = pg_escape_bytea(sha256_bin($contrasena));

    // Definición de las tablas a chequear
    $tablas = [
        ["tbl_usuario", "usr_cor", "usr_id", "usr_nom", "usr_con", "usuario"],
        ["tbl_cajero", "caj_ema", "caj_id", "caj_nom", "caj_con", "cajero"],
        ["tbl_admin", "adm_ema", "adm_id", "adm_nom", "adm_con", "admin"]
    ];

    $correo_esc = pg_escape_string($conn, $correo);

    foreach ($tablas as $t) {
        [$table, $email_field, $id_field, $name_field, $pass_field, $tipo] = $t;

        $query = "SELECT $id_field, $name_field, $pass_field FROM $table WHERE $email_field = '$correo_esc' LIMIT 1";
        $res = pg_query($conn, $query);

        if ($res && $row = pg_fetch_assoc($res)) {
            // Comparación de contraseñas (SHA-256 binario)
            if ($hash_bin_input === $row[$pass_field]) {
                // Login correcto
                return [
                    "tipo"   => $tipo,
                    "id"     => $row[$id_field],
                    "nombre" => $row[$name_field]
                ]; 
            } else {
                // Se encontró el correo, pero la contraseña es incorrecta para ese tipo de usuario
                // Por seguridad, devolvemos un error genérico
                return ["error" => "Correo no registrado o contraseña incorrecta."];
            }
        }
    }

    return ["error" => "Correo no registrado o contraseña incorrecta."];
}

// Inicialización de variables para la vista
$correo = ""; 

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo']);
    $contrasena = trim($_POST['contrasena']);

    if (empty($correo) || empty($contrasena)) {
        $error = "Por favor ingresa correo y contraseña.";
    } else {
        $result = check_login($conn, $correo, $contrasena);

        if (isset($result['tipo'])) {
            // Guardar sesión con ID, nombre y tipo
            $_SESSION['id']     = $result['id'];
            $_SESSION['nombre'] = $result['nombre'];
            $_SESSION['tipo']   = $result['tipo'];

            // Redirigir según tipo de usuario
            switch ($result['tipo']) {
                case 'usuario':
                    header("Location: ../../php/usuario/menu.php"); exit;
                case 'cajero':
                    header("Location: ../../php/cajero/menu.php"); exit;
                case 'admin':
                    header("Location: ../../php/admin/menu.php"); exit;
                default:
                    // Si el tipo no coincide con una ruta esperada
                    $error = "Tipo de usuario no válido.";
                    // Opcional: Destruir sesión si el tipo es desconocido
                    session_unset();
                    session_destroy();
            }
        } else {
            $error = $result['error'];
        }
    }
}

// Ahora que la lógica de manejo del POST está completa,
// el flujo continuará a la vista (login.php) que incluirá este controlador.