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
$mensaje = "";

// === PRECIOS ACTUALIZADOS ===
$PRECIO_GAS = 6.96;
$PRECIO_DIE = 17.95;

// === PROCESAR REGISTRO DE VALE ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $senor = trim($_POST['senor'] ?? '');
    $autorizado_por = trim($_POST['autorizado_por'] ?? '');
    $ci = trim($_POST['ci'] ?? '');
    $placa = trim($_POST['placa'] ?? '');
    $bomba = intval($_POST['bomba'] ?? 1);
    $gasolina_litros = floatval($_POST['gasolina_litros'] ?? 0);
    $diesel_litros = floatval($_POST['diesel_litros'] ?? 0);
    $otros = trim($_POST['otros'] ?? '');
    $monto_total = floatval($_POST['monto_total'] ?? 0);
    $recibido_conforme = trim($_POST['recibido_conforme'] ?? '');
    $entregue_conforme = trim($_POST['entregue_conforme'] ?? '');
    
    // Recalcular monto desde litros (garantiza consistencia)
    $monto_calculado = ($gasolina_litros * $PRECIO_GAS) + ($diesel_litros * $PRECIO_DIE);
    if ($monto_calculado > 0) {
        $monto_total = $monto_calculado;
    }

    if ($monto_total > 0 || $gasolina_litros > 0 || $diesel_litros > 0) {
        $sql = "INSERT INTO vales 
                (fecha, senor, autorizado_por, ci, placa, bomba, gasolina_litros, diesel_litros, otros, monto_total, recibido_conforme, entregue_conforme, usuario_id)
                VALUES 
                ('$fecha', '$senor', '$autorizado_por', '$ci', '$placa', $bomba, $gasolina_litros, $diesel_litros, '$otros', $monto_total, '$recibido_conforme', '$entregue_conforme', $usuario_id)";
        
        if (mysqli_query($conn, $sql)) {
            $mensaje = "✅ Vale registrado correctamente — Bomba N°$bomba";
        } else {
            $mensaje = "❌ Error al registrar: " . mysqli_error($conn);
        }
    } else {
        $mensaje = "⚠️ Debes ingresar litros de combustible";
    }
}

