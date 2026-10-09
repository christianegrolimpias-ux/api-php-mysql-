<?php
date_default_timezone_set('America/La_Paz'); // ✅ Zona horaria Bolivia
include_once 'conexion.php';
verificarSesion();
$nombre_admin = $_SESSION['usuario_nombre'] ?? 'Administrador';
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$es_admin = (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'administrador') 
         || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'administrador');

// === COSTOS FIJOS ===
$COSTO_PRECINTO   = 2.40;  // Bs por cada precinto
$COSTO_FORMULARIO = 3.00;  // Bs por cada formulario

$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && $es_admin) {
    $fecha               = $_POST['fecha'] ?? date('Y-m-d');
    $n_autorizacion      = trim($_POST['n_autorizacion'] ?? '');
    $n_factura           = trim($_POST['n_factura'] ?? '');
    $cantidad_precintos  = intval($_POST['cantidad_precintos'] ?? 0);
    $cantidad_formularios = intval($_POST['cantidad_formularios'] ?? 0);
    $litros_recibidos    = floatval($_POST['litros_recibidos'] ?? 0);
    $precio_unitario     = floatval($_POST['precio_unitario'] ?? 0);
    $recepcion_n         = trim($_POST['recepcion_n'] ?? '');
    $reingreso_n         = trim($_POST['reingreso_n'] ?? '');
    $demasia_litros      = floatval($_POST['demasia_litros'] ?? 0);
    $volumen_despachado  = floatval($_POST['volumen_despachado'] ?? 0);
    $vales               = floatval($_POST['vales'] ?? 0);
    $calibracion_otros   = floatval($_POST['calibracion_otros'] ?? 0);
    $mermas              = floatval($_POST['mermas'] ?? 0);
    $merma_recepcion     = floatval($_POST['merma_recepcion'] ?? 0);
    $turno               = $_POST['turno'] ?? 'mañana';

    // === CÁLCULOS ===
    $volumen_total        = $litros_recibidos + $demasia_litros;
    $subtotal_combustible = $volumen_total * $precio_unitario;
    $costo_precintos      = $cantidad_precintos * $COSTO_PRECINTO;
    $costo_formularios    = $cantidad_formularios * $COSTO_FORMULARIO;
    $total_ingreso        = $subtotal_combustible + $costo_precintos + $costo_formularios;
    $saldo_final          = $volumen_total - $volumen_despachado - $vales - $calibracion_otros - $mermas - $merma_recepcion;
    
    if ($litros_recibidos > 0) {
        $sql = "INSERT INTO movimientos (
            tipo, producto, fecha, turno, usuario_id,
            n_autorizacion, n_factura, precintos, formularios,
            litros_recibidos, precio_nacional, recepcion_n, reingreso_n,
            demasia_litros, litros_vales, calibracion_otros,
            mermas, merma_recepcion, volumen_total,
            costo_precintos, costo_formularios, total_ingreso, saldo_final
        ) VALUES (
            'entrada', 'diesel_oil', '$fecha', '$turno', $usuario_id,
            '$n_autorizacion', '$n_factura', $cantidad_precintos, $cantidad_formularios,
            $litros_recibidos, $precio_unitario, '$recepcion_n', '$reingreso_n',
            $demasia_litros, $vales, $calibracion_otros,
            $mermas, $merma_recepcion, $volumen_total,
            $costo_precintos, $costo_formularios, $total_ingreso, $saldo_final
        )";
        
        if (mysqli_query($conn, $sql)) {
            $mensaje = "✅ REGISTRO GUARDADO:<br>
                        Precintos: $cantidad_precintos × Bs $COSTO_PRECINTO = Bs " . number_format($costo_precintos, 2, ',', '.') . "<br>
                        Formularios: $cantidad_formularios × Bs $COSTO_FORMULARIO = Bs " . number_format($costo_formularios, 2, ',', '.') . "<br>
                        Total Compra: Bs " . number_format($total_ingreso, 2, ',', '.') . "<br>
                        Litros Recibidos: " . number_format($litros_recibidos, 2, ',', '.') . " L<br>
                        Saldo Final: " . number_format($saldo_final, 2, ',', '.') . " L";
            $tipo_mensaje = "exito";
        } else {
            $mensaje = "❌ Error: " . mysqli_error($conn);
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "❌ Ingresa una cantidad mayor a 0 litros";
        $tipo_mensaje = "error";
    }
}

