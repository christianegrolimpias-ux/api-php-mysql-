<?php
include_once 'conexion.php';
verificarSesion();

$es_admin = (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin', 'administrador'])) 
         || (isset($_SESSION['usuario_tipo']) && in_array($_SESSION['usuario_tipo'], ['admin', 'administrador']));
if (!$es_admin) { die("❌ Sin permiso"); }

$id = intval($_GET['id'] ?? 0);
$usuario = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM usuarios WHERE id = $id"));
if (!$usuario) { die("Usuario no encontrado"); }

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre']);
    $usuario_nombre = trim($_POST['usuario']);
    $correo = trim($_POST['correo']);
    $rol = $_POST['rol'];
    $contraseña = trim($_POST['contraseña'] ?? '');
    
    if (!empty($contraseña)) {
        $hash = password_hash($contraseña, PASSWORD_DEFAULT);
        $campo_pass = ", contrasenia = '$hash'";
    } else {
        $campo_pass = "";
    }
    
    mysqli_query($conn, "UPDATE usuarios SET 
        nombre = '$nombre', usuario = '$usuario_nombre', correo = '$correo', rol = '$rol' $campo_pass
        WHERE id = $id");
    
    header("Location: usuarios.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario — IMANOL</title>
    <link rel="icon" type="image/png" href="imagenes/favicon2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #F8F9FA; padding: 30px; max-width: 700px; margin: 0 auto; }
        h1 { color: #B91C1C; margin-bottom: 24px; }
        .caja { background: #fff; padding: 30px; border-radius: 16px; border: 1px solid #E5E7EB; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
        .fila { margin-bottom: 20px; }
        label { display: block; font-size: 14px; color: #374151; margin-bottom: 8px; font-weight: 500; }
        input, select { width: 100%; padding: 12px 16px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #F59E0B; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }
        .btn-guardar { background: linear-gradient(90deg, #B91C1C, #F59E0B); color: #fff; border: none; padding: 14px 28px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-volver { display: inline-flex; align-items: center; gap: 8px; color: #4B5563; text-decoration: none; margin-bottom: 20px; }
        .ayuda { font-size: 12px; color: #6B7280; margin-top: 6px; }
    </style>
</head>
<body>
    <a href="usuarios.php" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver a Usuarios</a>
    <div class="caja">
        <h1>✏️ Editar Cuenta</h1>
        <form method="post">
            <div class="fila">
                <label>Nombre Completo</label>
                <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
            </div>
            <div class="fila">
                <label>Nombre de Usuario</label>
                <input type="text" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>" required>
            </div>
            <div class="fila">
                <label>Correo Electrónico</label>
                <input type="email" name="correo" value="<?= htmlspecialchars($usuario['correo'] ?? '') ?>">
            </div>
            <div class="fila">
                <label>Rol en el Sistema</label>
                <select name="rol">
                    <option value="administrador" <?= ($usuario['rol'] ?? '') === 'administrador' ? 'selected' : '' ?>>🔑 Administrador</option>
                    <option value="operador" <?= ($usuario['rol'] ?? '') === 'operador' ? 'selected' : '' ?>>⛽ Operador</option>
                </select>
            </div>
            <div class="fila">
                <label>Nueva Contraseña <small>(dejar vacío para no cambiar)</small></label>
                <input type="password" name="contraseña" placeholder="••••••••">
                <p class="ayuda">Solo llena este campo si quieres cambiar la contraseña actual.</p>
            </div>
            <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar Cambios</button>
        </form>
    </div>
</body>
</html>