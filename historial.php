<?php
// === CONFIGURAR ZONA HORARIA BOLIVIA ===
date_default_timezone_set('America/La_Paz');
include_once 'conexion.php';
verificarSesion();
$nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Operador';
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$inicial = mb_substr($nombre_usuario, 0, 1);
$es_admin = (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'administrador') 
        || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'administrador')
        || (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin')
        || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin');
$rol_usuario = $es_admin ? 'Administrador' : 'Operador';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// === CONSULTAR SOLO LOS REGISTROS QUE SE MUESTRAN ===
$vales = mysqli_query($conn, "SELECT * FROM vales WHERE usuario_id = $usuario_id AND fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' ORDER BY fecha DESC, id DESC");
$cierres = mysqli_query($conn, "SELECT * FROM cierres_turno WHERE usuario_id = $usuario_id AND fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' ORDER BY fecha DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial — IMANOL</title>
    <!-- ✅ FAVICON IMANOL -->
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
        .contenedor { max-width: 1100px; margin: 0 auto; }
        
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
        /* === FILTRO DE FECHAS === */
        .filtro {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .filtro .grupo { flex: 1; min-width: 160px; }
        .filtro label {
            display: block; font-size: 13px; color: #374151; margin-bottom: 8px; font-weight: 500;
        }
        .filtro input {
            width: 100%; padding: 12px 16px; border-radius: 8px;
            border: 1px solid #D1D5DB; background: #F9FAFB;
            color: #1F2937; font-size: 14px; outline: none;
            transition: all 0.25s ease;
        }
        .filtro input:focus { border-color: #F59E0B; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15); }
        .filtro button {
            padding: 12px 24px; border-radius: 8px; border: none;
            background: linear-gradient(90deg, #B91C1C, #F59E0B);
            color: #fff; font-size: 14px; font-weight: 600; cursor: pointer;
            transition: all 0.25s ease;
        }
        .filtro button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(185, 28, 28, 0.2); }
        /* === SECCIONES === */
        .seccion {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .seccion h2 {
            font-size: 16px; color: #1F2937; margin-bottom: 16px;
            padding-bottom: 12px; border-bottom: 1px solid #E5E7EB;
            display: flex; align-items: center; gap: 10px; font-weight: 600;
        }
        .seccion h2 i { color: #F59E0B; }
        /* === TABLAS === */
        .tabla { width: 100%; border-collapse: collapse; font-size: 14px; }
        .tabla th {
            background: #F9FAFB; padding: 14px 12px; text-align: left;
            color: #374151; font-weight: 600; font-size: 12px;
            text-transform: uppercase; border-bottom: 2px solid #E5E7EB;
        }
        .tabla td {
            padding: 14px 12px; border-bottom: 1px solid #F3F4F6; color: #4B5563;
        }
        .tabla tr:hover { background: #FFFBEB; }
        .vacio {
            color: #6B7280; font-style: italic; padding: 30px 20px; text-align: center;
        }
        .vacio i { font-size: 28px; color: #D1D5DB; margin-bottom: 10px; display: block; }
        /* === ETIQUETAS / BADGES === */
        .badge {
            display: inline-block; padding: 5px 12px; border-radius: 6px;
            font-size: 11px; font-weight: 600;
        }
        .badge.mañana { background: #FFFBEB; color: #B45309; border: 1px solid #FDE68A; }
        .badge.tarde { background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; }
        .badge.inicio { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }
        .badge.fin { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
        .badge.gasolina { background: #FFFBEB; color: #B45309; border: 1px solid #FDE68A; }
        .badge.diesel { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
        .tabla strong { color: #1F2937; }
        .pie {
            margin-top: 32px;
            text-align: center;
            font-size: 12px;
            color: #9CA3AF;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
        }
        @media (max-width: 768px) {
            .sidebar { width: 220px; }
            .main { margin-left: 220px; padding: 16px; }
            .filtro { flex-direction: column; align-items: stretch; }
            .filtro .grupo { width: 100%; }
            .tabla { font-size: 12px; }
            .tabla th, .tabla td { padding: 10px 6px; }
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
        <?php if ($es_admin): ?>
            <a href="index_admin.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <a href="gasolina.php"><i class="fas fa-gas-pump"></i> Gasolina</a>
            <a href="diesel.php"><i class="fas fa-oil-can"></i> Diésel</a>
            <a href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a>
            <a href="registro.php"><i class="fas fa-clipboard-list"></i> Registros</a>
            <a href="reportes.php"><i class="fas fa-chart-bar"></i> Reportes</a>
            <a href="proveedores.php"><i class="fas fa-building"></i> Proveedores</a>
            <a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a>
            <a href="crear_cuenta.php"><i class="fas fa-user-plus"></i> Crear Cuentas</a>
        <?php else: ?>
            <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
            <a href="cierre_turno.php"><i class="fas fa-calculator"></i> Mi Turno</a>
            <a href="vales.php"><i class="fas fa-file-invoice-dollar"></i> Vales</a>
            <a href="historial.php" class="activo"><i class="fas fa-history"></i> Historial</a>
            <a href="mi_perfil.php"><i class="fas fa-user"></i> Mi Perfil</a>
        <?php endif; ?>
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
        <a href="index.php" class="btn-regresar"><i class="fas fa-arrow-left"></i> Volver al Dashboard</a>
        
        <div class="cabecera">
            <div class="circulo-logo"><i class="fas fa-history"></i></div>
            <div class="cabecera-texto">
                <h1>📋 Historial Completo</h1>
                <p>Consulta todos tus registros por rango de fechas</p>
            </div>
        </div>
        <!-- FILTRO DE FECHAS -->
        <form class="filtro" method="GET" action="historial.php">
            <div class="grupo">
                <label>📅 Desde</label>
                <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>">
            </div>
            <div class="grupo">
                <label>📅 Hasta</label>
                <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>">
            </div>
            <div class="grupo">
                <button type="submit"><i class="fas fa-search"></i> Buscar</button>
            </div>
        </form>

        <!-- VALES -->
        <div class="seccion">
            <h2><i class="fas fa-file-invoice-dollar"></i> Vales Entregados</h2>
            <?php if ($vales && mysqli_num_rows($vales) > 0): ?>
            <table class="tabla">
                <thead>
                    <tr><th>Fecha</th><th>Bomba</th><th>Cliente</th><th>Gasolina L</th><th>Diésel L</th><th>Monto Bs</th></tr>
                </thead>
                <tbody>
                    <?php while ($f = mysqli_fetch_assoc($vales)): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($f['fecha'])) ?></td>
                        <td>Bomba N°<?= $f['bomba'] ?? '-' ?></td>
                        <td><?= htmlspecialchars($f['senor'] ?? 'Sin nombre') ?></td>
                        <td><?= number_format($f['gasolina_litros'] ?? 0, 3, ',', '.') ?> L</td>
                        <td><?= number_format($f['diesel_litros'] ?? 0, 3, ',', '.') ?> L</td>
                        <td><strong>Bs <?= number_format($f['monto_total'] ?? 0, 2, ',', '.') ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="vacio"><i class="fas fa-inbox"></i>Sin registros en este período.</p>
            <?php endif; ?>
        </div>
        <!-- CIERRES DE TURNO -->
        <div class="seccion">
            <h2><i class="fas fa-calculator"></i> Cierres de Turno</h2>
            <?php if ($cierres && mysqli_num_rows($cierres) > 0): ?>
            <table class="tabla">
                <thead>
                    <tr><th>Fecha</th><th>Turno</th><th>Bomba</th><th>Producto</th><th>Litros Vendidos</th><th>Total Bs</th></tr>
                </thead>
                <tbody>
                    <?php while ($f = mysqli_fetch_assoc($cierres)): 
                        $es_gas = ($f['producto'] ?? '') === 'gasolina_especial';
                        $litros = $f['litros_vendidos'] ?? 0;
                        $total = $f['total_real'] ?? 0;
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($f['fecha'])) ?></td>
                        <td><span class="badge <?= $f['turno'] ?>"><?= $f['turno'] === 'mañana' ? '🌅 Mañana' : '🌆 Tarde' ?></span></td>
                        <td>Bomba N°<?= $f['bomba'] ?></td>
                        <td><span class="badge <?= $es_gas ? 'gasolina' : 'diesel' ?>"><?= $es_gas ? '⛽ Gasolina' : '🛢️ Diésel' ?></span></td>
                        <td><?= number_format($litros, 3, ',', '.') ?> L</td>
                        <td><strong>Bs <?= number_format($total, 2, ',', '.') ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="vacio"><i class="fas fa-inbox"></i>Sin registros en este período.</p>
            <?php endif; ?>
        </div>
        <div class="pie">© <?= date('Y') ?> IMANOL — Estación de Servicio · Historial de Operaciones</div>
    </div>
</main>
</body>
</html>