$res_compra = mysqli_query($conn, "SELECT SUM(litros_recibidos) AS total FROM movimientos WHERE tipo='entrada' AND producto='diesel_oil'");
$die_compra = $res_compra ? (mysqli_fetch_assoc($res_compra)['total'] ?? 0) : 0;
$res_venta = mysqli_query($conn, "SELECT SUM(litros_vales) AS total FROM movimientos WHERE producto='diesel_oil'");
$die_venta = $res_venta ? (mysqli_fetch_assoc($res_venta)['total'] ?? 0) : 0;
$die_stock = $die_compra - $die_venta;
$UMBRAL_DIESEL = 3000;
$alerta_stock = ($die_stock < $UMBRAL_DIESEL);
$inicial = mb_substr($nombre_admin, 0, 1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diésel Oil | IMANOL</title>
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
        .contenedor { max-width: 1400px; margin: 0 auto; }
        .btn-regresar {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 10px 20px;
            background: #F3F4F6; color: #4B5563;
            border: 1px solid #E5E7EB; border-radius: 10px;
            text-decoration: none; font-size: 14px; margin-bottom: 24px;
            transition: all 0.25s ease;
        }
        .btn-regresar:hover {
            background: #FEE2E2; color: #991B1B;
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
            background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 24px; color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        .cabecera-texto h1 { font-size: 22px; color: #B91C1C; margin-bottom: 4px; }
        .cabecera-texto p { font-size: 13px; color: #6B7280; }
        .caja-mensaje {
            border-radius: 12px; padding: 14px 18px; margin-bottom: 20px;
            display: flex; align-items: flex-start; gap: 12px;
            border: 1px solid transparent;
        }
        .exito {
            background: #F0FDF4; border-color: #BBF7D0;
            color: #166534;
        }
        .error {
            background: #FEF2F2; border-color: #FECACA;
            color: #991B1B;
        }
        .contenedor-tarjetas {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 28px;
        }
        .tarjeta {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 14px;
            padding: 22px;
            position: relative;
            overflow: visible;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            transition: all 0.35s ease;
            cursor: default;
        }
        .tarjeta::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            border-radius: 14px 14px 0 0;
            transition: all 0.35s ease;
        }
        .tarjeta:nth-child(1)::before {
            background: linear-gradient(90deg, #DC2626, #EF4444, #DC2626);
            box-shadow: 0 -4px 12px rgba(220, 38, 38, 0.4);
        }
        .tarjeta:nth-child(2)::before {
            background: linear-gradient(90deg, #3B82F6, #60A5FA, #3B82F6);
            box-shadow: 0 -4px 12px rgba(59, 130, 246, 0.4);
        }
        .tarjeta.stock::before {
            background: linear-gradient(90deg, #22C55E, #4ADE80, #22C55E);
            box-shadow: 0 -4px 12px rgba(34, 197, 94, 0.4);
        }
        .tarjeta.stock.alerta::before {
            background: linear-gradient(90deg, #DC2626, #EF4444, #DC2626);
            box-shadow: 0 -4px 12px rgba(220, 38, 38, 0.4);
        }
        .tarjeta:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        }
        .tarjeta h3 {
            font-size: 13px; color: #6B7280; font-weight: 500;
            margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
        }
        .tarjeta .valor {
            font-size: 28px; font-weight: 700;
            color: #B91C1C;
        }
        .tarjeta.stock .valor { color: #16A34A; }
        .tarjeta.stock.alerta .valor { color: #DC2626; }
        .etiqueta-alerta {
            margin-top: 10px; padding: 5px 12px;
            background: #FEF2F2; color: #DC2626;
            border-radius: 8px; font-size: 12px;
            display: inline-block;
        }
        .caja-formulario {
            background: #FFFFFF;
            border: 1px solid #FECACA;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.06);
        }
        .titulo-form {
            font-size: 17px; color: #B91C1C; margin-bottom: 24px;
            display: flex; align-items: center; gap: 10px;
            padding-bottom: 14px; border-bottom: 1px solid #FECACA;
        }
        .fila-seis, .fila-cinco, .fila-cuatro, .fila-doble, .fila-tres {
            display: grid; gap: 16px; margin-bottom: 16px;
        }
        .fila-seis { grid-template-columns: repeat(6,1fr); }
        .fila-cinco { grid-template-columns: 2fr 1fr 1fr 1fr 1fr; }
        .fila-cuatro { grid-template-columns: repeat(4,1fr); }
        .fila-tres { grid-template-columns: repeat(3,1fr); }
        .fila-doble { grid-template-columns: repeat(2,1fr); }
        .grupo-campo { margin-bottom: 14px; }
        label {
            display: block; font-size: 13px; color: #374151;
            margin-bottom: 7px; font-weight: 500;
        }
        label span { color: #DC2626; }
        input, select {
            width: 100%; padding: 12px 16px;
            background: #F9FAFB; border: 1px solid #D1D5DB;
            border-radius: 10px; color: #1F2937;
            font-size: 14px; transition: all 0.25s ease;
        }
        input:focus, select:focus {
            outline: none; border-color: #DC2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
            background: #FEF2F2;
        }
        input[readonly] {
            background: #F0FDF4; border-color: #86EFAC;
            color: #166534; font-weight: 600;
        }
        .resumen-costos {
            background: #F0FDF4; border: 1px solid #BBF7D0;
            border-radius: 12px; padding: 20px; margin: 20px 0;
        }
        .resumen-costos h4 {
            color: #166534; margin-bottom: 14px; font-size: 15px;
        }
        .fila-total {
            display: flex; justify-content: space-between;
            padding: 10px 0; border-bottom: 1px solid #D1FAE5;
        }
        .fila-total:last-child {
            border-bottom: none; font-size: 18px; font-weight: 700;
            color: #15803D; margin-top: 6px;
        }
        .boton-guardar {
            width: 100%; padding: 14px 24px;
            background: linear-gradient(90deg, #991B1B, #DC2626, #EF4444);
            color: #FFFFFF; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            margin-top: 10px; transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        .boton-guardar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
        }
        .etiqueta-seccion {
            display: inline-block; padding: 7px 16px;
            border-radius: 8px; font-size: 13px;
            font-weight: 600; margin: 10px 0;
        }
        .etiqueta-entrada {
            background: #F0FDF4; color: #166534;
            border: 1px solid #BBF7D0;
        }
        .etiqueta-salida {
            background: #FEF2F2; color: #991B1B;
            border: 1px solid #FECACA;
        }
        .etiqueta-saldo {
            background: #EFF6FF; color: #1E40AF;
            border: 1px solid #BFDBFE;
        }
        @media (max-width: 1200px) { .fila-seis { grid-template-columns: repeat(3,1fr); } }
        @media (max-width: 768px) { 
            .fila-seis, .fila-cinco, .fila-cuatro, .fila-doble, .fila-tres { grid-template-columns: 1fr; } 
            .sidebar { width: 220px; }
            .main { margin-left: 220px; padding: 16px; }
        }
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="logo-box">
        <img src="imagenes/logo.png" alt="IMANOL">
        <div><h2>MANOL</h2><p>Dios Con Nosotros</p></div>
    </div>
    <nav>
        <a href="index_admin.php"><i class="fas fa-chart-pie"></i> Inicio</a>
        <a href="gasolina.php"><i class="fas fa-gas-pump"></i> Gasolina</a>
        <a href="diesel.php" class="activo"><i class="fas fa-gas-pump"></i> Diésel</a>
        <a href="reportes_compras.php"><i class="fas fa-file-invoice-dollar"></i> Compras Combustible</a>
        <a href="registro.php"><i class="fas fa-th-list"></i> Registros</a>
        <a href="reportes.php"><i class="fas fa-chart-bar"></i> Reportes</a>
        <a href="reporte_vales.php"><i class="fas fa-ticket-alt"></i> Reporte Vales</a>
        <a href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a>
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
                <p>Administrador</p>
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
            <div class="circulo-logo">D</div>
            <div class="cabecera-texto">
                <h1>PRODUCTO: DIÉSEL OIL</h1>
                <p>Recepción, Ingresos, Salidas y Saldos — Control Completo</p>
            </div>
        </div>

        <?php if ($mensaje): ?>
        <div class="caja-mensaje <?= $tipo_mensaje ?>">
            <i class="fas <?= $tipo_mensaje==='exito'?'fa-check-circle':'fa-exclamation-triangle' ?>"></i>
            <div><?= $mensaje ?></div>
        </div>
        <?php endif; ?>

        <div class="contenedor-tarjetas">
            <div class="tarjeta">
                <h3><i class="fas fa-download" style="color:#DC2626;"></i> Litros Recibidos</h3>
                <div class="valor"><?= number_format($die_compra,0,',','.') ?> L</div>
            </div>
            <div class="tarjeta">
                <h3><i class="fas fa-upload" style="color:#3B82F6;"></i> Litros en Vales</h3>
                <div class="valor" style="color:#2563EB;"><?= number_format($die_venta,0,',','.') ?> L</div>
            </div>
            <div class="tarjeta stock <?= $alerta_stock?'alerta':'' ?>">
                <h3><i class="fas fa-chart-pie"></i> Stock Disponible</h3>
                <div class="valor"><?= number_format($die_stock,0,',','.') ?> L</div>
                <?php if($alerta_stock): ?><div class="etiqueta-alerta">⚠️ Stock bajo — Nivel mínimo: <?= number_format($UMBRAL_DIESEL,0,',','.') ?> L</div><?php endif; ?>
            </div>
        </div>

        <?php if ($es_admin): ?>
        <div class="caja-formulario">
            <h2 class="titulo-form"><i class="fas fa-clipboard-list"></i> CONTROL DIARIO — Diésel Oil</h2>
            <form method="post" id="form-control">
                <div class="fila-seis">
                    <div class="grupo-campo"><label>Fecha</label><input type="date" name="fecha" value="<?= date('Y-m-d') ?>"></div>
                    <div class="grupo-campo"><label>N° Autorización Local</label><input type="text" name="n_autorizacion"></div>
                    <div class="grupo-campo"><label>N° Factura YPFB/Compra</label><input type="text" name="n_factura"></div>
                    <div class="grupo-campo"><label>Cant. Precintos Recibidos</label><input type="number" id="cantidad_precintos" name="cantidad_precintos" value="0" min="0" oninput="calcularTodo()"></div>
                    <div class="grupo-campo"><label>Cant. Formularios Recibidos</label><input type="number" id="cantidad_formularios" name="cantidad_formularios" value="0" min="0" oninput="calcularTodo()"></div>
                    <div class="grupo-campo"><label>Turno</label><select name="turno"><option value="mañana">🌅 Mañana</option><option value="tarde">🌆 Tarde</option></select></div>
                </div>

                <span class="etiqueta-seccion etiqueta-entrada">📥 RECEPCIONES / INGRESOS</span>
                <div class="fila-cinco">
                    <div class="grupo-campo"><label>Litros Recibidos <span>*</span></label><input type="number" step="0.01" id="litros_recibidos" name="litros_recibidos" required oninput="calcularTodo()"></div>
                    <div class="grupo-campo"><label>Precio Unitario (Bs)</label><input type="number" step="0.01" id="precio_unitario" name="precio_unitario" oninput="calcularTodo()"></div>
                    <div class="grupo-campo"><label>Recepción N°</label><input type="text" name="recepcion_n"></div>
                    <div class="grupo-campo"><label>Reingreso N°</label><input type="text" name="reingreso_n"></div>
                    <div class="grupo-campo"><label>Demasía (L)</label><input type="number" step="0.01" id="demasia_litros" name="demasia_litros" value="0.00" oninput="calcularTodo()"></div>
                </div>

                <div class="fila-tres">
                    <div class="grupo-campo"><label>Volumen Total (L)</label><input type="text" id="volumen_total" readonly value="0.00"></div>
                    <div class="grupo-campo"><label>Subtotal Combustible (Bs)</label><input type="text" id="subtotal_combustible" readonly value="0.00"></div>
                    <div class="grupo-campo"><label>Total General (Bs)</label><input type="text" id="total_ingreso" readonly value="0.00" style="font-weight:bold; color:#15803D;"></div>
                </div>

                <!-- === RESUMEN DE COSTOS ADICIONALES === -->
                <div class="resumen-costos" id="resumenCostos" style="display: none;">
                    <h4>💰 Desglose de Costos Adicionales</h4>
                    <div class="fila-total">
                        <span>Precintos × Bs <?= number_format($COSTO_PRECINTO, 2, ',', '.') ?></span>
                        <span id="monto_precintos">Bs 0,00</span>
                    </div>
                    <div class="fila-total">
                        <span>Formularios × Bs <?= number_format($COSTO_FORMULARIO, 2, ',', '.') ?></span>
                        <span id="monto_formularios">Bs 0,00</span>
                    </div>
                </div>

                <span class="etiqueta-seccion etiqueta-salida">📤 SALIDAS / DESPACHOS</span>
                <div class="fila-cuatro">
                    <div class="grupo-campo"><label>Volumen Despachado (L)</label><input type="number" step="0.01" id="volumen_despachado" name="volumen_despachado" value="0.00"></div>
                    <div class="grupo-campo"><label>Vales (L)</label><input type="number" step="0.01" id="vales" name="vales" value="0.00"></div>
                    <div class="grupo-campo"><label>Calibración / Otros (L)</label><input type="number" step="0.01" id="calibracion_otros" name="calibracion_otros" value="0.00"></div>
                    <div class="grupo-campo"><label>Mermas (L)</label><input type="number" step="0.01" id="mermas" name="mermas" value="0.00"></div>
                </div>

                <span class="etiqueta-seccion etiqueta-saldo">💰 SALDOS</span>
                <div class="fila-doble">
                    <div class="grupo-campo"><label>Merma por Recepción (L)</label><input type="number" step="0.01" id="merma_recepcion" name="merma_recepcion" value="0.00"></div>
                    <div class="grupo-campo"><label>Saldo Final (L)</label><input type="text" id="saldo_final" readonly value="0.00" style="font-weight:700; font-size:16px;"></div>
                </div>

                <button type="submit" class="boton-guardar"><i class="fas fa-save"></i> GUARDAR REGISTRO COMPLETO</button>
            </form>
        </div>
        <?php else: ?>
        <div class="caja-formulario" style="text-align:center; padding:40px;">
            <i class="fas fa-lock" style="font-size:48px; color:#9CA3AF;"></i>
            <h3 style="margin:20px 0 10px; color:#4B5563;">Acceso Restringido</h3>
            <p style="color:#6B7280;">Solo el Administrador puede registrar nuevas entradas.</p>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
const COSTO_PRECINTO   = <?= $COSTO_PRECINTO ?>;
const COSTO_FORMULARIO = <?= $COSTO_FORMULARIO ?>;

const lr = document.getElementById('litros_recibidos');
const pu = document.getElementById('precio_unitario');
const dm = document.getElementById('demasia_litros');
const cp = document.getElementById('cantidad_precintos');
const cf = document.getElementById('cantidad_formularios');
const vt = document.getElementById('volumen_total');
const sc = document.getElementById('subtotal_combustible');
const ti = document.getElementById('total_ingreso');
const vd = document.getElementById('volumen_despachado');
const vl = document.getElementById('vales');
const co = document.getElementById('calibracion_otros');
const me = document.getElementById('mermas');
const mr = document.getElementById('merma_recepcion');
const sf = document.getElementById('saldo_final');
const resumen = document.getElementById('resumenCostos');
const mp = document.getElementById('monto_precintos');
const mf = document.getElementById('monto_formularios');

function calcularTodo() {
    const recibidos = parseFloat(lr.value) || 0;
    const precio = parseFloat(pu.value) || 0;
    const demasia = parseFloat(dm.value) || 0;
    const precintos = parseInt(cp.value) || 0;
    const formularios = parseInt(cf.value) || 0;
    const despachado = parseFloat(vd.value) || 0;
    const vales = parseFloat(vl.value) || 0;
    const calibracion = parseFloat(co.value) || 0;
    const mermas = parseFloat(me.value) || 0;
    const mermaRec = parseFloat(mr.value) || 0;
    
    const volTotal = recibidos + demasia;
    const subtotalComb = volTotal * precio;
    const costoPrec = precintos * COSTO_PRECINTO;
    const costoForm = formularios * COSTO_FORMULARIO;
    const totalIng = subtotalComb + costoPrec + costoForm;
    const saldoF = volTotal - despachado - vales - calibracion - mermas - mermaRec;
    
    vt.value = volTotal.toFixed(2);
    sc.value = subtotalComb.toFixed(2) + ' Bs';
    ti.value = totalIng.toFixed(2) + ' Bs';
    sf.value = saldoF.toFixed(2);
    
    if (precintos > 0 || formularios > 0) {
        resumen.style.display = 'block';
        mp.textContent = 'Bs ' + costoPrec.toFixed(2);
        mf.textContent = 'Bs ' + costoForm.toFixed(2);
    } else {
        resumen.style.display = 'none';
    }
}

[lr, pu, dm, cp, cf, vd, vl, co, me, mr].forEach(c => c.addEventListener('input', calcularTodo));
</script>
</body>
</html>