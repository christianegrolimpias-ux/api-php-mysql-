<?php
include_once 'conexion.php';
verificarSesion();

// === SOLO ADMINISTRADORES PUEDEN ENTRAR ===
$es_admin = (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin', 'administrador'])) 
         || (isset($_SESSION['usuario_tipo']) && in_array($_SESSION['usuario_tipo'], ['admin', 'administrador']));
if (!$es_admin) {
    die("❌ No tienes permiso para acceder a esta página.");
}

$nombre_admin = $_SESSION['usuario_nombre'] ?? 'Administrador';
$inicial = mb_substr($nombre_admin, 0, 1);
$rol_usuario = 'Administrador';

// === ELIMINAR USUARIO ===
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    // Evitar que se elimine a sí mismo
    if ($id !== intval($_SESSION['usuario_id'] ?? 0)) {
        mysqli_query($conn, "DELETE FROM usuarios WHERE id = $id");
    }
    header("Location: usuarios.php");
    exit;
}

// === OBTENER TODOS LOS USUARIOS ===
$usuarios = mysqli_query($conn, "SELECT * FROM usuarios ORDER BY 
    CASE WHEN (rol='administrador' OR tipo='administrador' OR rol='admin' OR tipo='admin') THEN 1 ELSE 2 END, 
    nombre ASC");

// === CONTADORES ===
$total_admin = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM usuarios WHERE rol IN ('admin','administrador') OR tipo IN ('admin','administrador')"))['total'] ?? 0;
$total_oper = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM usuarios WHERE rol NOT IN ('admin','administrador') AND tipo NOT IN ('admin','administrador')"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Usuarios — IMANOL</title>
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
        .contenedor { max-width: 1200px; margin: 0 auto; }
        
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
            margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;
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
        
        .tarjetas {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 32px;
        }
        .tarjeta {
            background: #FFFFFF; padding: 28px 24px; border-radius: 16px;
            border: 1px solid #E5E7EB; text-align: center;
            transition: all 0.3s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .tarjeta:hover {
            transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        .tarjeta.admin {
            border-top: 4px solid #2563EB; background: linear-gradient(to bottom, #EFF6FF, #FFFFFF);
        }
        .tarjeta.admin:hover { border-color: #3B82F6; background: #EFF6FF; }
        .tarjeta.oper {
            border-top: 4px solid #22C55E; background: linear-gradient(to bottom, #F0FDF4, #FFFFFF);
        }
        .tarjeta.oper:hover { border-color: #4ADE80; background: #F0FDF4; }
        .tarjeta h3 {
            font-size: 14px; color: #6B7280; margin-bottom: 12px; font-weight: 600;
        }
        .tarjeta p { font-size: 36px; font-weight: 800; margin-bottom: 4px; }
        .tarjeta.admin p { color: #2563EB; }
        .tarjeta.oper p { color: #16A34A; }
        .tarjeta .meta { font-size: 12px; color: #9CA3AF; }
        
        .caja-tabla {
            background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 16px;
            padding: 28px; box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .tabla {
            width: 100%; border-collapse: collapse; font-size: 14px;
        }
        .tabla th {
            background: #F9FAFB; padding: 14px 12px; text-align: left;
            color: #374151; font-weight: 600;
            border-bottom: 2px solid #E5E7EB; font-size: 12px; text-transform: uppercase; letter-spacing: 0.3px;
        }
        .tabla td {
            padding: 16px 12px; border-bottom: 1px solid #F3F4F6; color: #4B5563;
        }
        .tabla tr:hover { background: #FFFBEB; }
        .tabla tr:last-child td { border-bottom: none; }
        
        .badge {
            display: inline-block; padding: 6px 14px; border-radius: 8px;
            font-size: 12px; font-weight: 600;
        }
        .badge.admin {
            background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE;
        }
        .badge.operador {
            background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0;
        }
        
        .inicial {
            display: inline-flex; align-items: center; justify-content: center;
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #B91C1C, #F59E0B);
            color: #fff; font-weight: bold; margin-right: 12px; font-size: 15px;
            box-shadow: 0 2px 6px rgba(185, 28, 28, 0.2);
        }
        .celda-nombre { display: flex; align-items: center; }
        .celda-nombre > div > div:first-child { font-weight: 600; color: #1F2937; }
        .celda-nombre > div > div:last-child { font-size: 11px; color: #6B7280; margin-top: 2px; }
        
        .btn-accion {
            padding: 6px 10px; border-radius: 6px; font-size: 13px; text-decoration: none;
            display: inline-flex; align-items: center; gap: 5px; margin: 2px;
            transition: all 0.2s; border: none; cursor: pointer;
        }
        .btn-editar { background: #FEF3C7; color: #92400E; }
        .btn-editar:hover { background: #FDE68A; }
        .btn-eliminar { background: #FEE2E2; color: #991B1B; }
        .btn-eliminar:hover { background: #FECACA; }
        .btn-eliminar:disabled { opacity: 0.4; cursor: not-allowed; }
        
        .vacio {
            text-align: center; padding: 40px; color: #6B7280; font-style: italic;
        }
        .vacio i { font-size: 28px; color: #D1D5DB; margin-bottom: 10px; display: block; }
        
        @media (max-width: 768px) {
            .sidebar { width: 220px; }
            .main { margin-left: 220px; padding: 16px; }
            .tarjetas { grid-template-columns: 1fr; }
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
        <a href="registro.php"><i class="fas fa-th-list"></i> Registros</a>
        <a href="reportes.php"><i class="fas fa-chart-bar"></i> Reportes</a>
          <a href="reporte_vales.php"><i class="fas fa-ticket-alt"></i> Reporte Vales</a>
        <a href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a>
        <a href="historial_calibracion.php"><i class="fas fa-sliders-h"></i> Historial Calibración</a>
        <a href="proveedores.php"><i class="fas fa-truck"></i> Proveedores</a>
        <a href="usuarios.php" class="activo"><i class="fas fa-users"></i> Usuarios</a>
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
            <div class="cabecera-izq">
                <div class="circulo-logo"><i class="fas fa-users"></i></div>
                <div class="cabecera-texto">
                    <h1>Lista de Cuentas del Sistema</h1>
                    <p>Administración de usuarios y permisos</p>
                </div>
            </div>
        </div>

        <div class="tarjetas">
            <div class="tarjeta admin">
                <h3>🔑 Administradores</h3>
                <p><?= $total_admin ?></p>
                <span class="meta">del sistema</span>
            </div>
            <div class="tarjeta oper">
                <h3>⛽ Operadores</h3>
                <p><?= $total_oper ?></p>
                <span class="meta">de despacho</span>
            </div>
        </div>

        <div class="caja-tabla">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Usuario / Correo</th>
                        <th>Nombre Completo</th>
                        <th>Rol</th>
                        <th>Fecha de Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($usuarios) > 0): ?>
                        <?php while ($u = mysqli_fetch_assoc($usuarios)): 
                            $es_tipo_admin = in_array(($u['rol'] ?? ''), ['admin','administrador']) 
                                          || in_array(($u['tipo'] ?? ''), ['admin','administrador']);
                            $clase_badge = $es_tipo_admin ? 'admin' : 'operador';
                            $texto_tipo = $es_tipo_admin ? '🔑 Administrador' : '⛽ Operador';
                            $fecha = !empty($u['creado']) && $u['creado'] !== '0000-00-00 00:00:00' 
                                ? date('d/m/Y H:i', strtotime($u['creado'])) 
                                : '—';
                            $correo = !empty($u['correo']) ? htmlspecialchars($u['correo']) : 'Sin correo';
                            $es_mismo = ($u['id'] === intval($_SESSION['usuario_id'] ?? 0));
                        ?>
                        <tr>
                            <td>
                                <div class="celda-nombre">
                                    <span class="inicial"><?= mb_substr($u['nombre'], 0, 1) ?></span>
                                    <div>
                                        <div><?= htmlspecialchars($u['usuario']) ?></div>
                                        <div><?= $correo ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><strong><?= htmlspecialchars($u['nombre']) ?></strong></td>
                            <td><span class="badge <?= $clase_badge ?>"><?= $texto_tipo ?></span></td>
                            <td><?= $fecha ?></td>
                            <td>
                                <a href="editar_usuario.php?id=<?= $u['id'] ?>" class="btn-accion btn-editar" title="Editar usuario">
                                    <i class="fas fa-pen"></i> Editar
                                </a>
                                <?php if (!$es_mismo): ?>
                                    <a href="usuarios.php?eliminar=<?= $u['id'] ?>" 
                                       class="btn-accion btn-eliminar" 
                                       onclick="return confirm('¿Eliminar a <?= htmlspecialchars($u['nombre']) ?>? Esta acción no se puede deshacer.')"
                                       title="Eliminar usuario">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </a>
                                <?php else: ?>
                                    <button class="btn-accion btn-eliminar" disabled title="No puedes eliminarte a ti mismo">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="vacio">
                                <i class="fas fa-users-slash"></i>
                                No hay cuentas registradas en el sistema.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>