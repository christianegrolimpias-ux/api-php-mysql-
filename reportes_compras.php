<?php
date_default_timezone_set('America/La_Paz'); // ✅ Zona horaria Bolivia
include_once 'conexion.php';
verificarSesion();
$es_admin = (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin', 'administrador'])) 
        || (isset($_SESSION['usuario_tipo']) && in_array($_SESSION['usuario_tipo'], ['admin', 'administrador']));
if (!$es_admin) {
    die("❌ No tienes permiso para acceder a esta página.");
}
$nombre_admin = $_SESSION['usuario_nombre'] ?? 'Administrador';
$inicial = mb_substr($nombre_admin, 0, 1);
// === PRECIOS OFICIALES ACTUALIZADOS ===
$PRECIO_GASOLINA = 6.59;   // ✅ Precio original
$PRECIO_DIESEL   = 17.52;  // ✅ Precio original
// === FECHAS PARA BUSCAR ===
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin    = $_GET['fecha_fin'] ?? date('Y-m-d');
// === ELIMINAR REGISTRO ===
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    mysqli_query($conn, "DELETE FROM movimientos WHERE id = $id");
    header("Location: reportes_compras.php?fecha_inicio=$fecha_inicio&fecha_fin=$fecha_fin");
    exit;
}
// === CONSULTA: TODOS LOS REGISTROS DEL PERÍODO ===
$registros = mysqli_query($conn, "
    SELECT * FROM movimientos
    WHERE fecha BETWEEN '$fecha_inicio' AND DATE_ADD('$fecha_fin', INTERVAL 1 DAY)
    ORDER BY fecha ASC, producto ASC, id ASC
");
// === PROCESAR DATOS SEPARANDO POR PRODUCTO ===
$gasolina_filas = [];
$diesel_filas = [];
$gas_recibidos = $gas_vales = $gas_mermas = $gas_monto = 0;
$die_recibidos = $die_vales = $die_mermas = $die_monto = 0;
$saldo_gas = 0;
$saldo_die = 0;
while ($r = mysqli_fetch_assoc($registros)) {
    $prod = $r['producto'];
    $tipo = $r['tipo'] ?? '';
    
    $litros_recibidos = 0;
    $litros_vales = 0;
    $mermas = floatval($r['mermas'] ?? 0) + floatval($r['merma_recepcion'] ?? 0);
    
    // ✅ LÓGICA CORREGIDA: PRIMERO USA EL PRECIO GUARDADO EN LA BD, SI NO EXISTE USA EL PRECIO OFICIAL
    if (!empty($r['precio_unitario']) && floatval($r['precio_unitario']) > 0) {
        $precio_unit = floatval($r['precio_unitario']);
    } else {
        $precio_unit = ($prod === 'gasolina_especial') ? $PRECIO_GASOLINA : $PRECIO_DIESEL;
    }
    
    if ($tipo === 'entrada') {
        $litros_recibidos = floatval($r['litros_recibidos'] ?? $r['litros_despachados'] ?? 0);
    } elseif ($tipo === 'salida') {
        $litros_vales = floatval($r['litros_despachados'] ?? 0);
    }
    
    if ($prod === 'gasolina_especial') {
        $saldo_gas = $saldo_gas + $litros_recibidos - $litros_vales - $mermas;
        $fila = $r;
        $fila['litros_recibidos'] = $litros_recibidos;
        $fila['litros_vales'] = $litros_vales;
        $fila['mermas_tot'] = $mermas;
        $fila['precio_calc'] = $precio_unit;
        $fila['saldo_final'] = $saldo_gas;
        $gasolina_filas[] = $fila;
        
        $gas_recibidos += $litros_recibidos;
        $gas_vales += $litros_vales;
        $gas_mermas += $mermas;
        $gas_monto += $litros_recibidos * $precio_unit;
    }
    
    if ($prod === 'diesel_oil') {
        $saldo_die = $saldo_die + $litros_recibidos - $litros_vales - $mermas;
        $fila = $r;
        $fila['litros_recibidos'] = $litros_recibidos;
        $fila['litros_vales'] = $litros_vales;
        $fila['mermas_tot'] = $mermas;
        $fila['precio_calc'] = $precio_unit;
        $fila['saldo_final'] = $saldo_die;
        $diesel_filas[] = $fila;
        
        $die_recibidos += $litros_recibidos;
        $die_vales += $litros_vales;
        $die_mermas += $mermas;
        $die_monto += $litros_recibidos * $precio_unit;
    }
}
$total_recibidos = $gas_recibidos + $die_recibidos;
$total_vales = $gas_vales + $die_vales;
$total_mermas = $gas_mermas + $die_mermas;
$total_monto = $gas_monto + $die_monto;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte General — IMANOL</title>
    <link rel="icon" type="image/png" sizes="80x80" href="imagenes/favicon2.png">
    <link rel="icon" type="image/png" sizes="32x32" href="imagenes/favicon2.png">
    <link rel="icon" type="image/png" sizes="16x16" href="imagenes/favicon2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
            display: flex; align-items: center; gap: 5px; font-size: 11px; color: #90EE90; margin-bottom: 8px;
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
        
        .main {
            margin-left: 250px; flex: 1; padding: 24px 30px;
            background: #FFFFFF; min-height: 100vh;
        }
        .contenedor { max-width: 1600px; margin: 0 auto; }
        
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
            margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;
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
        .filtro .grupo { flex: 1; min-width: 160px; }
        .filtro label { 
            display: block; font-size: 13px; color: #374151; 
            margin-bottom: 6px; font-weight: 500;
        }
        .filtro input, .filtro button { 
            padding: 11px 16px; border-radius: 8px; 
            border: 1px solid #D1D5DB; 
            background: #F9FAFB; color: #1F2937; 
            font-size: 14px; transition: all 0.25s ease;
        }
        .filtro input:focus {
            outline: none; border-color: #F59E0B;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
            background: #FFFBEB;
        }
        .filtro button { 
            background: linear-gradient(90deg, #B45309, #F59E0B, #FBBF24); 
            border: none; cursor: pointer; font-weight: 700; 
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        .filtro button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }
        
        .seccion-producto { margin-bottom: 32px; }
        .titulo-producto {
            display: flex; align-items: center; gap: 12px; 
            padding: 16px 20px; border-radius: 14px; 
            margin-bottom: 16px; font-size: 18px; font-weight: 700;
        }
        .titulo-gas { 
            background: #FFFBEB; border: 1px solid #FDE68A; 
            color: #B45309;
        }
        .titulo-die { 
            background: #FEF2F2; border: 1px solid #FECACA; 
            color: #B91C1C;
        }
        .titulo-resumen { 
            background: #F0FDF4; border: 1px solid #BBF7D0; 
            color: #166534;
        }
        
        .tabla { 
            width: 100%; border-collapse: collapse; 
            font-size: 12.5px; background: #FFFFFF;
            border-radius: 12px; overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .tabla th { 
            background: #F9FAFB; padding: 12px 10px; text-align: left; 
            color: #374151; font-weight: 600; 
            border-bottom: 2px solid #E5E7EB; white-space: nowrap;
        }
        .tabla td { 
            padding: 12px 10px; 
            border-bottom: 1px solid #F3F4F6;
            color: #4B5563;
        }
        .tabla tr:hover { background: #FFFBEB; }
        .vacio { color: #6B7280; font-style: italic; padding: 32px; text-align: center; }
        .fila-total { 
            background: #F0FDF4; font-weight: bold; 
            border-top: 2px solid #BBF7D0;
        }
        .num-der { text-align: right; }
        .gas { color: #B45309; font-weight: 600; }
        .die { color: #B91C1C; font-weight: 600; }
        .verde { color: #16A34A; font-weight: 600; }
        .rojo { color: #DC2626; font-weight: 600; }
        .azul { color: #2563EB; font-weight: 600; }
        .sin-dato { color: #9CA3AF; font-style: italic; }
        
        .btn-accion {
            padding: 5px 9px; border-radius: 6px; font-size: 12px; text-decoration: none; display: inline-flex;
            align-items: center; gap: 4px; margin: 2px; transition: all 0.2s; border: none; cursor: pointer;
        }
        .btn-editar { background: #FEF3C7; color: #92400E; }
        .btn-editar:hover { background: #FDE68A; }
        .btn-eliminar { background: #FEE2E2; color: #991B1B; }
        .btn-eliminar:hover { background: #FECACA; }
        
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
        <div>
            <h2>MANOL</h2>
            <p>Dios Con Nosotros</p>
        </div>
    </div>
    <nav>
        <a href="index_admin.php"><i class="fas fa-chart-pie"></i> Inicio</a>
        <a href="gasolina.php"><i class="fas fa-gas-pump"></i> Gasolina</a>
        <a href="diesel.php"><i class="fas fa-gas-pump"></i> Diésel</a>
        <a href="reportes_compras.php" class="activo"><i class="fas fa-file-invoice-dollar"></i> Compras Combustible</a>
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
    <div class="contenedor" id="areaReporte">
        <a href="index_admin.php" class="btn-regresar"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
        
        <div class="cabecera">
            <div class="cabecera-izq">
                <div class="circulo-logo"><i class="fas fa-chart-bar"></i></div>
                <div class="cabecera-texto">
                    <h1>Reporte General de Movimientos</h1>
                    <p>Período: <strong><?= date('d/m/Y', strtotime($fecha_inicio)) ?></strong> al <strong><?= date('d/m/Y', strtotime($fecha_fin)) ?></strong></p>
                </div>
            </div>
            <div class="botones-exportar">
                <button class="btn-excel" onclick="exportarExcel()"><i class="fas fa-file-excel"></i> Excel</button>
                <button class="btn-pdf" onclick="exportarPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
        </div>
        <form class="filtro" method="GET" action="reportes_compras.php">
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
        <!-- ⛽ GASOLINA ESPECIAL -->
        <div class="seccion-producto">
            <h2 class="titulo-producto titulo-gas">
                <i class="fas fa-gas-pump" style="color:#F59E0B;"></i> PRODUCTO: GASOLINA ESPECIAL
            </h2>
            <table class="tabla" id="tablaGasolina">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>N° Autorización</th>
                        <th>N° Factura</th>
                        <th>Precintos</th>
                        <th>Formularios</th>
                        <th>Litros Recibidos</th>
                        <th>Precio Unit.</th>
                        <th>Litros Vendidos</th>
                        <th>Mermas</th>
                        <th>Saldo Final</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($gasolina_filas)): ?>
                        <?php foreach ($gasolina_filas as $r): ?>
                        <tr>
                            <td><strong><?= date('d/m/Y', strtotime($r['fecha'])) ?></strong></td>
                            <td><?= !empty($r['n_autorizacion']) ? htmlspecialchars($r['n_autorizacion']) : '<span class="sin-dato">—</span>' ?></td>
                            <td><?= !empty($r['n_factura']) ? htmlspecialchars($r['n_factura']) : '<span class="sin-dato">—</span>' ?></td>
                            <td class="num-der"><?= $r['precintos'] ?? 0 ?></td>
                            <td class="num-der"><?= $r['formularios'] ?? 0 ?></td>
                            <td class="num-der gas">
                                <?= $r['litros_recibidos'] > 0 ? number_format($r['litros_recibidos'], 2, ',', '.') . ' L' : '<span class="sin-dato">0,00 L</span>' ?>
                            </td>
                            <td class="num-der"><strong><?= number_format($r['precio_calc'], 2, ',', '.') ?> Bs</strong></td>
                            <td class="num-der azul">
                                <?= $r['litros_vales'] > 0 ? number_format($r['litros_vales'], 2, ',', '.') . ' L' : '<span class="sin-dato">0,00 L</span>' ?>
                            </td>
                            <td class="num-der rojo">
                                <?= $r['mermas_tot'] > 0 ? number_format($r['mermas_tot'], 2, ',', '.') . ' L' : '<span class="sin-dato">0,00 L</span>' ?>
                            </td>
                            <td class="num-der verde"><strong><?= number_format($r['saldo_final'], 2, ',', '.') ?> L</strong></td>
                            <td>
                                <a href="editar_movimiento.php?id=<?= $r['id'] ?>&fecha_inicio=<?= $fecha_inicio ?>&fecha_fin=<?= $fecha_fin ?>" class="btn-accion btn-editar" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="reportes_compras.php?eliminar=<?= $r['id'] ?>&fecha_inicio=<?= $fecha_inicio ?>&fecha_fin=<?= $fecha_fin ?>" 
                                   class="btn-accion btn-eliminar" 
                                   onclick="return confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')"
                                   title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="fila-total">
                            <td colspan="5"><strong>📊 TOTAL GASOLINA</strong></td>
                            <td class="num-der gas"><strong><?= number_format($gas_recibidos, 2, ',', '.') ?> L</strong></td>
                            <td class="num-der"><strong>Bs <?= number_format($PRECIO_GASOLINA, 2, ',', '.') ?></strong></td>
                            <td class="num-der azul"><strong><?= number_format($gas_vales, 2, ',', '.') ?> L</strong></td>
                            <td class="num-der rojo"><strong><?= number_format($gas_mermas, 2, ',', '.') ?> L</strong></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="11" class="vacio">📋 Sin registros de Gasolina en este período.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- 🛢️ DIÉSEL OIL -->
        <div class="seccion-producto">
            <h2 class="titulo-producto titulo-die">
                <i class="fas fa-gas-pump" style="color:#DC2626;"></i> PRODUCTO: DIÉSEL OIL
            </h2>
            <table class="tabla" id="tablaDiesel">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>N° Autorización</th>
                        <th>N° Factura</th>
                        <th>Precintos</th>
                        <th>Formularios</th>
                        <th>Litros Recibidos</th>
                        <th>Precio Unit.</th>
                        <th>Litros Vendidos</th>
                        <th>Mermas</th>
                        <th>Saldo Final</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($diesel_filas)): ?>
                        <?php foreach ($diesel_filas as $r): ?>
                        <tr>
                            <td><strong><?= date('d/m/Y', strtotime($r['fecha'])) ?></strong></td>
                            <td><?= !empty($r['n_autorizacion']) ? htmlspecialchars($r['n_autorizacion']) : '<span class="sin-dato">—</span>' ?></td>
                            <td><?= !empty($r['n_factura']) ? htmlspecialchars($r['n_factura']) : '<span class="sin-dato">—</span>' ?></td>
                            <td class="num-der"><?= $r['precintos'] ?? 0 ?></td>
                            <td class="num-der"><?= $r['formularios'] ?? 0 ?></td>
                            <td class="num-der die">
                                <?= $r['litros_recibidos'] > 0 ? number_format($r['litros_recibidos'], 2, ',', '.') . ' L' : '<span class="sin-dato">0,00 L</span>' ?>
                            </td>
                            <td class="num-der"><strong><?= number_format($r['precio_calc'], 2, ',', '.') ?> Bs</strong></td>
                            <td class="num-der azul">
                                <?= $r['litros_vales'] > 0 ? number_format($r['litros_vales'], 2, ',', '.') . ' L' : '<span class="sin-dato">0,00 L</span>' ?>
                            </td>
                            <td class="num-der rojo">
                                <?= $r['mermas_tot'] > 0 ? number_format($r['mermas_tot'], 2, ',', '.') . ' L' : '<span class="sin-dato">0,00 L</span>' ?>
                            </td>
                            <td class="num-der verde"><strong><?= number_format($r['saldo_final'], 2, ',', '.') ?> L</strong></td>
                            <td>
                                <a href="editar_movimiento_compra.php?id=<?= $r['id'] ?>&fecha_inicio=<?= $fecha_inicio ?>&fecha_fin=<?= $fecha_fin ?>" class="btn-accion btn-editar" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="reportes_compras.php?eliminar=<?= $r['id'] ?>&fecha_inicio=<?= $fecha_inicio ?>&fecha_fin=<?= $fecha_fin ?>" 
                                   class="btn-accion btn-eliminar" 
                                   onclick="return confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')"
                                   title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="fila-total">
                            <td colspan="5"><strong>📊 TOTAL DIÉSEL</strong></td>
                            <td class="num-der die"><strong><?= number_format($die_recibidos, 2, ',', '.') ?> L</strong></td>
                            <td class="num-der"><strong>Bs <?= number_format($PRECIO_DIESEL, 2, ',', '.') ?></strong></td>
                            <td class="num-der azul"><strong><?= number_format($die_vales, 2, ',', '.') ?> L</strong></td>
                            <td class="num-der rojo"><strong><?= number_format($die_mermas, 2, ',', '.') ?> L</strong></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="11" class="vacio">📋 Sin registros de Diésel en este período.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- 📈 RESUMEN FINAL -->
        <div class="seccion-producto">
            <h2 class="titulo-producto titulo-resumen">
                <i class="fas fa-calculator" style="color:#22C55E;"></i> RESUMEN FINAL DEL PERÍODO
            </h2>
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Litros Recibidos</th>
                        <th>Litros Vendidos</th>
                        <th>Total Mermas</th>
                        <th>Total en Bolivianos</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="gas"><strong>⛽ Gasolina Especial</strong></td>
                        <td class="num-der gas"><?= number_format($gas_recibidos, 2, ',', '.') ?> L</td>
                        <td class="num-der azul"><?= number_format($gas_vales, 2, ',', '.') ?> L</td>
                        <td class="num-der rojo"><?= number_format($gas_mermas, 2, ',', '.') ?> L</td>
                        <td class="num-der verde"><strong>Bs <?= number_format($gas_monto, 2, ',', '.') ?></strong></td>
                    </tr>
                    <tr>
                        <td class="die"><strong>🛢️ Diésel Oil</strong></td>
                        <td class="num-der die"><?= number_format($die_recibidos, 2, ',', '.') ?> L</td>
                        <td class="num-der azul"><?= number_format($die_vales, 2, ',', '.') ?> L</td>
                        <td class="num-der rojo"><?= number_format($die_mermas, 2, ',', '.') ?> L</td>
                        <td class="num-der verde"><strong>Bs <?= number_format($die_monto, 2, ',', '.') ?></strong></td>
                    </tr>
                    <tr class="fila-total">
                        <td><strong>📊 TOTAL GENERAL</strong></td>
                        <td class="num-der"><strong><?= number_format($total_recibidos, 2, ',', '.') ?> L</strong></td>
                        <td class="num-der azul"><strong><?= number_format($total_vales, 2, ',', '.') ?> L</strong></td>
                        <td class="num-der rojo"><strong><?= number_format($total_mermas, 2, ',', '.') ?> L</strong></td>
                        <td class="num-der verde"><strong>Bs <?= number_format($total_monto, 2, ',', '.') ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script>
// === EXPORTAR A EXCEL ===
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
    datos.push(['Gasolina', '<?= number_format($gas_recibidos, 2, ',', '.') ?> L', '<?= number_format($gas_vales, 2, ',', '.') ?> L', '<?= number_format($gas_mermas, 2, ',', '.') ?> L', 'Bs <?= number_format($gas_monto, 2, ',', '.') ?>']);
    datos.push(['Diésel', '<?= number_format($die_recibidos, 2, ',', '.') ?> L', '<?= number_format($die_vales, 2, ',', '.') ?> L', '<?= number_format($die_mermas, 2, ',', '.') ?> L', 'Bs <?= number_format($die_monto, 2, ',', '.') ?>']);
    datos.push(['TOTAL GENERAL', '<?= number_format($total_recibidos, 2, ',', '.') ?> L', '<?= number_format($total_vales, 2, ',', '.') ?> L', '<?= number_format($total_mermas, 2, ',', '.') ?> L', 'Bs <?= number_format($total_monto, 2, ',', '.') ?>']);
    
    const hoja = XLSX.utils.aoa_to_sheet(datos);
    const libro = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(libro, hoja, 'Compras');
    XLSX.writeFile(libro, 'Reporte_Compras_<?= date('d-m-Y', strtotime($fecha_inicio)) ?>_al_<?= date('d-m-Y', strtotime($fecha_fin)) ?>.xlsx');
}
// === EXPORTAR A PDF ===
function exportarPDF() {
    const contenido = document.getElementById('areaReporte').innerHTML;
    const ventana = window.open('', '_blank');
    ventana.document.write(`
        <html>
        <head>
            <title>Reporte Compras - IMANOL</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; font-size: 12px; }
                h2 { color: #B45309; margin: 20px 0 10px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background: #f5f5f5; font-weight: bold; }
                .fila-total { background: #f0fdf4; font-weight: bold; }
                @media print { body { padding: 0; } }
            </style>
        </head>
        <body>
            <h1 style="color:#B45309;">Reporte General de Compras - IMANOL</h1>
            <p><strong>Período:</strong> <?= date('d/m/Y', strtotime($fecha_inicio)) ?> al <?= date('d/m/Y', strtotime($fecha_fin)) ?></p>
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