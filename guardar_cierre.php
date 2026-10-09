<?php
require 'conexion.php';
verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $fecha = $_POST['fecha'] ?? '';
    $turno = $_POST['turno'] ?? '';
    $producto = $_POST['producto'] ?? '';
    $dispensador = $_POST['dispensador'] ?? '';
    $lectura_inicial = $_POST['lectura_inicial'] ?? 0;
    $lectura_final = $_POST['lectura_final'] ?? 0;
    $litros_despachados = $_POST['litros_despachados'] ?? 0;
    $litros_contado = $_POST['litros_contado'] ?? 0;
    $monto_contado = $_POST['monto_contado'] ?? 0;
    $litros_credito = $_POST['litros_credito'] ?? 0;
    $monto_credito = $_POST['monto_credito'] ?? 0;
    $litros_vales = $_POST['litros_vales'] ?? 0;
    $monto_vales = $_POST['monto_vales'] ?? 0;
    $precio_nacional = $_POST['precio_nacional'] ?? 0;
    $total_venta = $_POST['total_venta'] ?? 0;
    $dinero_entregado = $_POST['dinero_entregado'] ?? 0;
    $observacion = $_POST['observacion'] ?? '';
    $usuario_id = $_SESSION['usuario_id'];
    $tipo = 'salida';

    $sql = "INSERT INTO movimientos 
    (fecha, turno, tipo, producto, dispensador, lectura_inicial, lectura_final, litros_despachados, litros_contado, monto_contado, litros_credito, monto_credito, litros_vales, monto_vales, precio_nacional, total_venta, dinero_entregado, observacion, usuario_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssddddddddddddddsi", 
        $fecha, $turno, $tipo, $producto, $dispensador,
        $lectura_inicial, $lectura_final, $litros_despachados,
        $litros_contado, $monto_contado, $litros_credito, $monto_credito,
        $litros_vales, $monto_vales, $precio_nacional, $total_venta,
        $dinero_entregado, $observacion, $usuario_id
    );

    if (mysqli_stmt_execute($stmt)) {
        echo "OK";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>