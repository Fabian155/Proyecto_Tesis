<?php
// Datos de conexión
$host = "localhost";
$port = "5432";
$dbname = "BD_IP:EVENTOS";
$user = "postgres";
$password = "root";

// Conexión a PostgreSQL
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Error: No se pudo conectar a la base de datos.");
}
?>
