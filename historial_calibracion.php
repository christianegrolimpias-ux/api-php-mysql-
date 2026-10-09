<?php
date_default_timezone_set('America/La_Paz');
include_once 'conexion.php';
verificarSesion();
$nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Operador';
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$inicial = mb_substr($nombre_usuario, 0, 1);
$es_admin = (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin', 'administrador']))
         || (isset($_SESSION['usuario_tipo']) && in_array($_SESSION['usuario_tipo'], ['admin', 'administrador']));
if (!$es_admin) {
    die("❌ No tienes permiso para acceder a esta página.");
}
$nombre_admin = $nombre_usuario;
$rol_usuario = 'Administrador';

// === RESUMEN TOTAL ===
$sql_total = "SELECT 
                producto,
                SUM(litros_calibracion) AS total_calib
             FROM cierres_turno
             WHERE litros_calibracion > 0
             GROUP BY producto";
$res_total = mysqli_query($conn, $sql_total);
$totales = [];
$total_general = 0;
while ($t = mysqli_fetch_assoc($res_total)) {
    $totales[$t['producto']] = $t['total_calib'];
    $total_general += $t['total_calib'];
}
$gas_calib = $totales['gasolina_especial'] ?? 0;
$die_calib = $totales['diesel_oil'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Calibración | IMANOL</title>
    <link rel="icon" type="image/png" sizes="32x32" href="imagenes/favicon2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #F8F9FA; color: #1F2937; display: flex; min-height: 100vh; }
        
        /* 🌇 BARRA LATERAL — IGUAL AL DASHBOARD */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #B91C1C 0%, #EA580C 30%, #F59E0B 55%, #FB923C 80%, #DC2626 100%);
            border-right: 2px solid rgba(255, 255, 255, 0.25);
            height: 100vh; position: fixed; padding: 0; display: flex; flex-direction: column;
            box-shadow: 3px 0 15px rgba(185, 28, 28, 0.2);
        }
        .logo-box {
            padding: 14px 12px; display: flex; align-items: center; gap: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.1);
        }
        .logo-box img { max-width: 55px; }
        .logo-box h2 { font-size: 20px; color: #fff; }
        .logo-box p { font-size: 10px; color: rgba(255,255,255,0.8); margin: 1px 0 0 0; }
        .sidebar nav {
            display: flex; flex-direction: column; gap: 2px;
            padding: 10px; flex: 1; overflow-y: auto;
        }
        .sidebar nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; color: rgba(255,255,255,0.9);
            text-decoration: none; border-radius: 8px;
            font-size: 13px; font-weight: 500;
            transition: all 0.25s ease;
        }
        .sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #fff; transform: translateX(3px);
        }
        .sidebar nav a.activo {
            background: rgba(255, 255, 255, 0.3);
            color: #fff; border-left: 3px solid #fff; font-weight: 600;
        }
        .sidebar .user-info {
            padding: 12px; border-top: 1px solid rgba(255, 255, 255, 0.2);
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
        .sidebar .user-perfil h4 { color: #fff; font-size: 13px; margin: 0; }
        .sidebar .user-perfil p { color: rgba(255,255,255,0.75); font-size: 11px; margin: 2px 0 0 0; }
        .sidebar .estado {
            display: flex; align-items: center; gap: 5px;
            font-size: 11px; color: #90EE90; margin-bottom: 8px;
        }
        .sidebar .estado span {
            width: 7px; height: 7px; border-radius: 50%;
            background: #90EE90; box-shadow: 0 0 4px #90EE90;
        }
        .cerrar-btn {
            background: rgba(0, 0, 0, 0.2); color: #fff; border: none; padding: 8px; border-radius: 7px;
            width: 100%; font-size: 12px; display: flex; align-items: center; justify-content: center;
            gap: 7px; text-decoration: none; transition: all 0.2s;
        }
        .cerrar-btn:hover { background: rgba(0, 0, 0, 0.35); }

        /* === ÁREA PRINCIPAL === */
        .main {
            margin-left: 250px; flex: 1; padding: 20px 24px;
            background: #FFFFFF; min-height: 100vh;
        }
        .top-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;
            padding-bottom: 16px; border-bottom: 1px solid #E5E7EB;
        }
        .top-header .titulo h1 { font-size: 20px; color: #111827; }
        .top-header .titulo p { font-size: 13px; color: #6B7280; margin-top: 4px; }
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

        /* === TARJETAS RESUMEN === */
        .cards-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
        .stat-card {
            background: #FFFFFF; border-radius: 14px; padding: 20px;
            position: relative; overflow: visible;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.35s ease; cursor: default;
        }
        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            border-radius: 14px 14px 0 0; transition: all 0.35s ease;
        }
        .stat-card::after {
            content: ''; position: absolute; inset: 0; border-radius: 14px;
            border: 2px solid transparent; opacity: 0;
            transition: all 0.35s ease; pointer-events: none;
        }
        .stat-card.gas::before {
            background: linear-gradient(90deg, #F59E0B, #FBBF24, #F59E0B);
            box-shadow: 0 -4px 12px rgba(245, 158, 11, 0.4);
        }
        .stat-card.die::before {
            background: linear-gradient(90deg, #DC2626, #EF4444, #DC2626);
            box-shadow: 0 -4px 12px rgba(220, 38, 38, 0.4);
        }
        .stat-card.total::before {
            background: linear-gradient(90deg, #8B5CF6, #A78BFA, #8B5CF6);
            box-shadow: 0 -4px 12px rgba(139, 92, 246, 0.4);
        }
        .stat-card.gas::after { border-color: rgba(245, 158, 11, 0.5); }
        .stat-card.die::after { border-color: rgba(220, 38, 38, 0.5); }
        .stat-card.total::after { border-color: rgba(139, 92, 246, 0.5); }
        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
        }
        .stat-card:hover::after { opacity: 1; inset: -4px; }
        .stat-card:hover::before { height: 6px; filter: brightness(1.2); }
        .stat-card .icon-box {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; margin-bottom: 12px; transition: all 0.3s ease;
        }
        .stat-card:hover .icon-box { transform: scale(1.1); }
        .stat-card.gas .icon-box { background: linear-gradient(135deg, #FEF3C7, #FDE68A); color: #B45309; }
        .stat-card.die .icon-box { background: linear-gradient(135deg, #FEE2E2, #FECACA); color: #B91C1C; }
        .stat-card.total .icon-box { background: linear-gradient(135deg, #EDE9FE, #DDD6FE); color: #7C3AED; }
        .stat-card h3 { font-size: 13px; color: #6B7280; margin-bottom: 6px; font-weight: 500; }
        .stat-card .valor { font-size: 24px; font-weight: 700; color: #111827; }

        /* === TABLA === */
        .panel {
            background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 12px;
            padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .panel h4 { font-size: 14px; color: #1F2937; margin-bottom: 14px; font-weight: 600; }
        .tabla { width: 100%; border-collapse: collapse; }
        .tabla th {
            text-align: left; font-size: 11px; color: #6B7280; padding: 10px 8px;
            border-bottom: 2px solid #E5E7EB; text-transform: uppercase; letter-spacing: 0.4px;
        }
        .tabla td { padding: 12px 8px; font-size: 13px; border-bottom: 1px solid #F3F4F6; color: #374151; }
        .tabla tr:hover td { background: #FEFCE8; }
        .devuelto { color: #16A34A; font-weight: 600; }
        .sin-dev { color: #CA8A04; font-weight: 500; }
        .sin-datos { text-align: center; color: #6B7280; padding: 40px; font-size: 13px; font-style: italic; }
        .pie { margin-top: 30px; text-align: center; font-size: 11px; color: #9CA3AF; padding-top: 12px; border-top: 1px solid #E5E7EB; }
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
        <a href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a>
        <a href="historial_calibracion.php" class="activo"><i class="fas fa-sliders-h"></i> Historial Calibración</a>
        <a href="proveedores.php"><i class="fas fa-truck"></i> Proveedores</a>
        <a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a>
        <a href="crear_cuenta.php"><i class="fas fa-user-plus"></i> Crear Cuentas</a>
    </nav>
    <div class="user-info">
        <div class="user-perfil">
            <div class="avatar"><?= $inicial ?></div>
            <div>
                <h4><?= htmlspecialchars($nombre_admin) ?></h4>
                <p>Administrador</p>
            </div>
        </div>
        <div class="estado"><span></span> En línea</div>
        <a href="logout.php" class="cerrar-btn"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </div>
</aside>

<main class="main">
    <div class="top-header">
        <div class="titulo">
            <h1><i class="fas fa-sliders-h" style="color:#B91C1C;"></i> Historial de Calibración</h1>
            <p>Registro de litros retirados y devueltos al stock</p>
        </div>
        <div class="user-profile-header">
            <div class="avatar-sm"><?= $inicial ?></div>
            <div>
                <span class="name"><?= $nombre_admin ?></span>
                <span class="rol">Administrador</span>
            </div>
        </div>
    </div>

    <!-- === TARJETAS RESUMEN === -->
    <div class="cards-row">
        <div class="stat-card gas">
            <div class="icon-box"><i class="fas fa-gas-pump"></i></div>
            <h3>Calibración Gasolina</h3>
            <div class="valor"><?= number_format($gas_calib, 3, ',', '.') ?> L</div>
        </div>
        <div class="stat-card die">
            <div class="icon-box"><i class="fas fa-gas-pump"></i></div>
            <h3>Calibración Diésel</h3>
            <div class="valor"><?= number_format($die_calib, 3, ',', '.') ?> L</div>
        </div>
        <div class="stat-card total">
            <div class="icon-box"><i class="fas fa-chart-pie"></i></div>
            <h3>Total General</h3>
            <div class="valor"><?= number_format($total_general, 3, ',', '.') ?> L</div>
        </div>
    </div>

    <!-- === TABLA DETALLE === -->
    <div class="panel">
        <h4>📋 Detalle por Turno</h4>
        <table class="tabla">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Turno</th>
                    <th>Bomba</th>
                    <th>Combustible</th>
                    <th>Litros Calibración</th>
                    <th>Devolución al Stock</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT 
                        ct.fecha,
                        ct.turno,
                        ct.bomba,
                        ct.producto,
                        ct.litros_calibracion,
                        m.litros_recibidos AS devolucion_registrada
                    FROM cierres_turno ct
                    LEFT JOIN movimientos m 
                        ON m.fecha = ct.fecha 
                       AND m.dispensador = ct.bomba 
                       AND m.producto = ct.producto 
                       AND m.tipo = 'calibracion_devolucion'
                    WHERE ct.litros_calibracion > 0
                    ORDER BY ct.fecha DESC, ct.bomba";
            
            $res = mysqli_query($conn, $sql);
            if (!$res || mysqli_num_rows($res) === 0):
            ?>
                <tr>
                    <td colspan="6" class="sin-datos">
                        <i class="fas fa-info-circle"></i> Aún no hay registros de calibración
                    </td>
                </tr>
            <?php else:
                while ($fila = mysqli_fetch_assoc($res)):
                    $nombre_prod = $fila['producto'] === 'gasolina_especial' ? 'Gasolina' : 'Diésel';
                    $dev_ok = !empty($fila['devolucion_registrada']);
            ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($fila['fecha'])) ?></td>
                    <td><?= $fila['turno'] === 'mañana' ? '🌅 Mañana' : '🌆 Tarde' ?></td>
                    <td>Bomba N°<?= $fila['bomba'] ?></td>
                    <td><?= $nombre_prod ?></td>
                    <td><strong><?= number_format($fila['litros_calibracion'], 3, ',', '.') ?> L</strong></td>
                    <td class="<?= $dev_ok ? 'devuelto' : 'sin-dev' ?>">
                        <?= $dev_ok ? '✅ Devuelto' : '⚠️ Pendiente' ?>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pie">IMANOL - Dios Con Nosotros</div>
    <div class="pie">© <?= date('Y') ?> Desarrollado Por / Yaffar Villanueva B. y Christian Limpias B.</div>
</main>
</body>
</html>