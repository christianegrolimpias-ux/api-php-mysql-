<?php
date_default_timezone_set('America/La_Paz'); // ✅ Zona horaria Bolivia
include_once 'conexion.php';
verificarSesion();

// === SOLO ADMINISTRADORES PUEDEN ACCEDER ===
$es_admin = (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin', 'administrador'])) 
         || (isset($_SESSION['usuario_tipo']) && in_array($_SESSION['usuario_tipo'], ['admin', 'administrador']));
if (!$es_admin) {
    die("❌ No tienes permiso para acceder a esta página.");
}

$nombre_admin = $_SESSION['usuario_nombre'] ?? 'Administrador';
$inicial = mb_substr($nombre_admin, 0, 1);

// === PRECIOS ===
$PRECIO_GAS = 6.96;
$PRECIO_DIE = 17.95;

// === FILTRO DE FECHAS ===
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin    = $_GET['fecha_fin'] ?? date('Y-m-d');

// === ELIMINAR VALE ===
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    mysqli_query($conn, "DELETE FROM vales WHERE id = $id");
    header("Location: reporte_vales.php?fecha_inicio=$fecha_inicio&fecha_fin=$fecha_fin");
    exit;
}

// === CONSULTA: TODOS LOS VALES CON DATOS DEL OPERADOR ===
$vales = mysqli_query($conn, "
    SELECT v.*, u.nombre AS nombre_operador 
    FROM vales v
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    WHERE v.fecha BETWEEN '$fecha_inicio' AND DATE_ADD('$fecha_fin', INTERVAL 1 DAY)
    ORDER BY v.fecha DESC, v.id DESC
");

// === CALCULAR TOTALES ===
$total_gas_litros = $total_die_litros = $total_monto = 0;
$cantidad_vales = 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Vales — IMANOL</title>
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
        /* === BARRA LATERAL === */
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
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; color: rgba(255,255,255,0.9);
            text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 500;
            transition: all 0.25s ease;
        }
        .sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.25); color: #fff; transform: translateX(3px);
        }
        .sidebar nav a.activo {
            background: rgba(255, 255, 255, 0.3); color: #fff;
            border-left: 3px solid #fff; font-weight: 600;
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
        .sidebar .user-perfil h4 { font-size: 13px; color: #fff; margin: 0; }
        .sidebar .user-perfil p { font-size: 11px; color: rgba(255,255,255,0.75); margin: 2px 0 0 0; }
        .cerrar-btn {
            background: rgba(0, 0, 0, 0.2); color: #fff; border: none; padding: 8px; border-radius: 7px;
            width: 100%; font-size: 12px; display: flex; align-items: center; justify-content: center;
            gap: 7px; text-decoration: none; transition: all 0.2s;
        }
        .cerrar-btn:hover { background: rgba(0, 0, 0, 0.35); }
        
        /* === ÁREA PRINCIPAL === */
        .main {
            margin-left: 250px; flex: 1; padding: 24px 30px;
            background: #FFFFFF; min-height: 100vh;
        }
        .contenedor { max-width: 1600px; margin: 0 auto; }
        
        .btn-regresar {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 10px 20px; background: #F3F4F6; color: #4B5563;
            border: 1px solid #E5E7EB; border-radius: 10px;
            text-decoration: none; font-size: 14px; margin-bottom: 24px;
            transition: all 0.25s ease;
        }
        .btn-regresar:hover {
            background: #FEF2F2; color: #991B1B; border-color: #DC2626; transform: translateX(-3px);
        }
        .cabecera {
            background: #FFFFFF; border: 1px solid #FDE68A; border-radius: 16px;
            padding: 24px 28px; margin-bottom: 20px;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;
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
        
        /* === FILTRO FECHAS === */
        .filtro { 
            background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 14px; 
            padding: 20px; margin-bottom: 24px; 
            display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;
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
        .btn-buscar { 
            background: linear-gradient(90deg, #B45309, #F59E0B); 
            border: none; cursor: pointer; font-weight: 700; 
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        .btn-buscar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }
        
        /* === TABLA === */
        .caja-tabla {
            background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 14px;
            padding: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .tabla { width: 100%; border-collapse: collapse; font-size: 13px; }
        .tabla th {
            background: #F9FAFB; padding: 12px 10px; text-align: left;
            color: #374151; font-weight: 600; border-bottom: 2px solid #E5E7EB;
            white-space: nowrap; font-size: 11.5px; text-transform: uppercase;
        }
        .tabla td {
            padding: 12px 10px; border-bottom: 1px solid #F3F4F6; color: #4B5563;
        }
        .tabla tr:hover { background: #FFFBEB; }
        .vacio { text-align: center; padding: 45px; color: #6B7280; font-style: italic; }
        
        .badge {
            display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;
        }
        .bomba { background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; }
        .gas { color: #B45309; font-weight: 600; }
        .die { color: #B91C1C; font-weight: 600; }
        .monto { color: #16A34A; font-weight: 700; }
        .sin-dato { color: #9CA3AF; font-style: italic; }
        
        .btn-accion {
            padding: 5px 9px; border-radius: 6px; font-size: 12px; text-decoration: none; display: inline-flex;
            align-items: center; gap: 4px; margin: 2px; transition: all 0.2s; border: none; cursor: pointer;
        }
        .btn-editar { background: #FEF3C7; color: #92400E; }
        .btn-editar:hover { background: #FDE68A; }
        .btn-eliminar { background: #FEE2E2; color: #991B1B; }
        .btn-eliminar:hover { background: #FECACA; }
        
        .inicial {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 50%;
            background: linear-gradient(135deg, #B91C1C, #EF4444);
            color: #fff; font-weight: bold; margin-right: 8px; font-size: 12px;
        }
        .celda-nombre { display: flex; align-items: center; }
        
        /* === TOTALES DETALLADOS === */
        .totales {
            margin-top: 24px;
            padding: 28px 32px;
            background: linear-gradient(135deg, #FAFBF8 0%, #F8F9FA 100%);
            border-radius: 14px;
            border: 1px solid #E5E7EB;
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            align-items: flex-start;
        }
        .total-fila {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 14px;
        }
        .total-etiqueta {
            color: #4B5563;
            font-weight: 500;
        }
        .total-valor {
            font-weight: 700;
            color: #1F2937;
        }
        .total-bloque {
            flex: 1;
            min-width: 260px;
            padding: 16px 20px;
            border-radius: 10px;
            background: #FFFFFF;
            border: 1px solid #F3F4F6;
        }
        .total-titulo {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid;
        }
        .gas-titulo { color: #B45309; border-color: #FDE68A; }
        .die-titulo { color: #B91C1C; border-color: #FECACA; }
        .gas-litros { color: #B45309; }
        .die-litros { color: #B91C1C; }
        .total-destacado {
            margin-top: 8px;
            padding-top: 10px;
            border-top: 1px dashed #E5E7EB;
            font-weight: 700;
        }
        .total-general {
            width: 100%;
            font-size: 18px;
            padding: 16px 20px;
            background: #F0FDF4;
            border-radius: 10px;
            border: 1px solid #BBF7D0;
            margin-top: 8px;
        }
        .total-general .total-etiqueta { color: #166534; font-weight: 700; }
        .total-general .total-valor { color: #15803D; font-size: 20px; }
        .total-separador {
            width: 100%;
            height: 1px;
            background: #E5E7EB;
            margin: 4px 0;
        }
        .total-separador-vertical {
            width: 1px;
            background: #E5E7EB;
            min-height: 140px;
        }
        @media (max-width: 1400px) { .tabla { display: block; overflow-x: auto; } }
        @media (max-width: 900px) { .total-separador-vertical { display: none; } }
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
        <a href="reporte_vales.php" class="activo"><i class="fas fa-ticket-alt"></i> Reporte Vales</a>
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
            <div class="cabecera-izq">
                <div class="circulo-logo"><i class="fas fa-ticket-alt"></i></div>
                <div class="cabecera-texto">
                    <h1>🎫 Reporte General de Vales</h1>
                    <p>Consulta de todos los vales registrados por período y operador</p>
                </div>
            </div>
            <div class="botones-exportar">
                <button class="btn-excel" onclick="exportarExcel()"><i class="fas fa-file-excel"></i> Excel</button>
                <button class="btn-pdf" onclick="exportarPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
        </div>

        <!-- FILTRO DE FECHAS -->
        <form class="filtro" method="GET" action="reporte_vales.php">
            <div class="grupo">
                <label>📅 Desde</label>
                <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>">
            </div>
            <div class="grupo">
                <label>📅 Hasta</label>
                <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>">
            </div>
            <div class="grupo">
                <button type="submit" class="btn-buscar"><i class="fas fa-search"></i> Buscar</button>
            </div>
        </form>

        <div class="caja-tabla" id="areaReporte">
            <table class="tabla" id="tablaVales">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Operador</th>
                        <th>Bomba</th>
                        <th>Señor / Cliente</th>
                        <th>C.I.</th>
                        <th>Placa</th>
                        <th>Autorizado por</th>
                        <th>Gasolina L</th>
                        <th>Diésel L</th>
                        <th>Monto Bs</th>
                        <th>Recibí Conforme</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($vales) > 0): ?>
                        <?php while ($v = mysqli_fetch_assoc($vales)): 
                            $gas_litros = floatval($v['gasolina_litros'] ?? 0);
                            $die_litros = floatval($v['diesel_litros'] ?? 0);
                            $monto = floatval($v['monto_total'] ?? 0);
                            
                            $total_gas_litros += $gas_litros;
                            $total_die_litros += $die_litros;
                            $total_monto += $monto;
                            $cantidad_vales++;
                        ?>
                        <tr>
                            <td><strong><?= date('d/m/Y', strtotime($v['fecha'])) ?></strong></td>
                            <td>
                                <div class="celda-nombre">
                                    <span class="inicial"><?= mb_substr($v['nombre_operador'] ?? '?', 0, 1) ?></span>
                                    <?= htmlspecialchars($v['nombre_operador'] ?? 'Desconocido') ?>
                                </div>
                            </td>
                            <td><span class="badge bomba">Bomba N°<?= $v['bomba'] ?? '-' ?></span></td>
                            <td><?= htmlspecialchars($v['senor'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($v['ci'] ?? '<span class="sin-dato">—</span>') ?></td>
                            <td><?= htmlspecialchars($v['placa'] ?? '<span class="sin-dato">—</span>') ?></td>
                            <td><?= htmlspecialchars($v['autorizado_por'] ?? '<span class="sin-dato">—</span>') ?></td>
                            <td class="gas">
                                <?= $gas_litros > 0 ? number_format($gas_litros, 3, ',', '.') . ' L' : '<span class="sin-dato">—</span>' ?>
                            </td>
                            <td class="die">
                                <?= $die_litros > 0 ? number_format($die_litros, 3, ',', '.') . ' L' : '<span class="sin-dato">—</span>' ?>
                            </td>
                            <td class="monto">Bs <?= number_format($monto, 2, ',', '.') ?></td>
                            <td style="font-size: 12px; max-width: 120px;">
                                <?= htmlspecialchars($v['recibido_conforme'] ?? '<span class="sin-dato">—</span>') ?>
                            </td>
                            <td>
                                <a href="editar_vale.php?id=<?= $v['id'] ?>" class="btn-accion btn-editar" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="reporte_vales.php?eliminar=<?= $v['id'] ?>&fecha_inicio=<?= $fecha_inicio ?>&fecha_fin=<?= $fecha_fin ?>" 
                                   class="btn-accion btn-eliminar" 
                                   onclick="return confirm('¿Eliminar este vale? Esta acción no se puede deshacer.')"
                                   title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="12" class="vacio">📋 No hay vales registrados en el período seleccionado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- TOTALES DETALLADOS POR PRODUCTO -->
            <?php if ($cantidad_vales > 0): ?>
            <div class="totales">
                <div class="total-fila">
                    <span class="total-etiqueta">📋 Cantidad de Vales:</span>
                    <span class="total-valor"><?= $cantidad_vales ?></span>
                </div>
                
                <div class="total-separador"></div>
                
                <div class="total-bloque">
                    <div class="total-titulo gas-titulo">⛽ GASOLINA</div>
                    <div class="total-fila">
                        <span class="total-etiqueta">Litros registrados:</span>
                        <span class="total-valor gas-litros"><?= number_format($total_gas_litros, 3, ',', '.') ?> L</span>
                    </div>
                    <div class="total-fila">
                        <span class="total-etiqueta">Precio unitario:</span>
                        <span class="total-valor">Bs <?= number_format($PRECIO_GAS, 2, ',', '.') ?> / L</span>
                    </div>
                    <div class="total-fila total-destacado">
                        <span class="total-etiqueta">TOTAL GASOLINA:</span>
                        <span class="total-valor">Bs <?= number_format($total_gas_litros * $PRECIO_GAS, 2, ',', '.') ?></span>
                    </div>
                </div>
                
                <div class="total-separador-vertical"></div>
                
                <div class="total-bloque">
                    <div class="total-titulo die-titulo">🛢️ DIÉSEL</div>
                    <div class="total-fila">
                        <span class="total-etiqueta">Litros registrados:</span>
                        <span class="total-valor die-litros"><?= number_format($total_die_litros, 3, ',', '.') ?> L</span>
                    </div>
                    <div class="total-fila">
                        <span class="total-etiqueta">Precio unitario:</span>
                        <span class="total-valor">Bs <?= number_format($PRECIO_DIE, 2, ',', '.') ?> / L</span>
                    </div>
                    <div class="total-fila total-destacado">
                        <span class="total-etiqueta">TOTAL DIÉSEL:</span>
                        <span class="total-valor">Bs <?= number_format($total_die_litros * $PRECIO_DIE, 2, ',', '.') ?></span>
                    </div>
                </div>
                
                <div class="total-separador"></div>
                
                <div class="total-fila total-general">
                    <span class="total-etiqueta">💰 TOTAL GENERAL:</span>
                    <span class="total-valor">Bs <?= number_format($total_monto, 2, ',', '.') ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
// === EXPORTAR A EXCEL ===
function exportarExcel() {
    const tabla = document.getElementById('tablaVales');
    const filas = tabla.querySelectorAll('tr');
    const datos = [];
    
    filas.forEach(fila => {
        const celdas = fila.querySelectorAll('th, td');
        const filaDatos = [];
        for (let i = 0; i < celdas.length - 1; i++) { // Omitir columna de acciones
            filaDatos.push(celdas[i].innerText.replace(/<[^>]*>/g, '').trim());
        }
        datos.push(filaDatos);
    });
    
    const hoja = XLSX.utils.aoa_to_sheet(datos);
    const libro = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(libro, hoja, 'Vales');
    XLSX.writeFile(libro, 'Reporte_Vales_' + new Date().toLocaleDateString('es-BO').replace(/\//g, '-') + '.xlsx');
}

// === EXPORTAR A PDF (impresión del navegador) ===
function exportarPDF() {
    const contenido = document.getElementById('areaReporte').innerHTML;
    const ventana = window.open('', '_blank');
    ventana.document.write(`
        <html>
        <head>
            <title>Reporte de Vales - IMANOL</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h2 { color: #B45309; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 12px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background: #f5f5f5; font-weight: bold; }
                .totales { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px; }
                @media print { body { padding: 0; } }
            </style>
        </head>
        <body>
            <h2>🎫 Reporte de Vales - IMANOL</h2>
            <p>Período: <?= date('d/m/Y', strtotime($fecha_inicio)) ?> al <?= date('d/m/Y', strtotime($fecha_fin)) ?></p>
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