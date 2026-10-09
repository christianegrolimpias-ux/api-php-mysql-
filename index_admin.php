<?php
// === CONFIGURAR ZONA HORARIA BOLIVIA ===
date_default_timezone_set('America/La_Paz');
include_once 'conexion.php';
verificarSesion();
$es_admin = (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'administrador') 
         || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'administrador');
if (!$es_admin) {
    header("Location: index.php");
    exit;
}
$nombre_admin = $_SESSION['usuario_nombre'] ?? 'Administrador';
$inicial = mb_substr($nombre_admin, 0, 1);

// === GASOLINA ===
$gas_compra_litros = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(litros_recibidos) AS total FROM movimientos WHERE tipo='entrada' AND producto='gasolina_especial'"))['total'] ?? 0;
$gas_venta_litros = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(litros_despachados) AS total FROM movimientos WHERE tipo='salida' AND producto='gasolina_especial'"))['total'] ?? 0;
$gas_stock = $gas_compra_litros - $gas_venta_litros;

// === DIÉSEL ===
$die_compra_litros = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(litros_recibidos) AS total FROM movimientos WHERE tipo='entrada' AND producto='diesel_oil'"))['total'] ?? 0;
$die_venta_litros = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(litros_despachados) AS total FROM movimientos WHERE tipo='salida' AND producto='diesel_oil'"))['total'] ?? 0;
$die_stock = $die_compra_litros - $die_venta_litros;

// === UMBRALES ===
$UMBRAL_GAS = 5000;
$UMBRAL_DIE = 3000;
$cantidad_alertas = 0;
$alertas = [];
if ($gas_stock < $UMBRAL_GAS) {
    $cantidad_alertas++;
    $alertas[] = "⛽ Gasolina: Quedan " . number_format($gas_stock, 0, ',', '.') . " L (mínimo: {$UMBRAL_GAS} L)";
}
if ($die_stock < $UMBRAL_DIE) {
    $cantidad_alertas++;
    $alertas[] = "🛢️ Diésel: Quedan " . number_format($die_stock, 0, ',', '.') . " L (mínimo: {$UMBRAL_DIE} L)";
}

// === DATOS GENERALES ===
$total_stock_litros = $gas_stock + $die_stock;
$porc_gas = $total_stock_litros > 0 ? ($gas_stock / $total_stock_litros) * 100 : 0;
$porc_die = $total_stock_litros > 0 ? ($die_stock / $total_stock_litros) * 100 : 0;

$hoy = date('Y-m-d');
$gas_venta_hoy_litros = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(litros_despachados) AS total FROM movimientos WHERE tipo='salida' AND producto='gasolina_especial' AND fecha='$hoy'"))['total'] ?? 0;
$gas_venta_hoy_monto = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_venta) AS total FROM movimientos WHERE tipo='salida' AND producto='gasolina_especial' AND fecha='$hoy'"))['total'] ?? 0;
$die_venta_hoy_litros = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(litros_despachados) AS total FROM movimientos WHERE tipo='salida' AND producto='diesel_oil' AND fecha='$hoy'"))['total'] ?? 0;
$die_venta_hoy_monto = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_venta) AS total FROM movimientos WHERE tipo='salida' AND producto='diesel_oil' AND fecha='$hoy'"))['total'] ?? 0;
$ventas_hoy_litros = $gas_venta_hoy_litros + $die_venta_hoy_litros;
$ventas_hoy_monto = $gas_venta_hoy_monto + $die_venta_hoy_monto;
$movimientos_hoy = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM movimientos WHERE fecha='$hoy'"));

// === GRÁFICOS 7 DÍAS ===
$etiquetas = [];
$gas_datos = [];
$die_datos = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $etiquetas[] = date('d/m', strtotime("-$i days"));
    $g = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(litros_despachados) AS total FROM movimientos WHERE tipo='salida' AND producto='gasolina_especial' AND fecha='$fecha'"))['total'] ?? 0;
    $d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(litros_despachados) AS total FROM movimientos WHERE tipo='salida' AND producto='diesel_oil' AND fecha='$fecha'"))['total'] ?? 0;
    $gas_datos[] = (float)$g;
    $die_datos[] = (float)$d;
}

