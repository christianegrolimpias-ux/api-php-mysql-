<?php
include_once 'conexion.php';
verificarSesion();

// === SOLO ADMINISTRADORES PUEDEN ENTRAR ===
$es_admin = (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'administrador') 
         || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'administrador')
         || (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin')
         || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin');
if (!$es_admin) {
    die("❌ No tienes permiso para acceder a esta página. Solo los administradores pueden crear cuentas.");
}

$nombre_admin = $_SESSION['usuario_nombre'] ?? 'Administrador';
$inicial = mb_substr($nombre_admin, 0, 1);
$rol_usuario = 'Administrador';
$mensaje = "";

// === CONTAR CUENTAS CREADAS ===
$total_admins = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM usuarios WHERE rol='administrador' OR tipo='administrador' OR rol='admin' OR tipo='admin'"))['total'] ?? 0;
$total_operadores = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM usuarios WHERE rol='operador' OR tipo='operador'"))['total'] ?? 0;

// === LÍMITES ===
$limite_admins = 3;
$limite_operadores = 8;

// === PROCESAR FORMULARIO ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contraseña = trim($_POST['contraseña'] ?? '');
    $tipo_cuenta = $_POST['tipo_cuenta'] ?? 'operador';

    // ✅ Validaciones
    if (empty($nombre)) {
        $mensaje = "⚠️ Escribe el nombre completo.";
    } elseif (empty($usuario)) {
        $mensaje = "⚠️ Escribe el nombre de usuario.";
    } elseif (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠️ Escribe un correo válido (ejemplo@correo.com).";
    } elseif (empty($contraseña) || strlen($contraseña) < 6) {
        $mensaje = "⚠️ La contraseña debe tener al menos 6 caracteres.";
    } else {
        // ✅ Verificar límites
        if ($tipo_cuenta === 'admin' && $total_admins >= $limite_admins) {
            $mensaje = "⚠️ Límite alcanzado. Solo se permiten $limite_admins cuentas de Administrador.";
        } elseif ($tipo_cuenta === 'operador' && $total_operadores >= $limite_operadores) {
            $mensaje = "⚠️ Límite alcanzado. Solo se permiten $limite_operadores cuentas de Operador.";
        } else {
            // ✅ Verificar si el usuario ya existe
            $ver = mysqli_query($conn, "SELECT id FROM usuarios WHERE usuario='" . mysqli_real_escape_string($conn, $usuario) . "' LIMIT 1");
            if (mysqli_num_rows($ver) > 0) {
                $mensaje = "⚠️ Ese nombre de usuario YA EXISTE. Elige otro.";
            } else {
                // ✅ CREAR CUENTA
                $pass_cifrada = password_hash($contraseña, PASSWORD_DEFAULT);
                if ($tipo_cuenta === 'admin') {
                    $rol = 'administrador';
                    $tipo = 'administrador';
                } else {
                    $rol = 'operador';
                    $tipo = 'operador';
                }
                $nombre_seguro = mysqli_real_escape_string($conn, $nombre);
                $usuario_seguro = mysqli_real_escape_string($conn, $usuario);
                $correo_seguro = mysqli_real_escape_string($conn, $correo);
                
                $sql = "INSERT INTO usuarios (nombre, usuario, correo, contraseña, rol, tipo) 
                        VALUES ('$nombre_seguro', '$usuario_seguro', '$correo_seguro', '$pass_cifrada', '$rol', '$tipo')";
                
                if (mysqli_query($conn, $sql)) {
                    $mensaje = "✅ CUENTA CREADA CORRECTAMENTE.<br>🔑 Usuario: <strong>$usuario</strong><br>🔒 Contraseña: <strong>$contraseña</strong>";
                    // Recargar contadores
                    $total_admins = mysqli_fetch_assoc(mysqli_query($conn, 
                        "SELECT COUNT(*) AS total FROM usuarios WHERE rol='administrador' OR tipo='administrador' OR rol='admin' OR tipo='admin'"))['total'] ?? 0;
                    $total_operadores = mysqli_fetch_assoc(mysqli_query($conn, 
                        "SELECT COUNT(*) AS total FROM usuarios WHERE rol='operador' OR tipo='operador'"))['total'] ?? 0;
                } else {
                    $mensaje = "❌ ERROR al crear: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuentas — IMANOL</title>
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

        /* === TARJETAS DE LÍMITES === */
        .limites {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }
        .caja {
            background: #FFFFFF;
            padding: 28px 24px;
            border-radius: 16px;
            border: 1px solid #E5E7EB;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .caja:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        .caja.admin {
            border-top: 4px solid #2563EB;
            background: linear-gradient(to bottom, #EFF6FF, #FFFFFF);
        }
        .caja.admin:hover { border-color: #3B82F6; background: #EFF6FF; }
        .caja.oper {
            border-top: 4px solid #22C55E;
            background: linear-gradient(to bottom, #F0FDF4, #FFFFFF);
        }
        .caja.oper:hover { border-color: #4ADE80; background: #F0FDF4; }
        .caja h3 {
            font-size: 14px;
            color: #6B7280;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .caja p {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .caja.admin p { color: #2563EB; }
        .caja.oper p { color: #16A34A; }
        .caja .meta { font-size: 12px; color: #9CA3AF; }

        /* === MENSAJES === */
        .mensaje {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.6;
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
        .mensaje.error {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
        .mensaje i { margin-top: 2px; font-size: 16px; }

        /* === FORMULARIO === */
        .form-card {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 16px;
            padding: 32px;
            max-width: 540px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .form-card h2 {
            font-size: 18px;
            color: #1F2937;
            margin-bottom: 24px;
            font-weight: 600;
            display: flex; align-items: center; gap: 10px;
        }
        .form-card h2 i { color: #F59E0B; }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 14px;
            color: #374151;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #D1D5DB;
            background: #F9FAFB;
            color: #1F2937;
            font-size: 14px;
            transition: all 0.25s ease;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #F59E0B;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
            background: #FFFBEB;
        }
        .form-group select option { background: #FFFFFF; color: #1F2937; }
        .form-group select:disabled { background: #F3F4F6; color: #9CA3AF; }

        .btn-guardar {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(90deg, #B91C1C, #F59E0B);
            color: #fff;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            width: 100%;
            transition: all 0.25s ease;
        }
        .btn-guardar:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(185, 28, 28, 0.25);
        }
        .btn-guardar:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            background: #9CA3AF;
        }

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
            .limites { grid-template-columns: 1fr; }
            .form-card { padding: 24px; }
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
          <a href="reporte_vales.php"><i class="fas fa-ticket-alt"></i> Reporte Vales</a>
        <a href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a>
        <a href="historial_calibracion.php"><i class="fas fa-sliders-h"></i> Historial Calibración</a>
        <a href="proveedores.php"><i class="fas fa-truck"></i> Proveedores</a>
        <a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a>
        <a href="crear_cuenta.php" class="activo"><i class="fas fa-user-plus"></i> Crear Cuentas</a>
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
            <div class="circulo-logo"><i class="fas fa-user-plus"></i></div>
            <div class="cabecera-texto">
                <h1>Crear Nueva Cuenta</h1>
                <p>Administración de cuentas de usuario del sistema</p>
            </div>
        </div>

        <!-- CONTADOR DE CUENTAS -->
        <div class="limites">
            <div class="caja admin">
                <h3>🔑 Administradores</h3>
                <p><?= $total_admins ?></p>
                <span class="meta">de <?= $limite_admins ?> permitidos</span>
            </div>
            <div class="caja oper">
                <h3>⛽ Operadores</h3>
                <p><?= $total_operadores ?></p>
                <span class="meta">de <?= $limite_operadores ?> permitidos</span>
            </div>
        </div>

        <!-- MENSAJE -->
        <?php if ($mensaje): 
            $tipo_mensaje = str_contains($mensaje, '✅') ? 'exito' : (str_contains($mensaje, '❌') ? 'error' : 'aviso');
            $icono = $tipo_mensaje === 'exito' ? 'fa-check-circle' : ($tipo_mensaje === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
        ?>
        <div class="mensaje <?= $tipo_mensaje ?>">
            <i class="fas <?= $icono ?>"></i>
            <div><?= $mensaje ?></div>
        </div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <div class="form-card">
            <h2><i class="fas fa-edit"></i> Datos de la Nueva Cuenta</h2>
            <form action="crear_cuenta.php" method="POST">
                <div class="form-group">
                    <label>👤 Nombre Completo</label>
                    <input type="text" name="nombre" placeholder="Ej: Juan Pérez" required>
                </div>
                <div class="form-group">
                    <label>🔖 Nombre de Usuario</label>
                    <input type="text" name="usuario" placeholder="Ej: juanperez" required>
                </div>
                <div class="form-group">
                    <label>📧 Correo Electrónico</label>
                    <input type="email" name="correo" placeholder="correo@ejemplo.com" required>
                </div>
                <div class="form-group">
                    <label>🔒 Contraseña (mínimo 6 caracteres)</label>
                    <input type="password" name="contraseña" placeholder="Escribe una contraseña segura" required>
                </div>
                <div class="form-group">
                    <label>📋 Tipo de Cuenta</label>
                    <select name="tipo_cuenta" required>
                        <option value="admin" <?= $total_admins >= $limite_admins ? 'disabled' : '' ?>>🔑 Administrador</option>
                        <option value="operador" <?= $total_operadores >= $limite_operadores ? 'disabled' : '' ?>>⛽ Operador</option>
                    </select>
                </div>
                <button type="submit" class="btn-guardar" <?= ($total_admins >= $limite_admins && $total_operadores >= $limite_operadores) ? 'disabled' : '' ?>>
                    ✅ Crear Cuenta
                </button>
            </form>
        </div>

        <div class="pie">© <?= date('Y') ?> IMANOL — Sistema de Control de Combustible · Todos los derechos reservados</div>
    </div>
</main>
</body>
</html>