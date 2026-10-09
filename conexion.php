<?php
if (!defined('CONEXION_CARGADA')) {
    define('CONEXION_CARGADA', true);

    session_start();

    $servidor = "localhost";
    $usuario = "root";
    $contraseña = "";
    $base_datos = "sistema_movimientos";

    $conn = mysqli_connect($servidor, $usuario, $contraseña, $base_datos);

    if (!$conn) {
        die("❌ Error de conexión: " . mysqli_connect_error());
    }

    mysqli_set_charset($conn, "utf8mb4");

    if (!function_exists('verificarSesion')) {
        function verificarSesion() {
            if (!isset($_SESSION['usuario_id'])) {
                header("Location: login.php");
                exit;
            }
        }
    }
}