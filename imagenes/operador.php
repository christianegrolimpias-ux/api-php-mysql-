<?php
include_once 'conexion.php';
session_start();

// ✅ SOLO EL ADMINISTRADOR PUEDE ACCEDER A ESTA PÁGINA
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header("Location: index.php");
    exit;
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $contraseña = trim($_POST['contraseña']);

    // ✅ Verificar que no esté vacío
    if (empty($nombre) || empty($usuario) || empty($contraseña)) {
        $mensaje = "⚠️ Todos los campos son obligatorios";
    } else {
        // ✅ Verificar que el usuario NO exista ya
        $verificar = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $verificar->bind_param("s", $usuario);
        $verificar->execute();
        $verificar->store_result();

        if ($verificar->num_rows > 0) {
            $mensaje = "⚠️ Este nombre de usuario ya existe";
            $verificar->close();
        } else {
            $verificar->close();

            // ✅ INSERTAR COMO OPERADOR — NO se puede cambiar el rol desde aquí
            $rol = "operador";
            $tipo = "operador";

            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, contraseña, rol, tipo) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nombre, $usuario, $contraseña, $rol, $tipo);

            if ($stmt->execute()) {
                $mensaje = "✅ Operador creado exitosamente";
            } else {
                $mensaje = "❌ Error al crear: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crear Operador — IMANOL</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:
    linear-gradient(rgba(0,0,0,.60),rgba(0,0,0,.60)),
    url('imagenes/fondo.jpeg');
    background-size:cover;
    background-position:center;
}

.caja{
    width:420px;
    padding:35px;
    border-radius:20px;
    background:rgba(255,255,255,.08);
    backdrop-filter:blur(18px);
    border:1px solid rgba(255,255,255,.15);
    box-shadow:0 0 25px rgba(0,59,142,.8), 0 0 50px rgba(227,6,19,.3);
    color:white;
}

.caja h2{
    text-align:center;
    margin-bottom:25px;
    color:#FBBF24;
}

.mensaje{
    padding:12px;
    margin-bottom:18px;
    border-radius:8px;
    text-align:center;
}

.ok{ background:rgba(34,197,94,.2); color:#86EFAC; }
.error{ background:rgba(220,38,38,.2); color:#FECACA; }

.grupo{
    margin-bottom:16px;
}

.grupo label{
    display:block;
    margin-bottom:6px;
    font-size:14px;
    color:#FDE68A;
}

.grupo input{
    width:100%;
    padding:12px 15px;
    border:none;
    border-radius:10px;
    background:rgba(255,255,255,.12);
    color:white;
    font-size:14px;
    outline:none;
    border:1px solid transparent;
}

.grupo input:focus{
    border-color:#FBBF24;
    box-shadow:0 0 8px rgba(251,191,36,.25);
}

.boton{
    width:100%;
    padding:13px;
    border:none;
    border-radius:10px;
    background:linear-gradient(90deg, #003B8E, #E30613);
    color:white;
    font-size:15px;
    font-weight:bold;
    cursor:pointer;
    margin-top:8px;
    transition:.3s;
}

.boton:hover{
    transform:translateY(-2px);
    box-shadow:0 0 15px rgba(0,59,142,.6), 0 0 30px rgba(227,6,19,.25);
}

.volver{
    display:block;
    text-align:center;
    margin-top:20px;
    color:#FDE68A;
    text-decoration:none;
    font-size:14px;
}

.volver:hover{
    text-decoration:underline;
}
</style>
</head>
<body>

<div class="caja">
    <h2><i class="fas fa-user-plus"></i> Crear Operador</h2>

    <?php if ($mensaje): ?>
    <div class="mensaje <?= strpos($mensaje, '✅') !== false ? 'ok' : 'error' ?>">
        <?= $mensaje ?>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="grupo">
            <label for="nombre">Nombre Completo</label>
            <input type="text" id="nombre" name="nombre" placeholder="Nombre del operador" required>
        </div>

        <div class="grupo">
            <label for="usuario">Nombre de Usuario</label>
            <input type="text" id="usuario" name="usuario" placeholder="Usuario para iniciar sesión" required>
        </div>

        <div class="grupo">
            <label for="contraseña">Contraseña</label>
            <input type="password" id="contraseña" name="contraseña" placeholder="Contraseña segura" required>
        </div>

        <button type="submit" class="boton">
            <i class="fas fa-check-circle"></i> Crear Operador
        </button>
    </form>

    <a href="usuarios.php" class="volver">
        <i class="fas fa-arrow-left"></i> Volver al Panel de Usuarios
    </a>
</div>

</body>
</html>