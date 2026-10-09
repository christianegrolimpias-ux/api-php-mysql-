<?php
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

// === ACTUALIZAR CONTRASEÑA ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $actual = trim($_POST['contraseña_actual'] ?? '');
    $nueva = trim($_POST['contraseña_nueva'] ?? '');
    $confirmar = trim($_POST['contraseña_confirmar'] ?? '');

    if (empty($actual) || empty($nueva) || empty($confirmar)) {
        $mensaje = "⚠️ Todos los campos son obligatorios.";
        $tipo_mensaje = "aviso";
    } elseif ($nueva !== $confirmar) {
        $mensaje = "⚠️ La nueva contraseña no coincide con la confirmación.";
        $tipo_mensaje = "aviso";
    } elseif (strlen($nueva) < 6) {
        $mensaje = "⚠️ La nueva contraseña debe tener al menos 6 caracteres.";
        $tipo_mensaje = "aviso";
    } else {
        $res = mysqli_query($conn, "SELECT contraseña FROM usuarios WHERE id = $usuario_id LIMIT 1");
        $fila = mysqli_fetch_assoc($res);
        
        if (password_verify($actual, $fila['contraseña'])) {
            $nueva_cifrada = password_hash($nueva, PASSWORD_DEFAULT);
            $act = mysqli_query($conn, "UPDATE usuarios SET contraseña='$nueva_cifrada' WHERE id = $usuario_id");
            
            if ($act) {
                $mensaje = "✅ Contraseña actualizada correctamente.";
                $tipo_mensaje = "exito";
            } else {
                $mensaje = "❌ Error al guardar: " . mysqli_error($conn);
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "❌ La contraseña actual es incorrecta.";
            $tipo_mensaje = "error";
        }
    }
}

// Obtener datos del usuario
$usuario = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM usuarios WHERE id = $usuario_id LIMIT 1"));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil — IMANOL</title>
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
        .contenedor-pagina { max-width: 900px; margin: 0 auto; }
        
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

        /* === MENSAJES === */
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
        .mensaje.error {
            background: #FEF2F2;
            color: #B91C1C;
            border: 1px solid #FECACA;
        }
        .mensaje i { margin-top: 2px; font-size: 18px; }

        /* === TARJETAS === */
        .contenedor {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 24px;
        }
        .tarjeta {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .tarjeta h2 {
            font-size: 16px;
            color: #1F2937;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 1px solid #E5E7EB;
            display: flex; align-items: center; gap: 10px;
            font-weight: 600;
        }
        .tarjeta h2 i { color: #F59E0B; }

        /* === DATOS DEL USUARIO === */
        .dato { margin-bottom: 20px; }
        .dato label {
            display: block;
            font-size: 13px;
            color: #6B7280;
            margin-bottom: 6px;
        }
        .dato span {
            font-size: 15px;
            color: #1F2937;
            font-weight: 500;
        }
        .rol {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .rol.admin {
            background: #EFF6FF;
            color: #1D4ED8;
            border: 1px solid #BFDBFE;
        }
        .rol.operador {
            background: #F0FDF4;
            color: #16A34A;
            border: 1px solid #BBF7D0;
        }

        /* === FORMULARIO CONTRASEÑA === */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 14px;
            color: #374151;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #D1D5DB;
            background: #F9FAFB;
            color: #1F2937;
            font-size: 14px;
            outline: none;
            transition: all 0.25s ease;
        }
        .form-group input:focus {
            border-color: #F59E0B;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
            background: #FFFBEB;
        }
        .btn-guardar {
            width: 100%;
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(90deg, #B91C1C, #F59E0B);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .btn-guardar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(185, 28, 28, 0.25);
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
            .contenedor { grid-template-columns: 1fr; }
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
        <?php else: ?>
            <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
            <a href="cierre_turno.php"><i class="fas fa-calculator"></i> Mi Turno</a>
            <a href="vales.php"><i class="fas fa-file-invoice-dollar"></i> Vales</a>
            <a href="historial.php"><i class="fas fa-history"></i> Historial</a>
        <?php endif; ?>
        <a href="mi_perfil.php" class="activo"><i class="fas fa-user"></i> Mi Perfil</a>
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
    <div class="contenedor-pagina">
        <a href="index.php" class="btn-regresar"><i class="fas fa-arrow-left"></i> Volver al Dashboard</a>
        
        <div class="cabecera">
            <div class="circulo-logo"><i class="fas fa-user"></i></div>
            <div class="cabecera-texto">
                <h1>👤 Mi Perfil</h1>
                <p>Consulta tus datos y actualiza tu contraseña</p>
            </div>
        </div>

        <?php if ($mensaje): ?>
        <div class="mensaje <?= $tipo_mensaje ?>">
            <i class="fas <?= $tipo_mensaje === 'exito' ? 'fa-check-circle' : ($tipo_mensaje === 'error' ? 'fa-times-circle' : 'fa-exclamation-circle') ?>"></i>
            <div><?= $mensaje ?></div>
        </div>
        <?php endif; ?>

        <div class="contenedor">
            <!-- DATOS DEL USUARIO -->
            <div class="tarjeta">
                <h2><i class="fas fa-id-card"></i> Mis Datos</h2>
                <div class="dato">
                    <label>Nombre Completo</label>
                    <span><?= htmlspecialchars($usuario['nombre'] ?? '—') ?></span>
                </div>
                <div class="dato">
                    <label>Usuario</label>
                    <span><?= htmlspecialchars($usuario['usuario'] ?? '—') ?></span>
                </div>
                <div class="dato">
                    <label>Rol</label>
                    <span class="rol <?= ($usuario['rol'] ?? 'operador') === 'admin' ? 'admin' : 'operador' ?>">
                        <?= ($usuario['rol'] ?? 'operador') === 'admin' ? '🔑 Administrador' : '⛽ Operador' ?>
                    </span>
                </div>
                <div class="dato">
                    <label>Fecha de Creación</label>
                    <span><?= !empty($usuario['creado']) ? date('d/m/Y H:i', strtotime($usuario['creado'])) : '—' ?></span>
                </div>
            </div>

            <!-- CAMBIAR CONTRASEÑA -->
            <div class="tarjeta">
                <h2><i class="fas fa-lock"></i> Cambiar Contraseña</h2>
                <form action="mi_perfil.php" method="POST">
                    <div class="form-group">
                        <label>Contraseña Actual</label>
                        <input type="password" name="contraseña_actual" placeholder="Escribe tu contraseña actual" required>
                    </div>
                    <div class="form-group">
                        <label>Nueva Contraseña</label>
                        <input type="password" name="contraseña_nueva" placeholder="Mínimo 6 caracteres" required>
                    </div>
                    <div class="form-group">
                        <label>Confirmar Nueva Contraseña</label>
                        <input type="password" name="contraseña_confirmar" placeholder="Vuelve a escribir la nueva" required>
                    </div>
                    <button type="submit" class="btn-guardar">
                        <i class="fas fa-sync-alt"></i> Actualizar Contraseña
                    </button>
                </form>
            </div>
        </div>

        <div class="pie">© <?= date('Y') ?> IMANOL — Estación de Servicio · Panel de Operador</div>
    </div>
</main>
</body>
</html>