$ultimos_gas = mysqli_query($conn, "SELECT * FROM movimientos WHERE producto='gasolina_especial' ORDER BY fecha DESC, id DESC LIMIT 5");
$ultimos_die = mysqli_query($conn, "SELECT * FROM movimientos WHERE producto='diesel_oil' ORDER BY fecha DESC, id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Administrador | IMANOL</title>
    <link rel="icon" type="image/png" sizes="80x80" href="imagenes/favicon2.png">
    <link rel="icon" type="image/png" sizes="32x32" href="imagenes/favicon2.png">
    <link rel="icon" type="image/png" sizes="16x16" href="imagenes/favicon2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body { 
            background: #F8F9FA; 
            color: #1F2937; 
            display: flex; 
            min-height: 100vh; 
        }

        /* 🌇 BARRA LATERAL — DEGRADADO ATARDECER ROJO-NARANJA */
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
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.1);
        }
        .logo-box img { max-width: 55px; }
        .logo-box h2 { font-size: 24px; color: #fff; text-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        .logo-box p { font-size: 11px; color: rgba(255,255,255,0.8); margin-top: 2px; }

        .sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 10px 10px;
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
        .sidebar .user-perfil h4 { color: #fff; font-size: 13px; }
        .sidebar .user-perfil p { color: rgba(255,255,255,0.75); font-size: 11px; }
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

        /* === ÁREA PRINCIPAL — FONDO BLANCO === */
        .main {
            margin-left: 250px;
            flex: 1;
            padding: 20px 24px;
            background: #FFFFFF;
            min-height: 100vh;
        }
        .top-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;
            padding-bottom: 16px; border-bottom: 1px solid #E5E7EB;
        }
        .top-header .titulo h1 { font-size: 20px; color: #111827; }
        .top-header .titulo p { font-size: 13px; color: #6B7280; margin-top: 4px; }

        .bell-wrapper { position: relative; }
        .bell-icon { font-size: 20px; color: #6B7280; cursor: pointer; transition: color 0.2s; }
        .bell-icon:hover { color: #B91C1C; }
        .bell-badge {
            position: absolute; top: -6px; right: -6px;
            background: #DC2626; color: #fff; font-size: 9px; font-weight: bold;
            border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;
        }
        .bell-menu {
            position: absolute; top: 32px; right: 0; min-width: 290px;
            background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12); padding: 0; z-index: 100; display: none;
        }
        .bell-wrapper:hover .bell-menu { display: block; }
        .bell-menu-header { padding: 10px 14px; border-bottom: 1px solid #E5E7EB; font-size: 13px; font-weight: 600; color: #B91C1C; }
        .bell-menu-body { padding: 12px 14px; }
        .alerta-item-bell { padding: 10px 12px; border-radius: 7px; margin-bottom: 6px; font-size: 13px; background: #FEF3C7; border-left: 3px solid #F59E0B; color: #92400E; }
        .sin-alertas { padding: 12px; text-align: center; color: #6B7280; font-size: 13px; }
        .header-right { display: flex; align-items: center; gap: 16px; }
        .user-profile-header {
            display: flex; align-items: center; gap: 10px;
            background: #F3F4F6; padding: 5px 12px; border-radius: 30px; border: 1px solid #E5E7EB;
        }
        .user-profile-header .avatar-sm {
            width: 30px; height: 30px; border-radius: 50%;
            background: linear-gradient(135deg, #B91C1C, #EA580C);
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 12px; color: #fff;
        }
        .user-profile-header .name { font-size: 13px; color: #1F2937; font-weight: 500; }
        .user-profile-header .rol { font-size: 11px; color: #6B7280; }

        /* === TARJETAS CON EFECTOS MEJORADOS === */
        .cards-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
        .stat-card {
            background: #FFFFFF; 
            border-radius: 14px;
            padding: 20px; 
            position: relative; 
            overflow: visible;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.35s ease;
            cursor: default;
        }
        
        /* 🌟 Borde iluminado que brota en la parte superior */
        .stat-card::before { 
            content: ''; 
            position: absolute; 
            top: 0; 
            left: 0; 
            right: 0; 
            height: 4px; 
            border-radius: 14px 14px 0 0;
            transition: all 0.35s ease;
        }
        
        /* ✨ Contorno iluminado general */
        .stat-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 14px;
            border: 2px solid transparent;
            opacity: 0;
            transition: all 0.35s ease;
            pointer-events: none;
        }
        
        /* Colores por tarjeta */
        .stat-card.gas::before { 
            background: linear-gradient(90deg, #F59E0B, #FBBF24, #F59E0B);
            box-shadow: 0 -4px 12px rgba(245, 158, 11, 0.4), 0 0 20px rgba(245, 158, 11, 0.2);
        }
        .stat-card.die::before { 
            background: linear-gradient(90deg, #DC2626, #EF4444, #DC2626);
            box-shadow: 0 -4px 12px rgba(220, 38, 38, 0.4), 0 0 20px rgba(220, 38, 38, 0.2);
        }
        .stat-card.ventas::before { 
            background: linear-gradient(90deg, #8B5CF6, #A78BFA, #8B5CF6);
            box-shadow: 0 -4px 12px rgba(139, 92, 246, 0.4), 0 0 20px rgba(139, 92, 246, 0.2);
        }
        .stat-card.movs::before { 
            background: linear-gradient(90deg, #06B6D4, #22D3EE, #06B6D4);
            box-shadow: 0 -4px 12px rgba(6, 182, 212, 0.4), 0 0 20px rgba(6, 182, 212, 0.2);
        }
        
        /* Efecto iluminado en contorno al pasar el cursor */
        .stat-card.gas::after { border-color: rgba(245, 158, 11, 0.5); }
        .stat-card.die::after { border-color: rgba(220, 38, 38, 0.5); }
        .stat-card.ventas::after { border-color: rgba(139, 92, 246, 0.5); }
        .stat-card.movs::after { border-color: rgba(6, 182, 212, 0.5); }
        
        /* 🔥 Animación al pasar el cursor */
        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 12px 30px rgba(0,0,0,0.12), 0 0 0 1px rgba(0,0,0,0.02);
        }
        .stat-card:hover::after {
            opacity: 1;
            inset: -4px;
        }
        .stat-card:hover::before {
            height: 6px;
            filter: brightness(1.2);
        }
        
        .stat-card .icon-box { 
            width: 44px; height: 44px; border-radius: 10px; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 20px; margin-bottom: 12px; 
            transition: all 0.3s ease;
        }
        .stat-card:hover .icon-box {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .stat-card.gas .icon-box { background: linear-gradient(135deg, #FEF3C7, #FDE68A); color: #B45309; }
        .stat-card.die .icon-box { background: linear-gradient(135deg, #FEE2E2, #FECACA); color: #B91C1C; }
        .stat-card.ventas .icon-box { background: linear-gradient(135deg, #EDE9FE, #DDD6FE); color: #7C3AED; }
        .stat-card.movs .icon-box { background: linear-gradient(135deg, #CFFAFE, #B9E6FE); color: #0891B3; }
        .stat-card h3 { font-size: 13px; color: #6B7280; margin-bottom: 6px; font-weight: 500; }
        .stat-card .valor { font-size: 24px; font-weight: 700; color: #111827; transition: transform 0.3s ease; }
        .stat-card:hover .valor { transform: scale(1.05); }

        /* === PANELES === */
        .panel {
            background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 12px;
            padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .panel h4 { font-size: 14px; color: #1F2937; margin-bottom: 14px; font-weight: 600; }
        .chart-container { position: relative; height: 220px; width: 100%; }
        .tabla { width: 100%; border-collapse: collapse; }
        .tabla th {
            text-align: left; font-size: 11px; color: #6B7280; padding: 10px 8px;
            border-bottom: 2px solid #E5E7EB; text-transform: uppercase; letter-spacing: 0.4px;
        }
        .tabla td { padding: 12px 8px; font-size: 13px; border-bottom: 1px solid #F3F4F6; color: #374151; }
        .tabla tr:hover td { background: #F9FAFB; }
        .tabla-subtitulo {
            padding: 10px 14px; margin: 14px 0 6px 0; border-radius: 8px;
            font-size: 13px; font-weight: 600; display: flex; justify-content: space-between; align-items: center;
        }
        .tabla-subtitulo.gas { background: #FEF3C7; border-left: 3px solid #F59E0B; color: #92400E; }
        .tabla-subtitulo.die { background: #FEE2E2; border-left: 3px solid #DC2626; color: #991B1B; }
        .btn-ver-todos {
            font-size: 11px; padding: 5px 12px; border-radius: 6px;
            text-decoration: none; font-weight: 600; transition: all 0.2s;
        }
        .btn-ver-todos.gas-btn { background: #FDE68A; color: #92400E; }
        .btn-ver-todos.gas-btn:hover { background: #FCD34D; }
        .btn-ver-todos.die-btn { background: #FECACA; color: #991B1B; }
        .btn-ver-todos.die-btn:hover { background: #FCA5A5; }
        .sin-datos { text-align: center; color: #6B7280; padding: 20px; font-size: 13px; font-style: italic; }

        .row-1 { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .bottom-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .comb-card {
            background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 12px;
            padding: 18px; position: relative; overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .comb-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; }
        .comb-gas::before { background: linear-gradient(90deg, #F59E0B, #FBBF24); }
        .comb-die::before { background: linear-gradient(90deg, #DC2626, #EF4444); }
        .comb-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
        .comb-icon { width: 40px; height: 40px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .comb-gas .comb-icon { background: #FEF3C7; color: #D97706; }
        .comb-die .comb-icon { background: #FEE2E2; color: #DC2626; }
        .comb-valor { font-size: 22px; font-weight: 700; margin-bottom: 12px; }
        .comb-gas .comb-valor { color: #B45309; }
        .comb-die .comb-valor { color: #B91C1C; }
        .comb-datos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .dato small { color: #6B7280; font-size: 11px; display: block; margin-bottom: 3px; }
        .dato strong { font-size: 13px; color: #1F2937; }
        .barra-fondo { height: 7px; background: #E5E7EB; border-radius: 4px; overflow: hidden; margin-top: 6px; }
        .barra-llena { height: 100%; border-radius: 4px; transition: width 0.5s ease; }
        .barra-gas { background: linear-gradient(90deg, #F59E0B, #FBBF24); width: <?= round($porc_gas) ?>%; }
        .barra-die { background: linear-gradient(90deg, #DC2626, #EF4444); width: <?= round($porc_die) ?>%; }
        .pie { margin-top: 20px; text-align: center; font-size: 11px; color: #9CA3AF; padding-top: 12px; border-top: 1px solid #E5E7EB; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo-box" style="display: flex; align-items: center; gap: 8px;">
            <img src="imagenes/logo.png" alt="IMANOL">
            <div>
                <h2 style="font-size: 20px; color: #fff; margin: 0;">MANOL</h2>
                <p style="font-size: 10px; color: rgba(255,255,255,0.8); margin: 1px 0 0 0;">Dios Con Nosotros</p>
            </div>
        </div>
        <nav>
            <a href="index_admin.php" class="activo"><i class="fas fa-chart-pie"></i> Inicio</a>
            <a href="gasolina.php"><i class="fas fa-gas-pump"></i> Gasolina</a>
            <a href="diesel.php"><i class="fas fa-gas-pump"></i> Diésel</a>
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
                    <h4 style="margin:0;"><?= $nombre_admin ?></h4>
                    <p style="margin:0;">Administrador</p>
                </div>
            </div>
            <div class="estado"><span></span> En línea</div>
            <a href="logout.php" class="cerrar-btn"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
    </aside>

    <main class="main">
        <div class="top-header">
            <div class="titulo">
                <h1><i class="fas fa-bars" style="color:#B91C1C;"></i> Panel de Administración</h1>
                <p>Resumen general del movimiento de combustible</p>
            </div>
            <div class="header-right">
                <div class="bell-wrapper">
                    <div class="bell-icon" title="Alertas del sistema">
                        <i class="fas fa-bell"></i>
                        <?php if ($cantidad_alertas > 0): ?>
                            <span class="bell-badge"><?= $cantidad_alertas ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="bell-menu">
                        <div class="bell-menu-header">
                            <i class="fas fa-exclamation-triangle"></i> Alertas del Sistema
                        </div>
                        <div class="bell-menu-body">
                            <?php if ($cantidad_alertas > 0): ?>
                                <?php foreach ($alertas as $alerta): ?>
                                    <div class="alerta-item-bell">
                                        <i class="fas fa-exclamation-circle"></i> <?= $alerta ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="sin-alertas">
                                    ✅ Todo funciona correctamente.<br>Stock de combustible en niveles normales.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="user-profile-header">
                    <div class="avatar-sm"><?= $inicial ?></div>
                    <div>
                        <span class="name"><?= $nombre_admin ?></span>
                        <span class="rol">Administrador</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="cards-row">
            <div class="stat-card gas">
                <div class="icon-box"><i class="fas fa-gas-pump"></i></div>
                <h3>Stock de Gasolina</h3>
                <div class="valor"><?= number_format($gas_stock, 0, ',', '.') ?> L</div>
                <div style="font-size:11px; color:<?= $gas_stock < $UMBRAL_GAS ? '#B45309' : '#6B7280' ?>; margin-top: 8px;">
                    <?= $gas_stock < $UMBRAL_GAS ? '⚠️ Stock bajo' : '✅ Nivel normal' ?>
                </div>
            </div>
            <div class="stat-card die">
                <div class="icon-box"><i class="fas fa-gas-pump"></i></div>
                <h3>Stock de Diésel</h3>
                <div class="valor"><?= number_format($die_stock, 0, ',', '.') ?> L</div>
                <div style="font-size:11px; color:<?= $die_stock < $UMBRAL_DIE ? '#B91C1C' : '#6B7280' ?>; margin-top: 8px;">
                    <?= $die_stock < $UMBRAL_DIE ? '⚠️ Stock bajo' : '✅ Nivel normal' ?>
                </div>
            </div>
            <div class="stat-card ventas">
                <div class="icon-box"><i class="fas fa-chart-line"></i></div>
                <h3>Ventas de Hoy</h3>
                <div class="valor"><?= number_format($ventas_hoy_litros, 0, ',', '.') ?> L</div>
                <div style="font-size:12px; color:#6B7280; margin-top: 8px;">Bs. <?= number_format($ventas_hoy_monto, 2, ',', '.') ?></div>
            </div>
            <div class="stat-card movs">
                <div class="icon-box"><i class="fas fa-exchange-alt"></i></div>
                <h3>Movimientos del Día</h3>
                <div class="valor"><?= $movimientos_hoy ?></div>
            </div>
        </div>

        <div class="row-1">
            <div class="panel" style="grid-column: span 2;">
                <h4>Ventas últimos 7 días (Litros)</h4>
                <div style="font-size:11px; color:#6B7280; margin-bottom:8px;">
                    <span style="color:#F59E0B;">● Gasolina</span>
                    <span style="color:#DC2626; margin-left:12px;">● Diésel</span>
                </div>
                <div class="chart-container">
                    <canvas id="graficoLineas"></canvas>
                </div>
            </div>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div class="panel">
                    <h4>Ventas de Hoy</h4>
                    <div class="chart-container" style="height:160px;">
                        <canvas id="graficoBarras"></canvas>
                    </div>
                </div>
                <div class="panel">
                    <h4>Distribución del Inventario</h4>
                    <div class="chart-container" style="height:160px; position:relative;">
                        <canvas id="graficoCircular"></canvas>
                        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center;">
                            <div style="font-size:12px; color:#6B7280;">Total</div>
                            <div style="font-size:14px; font-weight:700; color:#111827;"><?= number_format($total_stock_litros,0,',','.') ?> L</div>
                        </div>
                    </div>
                    <div style="margin-top:10px; font-size:11px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                            <span style="color:#F59E0B; font-weight:600;">● Gasolina</span>
                            <span style="color:#374151;"><?= number_format($gas_stock,0,',','.') ?> L <?= round($porc_gas) ?>%</span>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:#DC2626; font-weight:600;">● Diésel</span>
                            <span style="color:#374151;"><?= number_format($die_stock,0,',','.') ?> L <?= round($porc_die) ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row-1">
            <div class="panel" style="grid-column: span 2;">
                <h4>Últimos Movimientos Registrados</h4>
                
                <div class="tabla-subtitulo gas">
                    <span><i class="fas fa-gas-pump"></i> Gasolina Especial</span>
                    <a href="gasolina.php" class="btn-ver-todos gas-btn">
                        <i class="fas fa-eye"></i> Ver todos
                    </a>
                </div>
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Turno</th>
                            <th>Dispensador</th>
                            <th>Litros</th>
                            <th>Total Bs.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $cont_gas = 1; $tiene_gas = false; while ($m = mysqli_fetch_assoc($ultimos_gas)): $tiene_gas = true; ?>
                        <tr>
                            <td><?= $cont_gas++ ?></td>
                            <td><?= date('d/m/Y', strtotime($m['fecha'])) ?></td>
                            <td><?= ($m['turno'] ?? '') === 'mañana' ? '🌅 Mañana' : '🌆 Tarde' ?></td>
                            <td><?= $m['dispensador'] ?? '—' ?></td>
                            <td><?= number_format($m['litros_despachados'] ?? 0, 2, ',', '.') ?> L</td>
                            <td><strong style="color:#B45309;">Bs. <?= number_format($m['total_venta'] ?? 0, 2, ',', '.') ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$tiene_gas): ?>
                        <tr>
                            <td colspan="6" class="sin-datos">⛽ Sin movimientos de Gasolina recientemente</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="tabla-subtitulo die">
                    <span><i class="fas fa-gas-pump"></i> Diésel Oil</span>
                    <a href="diesel.php" class="btn-ver-todos die-btn">
                        <i class="fas fa-eye"></i> Ver todos
                    </a>
                </div>
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Turno</th>
                            <th>Dispensador</th>
                            <th>Litros</th>
                            <th>Total Bs.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $cont_die = 1; $tiene_die = false; while ($m = mysqli_fetch_assoc($ultimos_die)): $tiene_die = true; ?>
                        <tr>
                            <td><?= $cont_die++ ?></td>
                            <td><?= date('d/m/Y', strtotime($m['fecha'])) ?></td>
                            <td><?= ($m['turno'] ?? '') === 'mañana' ? '🌅 Mañana' : '🌆 Tarde' ?></td>
                            <td><?= $m['dispensador'] ?? '—' ?></td>
                            <td><?= number_format($m['litros_despachados'] ?? 0, 2, ',', '.') ?> L</td>
                            <td><strong style="color:#B91C1C;">Bs. <?= number_format($m['total_venta'] ?? 0, 2, ',', '.') ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$tiene_die): ?>
                        <tr>
                            <td colspan="6" class="sin-datos">🛢️ Sin movimientos de Diésel recientemente</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="panel">
                <h4>Alertas del Sistema</h4>
                <?php if ($cantidad_alertas > 0): ?>
                    <?php foreach ($alertas as $alerta): ?>
                        <div class="alerta-item-bell" style="margin-bottom:6px;">
                            <i class="fas fa-exclamation-triangle"></i> <?= $alerta ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="color:#166534; background:#F0FDF4; padding:12px; border-radius:7px;">
                        ✅ Sistema funcionando correctamente
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bottom-row">
            <div class="comb-card comb-gas">
                <div class="comb-header">
                    <div class="comb-icon"><i class="fas fa-gas-pump"></i></div>
                    <h4>GASOLINA ESPECIAL</h4>
                </div>
                <div class="comb-valor"><?= number_format($gas_stock, 0, ',', '.') ?> L</div>
                <div class="comb-datos">
                    <div class="dato"><small>Vendido Hoy</small><strong><?= number_format($gas_venta_hoy_litros,0,',','.') ?> L</strong></div>
                    <div class="dato"><small>Ingresado</small><strong><?= number_format($gas_compra_litros,0,',','.') ?> L</strong></div>
                    <div class="dato"><small>Monto Hoy</small><strong>Bs. <?= number_format($gas_venta_hoy_monto,2,',','.') ?></strong></div>
                </div>
                <small style="color:#6B7280; font-size:11px;">Inventario — <?= round($porc_gas) ?>%</small>
                <div class="barra-fondo"><div class="barra-llena barra-gas"></div></div>
            </div>
            <div class="comb-card comb-die">
                <div class="comb-header">
                    <div class="comb-icon"><i class="fas fa-gas-pump"></i></div>
                    <h4>DIÉSEL OIL</h4>
                </div>
                <div class="comb-valor"><?= number_format($die_stock, 0, ',', '.') ?> L</div>
                <div class="comb-datos">
                    <div class="dato"><small>Vendido Hoy</small><strong><?= number_format($die_venta_hoy_litros,0,',','.') ?> L</strong></div>
                    <div class="dato"><small>Ingresado</small><strong><?= number_format($die_compra_litros,0,',','.') ?> L</strong></div>
                    <div class="dato"><small>Monto Hoy</small><strong>Bs. <?= number_format($die_venta_hoy_monto,2,',','.') ?></strong></div>
                </div>
                <small style="color:#6B7280; font-size:11px;">Inventario — <?= round($porc_die) ?>%</small>
                <div class="barra-fondo"><div class="barra-llena barra-die"></div></div>
            </div>
        </div>

        <div class="pie">IMANOL - Dios Con Nosotros</div>
        <div class="pie">© <?= date('Y') ?> Desarrollado Por / Yaffar Villanueva B. y Christian Limpias B.</div>
    </main>

    <script>
        const etiquetas = <?= json_encode($etiquetas) ?>;
        const gasData = <?= json_encode($gas_datos) ?>;
        const dieData = <?= json_encode($die_datos) ?>;
        const gasHoy = <?= (float)$gas_venta_hoy_litros ?>;
        const dieHoy = <?= (float)$die_venta_hoy_litros ?>;
        const porcGas = <?= round($porc_gas) ?>;
        const porcDie = <?= round($porc_die) ?>;
        
        new Chart(document.getElementById('graficoLineas'), {
            type: 'line',
            data: {
                labels: etiquetas,
                datasets: [
                    { label: 'Gasolina (L)', data: gasData, borderColor: '#F59E0B', backgroundColor: 'rgba(245,158,11,0.1)', fill: true, tension: 0.4, borderWidth: 3, pointBackgroundColor: '#fff', pointBorderColor: '#F59E0B', pointBorderWidth: 2, pointRadius: 4 },
                    { label: 'Diésel (L)', data: dieData, borderColor: '#DC2626', backgroundColor: 'rgba(220,38,38,0.08)', fill: true, tension: 0.4, borderWidth: 3, pointBackgroundColor: '#fff', pointBorderColor: '#DC2626', pointBorderWidth: 2, pointRadius: 4 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { color: '#6B7280' } }, x: { grid: { display: false }, ticks: { color: '#6B7280' } } } }
        });
        
        new Chart(document.getElementById('graficoBarras'), {
            type: 'bar',
            data: { labels: ['Gasolina', 'Diésel'], datasets: [{ data: [gasHoy, dieHoy], backgroundColor: ['#F59E0B', '#DC2626'], borderRadius: 6, barThickness: 36 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { color: '#6B7280' } }, x: { grid: { display: false }, ticks: { color: '#374151' } } } }
        });
        
        new Chart(document.getElementById('graficoCircular'), {
            type: 'doughnut',
            data: { labels: ['Gasolina', 'Diésel'], datasets: [{ data: [porcGas, porcDie], backgroundColor: ['#F59E0B', '#DC2626'], borderWidth: 0, cutout: '75%' }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    </script>
</body>
</html>