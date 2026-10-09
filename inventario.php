<?php
include_once 'conexion.php';
verificarSesion();

$es_admin = (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'administrador') 
         || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'administrador')
         || (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin')
         || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin');
if (!$es_admin) {
    die("❌ No tienes permiso para acceder a esta página.");
}

$nombre_admin = $_SESSION['usuario_nombre'] ?? 'Administrador';
$inicial = mb_substr($nombre_admin, 0, 1);
$rol_usuario = $es_admin ? 'Administrador' : 'Operador';

// === PRECIOS DE VENTA OFICIALES ===
$PRECIO_VENTA_GASOLINA = 6.96;
$PRECIO_VENTA_DIESEL   = 9.80;

// === PROCESAR FORMULARIO DE ENTRADA ===
$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fecha            = $_POST['fecha'] ?? date('Y-m-d');
    $producto         = $_POST['producto'] ?? '';
    $litros_recibidos = floatval($_POST['litros_recibidos'] ?? 0);
    $precio_compra    = floatval($_POST['precio_compra'] ?? 0);
    $n_factura        = trim($_POST['n_factura'] ?? '');

    if (!empty($producto) && $litros_recibidos > 0 && $precio_compra > 0) {
        $precio_venta = ($producto === 'gasolina_especial') ? $PRECIO_VENTA_GASOLINA : $PRECIO_VENTA_DIESEL;
        
        $sql = "INSERT INTO movimientos (fecha, tipo, producto, litros_recibidos, precio_nacional, precio_compra, n_factura) 
                VALUES ('$fecha', 'entrada', '$producto', $litros_recibidos, $precio_venta, $precio_compra, '$n_factura')";
        
        if (mysqli_query($conn, $sql)) {
            $ganancia_total = ($precio_venta - $precio_compra) * $litros_recibidos;
            $mensaje = "✅ Entrada registrada — Ganancia estimada: Bs " . number_format($ganancia_total, 2, ',', '.');
            $tipo_mensaje = "exito";
        } else {
            $mensaje = "❌ Error: " . mysqli_error($conn);
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "⚠️ Completa todos los campos correctamente.";
        $tipo_mensaje = "error";
    }
}

// === GASOLINA ===
$gas = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(litros_recibidos) AS total_litros,
        SUM(litros_recibidos * precio_compra) AS costo_total,
        SUM(litros_recibidos * precio_nacional) AS valor_venta
    FROM movimientos 
    WHERE tipo='entrada' AND producto='gasolina_especial'
"));
$gas_entradas = $gas['total_litros'] ?? 0;
$gas_costo = $gas['costo_total'] ?? 0;
$gas_valor = $gas['valor_venta'] ?? 0;
$gas_ganancia = $gas_valor - $gas_costo;

$gas_salidas = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(litros_despachados) AS total FROM movimientos 
    WHERE tipo='salida' AND producto='gasolina_especial'
"))['total'] ?? 0;
$gas_stock = $gas_entradas - $gas_salidas;

// === DIÉSEL ===
$die = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(litros_recibidos) AS total_litros,
        SUM(litros_recibidos * precio_compra) AS costo_total,
        SUM(litros_recibidos * precio_nacional) AS valor_venta
    FROM movimientos 
    WHERE tipo='entrada' AND producto='diesel_oil'
"));
$die_entradas = $die['total_litros'] ?? 0;
$die_costo = $die['costo_total'] ?? 0;
$die_valor = $die['valor_venta'] ?? 0;
$die_ganancia = $die_valor - $die_costo;

$die_salidas = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(litros_despachados) AS total FROM movimientos 
    WHERE tipo='salida' AND producto='diesel_oil'
"))['total'] ?? 0;
$die_stock = $die_entradas - $die_salidas;

// === TOTALES ===
$total_entradas = $gas_entradas + $die_entradas;
$total_salidas  = $gas_salidas + $die_salidas;
$total_stock    = $gas_stock + $die_stock;
$total_costo    = $gas_costo + $die_costo;
$total_valor    = $gas_valor + $die_valor;
$total_ganancia = $gas_ganancia + $die_ganancia;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario — IMANOL</title>
    <link rel="icon" type="image/png" sizes="80x80" href="imagenes/favicon2.png">
    <link rel="icon" type="image/png" sizes="32x32" href="imagenes/favicon2.png">
    <link rel="icon" type="image/png" sizes="16x16" href="imagenes/favicon2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body { 
            background: #F8F9FA; 
            color: #1F2937; 
            display: flex; 
            min-height: 100vh; 
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #B91C1C 0%, #EA580C 30%, #F59E0B 55%, #FB923C 80%, #DC2626 100%);
            border-right: 2px solid rgba(255, 255, 255, 0.25);
            height: 100vh;
            position: fixed;
            padding: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 3px 0 15px rgba(185, 28, 28, 0.2);
        }
        .logo-box {
            padding: 14px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.1);
        }
        .logo-box img { max-width: 55px; }
        .logo-box h2 { font-size: 20px; color: #fff; margin: 0; }
        .logo-box p { font-size: 10px; color: rgba(255,255,255,0.8); margin: 1px 0 0 0; }
        .sidebar nav {
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
            overflow-y: auto;
        }
        .sidebar nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px;
            color: rgba(255,255,255,0.9);
            text-decoration: none; border-radius: 8px;
            font-size: 13px; font-weight: 500;
            transition: all 0.25s ease;
        }
        .sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
            transform: translateX(3px);
        }
        .sidebar nav a.activo {
            background: rgba(255, 255, 255, 0.3);
            color: #fff;
            border-left: 3px solid #fff;
            font-weight: 600;
        }
        .sidebar .user-info {
            padding: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.1);
        }
        .sidebar .user-perfil {
            display: flex; align-items: center; gap: 10px; margin-bottom: 8px;
        }
        .sidebar .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: #fff; color: #B91C1C;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .sidebar .user-perfil h4 { font-size: 13px; color: #fff; margin: 0; }
        .sidebar .user-perfil p { font-size: 11px; color: rgba(255,255,255,0.75); margin: 2px 0 0 0; }
        .sidebar .estado {
            display: flex; align-items: center; gap: 5px;
            font-size: 11px; color: #90EE90; margin-bottom: 8px;
        }
        .sidebar .estado span {
            width: 7px; height: 7px; border-radius: 50%;
            background: #90EE90; box-shadow: 0 0 4px #90EE90;
        }
        .cerrar-btn {
            background: rgba(0, 0, 0, 0.2);
            color: #fff; border: none; padding: 8px; border-radius: 7px;
            width: 100%; font-size: 12px; display: flex; align-items: center; justify-content: center;
            gap: 7px; text-decoration: none; transition: all 0.2s;
        }
        .cerrar-btn:hover { background: rgba(0, 0, 0, 0.35); }
        
        .main {
            margin-left: 250px;
            flex: 1;
            padding: 24px 30px;
            background: #FFFFFF;
            min-height: 100vh;
        }
        .contenedor { max-width: 1200px; margin: 0 auto; }
        
        .btn-regresar {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 10px 20px;
            background: #F3F4F6; color: #4B5563;
            border: 1px solid #E5E7EB; border-radius: 10px;
            text-decoration: none; font-size: 14px; margin-bottom: 24px;
            transition: all 0.25s ease;
        }
        .btn-regresar:hover {
            background: #FEF2F2; color: #991B1B;
            border-color: #DC2626;
            transform: translateX(-3px);
        }
        .cabecera {
            background: #FFFFFF;
            border: 1px solid #FECACA;
            border-radius: 16px;
            padding: 24px 28px;
            margin-bottom: 24px;
            display: flex; align-items: center; gap: 20px;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.08);
        }
        .circulo-logo {
            width: 56px; height: 56px; border-radius: 12px;
            background: linear-gradient(135deg, #B91C1C 0%, #EF4444 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: #fff;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        .cabecera-texto h1 { font-size: 22px; color: #B91C1C; margin-bottom: 4px; }
        .cabecera-texto p { font-size: 13px; color: #6B7280; }
        
        .mensaje {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }
        .exito { background: #D1FAE5; color: #065F46; }
        .error { background: #FEE2E2; color: #991B1B; }
        
        .tarjetas { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 24px; 
            margin-bottom: 32px; 
        }
        .tarjeta {
            background: #FFFFFF;
            padding: 28px 24px;
            border-radius: 16px;
            border-top: 4px solid;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .tarjeta.gas { border-top-color: #F59E0B; }
        .tarjeta.die { border-top-color: #DC2626; }
        .tarjeta.total { border-top-color: #22C55E; }
        .tarjeta h3 { font-size: 14px; color: #6B7280; margin-bottom: 16px; font-weight: 600; }
        .tarjeta .grande { font-size: 36px; font-weight: 800; margin-bottom: 8px; }
        .tarjeta.gas .grande { color: #B45309; }
        .tarjeta.die .grande { color: #B91C1C; }
        .tarjeta.total .grande { color: #16A34A; }
        .tarjeta .etiqueta { font-size: 12px; color: #9CA3AF; margin-bottom: 16px; display: block; }
        .tarjeta .dato { font-size: 13px; margin: 6px 0; color: #4B5563; }
        
        .caja-tabla {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .tabla {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .tabla th {
            background: #F9FAFB;
            padding: 14px 16px;
            text-align: left;
            color: #374151;
            font-weight: 600;
            border-bottom: 2px solid #E5E7EB;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .tabla td {
            padding: 14px 16px;
            border-bottom: 1px solid #F3F4F6;
            color: #4B5563;
        }
        .tabla tr:last-child td { border-bottom: none; }
        .tabla tr.fila-total { background: #F0FDF4; font-weight: bold; }
        .stock-bajo { 
            color: #D97706; font-weight: 700; 
            background: #FEF3C7; padding: 4px 10px; 
            border-radius: 6px; display: inline-block;
        }
        .stock-ok { 
            color: #16A34A; font-weight: 700; 
            background: #D1FAE3; padding: 4px 10px; 
            border-radius: 6px; display: inline-block;
        }
        .ganancia-positiva { color: #059669; font-weight: bold; }
        @media (max-width: 900px) {
            .tarjetas { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { width: 220px; }
            .main { margin-left: 220px; padding: 16px; }
        }
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="logo-box">
        <img src="imagenes/logo.png" alt="IMANOL">
        <div>
            <h2>MANOL</h2>
            <p>Dios Con Nosotros</p>
        </div>
    </div>
    <nav>
        <a href="index_admin.php"><i class="fas fa-chart-pie"></i> Inicio</a>
        <a href="gasolina.php"><i class="fas fa-gas-pump"></i> Gasolina</a>
        <a href="diesel.php"><i class="fas fa-gas-pump"></i> Diésel</a>
        <a href="reportes_compras.php"><i class="fas fa-file-invoice-dollar"></i> Compras Combustible</a>
        <a href="registro.php"><i class="fas fa-th-list"></i> Registros</a>
        <a href="reportes.php"><i class="fas fa-chart-bar"></i> Reportes</a>
          <a href="reporte_vales.php"><i class="fas fa-ticket-alt"></i> Reporte Vales</a>
        <a href="inventario.php" class="activo"><i class="fas fa-boxes"></i> Inventario</a>
        <a href="historial_calibracion.php"><i class="fas fa-sliders-h"></i> Historial Calibración</a>
        <a href="proveedores.php"><i class="fas fa-truck"></i> Proveedores</a>
        <a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a>
        <a href="crear_cuenta.php"><i class="fas fa-user-plus"></i> Crear Cuentas</a>
    </nav>
    <div class="user-info">
        <div class="user-perfil">
            <div class="avatar"><?= $inicial ?></div>
            <div>
                <h4><?= $nombre_admin ?></h4>
                <p><?= $rol_usuario ?></p>
            </div>
        </div>
        <div class="estado"><span></span> En línea</div>
        <a href="logout.php" class="cerrar-btn"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </div>
</aside>
<main class="main">
    <div class="contenedor">
        <a href="index_admin.php" class="btn-regresar"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
        
        <div class="cabecera">
            <div class="circulo-logo"><i class="fas fa-boxes"></i></div>
            <div class="cabecera-texto">
                <h1>Inventario de Combustible</h1>
                <p>Control de existencias — entradas, salidas y stock disponible</p>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="mensaje <?= $tipo_mensaje ?>"><?= $mensaje ?></div>
        <?php endif; ?>

        <!-- === TARJETAS — IGUAL QUE TU IMAGEN === -->
        <div class="tarjetas">
            <div class="tarjeta gas">
                <h3><i class="fas fa-gas-pump"></i> Gasolina Especial</h3>
                <div class="grande"><?= number_format($gas_stock, 2, ',', '.') ?> L</div>
                <span class="etiqueta">En Stock</span>
                <div class="dato"><i class="fas fa-download"></i> Comprado: <?= number_format($gas_entradas, 2, ',', '.') ?> L</div>
                <div class="dato"><i class="fas fa-upload"></i> Vendido: <?= number_format($gas_salidas, 2, ',', '.') ?> L</div>
            </div>
            <div class="tarjeta die">
                <h3><i class="fas fa-gas-pump"></i> Diésel Oil</h3>
                <div class="grande"><?= number_format($die_stock, 2, ',', '.') ?> L</div>
                <span class="etiqueta">En Stock</span>
                <div class="dato"><i class="fas fa-download"></i> Comprado: <?= number_format($die_entradas, 2, ',', '.') ?> L</div>
                <div class="dato"><i class="fas fa-upload"></i> Vendido: <?= number_format($die_salidas, 2, ',', '.') ?> L</div>
            </div>
            <div class="tarjeta total">
                <h3><i class="fas fa-chart-bar"></i> Total General</h3>
                <div class="grande"><?= number_format($total_stock, 2, ',', '.') ?> L</div>
                <span class="etiqueta">Combustible Disponible</span>
                <div class="dato"><i class="fas fa-download"></i> Total Comprado: <?= number_format($total_entradas, 2, ',', '.') ?> L</div>
                <div class="dato"><i class="fas fa-upload"></i> Total Vendido: <?= number_format($total_salidas, 2, ',', '.') ?> L</div>
            </div>
        </div>

        <!-- === TABLA — IGUAL QUE IMAGEN + COLUMNAS NUEVAS === -->
        <div class="caja-tabla">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th><i class="fas fa-download"></i> Litros Comprados</th>
                        <th>💰 Costo Total</th>
                        <th><i class="fas fa-upload"></i> Litros Vendidos</th>
                        <th>💵 Valor Venta</th>
                        <th>📦 Stock Actual</th>
                        <th>💰 Ganancia</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><i class="fas fa-gas-pump" style="color:#F59E0B"></i> Gasolina Especial</strong></td>
                        <td><?= number_format($gas_entradas, 2, ',', '.') ?> L</td>
                        <td>Bs <?= number_format($gas_costo, 2, ',', '.') ?></td>
                        <td><?= number_format($gas_salidas, 2, ',', '.') ?> L</td>
                        <td>Bs <?= number_format($gas_valor, 2, ',', '.') ?></td>
                        <td><strong><?= number_format($gas_stock, 2, ',', '.') ?> L</strong></td>
                        <td class="ganancia-positiva">Bs <?= number_format($gas_ganancia, 2, ',', '.') ?></td>
                        <td>
                            <?php if ($gas_stock < 500): ?>
                                <span class="stock-bajo">⚠️ Stock Bajo</span>
                            <?php else: ?>
                                <span class="stock-ok">✅ Disponible</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><i class="fas fa-gas-pump" style="color:#DC2626"></i> Diésel Oil</strong></td>
                        <td><?= number_format($die_entradas, 2, ',', '.') ?> L</td>
                        <td>Bs <?= number_format($die_costo, 2, ',', '.') ?></td>
                        <td><?= number_format($die_salidas, 2, ',', '.') ?> L</td>
                        <td>Bs <?= number_format($die_valor, 2, ',', '.') ?></td>
                        <td><strong><?= number_format($die_stock, 2, ',', '.') ?> L</strong></td>
                        <td class="ganancia-positiva">Bs <?= number_format($die_ganancia, 2, ',', '.') ?></td>
                        <td>
                            <?php if ($die_stock < 500): ?>
                                <span class="stock-bajo">⚠️ Stock Bajo</span>
                            <?php else: ?>
                                <span class="stock-ok">✅ Disponible</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr class="fila-total">
                        <td><strong><i class="fas fa-chart-bar"></i> Totales Generales</strong></td>
                        <td><strong><?= number_format($total_entradas, 2, ',', '.') ?> L</strong></td>
                        <td><strong>Bs <?= number_format($total_costo, 2, ',', '.') ?></strong></td>
                        <td><strong><?= number_format($total_salidas, 2, ',', '.') ?> L</strong></td>
                        <td><strong>Bs <?= number_format($total_valor, 2, ',', '.') ?></strong></td>
                        <td><strong><?= number_format($total_stock, 2, ',', '.') ?> L</strong></td>
                        <td class="ganancia-positiva" style="font-size:16px;">💰 Bs <?= number_format($total_ganancia, 2, ',', '.') ?></td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>