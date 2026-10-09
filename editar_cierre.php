<?php
include_once 'conexion.php';
verificarSesion();

$es_admin = (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin', 'administrador'])) 
         || (isset($_SESSION['usuario_tipo']) && in_array($_SESSION['usuario_tipo'], ['admin', 'administrador']));
if (!$es_admin) { die("❌ Sin permiso"); }

$id = intval($_GET['id'] ?? 0);
$cierre = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM cierres_turno WHERE id = $id"));
if (!$cierre) { die("Registro no encontrado"); }

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fecha = $_POST['fecha'];
    $bomba = intval($_POST['bomba']);
    $turno = $_POST['turno'];
    $producto = $_POST['producto'];
    $lectura_inicial = floatval($_POST['lectura_inicial']);
    $lectura_final = floatval($_POST['lectura_final']);
    $litros_vendidos = floatval($_POST['litros_vendidos']);
    $total_venta = floatval($_POST['total_venta']);
    $observaciones = trim($_POST['observaciones']);
    $usuario_id = intval($_POST['usuario_id']);
    
    mysqli_query($conn, "UPDATE cierres_turno SET
        fecha = '$fecha',
        bomba = $bomba,
        turno = '$turno',
        producto = '$producto',
        lectura_inicial = $lectura_inicial,
        lectura_final = $lectura_final,
        litros_vendidos = $litros_vendidos,
        total_venta = $total_venta,
        observaciones = '$observaciones',
        usuario_id = $usuario_id
        WHERE id = $id");
    
    header("Location: registro.php");
    exit;
}

// Obtener lista de operadores para el select
$usuarios = mysqli_query($conn, "SELECT id, nombre FROM usuarios ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cierre de Turno — IMANOL</title>
    <link rel="icon" type="image/png" href="imagenes/favicon2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #F8F9FA; padding: 30px; max-width: 800px; margin: 0 auto; }
        h1 { color: #B91C1C; margin-bottom: 24px; }
        .caja { background: #fff; padding: 30px; border-radius: 16px; border: 1px solid #E5E7EB; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
        .fila { margin-bottom: 20px; }
        label { display: block; font-size: 14px; color: #374151; margin-bottom: 8px; font-weight: 500; }
        input, select, textarea { width: 100%; padding: 12px 16px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #F59E0B; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }
        .btn-guardar { background: linear-gradient(90deg, #B91C1C, #F59E0B); color: #fff; border: none; padding: 14px 28px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-volver { display: inline-flex; align-items: center; gap: 8px; color: #4B5563; text-decoration: none; margin-bottom: 20px; }
        .fila-doble { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 600px) { .fila-doble { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <a href="registro.php" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver a Registros</a>
    <div class="caja">
        <h1>✏️ Editar Cierre de Turno</h1>
        <form method="post">
            <div class="fila-doble">
                <div class="fila">
                    <label>Fecha</label>
                    <input type="date" name="fecha" value="<?= date('Y-m-d', strtotime($cierre['fecha'])) ?>" required>
                </div>
                <div class="fila">
                    <label>Operador Responsable</label>
                    <select name="usuario_id" required>
                        <?php while ($u = mysqli_fetch_assoc($usuarios)): ?>
                        <option value="<?= $u['id'] ?>" <?= ($u['id'] == $cierre['usuario_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nombre']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="fila-doble">
                <div class="fila">
                    <label>Bomba N°</label>
                    <input type="number" name="bomba" value="<?= $cierre['bomba'] ?>" min="1" required>
                </div>
                <div class="fila">
                    <label>Turno</label>
                    <select name="turno" required>
                        <option value="mañana" <?= ($cierre['turno'] ?? '') === 'mañana' ? 'selected' : '' ?>>🌅 Mañana</option>
                        <option value="tarde" <?= ($cierre['turno'] ?? '') === 'tarde' ? 'selected' : '' ?>>🌇 Tarde</option>
                        <option value="noche" <?= ($cierre['turno'] ?? '') === 'noche' ? 'selected' : '' ?>>🌙 Noche</option>
                    </select>
                </div>
            </div>
            
            <div class="fila">
                <label>Producto</label>
                <select name="producto" required>
                    <option value="gasolina_especial" <?= ($cierre['producto'] ?? '') === 'gasolina_especial' ? 'selected' : '' ?>>⛽ Gasolina Especial</option>
                    <option value="diesel_oil" <?= ($cierre['producto'] ?? '') === 'diesel_oil' ? 'selected' : '' ?>>🛢️ Diésel Oil</option>
                </select>
            </div>
            
            <div class="fila-doble">
                <div class="fila">
                    <label>Lectura Inicial (Litros)</label>
                    <input type="number" step="0.001" name="lectura_inicial" value="<?= $cierre['lectura_inicial'] ?>" required>
                </div>
                <div class="fila">
                    <label>Lectura Final (Litros)</label>
                    <input type="number" step="0.001" name="lectura_final" value="<?= $cierre['lectura_final'] ?>" required>
                </div>
            </div>
            
            <div class="fila-doble">
                <div class="fila">
                    <label>Litros Vendidos</label>
                    <input type="number" step="0.001" name="litros_vendidos" value="<?= $cierre['litros_vendidos'] ?>" required>
                </div>
                <div class="fila">
                    <label>Total Venta (Bs)</label>
                    <input type="number" step="0.01" name="total_venta" value="<?= $cierre['total_venta'] ?? $cierre['total_real'] ?? 0 ?>" required>
                </div>
            </div>
            
            <div class="fila">
                <label>Observaciones</label>
                <textarea name="observaciones" rows="3"><?= htmlspecialchars($cierre['observaciones'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar Cambios</button>
        </form>
    </div>
</body>
</html>