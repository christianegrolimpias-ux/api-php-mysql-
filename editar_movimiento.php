<?php
include_once 'conexion.php';
verificarSesion();

$es_admin = (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin', 'administrador'])) 
         || (isset($_SESSION['usuario_tipo']) && in_array($_SESSION['usuario_tipo'], ['admin', 'administrador']));
if (!$es_admin) { die("❌ Sin permiso"); }

$id = intval($_GET['id'] ?? 0);
$fecha_volver = $_GET['fecha'] ?? date('Y-m-d');
$mov = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM movimientos WHERE id = $id"));
if (!$mov) { die("Registro no encontrado"); }

$PRECIO_GASOLINA = 6.96;
$PRECIO_DIESEL = 9.80;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $producto = $_POST['producto'];
    $dispensador = intval($_POST['dispensador']);
    $turno = $_POST['turno'];
    $lectura_inicial = floatval($_POST['lectura_inicial']);
    $lectura_final = floatval($_POST['lectura_final']);
    $litros_despachados = $lectura_final - $lectura_inicial;
    $precio_unit = $producto === 'gasolina_especial' ? $PRECIO_GASOLINA : $PRECIO_DIESEL;
    $total_venta = $litros_despachados * $precio_unit;
    $monto_vales = floatval($_POST['monto_vales']);
    $dinero_entregado = $total_venta - $monto_vales;
    
    mysqli_query($conn, "UPDATE movimientos SET 
        producto = '$producto', dispensador = $dispensador, turno = '$turno',
        lectura_inicial = $lectura_inicial, lectura_final = $lectura_final,
        litros_despachados = $litros_despachados, total_venta = $total_venta,
        monto_vales = $monto_vales, dinero_entregado = $dinero_entregado
        WHERE id = $id");
    
    header("Location: reportes.php?fecha=$fecha_volver");
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
        body { background: #F8F9FA; padding: 30px; max-width: 900px; margin: 0 auto; }
        h1 { color: #B45309; margin-bottom: 24px; }
        .caja { background: #fff; padding: 30px; border-radius: 16px; border: 1px solid #E5E7EB; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
        .fila { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px; }
        label { display: block; font-size: 14px; color: #374151; margin-bottom: 8px; font-weight: 500; }
        input, select { width: 100%; padding: 12px 16px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #F59E0B; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }
        .btn-guardar { background: linear-gradient(90deg, #B91C1C, #F59E0B); color: #fff; border: none; padding: 14px 28px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-volver { display: inline-flex; align-items: center; gap: 8px; color: #4B5563; text-decoration: none; margin-bottom: 20px; }
        .resumen { background: #F0FDF4; padding: 16px; border-radius: 10px; margin-top: 20px; border: 1px solid #BBF7D0; }
        .resumen p { margin: 8px 0; font-size: 14px; }
        @media (max-width: 768px) { .fila { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <a href="reportes.php?fecha=<?= $fecha_volver ?>" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver al Reporte</a>
    <div class="caja">
        <h1>✏️ Editar Movimiento</h1>
        <form method="post" id="formEditar">
            <div class="fila">
                <div>
                    <label>Producto</label>
                    <select name="producto" id="producto">
                        <option value="gasolina_especial" <?= $mov['producto']==='gasolina_especial'?'selected':'' ?>>Gasolina Especial</option>
                        <option value="diesel_oil" <?= $mov['producto']==='diesel_oil'?'selected':'' ?>>Diésel Oil</option>
                    </select>
                </div>
                <div>
                    <label>Bomba / Dispensador</label>
                    <select name="dispensador">
                        <?php for($i=1; $i<=4; $i++): ?>
                        <option value="<?= $i ?>" <?= $mov['dispensador']==$i?'selected':'' ?>>Bomba N° <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="fila">
                <div>
                    <label>Turno</label>
                    <select name="turno">
                        <option value="mañana" <?= $mov['turno']==='mañana'?'selected':'' ?>>Mañana</option>
                        <option value="tarde" <?= $mov['turno']==='tarde'?'selected':'' ?>>Tarde</option>
                    </select>
                </div>
                <div>
                    <label>Lectura Inicial</label>
                    <input type="number" step="0.01" name="lectura_inicial" id="li" value="<?= $mov['lectura_inicial'] ?>" oninput="calcular()">
                </div>
            </div>
            <div class="fila">
                <div>
                    <label>Lectura Final</label>
                    <input type="number" step="0.01" name="lectura_final" id="lf" value="<?= $mov['lectura_final'] ?>" oninput="calcular()">
                </div>
                <div>
                    <label>Vales (Bs)</label>
                    <input type="number" step="0.01" name="monto_vales" id="vales" value="<?= $mov['monto_vales'] ?>" oninput="calcular()">
                </div>
            </div>
            <div class="resumen" id="resumen">
                <p><strong>Litros Despachados:</strong> <span id="litros">0.00</span> L</p>
                <p><strong>Precio Unitario:</strong> Bs <span id="precio">0.00</span> / L</p>
                <p><strong>Total Venta:</strong> Bs <span id="total">0.00</span></p>
                <p><strong>Dinero Recibido:</strong> Bs <span id="recibido">0.00</span></p>
            </div>
            <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar Cambios</button>
        </form>
    </div>
<script>
const PRECIOS = { gasolina_especial: 6.96, diesel_oil: 9.80 };
function calcular() {
    const prod = document.getElementById('producto').value;
    const li = parseFloat(document.getElementById('li').value) || 0