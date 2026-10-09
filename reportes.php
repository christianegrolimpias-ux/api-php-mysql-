<?php
date_default_timezone_set('America/La_Paz');
include_once 'conexion.php';
verificarSesion();
$es_admin = (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin', 'administrador'])) 
        || (isset($_SESSION['usuario_tipo']) && in_array($_SESSION['usuario_tipo'], ['admin', 'administrador']));
if (!$es_admin) {
    die("❌ No tienes permiso para acceder a esta página.");
}
$nombre_admin = $_SESSION['usuario_nombre'] ?? 'Administrador';
$inicial = mb_substr($nombre_admin, 0, 1);

// === PRECIOS OFICIALES ===
$PRECIO_GASOLINA = 6.96;
$PRECIO_DIESEL   = 17.95;

$fecha_consulta = $_GET['fecha'] ?? date('Y-m-d');

if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    mysqli_query($conn, "DELETE FROM movimientos WHERE id = $id");
    header("Location: reportes.php?fecha=$fecha_consulta");
    exit;
}

$ventas = mysqli_query($conn, "
    SELECT m.*, u.nombre AS usuario_nombre 
    FROM movimientos m
    LEFT JOIN usuarios u ON m.usuario_id = u.id
    WHERE DATE(m.fecha) = '$fecha_consulta'
      AND m.tipo = 'salida'
    ORDER BY 
      m.producto = 'gasolina_especial' DESC,
      m.dispensador ASC,
      m.turno ASC
");

$gasolina_ventas = [];
$diesel_ventas = [];
$total_gas_litros = $total_gas_monto = $total_gas_vales = $total_gas_real = 0;
$total_die_litros = $total_die_monto = $total_die_vales = $total_die_real = 0;

while ($v = mysqli_fetch_assoc($ventas)) {
    $producto = $v['producto'];
    $litros = $v['litros_despachados'] ?? 0;
    $monto = $v['total_venta'] ?? ($litros * ($producto === 'gasolina_especial' ? $PRECIO_GASOLINA : $PRECIO_DIESEL));
    $vales = $v['monto_vales'] ?? 0;
    $real = $v['dinero_entregado'] ?? ($monto - $vales);
    
    if ($producto === 'gasolina_especial') {
        $gasolina_ventas[] = $v;
        $total_gas_litros += $litros;
        $total_gas_monto += $monto;
        $total_gas_vales += $vales;
        $total_gas_real += $real;
    }
    if ($producto === 'diesel_oil') {
        $diesel_ventas[] = $v;
        $total_die_litros += $litros;
        $total_die_monto += $monto;
        $total_die_vales += $vales;
        $total_die_real += $real;
    }
}

$gran_litros = $total_gas_litros + $total_die_litros;
$gran_monto = $total_gas_monto + $total_die_monto;
$gran_vales = $total_gas_vales + $total_die_vales;
$gran_real = $total_gas_real + $total_die_real;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Diario de Ventas — IMANOL</title>
    <link rel="icon" type="image/png" sizes="80x80" href="imagenes/favicon2.png">
    <link rel="icon" type="image/png" sizes="32x32" href="imagenes/favicon2.png">
    <link rel="icon" type="image/png" sizes="16x16" href="imagenes/favicon2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #F8F9FA; color: #1F2937; display: flex; min-height: 100vh; }
        
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
            display: flex; align-items: center; gap: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.1);
        }
        .logo-box img { max-width: 55px; }
        .logo-box h2 { font-size: 20px; color: #fff; margin: 0; }
        .logo-box p { font-size: 10px; color: rgba(255,255,255,0.8); margin: 1px 0 0 0; }
        .sidebar nav {
            padding: 10px; display: flex; flex-direction: column; gap: 2px; flex: 1; overflow-y: auto;
        }
        .sidebar nav a {
            display: flex; align-items: center; gap: 10px; padding: 9px 12px;
            color: rgba(255,255,255,0.9); text-decoration: none; border-radius: 8px;
            font-size: 13px; font-weight: 500; transition: all 0.25s ease;
        }
        .sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.25); color: #fff; transform: translateX(3px);
        }
        .sidebar nav a.activo {
            background: rgba(255, 255, 255, 0.3); color: #fff; border-left: 3px solid #fff; font-weight: 600;
        }
        .sidebar .user-info {
            padding: 12px; border-top: 1px solid rgba(255, 255, 255, 0.2); background: rgba(0, 0, 0, 0.1);
        }
        .sidebar .user-perfil { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .sidebar .avatar {
            width: 34px; height: 34px; border-radius: 50%; background: #fff; color: #B91C1C;
            display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .sidebar .user-perfil h4 { font-size: 13px; color: #fff; margin: 0; }
        .sidebar .user-perfil p { font-size: 11px; color: rgba(255,255,255,0.75); margin: 2px 0 0 0; }
        .sidebar .estado {
            display: flex; align-items: center; gap: 5px; font-size: 11px; color: #90EE90; margin-bottom: 8px;
        }
        .sidebar .estado span {
            width: 7px; height: 7px; border-radius: 50%; background: #90EE90; box-shadow: 0 0 4px #90EE90;
        }
        .cerrar-btn {
            background: rgba(0, 0, 0, 0.2); color: #fff; border: none; padding: 8px; border-radius: 7px;
            width: 100%; font-size: 12px; display: flex; align-items: center; justify-content: center;
            gap: 7px; text-decoration: none; transition: all 0.2s;
        }
        .cerrar-btn:hover { background: rgba(0, 0, 0, 0.35); }
        
        .main { margin-left: 250px; flex: 1; padding: 24px 30px; background: #FFFFFF; min-height: 100vh; }
        .contenedor { max-width: 1400px; margin: 0 auto; }
        
        .btn-regresar {
            display: inline-flex; align-items: center; gap: 10px; padding: 10px 20px;
            background: #F3F4F6; color: #4B5563; border: 1px solid #E5E7EB; border-radius: 10px;
            text-decoration: none; font-size: 14px; margin-bottom: 24px; transition: all 0.25s ease;
        }
        .btn-regresar:hover {
            background: #FEF3C7; color: #92400E; border-color: #F59E0B; transform: translateX(-3px);
        }
        
        .cabecera {
            background: #FFFFFF; border: 1px solid #FDE68A; border-radius: 16px; padding: 24px 28px;
            margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.08);
        }
        .cabecera-izq { display: flex; align-items: center; gap: 20px; }
        .circulo-logo {
            width: 56px; height: 56px; border-radius: 12px;
            background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: #1F2937;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        .cabecera-texto h1 { font-size: 22px; color: #B45309; margin-bottom: 4px; }
        .cabecera-texto p { font-size: 13px; color: #6B7280; }
        
        .botones-exportar { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-excel {
            background: #10B981; color: #fff; border: none; padding: 10px 18px; border-radius: 8px;
            font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.25s ease;
        }
        .btn-excel:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
        .btn-pdf {
            background: #EF4444; color: #fff; border: none; padding: 10px 18px; border-radius: 8px;
            font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.25s ease;
        }
        .btn-pdf:hover { background: #DC2626; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
        
        .filtro { 
            background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 14px; 
            padding: 20px; margin-bottom: 24px; display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .filtro .grupo { flex: 1; min-width: 200px; }
        .filtro label { display: block; font-size: 13px; color: #374151; margin-bottom: 6px; font-weight: 500; }
        .filtro input { 
            padding: 11px 16px; border-radius: 8px; border: 1px solid #D1D5DB; 
            background: #F9FAFB; color: #1F2937; font-size: 14px; transition: all 0.25s ease; width: 100%;
        }
        .filtro input:focus {
            outline: none; border-color: #F59E0B; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15); background: #FFFBEB;
        }
        
        .seccion-producto { margin-bottom: 32px; }
        .titulo-producto {
            display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-radius: 14px;
            margin-bottom: 16px; font-size: 18px; font-weight: 700;
        }
        .titulo-gas { background: #FFFBEB; border: 1px solid #FDE68A; color: #B45309; }
        .titulo-die { background: #FEF2F2; border: 1px solid #FECACA; color: #B91C1C; }
        .titulo-gran { background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; }
        
        .tabla { 
            width: 100%; border-collapse: collapse; font-size: 14px; background: #FFFFFF;
            border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .tabla th { 
            background: linear-gradient(to bottom, #F9FAFB, #F3F4F6); padding: 14px 10px; 
            text-align: center; color: #374151; font-weight: 700; 
            border-bottom: 2px solid #E5E7EB; white-space: nowrap; 
            font-size: 12px; text-transform: uppercase; letter-spacing: 0.3px;
        }
        .tabla td { 
            padding: 14px 10px; border-bottom: 1px solid #F3F4F6; color: #4B5563;
            transition: background 0.15s ease;
        }
        .tabla tr:hover td { background: #FFFBEB; }
        .vacio { color: #6B7280; font-style: italic; padding: 40px; text-align: center; }
        .fila-total { background: #F0FDF4; font-weight: bold; border-top: 2px solid #BBF7D0; }
        
        /* === CENTRADO PROFESIONAL DE COLUMNAS === */
        .col-texto { text-align: left; }
        .col-num { 
            text-align: center; 
            font-family: 'Segoe UI', Roboto, sans-serif; 
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.2px;
        }
        .col-monto { 
            text-align: center; 
            font-family: 'Segoe UI', Roboto, sans-serif; 
            font-variant-numeric: tabular-nums;
            font-weight: 500;
        }
        
        .gas { color: #B45309; font-weight: 600; }
        .die { color: #B91C1C; font-weight: 600; }
        .verde { color: #16A34A; font-weight: 600; }
        .rojo { color: #DC2626; font-weight: 600; }
        .dispensador-n { font-weight: 600; }
        .turno-mañana { color: #2563EB; font-weight: 500; }
        .turno-tarde { color: #F97316; font-weight: 500; }
        .dato-vacio { color: #9CA3AF; font-style: italic; }
        .operador-n { font-weight: 500; color: #1F2937; }
        
        .btn-accion {
            padding: 6px 10px; border-radius: 6px; font-size: 13px; text-decoration: none; display: inline-flex;
            align-items: center; gap: 4px; margin: 2px; transition: all 0.2s; border: none; cursor: pointer;
        }
        .btn-editar { background: #FEF3C7; color: #92400E; }
        .btn-editar:hover { background: #FDE68A; transform: scale(1.05); }
        .btn-eliminar { background: #FEE2E2; color: #991B1B; }
        .btn-eliminar:hover { background: #FECACA; transform: scale(1.05); }
        
        @media (max-width: 1400px) { .tabla { display: block; overflow-x: auto; } }
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
        <div><h2>MANOL</h2><p>Dios Con Nosotros</p></div>
    </div>
    <nav>
        <a href="index_admin.php"><i class="fas fa-chart-pie"></i> Inicio</a>
        <a href="gasolina.php"><i class="fas fa-gas-pump"></i> Gasolina</a>
        <a href="diesel.php"><i class="fas fa-gas-pump"></i> Diésel</a>
        <a href="reportes_compras.php"><i class="fas fa-file-invoice-dollar"></i> Compras Combustible</a>
        <a href="registro.php"><i class="fas fa-th-list"></i> Registros</a>
        <a href="reportes.php" class="activo"><i class="fas fa-chart-bar"></i> Reportes</a>
        <a href="reporte_vales.php"><i class="fas fa-ticket-alt"></i> Reporte Vales</a>
        <a href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a>
        <a href="proveedores.php"><i class="fas fa-truck"></i> Proveedores</a>
        <a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a>
        <a href="crear_cuenta.php"><i class="fas fa-user-plus"></i> Crear Cuentas</a>
    </nav>
    <div class="user-info">
        <div class="user-perfil">
            <div class="avatar"><?= $inicial ?></div>
            <div><h4><?= $nombre_admin ?></h4><p>Administrador</p></div>
        </div>
        <div class="estado"><span></span> En línea</div>
        <a href="logout.php" class="cerrar-btn"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </div>
</aside>
<main class="main">
    <div class="contenedor" id="areaReporte">
        <a href="index_admin.php" class="btn-regresar"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
        
        <div class="cabecera">
            <div class="cabecera-izq">
                <div class="circulo-logo"><i class="fas fa-flag-checkered"></i></div>
                <div class="cabecera-texto">
                    <h1>Reporte Diario de Ventas</h1>
                    <p>Fecha: <strong><?= date('d/m/Y', strtotime($fecha_consulta)) ?></strong> — 
                       Precios oficiales: 
                       <span class="gas">Bs <?= number_format($PRECIO_GASOLINA, 2, ',', '.') ?>/L Gasolina</span> · 
                       <span class="die">Bs <?= number_format($PRECIO_DIESEL, 2, ',', '.') ?>/L Diésel</span>
                    </p>
                </div>
            </div>
            <div class="botones-exportar">
                <button class="btn-excel" onclick="exportarExcel()"><i class="fas fa-file-excel"></i> Excel</button>
                <button class="btn-pdf" onclick="exportarPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
        </div>

        <form class="filtro" method="GET" action="reportes.php">
            <div class="grupo">
                <label>📅 Seleccionar Fecha</label>
                <input type="date" name="fecha" value="<?= $fecha_consulta ?>" onchange="this.form.submit()">
            </div>
        </form>

        <!-- ⛽ GASOLINA ESPECIAL -->
        <div class="seccion-producto">
            <h2 class="titulo-producto titulo-gas">
                <i class="fas fa-gas-pump" style="color:#F59E0B;"></i> GASOLINA ESPECIAL
            </h2>
            <table class="tabla" id="tablaGasolina">
                <thead>
                    <tr>
                        <th class="col-texto">Operador</th>
                        <th class="col-texto">Bomba / Dispensador</th>
                        <th class="col-texto">Turno</th>
                        <th class="col-num">Lectura Inicial</th>
                        <th class="col-num">Lectura Final</th>
                        <th class="col-num">Litros Despachados</th>
                        <th class="col-monto">Total Venta (Bs)</th>
                        <th class="col-monto">Vales (Bs)</th>
                        <th class="col-monto">Dinero Recibido (Bs)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($gasolina_ventas)): ?>
                        <?php foreach ($gasolina_ventas as $v): 
                            $turno_class = ($v['turno'] === 'mañana') ? 'turno-mañana' : 'turno-tarde';
                            $disp = $v['dispensador'] ?? '<span class="dato-vacio">—</span>';
                            $operador = $v['usuario_nombre'] ?? '<span class="dato-vacio">Desconocido</span>';
                            $liq_inicial = $v['lectura_inicial'] ?? 0;
                            $liq_final = $v['lectura_final'] ?? 0;
                            $litros = $v['litros_despachados'] ?? 0;
                            $total_v = $v['total_venta'] ?? ($litros * $PRECIO_GASOLINA);
                            $vales = $v['monto_vales'] ?? 0;
                            $recibido = $v['dinero_entregado'] ?? ($total_v - $vales);
                        ?>
                        <tr>
                            <td class="operador-n col-texto"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($operador) ?></td>
                            <td class="dispensador-n col-texto"><i class="fas fa-gas-pump"></i> Bomba N°<?= $disp ?></td>
                            <td class="<?= $turno_class ?> col-texto">
                                <i class="fas fa-<?= $v['turno'] === 'mañana' ? 'sun' : 'mountain-sun' ?>"></i> 
                                <?= ucfirst($v['turno'] ?? '') ?>
                            </td>
                            <td class="col-num"><?= number_format($liq_inicial, 2, ',', '.') ?></td>
                            <td class="col-num"><?= number_format($liq_final, 2, ',', '.') ?></td>
                            <td class="col-num gas"><strong><?= number_format($litros, 2, ',', '.') ?> L</strong></td>
                            <td class="col-monto verde">Bs <?= number_format($total_v, 2, ',', '.') ?></td>
                            <td class="col-monto rojo">Bs <?= number_format($vales, 2, ',', '.') ?></td>
                            <td class="col-monto verde"><strong>Bs <?= number_format($recibido, 2, ',', '.') ?></strong></td>
                            <td style="text-align: center;">
                                <a href="editar_movimiento.php?id=<?= $v['id'] ?>&fecha=<?= $fecha_consulta ?>" class="btn-accion btn-editar" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="reportes.php?eliminar=<?= $v['id'] ?>&fecha=<?= $fecha_consulta ?>" 
                                   class="btn-accion btn-eliminar" 
                                   onclick="return confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')"
                                   title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="fila-total">
                            <td colspan="3" class="col-texto"><strong>📊 TOTAL GASOLINA</strong></td>
                            <td class="col-num">—</td>
                            <td class="col-num">—</td>
                            <td class="col-num gas"><strong><?= number_format($total_gas_litros, 2, ',', '.') ?> L</strong></td>
                            <td class="col-monto verde"><strong>Bs <?= number_format($total_gas_monto, 2, ',', '.') ?></strong></td>
                            <td class="col-monto rojo"><strong>Bs <?= number_format($total_gas_vales, 2, ',', '.') ?></strong></td>
                            <td class="col-monto verde"><strong>Bs <?= number_format($total_gas_real, 2, ',', '.') ?></strong></td>
                            <td></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="10" class="vacio">⛽ Sin ventas de Gasolina en esta fecha.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 🛢️ DIÉSEL OIL -->
        <div class="seccion-producto">
            <h2 class="titulo-producto titulo-die">
                <i class="fas fa-gas-pump" style="color:#DC2626;"></i> DIÉSEL OIL
            </h2>
            <table class="tabla" id="tablaDiesel">
                <thead>
                    <tr>
                        <th class="col-texto">Operador</th>
                        <th class="col-texto">Bomba / Dispensador</th>
                        <th class="col-texto">Turno</th>
                        <th class="col-num">Lectura Inicial</th>
                        <th class="col-num">Lectura Final</th>
                        <th class="col-num">Litros Despachados</th>
                        <th class="col-monto">Total Venta (Bs)</th>
                        <th class="col-monto">Vales (Bs)</th>
                        <th class="col-monto">Dinero Recibido (Bs)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($diesel_ventas)): ?>
                        <?php foreach ($diesel_ventas as $v): 
                            $turno_class = ($v['turno'] === 'mañana') ? 'turno-mañana' : 'turno-tarde';
                            $disp = $v['dispensador'] ?? '<span class="dato-vacio">—</span>';
                            $operador = $v['usuario_nombre'] ?? '<span class="dato-vacio">Desconocido</span>';
                            $liq_inicial = $v['lectura_inicial'] ?? 0;
                            $liq_final = $v['lectura_final'] ?? 0;
                            $litros = $v['litros_despachados'] ?? 0;
                            $total_v = $v['total_venta'] ?? ($litros * $PRECIO_DIESEL);
                            $vales = $v['monto_vales'] ?? 0;
                            $recibido = $v['dinero_entregado'] ?? ($total_v - $vales);
                        ?>
                        <tr>
                            <td class="operador-n col-texto"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($operador) ?></td>
                            <td class="dispensador-n col-texto"><i class="fas fa-gas-pump"></i> Bomba N°<?= $disp ?></td>
                            <td class="<?= $turno_class ?> col-texto">
                                <i class="fas fa-<?= $v['turno'] === 'mañana' ? 'sun' : 'mountain-sun' ?>"></i> 
                                <?= ucfirst($v['turno'] ?? '') ?>
                            </td>
                            <td class="col-num"><?= number_format($liq_inicial, 2, ',', '.') ?></td>
                            <td class="col-num"><?= number_format($liq_final, 2, ',', '.') ?></td>
                            <td class="col-num die"><strong><?= number_format($litros, 2, ',', '.') ?> L</strong></td>
                            <td class="col-monto verde">Bs <?= number_format($total_v, 2, ',', '.') ?></td>
                            <td class="col-monto rojo">Bs <?= number_format($vales, 2, ',', '.') ?></td>
                            <td class="col-monto verde"><strong>Bs <?= number_format($recibido, 2, ',', '.') ?></strong></td>
                            <td style="text-align: center;">
                                <a href="editar_movimiento.php?id=<?= $v['id'] ?>&fecha=<?= $fecha_consulta ?>" class="btn-accion btn-editar" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="reportes.php?eliminar=<?= $v['id'] ?>&fecha=<?= $fecha_consulta ?>" 
                                   class="btn-accion btn-eliminar" 
                                   onclick="return confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')"
                                   title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="fila-total">
                            <td colspan="3" class="col-texto"><strong>📊 TOTAL DIÉSEL</strong></td>
                            <td class="col-num">—</td>
                            <td class="col-num">—</td>
                            <td class="col-num die"><strong><?= number_format($total_die_litros, 2, ',', '.') ?> L</strong></td>
                            <td class="col-monto verde"><strong>Bs <?= number_format($total_die_monto, 2, ',', '.') ?></strong></td>
                            <td class="col-monto rojo"><strong>Bs <?= number_format($total_die_vales, 2, ',', '.') ?></strong></td>
                            <td class="col-monto verde"><strong>Bs <?= number_format($total_die_real, 2, ',', '.') ?></strong></td>
                            <td></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="10" class="vacio">🛢️ Sin ventas de Diésel en esta fecha.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 💰 RESUMEN FINAL DEL DÍA -->
        <div class="seccion-producto">
            <h2 class="titulo-producto titulo-gran">
                <i class="fas fa-coins" style="color:#22C55E;"></i> RESUMEN FINAL DEL DÍA
            </h2>
            <table class="tabla">
                <thead>
                    <tr>
                        <th class="col-texto">Producto</th>
                        <th class="col-num">Litros Despachados</th>
                        <th class="col-monto">Total Venta (Bs)</th>
                        <th class="col-monto">Vales (Bs)</th>
                        <th class="col-monto">Dinero Recibido (Bs)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="gas col-texto"><strong>⛽ Gasolina Especial</strong></td>
                        <td class="col-num gas"><?= number_format($total_gas_litros, 2, ',', '.') ?> L</td>
                        <td class="col-monto verde">Bs <?= number_format($total_gas_monto, 2, ',', '.') ?></td>
                        <td class="col-monto rojo">Bs <?= number_format($total_gas_vales, 2, ',', '.') ?></td>
                        <td class="col-monto verde">Bs <?= number_format($total_gas_real, 2, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td class="die col-texto"><strong>🛢️ Diésel Oil</strong></td>
                        <td class="col-num die"><?= number_format($total_die_litros, 2, ',', '.') ?> L</td>
                        <td class="col-monto verde">Bs <?= number_format($total_die_monto, 2, ',', '.') ?></td>
                        <td class="col-monto rojo">Bs <?= number_format($total_die_vales, 2, ',', '.') ?></td>
                        <td class="col-monto verde">Bs <?= number_format($total_die_real, 2, ',', '.') ?></td>
                    </tr>
                    <tr class="fila-total">
                        <td class="col-texto"><strong>💰 TOTAL GENERAL</strong></td>
                        <td class="col-num"><strong><?= number_format($gran_litros, 2, ',', '.') ?> L</strong></td>
                        <td class="col-monto verde"><strong>Bs <?= number_format($gran_monto, 2, ',', '.') ?></strong></td>
                        <td class="col-monto rojo"><strong>Bs <?= number_format($gran_vales, 2, ',', '.') ?></strong></td>
                        <td class="col-monto verde"><strong>Bs <?= number_format($gran_real, 2, ',', '.') ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script>
function exportarExcel() {
    const filasGas = document.querySelectorAll('#tablaGasolina tr');
    const filasDie = document.querySelectorAll('#tablaDiesel tr');
    const datos = [];
    
    datos.push(['GASOLINA ESPECIAL']);
    filasGas.forEach(fila => {
        const celdas = fila.querySelectorAll('th, td');
        const filaDatos = [];
        for (let i = 0; i < celdas.length - 1; i++) {
            filaDatos.push(celdas[i].innerText.replace(/<[^>]*>/g, '').trim());
        }
        datos.push(filaDatos);
    });
    
    datos.push([]);
    datos.push(['DIÉSEL OIL']);
    filasDie.forEach(fila => {
        const celdas = fila.querySelectorAll('th, td');
        const filaDatos = [];
        for (let i = 0; i < celdas.length - 1; i++) {
            filaDatos.push(celdas[i].innerText.replace(/<[^>]*>/g, '').trim());
        }
        datos.push(filaDatos);
    });
    
    datos.push([]);
    datos.push(['RESUMEN FINAL']);
    datos.push(['Gasolina', '<?= number_format($total_gas_litros, 2, ',', '.') ?> L', 'Bs <?= number_format($total_gas_monto, 2, ',', '.') ?>', 'Bs <?= number_format($total_gas_vales, 2, ',', '.') ?>', 'Bs <?= number_format($total_gas_real, 2, ',', '.') ?>']);
    datos.push(['Diésel', '<?= number_format($total_die_litros, 2, ',', '.') ?> L', 'Bs <?= number_format($total_die_monto, 2, ',', '.') ?>', 'Bs <?= number_format($total_die_vales, 2, ',', '.') ?>', 'Bs <?= number_format($total_die_real, 2, ',', '.') ?>']);
    datos.push(['TOTAL GENERAL', '<?= number_format($gran_litros, 2, ',', '.') ?> L', 'Bs <?= number_format($gran_monto, 2, ',', '.') ?>', 'Bs <?= number_format($gran_vales, 2, ',', '.') ?>', 'Bs <?= number_format($gran_real, 2, ',', '.') ?>']);
    
    const hoja = XLSX.utils.aoa_to_sheet(datos);
    const libro = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(libro, hoja, 'Ventas');
    XLSX.writeFile(libro, 'Reporte_Ventas_<?= date('d-m-Y', strtotime($fecha_consulta)) ?>.xlsx');
}

function exportarPDF() {
    const contenido = document.getElementById('areaReporte').innerHTML;
    const ventana = window.open('', '_blank');
    ventana.document.write(`
        <html>
        <head>
            <title>Reporte de Ventas - IMANOL</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; font-size: 12px; }
                h2 { color: #B45309; margin: 20px 0 10px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
                th { background: #f5f5f5; font-weight: bold; }
                .fila-total { background: #f0fdf4; font-weight: bold; }
                @media print { body { padding: 0; } }
            </style>
        </head>
        <body>
            <h1 style="color:#B45309; text-align:center;">Reporte Diario de Ventas - IMANOL</h1>
            <p style="text-align:center;"><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($fecha_consulta)) ?></p>
            ${contenido.replace(/<th>Acciones<\/th>[\s\S]*?<\/td>/gi, '')}
            <script>window.onload = function() { window.print(); window.close(); };<\/script>
        </body>
        </html>
    `);
    ventana.document.close();
}
</script>
</body>
</html>