// === TRAER MIS VALES ===
$mis_vales = mysqli_query($conn, "SELECT * FROM vales WHERE usuario_id = $usuario_id ORDER BY fecha DESC, id DESC LIMIT 30");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Vales | IMANOL</title>
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
        .contenedor { max-width: 1200px; margin: 0 auto; }
        
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
        .fecha-actual { 
            font-size: 14px; color: #B45309; 
            background: #FFFBEB; padding: 8px 16px; border-radius: 20px;
            border: 1px solid #FDE68A; font-weight: 500;
        }
        
        .mensaje {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex; align-items: flex-start; gap: 12px;
        }
        .mensaje.exito {
            background: #F0FDF4;
            color: #166534;
            border: 1px solid #BBF7D0;
        }
        .mensaje.aviso {
            background: #FFFBEB;
            color: #B45309;
            border: 1px solid #FDE68A;
        }
        .mensaje i { margin-top: 2px; font-size: 16px; }
        
        .caja-form {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .caja-form h2 {
            font-size: 18px;
            color: #1F2937;
            margin-bottom: 24px;
            font-weight: 600;
            display: flex; align-items: center; gap: 10px;
        }
        .caja-form h2 i { color: #F59E0B; }
        
        .fila { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px; }
        .fila-doble { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px; }
        .grupo { }
        
        label {
            display: block;
            font-size: 14px;
            color: #374151;
            margin-bottom: 8px;
            font-weight: 500;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px 16px;
            background: #F9FAFB;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            color: #1F2937;
            font-size: 14px;
            outline: none;
            transition: all 0.25s ease;
        }
        input:focus, select:focus, textarea:focus { 
            border-color: #F59E0B; 
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
            background: #FFFBEB;
        }
        input.solo-lectura { 
            background: #FEF3C7; 
            border-color: #F59E0B;
            font-weight: 600;
            color: #92400E;
        }
        textarea { resize: vertical; min-height: 80px; }
        
        .precio-ayuda {
            font-size: 11px;
            color: #9CA3AF;
            margin-top: 4px;
        }
        
        .btn-registrar {
            padding: 14px 28px;
            background: linear-gradient(90deg, #B91C1C, #F59E0B);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.25s ease;
        }
        .btn-registrar:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 20px rgba(185, 28, 28, 0.25);
        }
        
        .tabla-caja {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .tabla-caja h3 {
            font-size: 16px;
            color: #1F2937;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex; align-items: center; gap: 10px;
        }
        .tabla-caja h3 i { color: #F59E0B; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 14px; 
        }
        th { 
            background: #F9FAFB; 
            padding: 14px 12px; 
            text-align: left; 
            color: #374151; 
            font-weight: 600; 
            font-size: 12px; 
            text-transform: uppercase; 
            border-bottom: 2px solid #E5E7EB;
        }
        td { 
            padding: 14px 12px; 
            border-bottom: 1px solid #F3F4F6; 
            color: #4B5563; 
        }
        tr:hover { background: #FFFBEB; }
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
        .bomba-etiqueta { 
            display: inline-block; 
            background: #EFF6FF; 
            color: #1D4ED8; 
            padding: 4px 10px; 
            border-radius: 8px; 
            font-size: 12px; 
            font-weight: 600; 
            border: 1px solid #BFDBFE;
        }
        td strong { color: #1F2937; }
        .pie {
            margin-top: 32px;
            text-align: center;
            font-size: 12px;
            color: #9CA3AF;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
        }
        @media (max-width: 1000px) {
            .fila, .fila-doble { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { width: 220px; }
            .main { margin-left: 220px; padding: 16px; }
            .fila, .fila-doble { grid-template-columns: 1fr; }
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
        <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
      <a href="cierre_turno.php"><i class="fas fa-calculator"></i> Mi Turno</a>
        <a href="vales.php" class="activo"><i class="fas fa-file-invoice-dollar"></i> Vales</a>
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
        <a href="index.php" class="btn-regresar"><i class="fas fa-arrow-left"></i> Volver al Dashboard</a>
        
        <div class="cabecera">
            <div class="cabecera-izq">
                <div class="circulo-logo"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="cabecera-texto">
                    <h1>🎫 Registro de Vales</h1>
                    <p>IMANOL — AV. Héroes del Chaco</p>
                </div>
            </div>
            <div class="fecha-actual"><?= date('d/m/Y') ?></div>
        </div>
        
        <?php if ($mensaje): 
            $tipo_mensaje = str_contains($mensaje, '✅') ? 'exito' : 'aviso';
            $icono = $tipo_mensaje === 'exito' ? 'fa-check-circle' : 'fa-info-circle';
        ?>
        <div class="mensaje <?= $tipo_mensaje ?>">
            <i class="fas <?= $icono ?>"></i>
            <div><?= $mensaje ?></div>
        </div>
        <?php endif; ?>
        
        <div class="caja-form">
            <h2><i class="fas fa-edit"></i> Nuevo Vale</h2>
            <form method="post" id="formVale">
                <div class="fila">
                    <div class="grupo">
                        <label>📅 Fecha</label>
                        <input type="date" name="fecha" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="grupo">
                        <label>👤 Señor(es)</label>
                        <input type="text" name="senor" placeholder="Nombre del cliente/empresa">
                    </div>
                    <div class="grupo">
                        <label>✅ Autorizado por</label>
                        <input type="text" name="autorizado_por" placeholder="Quién autoriza">
                    </div>
                </div>
                <div class="fila">
                    <div class="grupo">
                        <label>🪪 C.I. / Documento</label>
                        <input type="text" name="ci" placeholder="Documento de identidad">
                    </div>
                    <div class="grupo">
                        <label>🚗 Placa N°</label>
                        <input type="text" name="placa" placeholder="Placa del vehículo">
                    </div>
                    <div class="grupo">
                        <label>⛽ Bomba que descuenta</label>
                        <select name="bomba">
                            <option value="1">Bomba N° 1</option>
                            <option value="2">Bomba N° 2</option>
                            <option value="3">Bomba N° 3</option>
                            <option value="4">Bomba N° 4</option>
                        </select>
                    </div>
                </div>
                <div class="fila">
                    <div class="grupo">
                        <label>⛽ Gasolina (Litros)</label>
                        <input type="number" step="0.001" name="gasolina_litros" id="gasolina_litros" placeholder="0.000" oninput="calcularTotal()">
                        <div class="precio-ayuda">Precio: Bs 6,96 / litro</div>
                    </div>
                    <div class="grupo">
                        <label>🛢️ Diésel (Litros)</label>
                        <input type="number" step="0.001" name="diesel_litros" id="diesel_litros" placeholder="0.000" oninput="calcularTotal()">
                        <div class="precio-ayuda">Precio: Bs 17,95 / litro</div>
                    </div>
                    <div class="grupo">
                        <label>💰 Monto en Bs.</label>
                        <input type="number" step="0.01" name="monto_total" id="monto_total" placeholder="0.00" class="solo-lectura" readonly>
                        <div class="precio-ayuda">Calculado automáticamente</div>
                    </div>
                </div>
                <div class="fila-doble">
                    <div class="grupo">
                        <label>📋 Otros detalles</label>
                        <input type="text" name="otros" placeholder="Detalle adicional">
                    </div>
                </div>
                <div class="fila-doble">
                    <div class="grupo">
                        <label>✍️ Entregué Conforme (Operador)</label>
                        <input type="text" name="entregue_conforme" placeholder="Nombre y firma">
                    </div>
                    <div class="grupo">
                        <label>✍️ Recibí Conforme</label>
                        <input type="text" name="recibido_conforme" placeholder="Nombre y firma">
                    </div>
                </div>
                <button type="submit" class="btn-registrar"><i class="fas fa-save"></i> Registrar Vale</button>
            </form>
        </div>
        
        <div class="tabla-caja">
            <h3><i class="fas fa-list"></i> Mis Vales Registrados</h3>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Bomba</th>
                        <th>Señor / Cliente</th>
                        <th>Placa</th>
                        <th>Gasolina L</th>
                        <th>Diésel L</th>
                        <th>Monto Bs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $hay = false; while ($v = mysqli_fetch_assoc($mis_vales)) { $hay = true; ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($v['fecha'])) ?></td>
                        <td><span class="bomba-etiqueta">Bomba N°<?= $v['bomba'] ?? '-' ?></span></td>
                        <td><?= htmlspecialchars($v['senor'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($v['placa'] ?? '-') ?></td>
                        <td><?= number_format($v['gasolina_litros'] ?? 0, 3, ',', '.') ?></td>
                        <td><?= number_format($v['diesel_litros'] ?? 0, 3, ',', '.') ?></td>
                        <td><strong>Bs <?= number_format($v['monto_total'] ?? 0, 2, ',', '.') ?></strong></td>
                    </tr>
                    <?php } if (!$hay): ?>
                    <tr>
                        <td colspan="7" class="sin-datos">
                            <i class="fas fa-inbox"></i>
                            No has registrado ningún vale aún.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pie">© <?= date('Y') ?> IMANOL — Estación de Servicio · Panel de Operador</div>
    </div>
</main>
<script>
// === PRECIOS IGUALES A LOS DEL PHP ===
const PRECIO_GASOLINA = 6.96;
const PRECIO_DIESEL = 17.95;

function calcularTotal() {
    const gasLitros = parseFloat(document.getElementById('gasolina_litros').value) || 0;
    const dieLitros = parseFloat(document.getElementById('diesel_litros').value) || 0;
    
    const total = (gasLitros * PRECIO_GASOLINA) + (dieLitros * PRECIO_DIESEL);
    
    document.getElementById('monto_total').value = total.toFixed(2);
}
</script>
</body>
</html>