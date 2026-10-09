<?php
include_once 'conexion.php';
verificarSesion();

// === SOLO ADMINISTRADORES PUEDEN VER ESTA PÁGINA ===
$es_admin = (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin', 'administrador'])) 
         || (isset($_SESSION['usuario_tipo']) && in_array($_SESSION['usuario_tipo'], ['admin', 'administrador']));
if (!$es_admin) {
    die("❌ No tienes permiso para ver esta página.");
}

$nombre_admin = $_SESSION['usuario_nombre'] ?? 'Administrador';
$rol_usuario = $es_admin ? 'Administrador' : 'Operador';
$inicial = mb_substr($nombre_admin, 0, 1);

// === ELIMINAR CIERRE DE TURNO ===
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    mysqli_query($conn, "DELETE FROM cierres_turno WHERE id = $id");
    header("Location: registro.php");
    exit;
}

// === TRAER TODOS LOS CIERRES DE TURNO CON DATOS COMPLETOS ===
$resultado = mysqli_query($conn, "
    SELECT ct.*, 
           u.nombre AS nombre_operador,
           CASE 
               WHEN ct.producto = 'gasolina_especial' THEN ct.litros_vendidos
               ELSE 0 
           END AS gasolina_vendida,
           CASE 
               WHEN ct.producto = 'diesel_oil' THEN ct.litros_vendidos
               ELSE 0 
           END AS diesel_vendido
    FROM cierres_turno ct 
    LEFT JOIN usuarios u ON ct.usuario_id = u.id 
    ORDER BY ct.fecha DESC, ct.id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registros — IMANOL</title>
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
        .contenedor { max-width: 1400px; margin: 0 auto; }
        
        .btn-regresar {
            display: inline-flex; align-items: center; gap: 10px; padding: 10px 20px;
            background: #F3F4F6; color: #4B5563; border: 1px solid #E5E7EB; border-radius: 10px;
            text-decoration: none; font-size: 14px; margin-bottom: 24px; transition: all 0.25s ease;
        }
        .btn-regresar:hover {
            background: #FEF2F2; color: #991B1B; border-color: #DC2626; transform: translateX(-3px);
        }
        
        .cabecera {
            background: #FFFFFF; border: 1px solid #FECACA; border-radius: 16px; padding: 24px 28px;
            margin-bottom: 24px; display: flex; align-items: center; gap: 20px;
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
        
        .caja-tabla { 
            background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 14px; 
            padding: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); 
        }
        .tabla { width: 100%; border-collapse: collapse; }
        .tabla th { 
            text-align: left; font-size: 12px; color: #6B7280; padding: 12px 10px; 
            border-bottom: 2px solid #E5E7EB; text-transform: uppercase; letter-spacing: 0.3px; 
            font-weight: 600; background: #F9FAFB;
            white-space: nowrap;
        }
        .tabla td { 
            padding: 14px 10px; font-size: 14px; 
            border-bottom: 1px solid #F3F4F6; color: #4B5563; 
        }
        .tabla tr:hover { background: #FEF2F2; }
        .vacio { text-align: center; padding: 45px; color: #6B7280; font-style: italic; }
        
        .inicial { 
            display: inline-flex; align-items: center; justify-content: center; 
            width: 32px; height: 32px; border-radius: 50%; 
            background: linear-gradient(135deg, #B91C1C, #EF4444); 
            color: #fff; font-weight: bold; margin-right: 10px; font-size: 14px; 
        }
        .celda-nombre { display: flex; align-items: center; }
        
        .totales { 
            margin-top: 20px; padding: 18px 24px; 
            background: #F0FDF4; border-radius: 10px; 
            display: flex; gap: 32px; flex-wrap: wrap; 
            font-weight: 700; color: #166534; 
            border: 1px solid #BBF7D0;
        }
        .totales span { display: flex; align-items: center; gap: 8px; }
        
        .badge { 
            display: inline-block; padding: 6px 14px; border-radius: 8px; 
            font-size: 13px; font-weight: 600; 
        }
        .gas { 
            background: #FFFBEB; color: #B45309; border: 1px solid #FDE68A;
        }
        .die { 
            background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA;
        }
        .badge-bomba {
            background: #EFF6FF; color: #1E40AF; border: 1px solid #BFDBFE;
        }
        .badge-turno {
            background: #F3E8FF; color: #6B21A8; border: 1px solid #DDD6FE;
        }
        .sin-dato { color: #9CA3AF; font-style: italic; }
        
        /* === BOTONES DE ACCIÓN === */
        .btn-accion {
            padding: 6px 10px; border-radius: 6px; font-size: 13px; text-decoration: none;
            display: inline-flex; align-items: center; gap: 5px; margin: 2px;
            transition: all 0.2s; border: none; cursor: pointer;
        }
        .btn-editar { background: #FEF3C7; color: #92400E; }
        .btn-editar:hover { background: #FDE68A; }
        .btn-eliminar { background: #FEE2E2; color: #991B1B; }
        .btn-eliminar:hover { background: #FECACA; }
        
        @media (max-width: 768px) { 
            .sidebar { width: 220px; }
            .main { margin-left: 220px; padding: 16px; }
            .tabla { display: block; overflow-x: auto; }
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
        <a href="registro.php" class="activo"><i class="fas fa-th-list"></i> Registros</a>
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
            <div class="circulo-logo"><i class="fas fa-th-list"></i></div>
            <div class="cabecera-texto">
                <h1>📋 Todos los Cierres de Turno</h1>
                <p>Historial completo — recepciones, ventas, responsables y totales</p>
            </div>
        </div>

        <div class="caja-tabla">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>📅 Fecha</th>
                        <th>👤 Operador</th>
                        <th>⛽ Bomba</th>
                        <th>🕐 Turno</th>
                        <th>📖 Lectura Inicial</th>
                        <th>📕 Lectura Final</th>
                        <th>⛽ Gasolina Vendida</th>
                        <th>🛢️ Diésel Vendido</th>
                        <th>💰 Venta Total</th>
                        <th>📝 Observaciones</th>
                        <th>⚙️ Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_gas = 0;
                    $total_die = 0;
                    $total_venta = 0;
                    ?>
                    <?php if (mysqli_num_rows($resultado) > 0): ?>
                        <?php while ($fila = mysqli_fetch_assoc($resultado)): 
                            $gas = floatval($fila['gasolina_vendida'] ?? 0);
                            $die = floatval($fila['diesel_vendido'] ?? 0);
                            $total_gas += $gas;
                            $total_die += $die;
                            $venta = floatval($fila['total_real'] ?? $fila['total_venta'] ?? 0);
                            $total_venta += $venta;
                            $bomba = $fila['bomba'] ?? 0;
                            $turno = $fila['turno'] ?? '';
                            $lectura_inicial = floatval($fila['lectura_inicial'] ?? 0);
                            $lectura_final = floatval($fila['lectura_final'] ?? 0);
                        ?>
                        <tr>
                            <td><strong><?= !empty($fila['fecha']) ? date('d/m/Y', strtotime($fila['fecha'])) : '—' ?></strong></td>
                            <td>
                                <div class="celda-nombre">
                                    <span class="inicial"><?= mb_substr($fila['nombre_operador'] ?? '?', 0, 1) ?></span>
                                    <?= $fila['nombre_operador'] ?? 'Desconocido' ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($bomba > 0): ?>
                                    <span class="badge badge-bomba">Bomba N°<?= $bomba ?></span>
                                <?php else: ?>
                                    <span class="sin-dato">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($turno): ?>
                                    <span class="badge badge-turno"><?= ucfirst($turno) ?></span>
                                <?php else: ?>
                                    <span class="sin-dato">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $lectura_inicial > 0 ? number_format($lectura_inicial, 3, ',', '.') . ' L' : '<span class="sin-dato">—</span>' ?>
                            </td>
                            <td>
                                <?= $lectura_final > 0 ? number_format($lectura_final, 3, ',', '.') . ' L' : '<span class="sin-dato">—</span>' ?>
                            </td>
                            <td>
                                <?php if ($gas > 0): ?>
                                    <span class="badge gas"><?= number_format($gas, 3, ',', '.') ?> L</span>
                                <?php else: ?>
                                    <span class="sin-dato">0,000 L</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($die > 0): ?>
                                    <span class="badge die"><?= number_format($die, 3, ',', '.') ?> L</span>
                                <?php else: ?>
                                    <span class="sin-dato">0,000 L</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>Bs <?= number_format($venta, 2, ',', '.') ?></strong>
                            </td>
                            <td style="color: #6B7280; font-size: 12px;">
                                <?= !empty($fila['observaciones']) ? htmlspecialchars($fila['observaciones']) : '<span class="sin-dato">Sin observaciones</span>' ?>
                            </td>
                            <td>
                                <a href="editar_cierre.php?id=<?= $fila['id'] ?>" class="btn-accion btn-editar" title="Editar cierre">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="registro.php?eliminar=<?= $fila['id'] ?>" 
                                   class="btn-accion btn-eliminar" 
                                   onclick="return confirm('¿Eliminar este cierre de turno? Esta acción no se puede deshacer.')"
                                   title="Eliminar cierre">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="11" class="vacio">Aún no hay registros de cierres de turno.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($total_gas > 0 || $total_die > 0 || $total_venta > 0): ?>
            <div class="totales">
                <span>⛽ TOTAL GASOLINA: <?= number_format($total_gas, 3, ',', '.') ?> Litros</span>
                <span>🛢️ TOTAL DIÉSEL: <?= number_format($total_die, 3, ',', '.') ?> Litros</span>
                <span>💰 TOTAL INGRESOS: Bs <?= number_format($total_venta, 2, ',', '.') ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>