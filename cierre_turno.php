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

// === PRECIOS ACTUALIZADOS ===
$PRECIO_GASOLINA = 6.96;
$PRECIO_DIESEL   = 17.95;

$mensaje = "";
$tipo_mensaje = "";

// === FECHA ===
$fecha_hoy = date('Y-m-d');
$fecha_mostrar = date('d/m/Y');

// === RECIBIR VALORES ===
$bomba_seleccionada = intval($_POST['bomba'] ?? $_GET['bomba'] ?? 1);
$turno_seleccionado = $_POST['turno'] ?? $_GET['turno'] ?? (date('H') < 12 ? 'mañana' : 'tarde');
$tipo_combustible = $_POST['tipo_combustible'] ?? $_GET['tipo_combustible'] ?? 'gasolina';
$producto = ($tipo_combustible === 'gasolina') ? 'gasolina_especial' : 'diesel_oil';

// === BUSCAR REGISTRO EXISTENTE ===
$sql_estado = "SELECT * FROM cierres_turno 
               WHERE usuario_id = $usuario_id 
                 AND fecha = '$fecha_hoy' 
                 AND turno = '$turno_seleccionado' 
                 AND bomba = $bomba_seleccionada 
                 AND producto = '$producto'
               ORDER BY id DESC LIMIT 1";
$res_estado = mysqli_query($conn, $sql_estado);
$registro = ($res_estado && mysqli_num_rows($res_estado) > 0) ? mysqli_fetch_assoc($res_estado) : null;

$ya_guardo_inicial = false;
$lectura_inicial_guardada = 0;
$lectura_final_guardada = 0;
$litros_calibracion_guardada = 0;
$estado_turno = 'nuevo';

if ($registro) {
    $lectura_inicial_guardada = floatval($registro['lectura_inicial'] ?? 0);
    $lectura_final_guardada = floatval($registro['lectura_final'] ?? 0);
    $litros_calibracion_guardada = floatval($registro['litros_calibracion'] ?? 0);
    
    if ($lectura_inicial_guardada > 0 && $lectura_final_guardada == 0) {
        $ya_guardo_inicial = true;
        $estado_turno = 'inicial_guardada';
    } elseif ($lectura_final_guardada > 0) {
        $estado_turno = 'cerrado';
    }
}

// === OBTENER VALES SEPARADOS POR COMBUSTIBLE ===
$monto_vales_gasolina = 0;
$monto_vales_diesel = 0;
$gas_litros_bomba_actual = 0;
$die_litros_bomba_actual = 0;

$sql_vales = "SELECT 
                COALESCE(SUM(gasolina_litros * {$PRECIO_GASOLINA}), 0) AS monto_gasolina,
                COALESCE(SUM(diesel_litros * {$PRECIO_DIESEL}), 0) AS monto_diesel,
                COALESCE(SUM(gasolina_litros), 0) AS gas_litros,
                COALESCE(SUM(diesel_litros), 0) AS die_litros
              FROM vales 
              WHERE DATE(fecha) = '$fecha_hoy' 
                AND bomba = $bomba_seleccionada";
$res_vales = mysqli_query($conn, $sql_vales);
if ($res_vales && $v = mysqli_fetch_assoc($res_vales)) {
    $monto_vales_gasolina = floatval($v['monto_gasolina'] ?? 0);
    $monto_vales_diesel = floatval($v['monto_diesel'] ?? 0);
    $gas_litros_bomba_actual = floatval($v['gas_litros'] ?? 0);
    $die_litros_bomba_actual = floatval($v['die_litros'] ?? 0);
}

// === SOLO DESCONTAR VALES DEL PRODUCTO SELECCIONADO ===
$monto_vales_a_descontar = ($tipo_combustible === 'gasolina') ? $monto_vales_gasolina : $monto_vales_diesel;

