<?php
// === CONFIGURAR ZONA HORARIA BOLIVIA ===
date_default_timezone_set('America/La_Paz');
include_once 'conexion.php';
verificarSesion();

// === DETECTAR ROL DEL USUARIO ===
$es_admin = (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'administrador') 
         || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'administrador')
         || (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin')
         || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin');

// === SI ES ADMINISTRADOR, REDIRIGIR A SU DASHBOARD ===
if ($es_admin) {
    header("Location: index_admin.php");
    exit;
}

// === DATOS DEL USUARIO (OPERADOR) ===
$nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Operador';
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$inicial = mb_substr($nombre_usuario, 0, 1);
$rol_usuario = 'Operador';

// === CONSULTAR SOLO LOS MOVIMIENTOS DE ESTE OPERADOR ===
$hoy = date('Y-m-d');

// Gasolina
$gas_entradas = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT SUM(litros_recibidos) AS total FROM movimientos WHERE usuario_id = $usuario_id AND fecha = '$hoy' AND producto='gasolina_especial' AND tipo='entrada'"))['total'] ?? 0;
$gas_salidas = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT SUM(litros_despachados) AS total FROM movimientos WHERE usuario_id = $usuario_id AND fecha = '$hoy' AND producto='gasolina_especial' AND tipo='salida'"))['total'] ?? 0;
$gas_total_venta = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT SUM(total_venta) AS total FROM movimientos WHERE usuario_id = $usuario_id AND fecha = '$hoy' AND producto='gasolina_especial' AND tipo='salida'"))['total'] ?? 0;

// Diésel
$die_entradas = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT SUM(litros_recibidos) AS total FROM movimientos WHERE usuario_id = $usuario_id AND fecha = '$hoy' AND producto='diesel_oil' AND tipo='entrada'"))['total'] ?? 0;
$die_salidas = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT SUM(litros_despachados) AS total FROM movimientos WHERE usuario_id = $usuario_id AND fecha = '$hoy' AND producto='diesel_oil' AND tipo='salida'"))['total'] ?? 0;
$die_total_venta = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT SUM(total_venta) AS total FROM movimientos WHERE usuario_id = $usuario_id AND fecha = '$hoy' AND producto='diesel_oil' AND tipo='salida'"))['total'] ?? 0;

// Totales del día
$total_litros = $gas_salidas + $die_salidas;
$total_monto = $gas_total_venta + $die_total_venta;

// Últimos movimientos del operador
$ultimos = mysqli_query($conn, 
    "SELECT * FROM movimientos WHERE usuario_id = $usuario_id ORDER BY fecha DESC, id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio — IMANOL</title>

    <!-- ✅ ICONO DE IMANOL EN LA PESTAÑA -->
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

        /* 🌇 BARRA LATERAL — DEGRADADO ATARDECER UNIFICADO */
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

        /* === ÁREA PRINCIPAL — FONDO BLANCO === */
        .main {
            margin-left: 250px;
            flex: 1;
            padding: 24px 30px;
            background: #FFFFFF;
            min-height: 100vh;
        }
        .contenedor { max-width: 1200px; margin: 0 auto; }

        .cabecera {
            background: #FFFFFF;
            border: 1px solid #FECACA;
            border-radius: 16px;
            padding: 24px 28px;
            margin-bottom: 24px;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 16px;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.08);
        }
        .cabecera-izq { display: flex; align-items: center; gap: 20px; }
        .circulo-logo {
            width: 56px; height: 56px; border-radius: 12px;
            background: linear-gradient(135deg, #B91C1C 0%, #EF4444 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: #fff;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        .cabecera-texto h1 { font-size: 22px; color: #B91C1C; margin-bottom: 4px; }
        .cabecera-texto p { font-size: 13px; color: #6B7280; }
        .fecha-top { 
            font-size: 14px; color: #B45309; 
            background: #FFFBEB; padding: 8px 16px; border-radius: 20px;
            border: 1px solid #FDE68A; font-weight: 500;
        }

        /* === TARJETAS RESUMEN === */
        .cards-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: #FFFFFF;
            padding: 24px;
            border-radius: 16px;
            border: 1px solid #E5E7EB;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        .stat-card.gas {
            border-top: 4px solid #F59E0B;
            background: linear-gradient(to bottom, #FFFBEB, #FFFFFF);
        }
        .stat-card.gas:hover { border-color: #FBBF24; background: #FFFBEB; }
        .stat-card.die {
            border-top: 4px solid #DC2626;
            background: linear-gradient(to bottom, #FEF2F2, #FFFFFF);
        }
        .stat-card.die:hover { border-color: #EF4444; background: #FEF2F2; }
        .stat-card.total {
            border-top: 4px solid #16A34A;
            background: linear-gradient(to bottom, #F0FDF4, #FFFFFF);
        }
        .stat-card.total:hover { border-color: #22C55E; background: #F0FDF4; }
        
        .icon-box {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; margin-bottom: 14px;
        }
        .stat-card.gas .icon-box { background: #FDE68A; color: #B45309; }
        .stat-card.die .icon-box { background: #FECACA; color: #B91C1C; }
        .stat-card.total .icon-box { background: #BBF7D0; color: #16A34A; }
        
        .stat-card h3 {
            font-size: 13px;
            color: #6B7280;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .stat-card .valor {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .stat-card.gas .valor { color: #B45309; }
        .stat-card.die .valor { color: #B91C1C; }
        .stat-card.total .valor { color: #16A34A; }
        .stat-card .subvalor { font-size: 12px; color: #9CA3AF; }

        /* === TABLA MOVIMIENTOS === */
        .panel {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .panel h4 {
            font-size: 16px;
            color: #1F2937;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex; align-items: center; gap: 10px;
        }
        .panel h4 i { color: #F59E0B; }
        .tabla {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .tabla th {
            background: #F9FAFB;
            padding: 14px 12px;
            text-align: left;
            color: #374151;
            font-weight: 600;
            border-bottom: 2px solid #E5E7EB;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .tabla td {
            padding: 14px 12px;
            border-bottom: 1px solid #F3F4F6;
            color: #4B5563;
        }
        .tabla tr:hover {
            background: #FFFBEB;
        }
        .tabla tr:last-child td { border-bottom: none; }
        .producto-gas { color: #B45309; font-weight: 600; }
        .producto-die { color: #B91C1C; font-weight: 600; }
        .monto { font-weight: 700; color: #1F2937; }
        .sin-datos {
            text-align: center;
            padding: 40px;
            color: #6B7280;
            font-style: italic;
        }
        .sin-datos i {
            font-size: 28px;
            color: #D1D5DB;
            margin-bottom: 12px;
            display: block;
        }
        .pie {
            margin-top: 32px;
            text-align: center;
            font-size: 12px;
            color: #9CA3AF;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
        }
        @media (max-width: 1000px) {
            .cards-row { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { width: 220px; }
            .main { margin-left: 220px; padding: 16px; }
            .cards-row { grid-template-columns: 1fr; }
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
        <a href="index.php" class="activo"><i class="fas fa-home"></i> Inicio</a>
         <a href="cierre_turno.php"><i class="fas fa-calculator"></i> Mi Turno</a>
        <a href="vales.php"><i class="fas fa-ticket-alt"></i> Vales</a>
        <a href="historial.php"><i class="fas fa-history"></i> Historial</a>
        <a href="mi_perfil.php"><i class="fas fa-user"></i> Mi Perfil</a>
    </nav>
    <div class="user-info">
        <div class="user-perfil">
            <div class="avatar"><?= $inicial ?></div>
            <div>
                <h4><?= htmlspecialchars($nombre_usuario) ?></h4>
                <p><?= $rol_usuario ?></p>
            </div>
        </div>
        <div class="estado"><span></span> En línea</div>
        <a href="logout.php" class="cerrar-btn"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </div>
</aside>

<main class="main">
    <div class="contenedor">
        <div class="cabecera">
            <div class="cabecera-izq">
                <div class="circulo-logo"><i class="fas fa-chart-pie"></i></div>
                <div class="cabecera-texto">
                    <h1>Inicio</h1>
                    <p>Resumen de tu trabajo de hoy</p>
                </div>
            </div>
            <span class="fecha-top"><?= date('d M, Y') ?></span>
        </div>

        <!-- TARJETAS RESUMEN -->
        <div class="cards-row">
            <div class="stat-card gas">
                <div class="icon-box"><i class="fas fa-gas-pump"></i></div>
                <h3>⛽ Gasolina Vendida</h3>
                <div class="valor"><?= number_format($gas_salidas, 0, ',', '.') ?> L</div>
                <div class="subvalor">Bs. <?= number_format($gas_total_venta, 2, ',', '.') ?></div>
            </div>
            <div class="stat-card die">
                <div class="icon-box"><i class="fas fa-gas-pump"></i></div>
                <h3>🛢️ Diésel Vendido</h3>
                <div class="valor"><?= number_format($die_salidas, 0, ',', '.') ?> L</div>
                <div class="subvalor">Bs. <?= number_format($die_total_venta, 2, ',', '.') ?></div>
            </div>
            <div class="stat-card total">
                <div class="icon-box"><i class="fas fa-chart-line"></i></div>
                <h3>Total Litros</h3>
                <div class="valor"><?= number_format($total_litros, 0, ',', '.') ?> L</div>
                <div class="subvalor">Acumulado hoy</div>
            </div>
            <div class="stat-card total">
                <div class="icon-box"><i class="fas fa-money-bill-wave"></i></div>
                <h3>Total Ventas</h3>
                <div class="valor">Bs. <?= number_format($total_monto, 2, ',', '.') ?></div>
                <div class="subvalor">Del día</div>
            </div>
        </div>

        <!-- ÚLTIMOS MOVIMIENTOS -->
        <div class="panel">
            <h4><i class="fas fa-list"></i> Mis Últimos Movimientos</h4>
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Litros</th>
                        <th>Total Bs.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fila = mysqli_fetch_assoc($ultimos)): 
                        $es_gas = $fila['producto'] === 'gasolina_especial';
                        $clase_prod = $es_gas ? 'producto-gas' : 'producto-die';
                        $texto_prod = $es_gas ? '⛽ Gasolina' : '🛢️ Diésel';
                        $litros = $fila['litros_despachados'] ?? $fila['litros_recibidos'] ?? 0;
                        $monto = $fila['total_venta'] ?? 0;
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($fila['fecha'])) ?></td>
                        <td><span class="<?= $clase_prod ?>"><?= $texto_prod ?></span></td>
                        <td><?= $fila['tipo'] === 'entrada' ? '📥 Entrada' : '📤 Salida' ?></td>
                        <td><?= number_format($litros, 0, ',', '.') ?> L</td>
                        <td class="monto">Bs. <?= number_format($monto, 2, ',', '.') ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($ultimos) === 0): ?>
                    <tr>
                        <td colspan="5" class="sin-datos">
                            <i class="fas fa-inbox"></i>
                            Aún no tienes movimientos registrados hoy.<br>Comienza registrando tu lectura del surtidor.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pie">© <?= date('Y') ?> IMANOL — Estación de Servicio · Desarrollado por/ Yaffar Villanueva B. y Christian Limpia B.</div>
    </div>
</main>
</body>
</html>