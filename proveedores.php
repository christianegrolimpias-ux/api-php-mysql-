<?php
include_once 'conexion.php';
verificarSesion();

// === VERIFICAR SOLO ADMINISTRADOR ===
$es_admin = (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'administrador') 
         || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'administrador')
         || (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin')
         || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin');
if (!$es_admin) {
    die("❌ No tienes permiso para acceder a esta página.");
}

$nombre_admin = $_SESSION['usuario_nombre'] ?? 'Administrador';
$inicial = mb_substr($nombre_admin, 0, 1);
$rol_usuario = $es_admin ? 'Administrador' : 'Operador';
$mensaje = "";

// === GUARDAR PROVEEDOR ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre   = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono'] ?? '');
    $empresa  = trim($_POST['empresa'] ?? '');
    $producto = trim($_POST['producto']);
    
    if (!empty($nombre) && !empty($producto)) {
        $nombre_esc = mysqli_real_escape_string($conn, $nombre);
        $tel_esc = mysqli_real_escape_string($conn, $telefono);
        $emp_esc = mysqli_real_escape_string($conn, $empresa);
        $prod_esc = mysqli_real_escape_string($conn, $producto);
        
        $sql = "INSERT INTO proveedores (nombre, telefono, empresa, producto) 
                VALUES ('$nombre_esc', '$tel_esc', '$emp_esc', '$prod_esc')";
        if (mysqli_query($conn, $sql)) {
            $mensaje = "✅ Proveedor registrado correctamente.";
        } else {
            $mensaje = "❌ Error al registrar: " . mysqli_error($conn);
        }
    } else {
        $mensaje = "❌ Nombre y Producto son obligatorios.";
    }
}

// === LISTAR PROVEEDORES ===
$proveedores = mysqli_query($conn, "SELECT * FROM proveedores ORDER BY nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proveedores — IMANOL</title>
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

        /* === MENSAJES === */
        .mensaje {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex; align-items: center; gap: 10px;
        }
        .mensaje.ok {
            background: #F0FDF4;
            color: #166534;
            border: 1px solid #BBF7D0;
        }
        .mensaje.err {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        /* === DOS COLUMNAS === */
        .dos-columnas {
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 24px;
        }
        .caja {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .caja h2 {
            font-size: 16px;
            margin-bottom: 20px;
            color: #1F2937;
            font-weight: 600;
            display: flex; align-items: center; gap: 8px;
        }

        /* === FORMULARIO === */
        .form-group { margin-bottom: 18px; }
        label {
            display: block;
            font-size: 13px;
            color: #374151;
            margin-bottom: 8px;
            font-weight: 500;
        }
        input, select {
            width: 100%;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid #D1D5DB;
            background: #F9FAFB;
            color: #1F2937;
            font-size: 14px;
            transition: all 0.25s ease;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #F59E0B;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
            background: #FFFBEB;
        }
        button {
            width: 100%;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            background: linear-gradient(90deg, #B91C1C, #F59E0B);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.25s ease;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(185, 28, 28, 0.25);
        }

        /* === TABLA === */
        .tabla {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .tabla th {
            background: #F9FAFB;
            padding: 14px 12px;
            text-align: left;
            color: #374151;
            font-weight: 600;
            border-bottom: 2px solid #E5E7EB;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .tabla td {
            padding: 14px 12px;
            border-bottom: 1px solid #F3F4F6;
            color: #4B5563;
        }
        .tabla tr:hover {
            background: #FFFBEB;
        }
        .tabla tr:last-child td { border-bottom: none; }

        /* === ETIQUETAS DE PRODUCTO === */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge.gas {
            background: #FFFBEB;
            color: #B45309;
            border: 1px solid #FDE68A;
        }
        .badge.die {
            background: #FEF2F2;
            color: #B91C1C;
            border: 1px solid #FECACA;
        }
        .badge.ambos {
            background: #F0FDF4;
            color: #166534;
            border: 1px solid #BBF7D0;
        }

        @media (max-width: 1000px) {
            .dos-columnas { grid-template-columns: 1fr; }
        }
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
          <a href="reporte_vales.php"><i class="fas fa-ticket-alt"></i> Reporte Vales</a>
        <a href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a>
        <a href="historial_calibracion.php"><i class="fas fa-sliders-h"></i> Historial Calibración</a>
        <a href="proveedores.php" class="activo"><i class="fas fa-truck"></i> Proveedores</a>
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
            <div class="circulo-logo"><i class="fas fa-truck"></i></div>
            <div class="cabecera-texto">
                <h1>Gestión de Proveedores</h1>
                <p>Registro y control de proveedores de combustible</p>
            </div>
        </div>

        <?php if ($mensaje): ?>
        <div class="mensaje <?= strpos($mensaje, '✅') !== false ? 'ok' : 'err' ?>">
            <i class="fas <?= strpos($mensaje, '✅') !== false ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <?= $mensaje ?>
        </div>
        <?php endif; ?>

        <div class="dos-columnas">
            <!-- FORMULARIO REGISTRAR -->
            <div class="caja">
                <h2><i class="fas fa-plus-circle" style="color:#F59E0B;"></i> Registrar Proveedor</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Nombre / Persona de Contacto *</label>
                        <input type="text" name="nombre" placeholder="Ej: Juan Pérez" required>
                    </div>
                    <div class="form-group">
                        <label>Empresa / Razón Social</label>
                        <input type="text" name="empresa" placeholder="Ej: Distribuidora XYZ">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" placeholder="Ej: 77777777">
                    </div>
                    <div class="form-group">
                        <label>Producto que suministra *</label>
                        <select name="producto" required>
                            <option value="">Seleccionar...</option>
                            <option value="gasolina_especial">⛽ Gasolina Especial</option>
                            <option value="diesel_oil">🛢️ Diésel Oil</option>
                            <option value="ambos">⛽🛢️ Ambos</option>
                        </select>
                    </div>
                    <button type="submit"><i class="fas fa-save"></i> Guardar Proveedor</button>
                </form>
            </div>

            <!-- LISTA PROVEEDORES -->
            <div class="caja">
                <h2><i class="fas fa-list" style="color:#B91C1C;"></i> Lista de Proveedores</h2>
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Empresa</th>
                            <th>Producto</th>
                            <th>Teléfono</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($proveedores) > 0): ?>
                            <?php while ($p = mysqli_fetch_assoc($proveedores)): 
                                $prod = $p['producto'];
                                if ($prod === 'gasolina_especial') {
                                    $clase = 'gas';
                                    $texto = '⛽ Gasolina';
                                } elseif ($prod === 'diesel_oil') {
                                    $clase = 'die';
                                    $texto = '🛢️ Diésel';
                                } else {
                                    $clase = 'ambos';
                                    $texto = '⛽🛢️ Ambos';
                                }
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                                <td><?= htmlspecialchars($p['empresa'] ?: '—') ?></td>
                                <td><span class="badge <?= $clase ?>"><?= $texto ?></span></td>
                                <td><?= htmlspecialchars($p['telefono'] ?: '—') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center; color:#6B7280; padding:40px;">
                                    <i class="fas fa-inbox" style="font-size:24px; margin-bottom:10px; display:block; color:#D1D5DB;"></i>
                                    No hay proveedores registrados.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>