// === PROCESAR FORMULARIO ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fecha = $_POST['fecha'] ?? $fecha_hoy;
    $turno = $_POST['turno'] ?? $turno_seleccionado;
    $bomba = intval($_POST['bomba'] ?? $bomba_seleccionada);
    $tipo_combustible = $_POST['tipo_combustible'] ?? $tipo_combustible;
    $producto = ($tipo_combustible === 'gasolina') ? 'gasolina_especial' : 'diesel_oil';
    
    // Recalcular vales
    $monto_vales_gasolina_form = 0;
    $monto_vales_diesel_form = 0;
    $sql_vales_form = "SELECT 
                        COALESCE(SUM(gasolina_litros * {$PRECIO_GASOLINA}), 0) AS monto_gasolina,
                        COALESCE(SUM(diesel_litros * {$PRECIO_DIESEL}), 0) AS monto_diesel
                       FROM vales 
                       WHERE DATE(fecha) = '$fecha_hoy' 
                         AND bomba = $bomba";
    $res_vales_form = mysqli_query($conn, $sql_vales_form);
    if ($res_vales_form && $vf = mysqli_fetch_assoc($res_vales_form)) {
        $monto_vales_gasolina_form = floatval($vf['monto_gasolina'] ?? 0);
        $monto_vales_diesel_form = floatval($vf['monto_diesel'] ?? 0);
    }
    $monto_vales_a_descontar_form = ($tipo_combustible === 'gasolina') ? $monto_vales_gasolina_form : $monto_vales_diesel_form;
    
    // Lecturas
    $litros_inicial = ($tipo_combustible === 'gasolina') 
        ? floatval($_POST['litros_inicial'] ?? 0) 
        : floatval($_POST['litros_inicial_d'] ?? 0);
    $litros_final = ($tipo_combustible === 'gasolina') 
        ? floatval($_POST['litros_final'] ?? 0) 
        : floatval($_POST['litros_final_d'] ?? 0);
    $litros_calibracion = floatval($_POST['litros_calibracion'] ?? 0);
    
    // === GUARDAR LECTURA INICIAL ===
    if (isset($_POST['guardar_inicial'])) {
        if ($litros_inicial > 0) {
            if ($registro) {
                $sql = "UPDATE cierres_turno SET lectura_inicial = $litros_inicial WHERE id = {$registro['id']}";
            } else {
                $sql = "INSERT INTO cierres_turno 
                        (fecha, turno, bomba, producto, lectura_inicial, usuario_id) 
                        VALUES ('$fecha', '$turno', $bomba, '$producto', $litros_inicial, $usuario_id)";
            }
            if (mysqli_query($conn, $sql)) {
                $mensaje = "✅ Lectura inicial GUARDADA.<br>Fecha: $fecha_mostrar — Turno: $turno — Bomba N°$bomba";
                $tipo_mensaje = "exito";
                $ya_guardo_inicial = true;
                $lectura_inicial_guardada = $litros_inicial;
                $estado_turno = 'inicial_guardada';
            } else {
                $mensaje = "❌ Error al guardar: " . mysqli_error($conn);
                $tipo_mensaje = "aviso";
            }
        } else {
            $mensaje = "⚠️ Escribe la lectura inicial antes de guardar";
            $tipo_mensaje = "aviso";
        }
    }
    
    // === CERRAR TURNO ===
    if (isset($_POST['cerrar_turno']) && $ya_guardo_inicial) {
        $litros_brutos = $litros_final - $lectura_inicial_guardada;
        
        // ⚠️ APLICAR CALIBRACIÓN: se descuenta pero NO es venta → vuelve al stock
        $litros_vendidos = $litros_brutos - $litros_calibracion;
        
        $precio_unit = ($producto === 'gasolina_especial') ? $PRECIO_GASOLINA : $PRECIO_DIESEL;
        $monto_total = $litros_vendidos * $precio_unit;
        $total_real = $monto_total - $monto_vales_a_descontar_form;
        
        if ($litros_brutos >= 0) {
            $sql = "UPDATE cierres_turno 
                    SET lectura_final = $litros_final, 
                        litros_calibracion = $litros_calibracion,
                        litros_vendidos = $litros_vendidos, 
                        litros_despachados_final = $litros_vendidos,
                        total_venta = $monto_total, 
                        vales_descontados = $monto_vales_a_descontar_form, 
                        total_real = $total_real
                    WHERE id = {$registro['id']}";
            mysqli_query($conn, $sql);
            
            // === GUARDAR EN MOVIMIENTOS ===
            // Tipo "salida" = litros vendidos reales
            $sql_mov = "INSERT INTO movimientos (
                fecha, tipo, producto, dispensador, lectura_inicial, lectura_final,
                litros_despachados, litros_calibracion, total_venta, monto_vales, 
                dinero_entregado, usuario_id, turno
            ) VALUES (
                '$fecha', 'salida', '$producto', $bomba, $lectura_inicial_guardada, $litros_final,
                $litros_vendidos, $litros_calibracion, $monto_total, $monto_vales_a_descontar_form, 
                $total_real, $usuario_id, '$turno'
            )";
            mysqli_query($conn, $sql_mov);
            
            // 🔄 DEVOLVER CALIBRACIÓN AL STOCK — movimiento de tipo "calibracion_devolucion"
            if ($litros_calibracion > 0) {
                $sql_devol = "INSERT INTO movimientos (
                    fecha, tipo, producto, dispensador, litros_recibidos, 
                    observacion, usuario_id, turno
                ) VALUES (
                    '$fecha', 'calibracion_devolucion', '$producto', $bomba, $litros_calibracion,
                    'Devolución por calibración de bomba N°$bomba', $usuario_id, '$turno'
                )";
                mysqli_query($conn, $sql_devol);
            }
            
            $nombre_producto = ($producto === 'gasolina_especial') ? 'Gasolina' : 'Diésel';
            $mensaje = "✅ TURNO CERRADO — Bomba N°$bomba<br>
                        ⛽ $nombre_producto bruto: " . number_format($litros_brutos, 3, ',', '.') . " L<br>
                        🔧 Calibración descontada: - " . number_format($litros_calibracion, 3, ',', '.') . " L<br>
                        ✅ Venta real: " . number_format($litros_vendidos, 3, ',', '.') . " L<br>
                        💵 Venta Bruta: Bs " . number_format($monto_total, 2, ',', '.') . "<br>
                        💵 Vales a descontar: - Bs " . number_format($monto_vales_a_descontar_form, 2, ',', '.') . "<br>
                        💰 TOTAL A RECIBIR: Bs " . number_format($total_real, 2, ',', '.');
            $tipo_mensaje = "exito";
            $estado_turno = 'cerrado';
        } else {
            $mensaje = "❌ Lectura final no puede ser menor a la inicial";
            $tipo_mensaje = "aviso";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Turno | IMANOL</title>
    <link rel="icon" href="imagenes/favicon2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #F8F9FA; display: flex; min-height: 100vh; }
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #B91C1C 0%, #EA580C 30%, #F59E0B 55%, #FB923C 80%, #DC2626 100%);
            height: 100vh; position: fixed; padding: 0; display: flex; flex-direction: column;
        }
        .logo-box { padding: 14px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .logo-box img { max-width: 55px; }
        .logo-box h2 { font-size: 20px; color: #fff; }
        .sidebar nav { padding: 10px; display: flex; flex-direction: column; gap: 4px; }
        .sidebar nav a { display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: rgba(255,255,255,0.9); text-decoration: none; border-radius: 8px; }
        .sidebar nav a.activo { background: rgba(255,255,255,0.3); font-weight: 600; }
        .sidebar .user-info { padding: 14px; border-top: 1px solid rgba(255,255,255,0.2); margin-top: auto; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; background: #fff; color: #B91C1C; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .cerrar-btn { background: rgba(0,0,0,0.2); color: #fff; padding: 8px; border-radius: 7px; text-align: center; text-decoration: none; display: block; margin-top: 10px; }
        .main { margin-left: 250px; flex: 1; padding: 30px; background: #fff; }
        .contenedor { max-width: 560px; margin: 0 auto; }
        .estado { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 10px; }
        .estado-nuevo { background: #EFF6FF; color: #1E40AF; border: 1px solid #BFDBFE; }
        .estado-ok { background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; }
        .fila-tres { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
        .fila-doble { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 20px; }
        .grupo { margin-bottom: 18px; }
        label { display: block; font-size: 13px; color: #374151; margin-bottom: 6px; font-weight: 500; }
        input, select { width: 100%; padding: 12px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; background: #F9FAFB; }
        input:focus { border-color: #F59E0B; outline: none; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }
        input.bloqueado { background: #ECFDF5; border-color: #34D399; font-weight: 600; color: #065F46; }
        input.deshabilitado { background: #E5E7EB; opacity: 0.6; }
        .selector-tipo { display: flex; gap: 12px; margin: 10px 0 20px; width: 100%; }
        .selector-tipo form { flex: 1; margin: 0; padding: 0; }
        .opcion { 
            padding: 18px; border-radius: 10px; border: 2px solid #E5E7EB; 
            text-align: center; cursor: pointer; width: 100%; font-size: 16px;
            background: transparent; border: none; cursor: pointer;
        }
        .opcion.activo-gas { border: 2px solid #F59E0B; background: #FFFBEB; }
        .opcion.activo-die { border: 2px solid #EF4444; background: #FEF2F2; }
        .caja-vales { background: #EFF6FF; border-radius: 10px; padding: 16px; margin: 24px 0; border: 1px solid #BFDBFE; }
        .caja-calculo { background: #F0FDF4; border-radius: 10px; padding: 16px; margin: 16px 0; border: 1px solid #BBF7D0; display: none; }
        .caja-calibracion { background: #FFFBEB; border-radius: 10px; padding: 16px; margin: 16px 0; border: 1px solid #FCD34D; display: none; }
        .fila-vale, .fila-calculo { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
        .fila-vale.total { border-top: 1px dashed #BFDBFE; padding-top: 10px; font-weight: 600; }
        .fila-calculo.resaltado { border-top: 2px solid #22C55E; padding-top: 10px; font-weight: 700; font-size: 16px; color: #166534; margin-top: 4px; }
        .fila-calculo.calibracion { color: #B45309; font-weight: 500; }
        .mensaje { padding: 14px; border-radius: 10px; margin-bottom: 20px; line-height: 1.6; }
        .mensaje.exito { background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; }
        .mensaje.aviso { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
        .btn-verde { width: 100%; padding: 14px; border: none; border-radius: 10px; background: linear-gradient(90deg, #22C55E, #16A34A); color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; }
        .btn-naranja { width: 100%; padding: 14px; border: none; border-radius: 10px; background: linear-gradient(90deg, #B91C1C, #F59E0B); color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-deshabilitado { opacity: 0.5; cursor: not-allowed; }
        .vale-no-aplicable { color: #9CA3AF; font-style: italic; }
        .info-calibracion { font-size: 12px; color: #92400E; margin-top: 6px; }
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="logo-box">
        <img src="imagenes/logo.png" alt="IMANOL">
        <div><h2 style="color:#fff;">MANOL</h2><p style="color:rgba(255,255,255,0.7);">Dios Con Nosotros</p></div>
    </div>
    <nav>
        <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
        <a href="cierre_turno.php" class="activo"><i class="fas fa-calculator"></i> Mi Turno</a>
        <a href="vales.php"><i class="fas fa-file-invoice-dollar"></i> Vales</a>
        <a href="historial.php"><i class="fas fa-history"></i> Historial</a>
        <a href="mi_perfil.php"><i class="fas fa-user"></i> Mi Perfil</a>
    </nav>
    <div class="user-info">
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="avatar"><?= $inicial ?></div>
            <div><h4 style="color:#fff;"><?= htmlspecialchars($nombre_usuario) ?></h4><p style="color:rgba(255,255,255,0.7);"><?= $rol_usuario ?></p></div>
        </div>
        <a href="logout.php" class="cerrar-btn"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </div>
</aside>
<main class="main">
    <div class="contenedor">
        <h2 style="margin-bottom:24px;">📋 Inicio y Cierre de Turno</h2>
        
        <?php if ($estado_turno === 'nuevo'): ?>
        <div class="estado estado-nuevo">
            <i class="fas fa-info-circle"></i>
            <div>Ingresa tu lectura inicial y guárdala. Cierra sesión y vuelve a entrar — aparecerá cargada automáticamente.</div>
        </div>
        <?php elseif ($estado_turno === 'inicial_guardada'): ?>
        <div class="estado estado-ok">
            <i class="fas fa-check-circle"></i>
            <div><strong>Lectura inicial cargada ✅</strong> — Ingresa la lectura final y la calibración para ver el cálculo.</div>
        </div>
        <?php endif; ?>
        
        <?php if ($mensaje): ?>
        <div class="mensaje <?= $tipo_mensaje ?>"><?= $mensaje ?></div>
        <?php endif; ?>
        
        <?php if ($estado_turno !== 'cerrado'): ?>
        
        <!-- === SELECCIÓN DE COMBUSTIBLE === -->
        <p style="font-weight:600;margin:10px 0;">1. ¿Qué combustible vendiste?</p>
        <div class="selector-tipo">
            <form method="post" action="cierre_turno.php">
                <button type="submit" name="tipo_combustible" value="gasolina" 
                        class="opcion <?= $tipo_combustible==='gasolina'?'activo-gas':'' ?>">
                    <i class="fas fa-gas-pump" style="font-size:22px;color:#F59E0B;"></i><br>Gasolina
                </button>
            </form>
            <form method="post" action="cierre_turno.php">
                <button type="submit" name="tipo_combustible" value="diesel" 
                        class="opcion <?= $tipo_combustible==='diesel'?'activo-die':'' ?>">
                    <i class="fas fa-oil-can" style="font-size:22px;color:#EF4444;"></i><br>Diésel
                </button>
            </form>
        </div>
        
        <p style="font-size:13px;color:#6B7280;margin-bottom:20px;">
            Precio: <?= $tipo_combustible==='gasolina' ? 'Gasolina = Bs 6,96 / Litro' : 'Diésel = Bs 17,95 / Litro' ?>
        </p>
        
        <form method="post" style="margin-top:20px;" id="form-turno">
            <input type="hidden" name="tipo_combustible" value="<?= $tipo_combustible ?>" id="hdn-tipo">
            <input type="hidden" id="precio-litro" value="<?= $tipo_combustible==='gasolina' ? $PRECIO_GASOLINA : $PRECIO_DIESEL ?>">
            <input type="hidden" id="monto-vales-aplicar" value="<?= $monto_vales_a_descontar ?>">
            
            <div class="fila-tres">
                <div class="grupo">
                    <label>📅 Fecha</label>
                    <input type="date" name="fecha" value="<?= $fecha_hoy ?>" readonly>
                </div>
                <div class="grupo">
                    <label>🕐 Turno</label>
                    <select name="turno" onchange="this.form.submit()">
                        <option value="mañana" <?= $turno_seleccionado==='mañana'?'selected':'' ?>>Mañana</option>
                        <option value="tarde" <?= $turno_seleccionado==='tarde'?'selected':'' ?>>Tarde</option>
                    </select>
                </div>
                <div class="grupo">
                    <label>⛽ Bomba N°</label>
                    <select name="bomba" onchange="this.form.submit()">
                        <?php for ($b = 1; $b <= 4; $b++): ?>
                        <option value="<?= $b ?>" <?= $bomba_seleccionada===$b?'selected':'' ?>>Bomba N°<?= $b ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <p style="font-weight:600;margin:20px 0 10px;">2. Lecturas del surtidor (en LITROS)</p>
            <div class="fila-doble">
                <div class="grupo">
                    <label>Lectura Inicial (L)</label>
                    <?php if ($tipo_combustible === 'gasolina'): ?>
                    <input type="number" step="0.001" name="litros_inicial" id="lectura-inicial"
                           value="<?= $ya_guardo_inicial ? number_format($lectura_inicial_guardada, 3, '.', '') : '' ?>"
                           <?= $ya_guardo_inicial ? 'readonly class="bloqueado"' : '' ?> placeholder="0.000">
                    <?php else: ?>
                    <input type="number" step="0.001" name="litros_inicial_d" id="lectura-inicial"
                           value="<?= $ya_guardo_inicial ? number_format($lectura_inicial_guardada, 3, '.', '') : '' ?>"
                           <?= $ya_guardo_inicial ? 'readonly class="bloqueado"' : '' ?> placeholder="0.000">
                    <?php endif; ?>
                    <?php if ($ya_guardo_inicial): ?>
                    <small style="color:#166534;margin-top:5px;display:block;">✅ Guardada el <?= $fecha_mostrar ?></small>
                    <?php endif; ?>
                </div>
                <div class="grupo">
                    <label>Lectura Final (L)</label>
                    <?php if ($tipo_combustible === 'gasolina'): ?>
                    <input type="number" step="0.001" name="litros_final" id="lectura-final" placeholder="0.000"
                           <?= !$ya_guardo_inicial ? 'disabled class="deshabilitado"' : '' ?>>
                    <?php else: ?>
                    <input type="number" step="0.001" name="litros_final_d" id="lectura-final" placeholder="0.000"
                           <?= !$ya_guardo_inicial ? 'disabled class="deshabilitado"' : '' ?>>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- === CAMPO DE CALIBRACIÓN === -->
            <div class="grupo" id="grupo-calibracion" style="display:<?= $ya_guardo_inicial ? 'block' : 'none' ?>;">
                <label>🔧 Litros usados en Calibración</label>
                <input type="number" step="0.001" name="litros_calibracion" id="litros-calibracion" 
                       value="<?= number_format($litros_calibracion_guardada, 3, '.', '') ?>" placeholder="0.000">
                <p class="info-calibracion">ℹ️ Esta cantidad se descuenta de la venta pero <strong>vuelve al stock</strong> como devolución.</p>
            </div>
            
            <!-- === CAJA DE CÁLCULOS EN VIVO === -->
            <div class="caja-calculo" id="caja-calculo">
                <h4 style="color:#166534;margin-bottom:10px;">📊 Cálculo Automático</h4>
                <div class="fila-calculo">
                    <span>Litros brutos despachados:</span>
                    <span id="calc-brutos">0,000 L</span>
                </div>
                <div class="fila-calculo calibracion">
                    <span>🔧 Menos Calibración:</span>
                    <span id="calc-calibracion">- 0,000 L</span>
                </div>
                <div class="fila-calculo resaltado" style="border-color:#22C55E; margin-top:8px;">
                    <span>Litros VENDIDOS reales:</span>
                    <span id="calc-litros">0,000 L</span>
                </div>
                <div class="fila-calculo">
                    <span>Precio por litro:</span>
                    <span id="calc-precio-litro">Bs 0,00</span>
                </div>
                <div class="fila-calculo">
                    <span>💵 Venta Bruta:</span>
                    <span id="calc-venta-bruta">Bs 0,00</span>
                </div>
                <div class="fila-calculo">
                    <span>💵 Vales a descontar:</span>
                    <span style="color:#B91C1C;">- Bs <?= number_format($monto_vales_a_descontar, 2, ',', '.') ?></span>
                </div>
                <div class="fila-calculo resaltado">
                    <span>💰 TOTAL A RECIBIR:</span>
                    <span id="calc-total">Bs 0,00</span>
                </div>
            </div>
            
            <div class="caja-vales">
                <h4 style="color:#1E40AF;margin-bottom:10px;">💰 Vales de Bomba N°<?= $bomba_seleccionada ?></h4>
                <div class="fila-vale total">
                    <span>Vales de Gasolina:</span>
                    <span class="<?= $tipo_combustible!=='gasolina' ? 'vale-no-aplicable' : '' ?>">
                        <?= $tipo_combustible==='gasolina' ? '-' : '' ?> Bs <?= number_format($monto_vales_gasolina, 2, ',', '.') ?>
                        <?= $tipo_combustible!=='gasolina' ? '(no aplica)' : '' ?>
                    </span>
                </div>
                <div class="fila-vale total">
                    <span>Vales de Diésel:</span>
                    <span class="<?= $tipo_combustible!=='diesel' ? 'vale-no-aplicable' : '' ?>">
                        <?= $tipo_combustible==='diesel' ? '-' : '' ?> Bs <?= number_format($monto_vales_diesel, 2, ',', '.') ?>
                        <?= $tipo_combustible!=='diesel' ? '(no aplica)' : '' ?>
                    </span>
                </div>
                <div class="fila-vale">
                    <span>⛽ Gasolina</span>
                    <span><?= number_format($gas_litros_bomba_actual, 3, ',', '.') ?> L</span>
                </div>
                <div class="fila-vale">
                    <span>🛢️ Diésel</span>
                    <span><?= number_format($die_litros_bomba_actual, 3, ',', '.') ?> L</span>
                </div>
            </div>
            
            <?php if (!$ya_guardo_inicial): ?>
            <button type="submit" name="guardar_inicial" class="btn-verde">
                <i class="fas fa-save"></i> GUARDAR LECTURA INICIAL
            </button>
            <?php else: ?>
            <button type="button" class="btn-verde btn-deshabilitado" disabled>
                ✅ LECTURA INICIAL YA GUARDADA
            </button>
            <button type="submit" name="cerrar_turno" class="btn-naranja" id="btn-cerrar">
                <i class="fas fa-lock"></i> CERRAR TURNO Y GUARDAR VENTA
            </button>
            <?php endif; ?>
        </form>
        
        <?php else: ?>
        <div style="text-align:center;padding:40px;">
            <i class="fas fa-check-circle" style="font-size:48px;color:#22C55E;"></i>
            <h3 style="color:#166534;margin:15px 0;">Turno Cerrado Exitosamente ✅</h3>
            <p style="color:#6B7280;">El registro de hoy quedó finalizado. Vuelve mañana para un nuevo turno.</p>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
const lecturaInicial = document.getElementById('lectura-inicial');
const lecturaFinal = document.getElementById('lectura-final');
const litrosCalibracion = document.getElementById('litros-calibracion');
const cajaCalculo = document.getElementById('caja-calculo');
const precioLitro = parseFloat(document.getElementById('precio-litro').value);
const montoValesAplicar = parseFloat(document.getElementById('monto-vales-aplicar').value);

function formatearNumero(num, decimales = 2) {
    return num.toLocaleString('es-BO', { minimumFractionDigits: decimales, maximumFractionDigits: decimales });
}

function actualizarCalculo() {
    const li = parseFloat(lecturaInicial.value) || 0;
    const lf = parseFloat(lecturaFinal.value) || 0;
    const cal = parseFloat(litrosCalibracion.value) || 0;
    
    if (li > 0 && lf >= li) {
        const litrosBrutos = lf - li;
        const litrosVendidos = litrosBrutos - cal;
        const ventaBruta = litrosVendidos * precioLitro;
        const totalReal = ventaBruta - montoValesAplicar;
        
        document.getElementById('calc-brutos').textContent = formatearNumero(litrosBrutos, 3) + ' L';
        document.getElementById('calc-calibracion').textContent = '- ' + formatearNumero(cal, 3) + ' L';
        document.getElementById('calc-litros').textContent = formatearNumero(litrosVendidos, 3) + ' L';
        document.getElementById('calc-precio-litro').textContent = 'Bs ' + formatearNumero(precioLitro);
        document.getElementById('calc-venta-bruta').textContent = 'Bs ' + formatearNumero(ventaBruta);
        document.getElementById('calc-total').textContent = 'Bs ' + formatearNumero(totalReal);
        
        cajaCalculo.style.display = 'block';
    } else {
        cajaCalculo.style.display = 'none';
    }
}

if (lecturaFinal) lecturaFinal.addEventListener('input', actualizarCalculo);
if (litrosCalibracion) litrosCalibracion.addEventListener('input', actualizarCalculo);
</script>
</body>
</html>