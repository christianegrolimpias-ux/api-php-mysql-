<?php
include_once 'conexion.php';
verificarSesion();

$es_admin = (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin', 'administrador'])) 
         || (isset($_SESSION['usuario_tipo']) && in_array($_SESSION['usuario_tipo'], ['admin', 'administrador']));
if (!$es_admin) { die("❌ Sin permiso"); }

$id = intval($_GET['id'] ?? 0);
$vale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM vales WHERE id = $id"));
if (!$vale) { die("Vale no encontrado"); }

$PRECIO_GAS = 6.96;
$PRECIO_DIE = 17.95;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fecha = $_POST['fecha'];
    $senor = $_POST['senor'];
    $autorizado_por = $_POST['autorizado_por'];
    $ci = $_POST['ci'];
    $placa = $_POST['placa'];
    $bomba = intval($_POST['bomba']);
    $gasolina_litros = floatval($_POST['gasolina_litros']);
    $diesel_litros = floatval($_POST['diesel_litros']);
    $otros = $_POST['otros'];
    $monto_calculado = ($gasolina_litros * $PRECIO_GAS) + ($diesel_litros * $PRECIO_DIE);
    $recibido_conforme = $_POST['recibido_conforme'];
    $entregue_conforme = $_POST['entregue_conforme'];
    
    mysqli_query($conn, "UPDATE vales SET 
        fecha = '$fecha', senor = '$senor', autorizado_por = '$autorizado_por',
        ci = '$ci', placa = '$placa', bomba = $bomba,
        gasolina_litros = $gasolina_litros, diesel_litros = $diesel_litros,
        otros = '$otros', monto_total = $monto_calculado,
        recibido_conforme = '$recibido_conforme', entregue_conforme = '$entregue_conforme'
        WHERE id = $id");
    
    header("Location: reporte_vales.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Vale — IMANOL</title>
    <link rel="icon" type="image/png" href="imagenes/favicon2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #F8F9FA; padding: 30px; max-width: 900px; margin: 0 auto; }
        h1 { color: #B45309; margin-bottom: 24px; }
        .caja { background: #fff; padding: 30px; border-radius: 16px; border: 1px solid #E5E7EB; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
        .fila { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px; }
        .fila-doble { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px; }
        label { display: block; font-size: 14px; color: #374151; margin-bottom: 8px; font-weight: 500; }
        input, select, textarea { width: 100%; padding: 12px 16px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; }
        input:focus { outline: none; border-color: #F59E0B; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }
        .btn-guardar { background: linear-gradient(90deg, #B91C1C, #F59E0B); color: #fff; border: none; padding: 14px 28px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-volver { display: inline-flex; align-items: center; gap: 8px; color: #4B5563; text-decoration: none; margin-bottom: 20px; }
        .monto { background: #FEF3C7; border-color: #F59E0B; font-weight: 600; color: #92400E; }
        @media (max-width: 768px) { .fila, .fila-doble { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <a href="reporte_vales.php" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver al Reporte</a>
    <div class="caja">
        <h1>✏️ Editar Vale</h1>
        <form method="post">
            <div class="fila">
                <div>
                    <label>Fecha</label>
                    <input type="date" name="fecha" value="<?= $vale['fecha'] ?>">
                </div>
                <div>
                    <label>Señor / Cliente</label>
                    <input type="text" name="senor" value="<?= htmlspecialchars($vale['senor']) ?>">
                </div>
                <div>
                    <label>Autorizado por</label>
                    <input type="text" name="autorizado_por" value="<?= htmlspecialchars($vale['autorizado_por']) ?>">
                </div>
            </div>
            <div class="fila">
                <div>
                    <label>C.I.</label>
                    <input type="text" name="ci" value="<?= htmlspecialchars($vale['ci']) ?>">
                </div>
                <div>
                    <label>Placa</label>
                    <input type="text" name="placa" value="<?= htmlspecialchars($vale['placa']) ?>">
                </div>
                <div>
                    <label>Bomba</label>
                    <select name="bomba">
                        <option value="1" <?= $vale['bomba']==1?'selected':'' ?>>Bomba N° 1</option>
                        <option value="2" <?= $vale['bomba']==2?'selected':'' ?>>Bomba N° 2</option>
                        <option value="3" <?= $vale['bomba']==3?'selected':'' ?>>Bomba N° 3</option>
                        <option value="4" <?= $vale['bomba']==4?'selected':'' ?>>Bomba N° 4</option>
                    </select>
                </div>
            </div>
            <div class="fila">
                <div>
                    <label>Gasolina (Litros)</label>
                    <input type="number" step="0.001" name="gasolina_litros" id="gas" value="<?= $vale['gasolina_litros'] ?>" oninput="calcular()">
                </div>
                <div>
                    <label>Diésel (Litros)</label>
                    <input type="number" step="0.001" name="diesel_litros" id="die" value="<?= $vale['diesel_litros'] ?>" oninput="calcular()">
                </div>
                <div>
                    <label>Monto Bs</label>
                    <input type="text" id="monto" class="monto" readonly value="<?= number_format($vale['monto_total'], 2, '.', '') ?>">
                </div>
            </div>
            <div class="fila-doble">
                <div>
                    <label>Otros detalles</label>
                    <input type="text" name="otros" value="<?= htmlspecialchars($vale['otros']) ?>">
                </div>
            </div>
            <div class="fila-doble">
                <div>
                    <label>Entregué Conforme</label>
                    <input type="text" name="entregue_conforme" value="<?= htmlspecialchars($vale['entregue_conforme']) ?>">
                </div>
                <div>
                    <label>Recibí Conforme</label>
                    <input type="text" name="recibido_conforme" value="<?= htmlspecialchars($vale['recibido_conforme']) ?>">
                </div>
            </div>
            <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar Cambios</button>
        </form>
    </div>
<script>
const PRECIO_GAS = 6.96, PRECIO_DIE = 17.95;
function calcular() {
    const gas = parseFloat(document.getElementById('gas').value) || 0;
    const die = parseFloat(document.getElementById('die').value) || 0;
    document.getElementById('monto').value = (gas * PRECIO_GAS + die * PRECIO_DIE).toFixed(2);
}
</script>
</body>
</html>