<?php
include_once 'conexion.php';
verificarSesion();

$es_admin = (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin', 'administrador'])) 
         || (isset($_SESSION['usuario_tipo']) && in_array($_SESSION['usuario_tipo'], ['admin', 'administrador']));
if (!$es_admin) { die("❌ Sin permiso"); }

$id = intval($_GET['id'] ?? 0);
$fecha_inicio_volver = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin_volver = $_GET['fecha_fin'] ?? date('Y-m-d');
$mov = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM movimientos WHERE id = $id"));
if (!$mov) { die("Registro no encontrado"); }

$PRECIO_GASOLINA = 6.96;
$PRECIO_DIESEL = 17.95;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fecha = $_POST['fecha'];
    $producto = $_POST['producto'];
    $tipo = $_POST['tipo'];
    $n_autorizacion = $_POST['n_autorizacion'];
    $n_factura = $_POST['n_factura'];
    $precintos = intval($_POST['precintos']);
    $formularios = intval($_POST['formularios']);
    $litros_recibidos = $tipo === 'entrada' ? floatval($_POST['litros_recibidos']) : 0;
    $dispensador = $tipo === 'salida' ? intval($_POST['dispensador']) : 0;
    $lectura_inicial = $tipo === 'salida' ? floatval($_POST['lectura_inicial']) : 0;
    $lectura_final = $tipo === 'salida' ? floatval($_POST['lectura_final']) : 0;
    $litros_despachados = $tipo === 'salida' ? $lectura_final - $lectura_inicial : 0;
    $precio_unitario = !empty($_POST['precio_unitario']) ? floatval($_POST['precio_unitario']) : 
        ($producto === 'gasolina_especial' ? $PRECIO_GASOLINA : $PRECIO_DIESEL);
    $mermas = floatval($_POST['mermas']);
    $monto_vales = $tipo === 'salida' ? floatval($_POST['monto_vales']) : 0;
    $dinero_entregado = $tipo === 'salida' ? ($litros_despachados * $precio_unitario) - $monto_vales : 0;
    
    mysqli_query($conn, "UPDATE movimientos SET 
        fecha = '$fecha', producto = '$producto', tipo = '$tipo',
        n_autorizacion = '$n_autorizacion', n_factura = '$n_factura',
        precintos = $precintos, formularios = $formularios,
        litros_recibidos = $litros_recibidos, dispensador = $dispensador,
        lectura_inicial = $lectura_inicial, lectura_final = $lectura_final,
        litros_despachados = $litros_despachados, precio_unitario = $precio_unitario,
        mermas = $mermas, monto_vales = $monto_vales, dinero_entregado = $dinero_entregado
        WHERE id = $id");
    
    header("Location: reportes_compras.php?fecha_inicio=$fecha_inicio_volver&fecha_fin=$fecha_fin_volver");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Movimiento — IMANOL</title>
    <link rel="icon" type="image/png" href="imagenes/favicon2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #F8F9FA; padding: 30px; max-width: 1000px; margin: 0 auto; }
        h1 { color: #B45309; margin-bottom: 24px; }
        .caja { background: #fff; padding: 30px; border-radius: 16px; border: 1px solid #E5E7EB; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
        .fila { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px; }
        label { display: block; font-size: 14px; color: #374151; margin-bottom: 8px; font-weight: 500; }
        input, select { width: 100%; padding: 12px 16px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #F59E0B; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }
        .btn-guardar { background: linear-gradient(90deg, #B91C1C, #F59E0B); color: #fff; border: none; padding: 14px 28px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-volver { display: inline-flex; align-items: center; gap: 8px; color: #4B5563; text-decoration: none; margin-bottom: 20px; }
        .seccion { display: none; }
        .seccion.activa { display: block; }
        .resumen { background: #F0FDF4; padding: 16px; border-radius: 10px; margin-top: 20px; border: 1px solid #BBF7D0; }
        .resumen p { margin: 8px 0; font-size: 14px; }
        @media (max-width: 768px) { .fila { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <a href="reportes_compras.php?fecha_inicio=<?= $fecha_inicio_volver ?>&fecha_fin=<?= $fecha_fin_volver ?>" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver al Reporte</a>
    <div class="caja">
        <h1>✏️ Editar Movimiento</h1>
        <form method="post" id="formEditar">
            <div class="fila">
                <div>
                    <label>Fecha</label>
                    <input type="date" name="fecha" value="<?= $mov['fecha']