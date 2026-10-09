<?php
include_once 'conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['contraseña']);
    // DETECCIÓN DE ATAQUES SQLi
    $patrones_sql = [
        "/(\%27)|(\')|(\-\-)|(\%23)|(#)/i",
        "/\b(OR|AND)\b.+(=)/i",
        "/UNION\s+SELECT/i",
        "/SELECT.+FROM/i",
        "/INSERT\s+INTO/i",
        "/DELETE\s+FROM/i",
        "/DROP\s+TABLE/i"
    ];
    $ataqueDetectado = false;
    foreach ($patrones_sql as $patron) {
        if (preg_match($patron, $usuario) || preg_match($patron, $password)) {
            $ataqueDetectado = true;
            $fecha = date("Y-m-d H:i:s");
            $log = "[$fecha] Intento de SQL Injection\n";
            $log .= "Usuario ingresado: $usuario\n";
            $log .= "Contraseña ingresada: $password\n";
            $log .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
            $log .= "----------------------------------\n";
            file_put_contents("attack_log.txt", $log, FILE_APPEND);
            break;
        }
    }
    if ($ataqueDetectado) {
        $mensaje = "⚠️ Ataque detectado. Intento registrado.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($resultado->num_rows === 1) {
            $fila = $resultado->fetch_assoc();
            
            // ✅ AHORA SÍ: Verifica contraseña CIFRADA correctamente
            if (password_verify($password, $fila['contraseña'])) {
                $_SESSION['usuario_id'] = $fila['id'];
                $_SESSION['usuario_nombre'] = $fila['nombre'];
                $_SESSION['usuario_rol'] = $fila['rol'];
                $_SESSION['usuario_tipo'] = $fila['tipo'];
                header("Location: index.php");
                exit;
            } else {
                $mensaje = "❌ Contraseña incorrecta";
            }
        } else {
            $mensaje = "❌ Usuario no encontrado";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IMANOL - Sistema de Gestión</title>
<link rel="icon" type="image/png" sizes="32x32" href="imagenes/logo1.png?v=4">
    <link rel="icon" type="image/png" sizes="16x16" href="imagenes/logo1.png?v=4">
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
    overflow:hidden;
    background:#081426;
    position:relative;
}
/* Fondo animado */
.background{
    position:absolute;
    width:100%;
    height:100%;
    background:
    linear-gradient(rgba(0,0,0,.60),rgba(0,0,0,.60)),
    url('imagenes/fondo.jpeg');
    background-size:cover;
    background-position:center;
    animation:zoomBg 20s infinite alternate;
    z-index:1;
}
@keyframes zoomBg{
    from{ transform:scale(1); }
    to{ transform:scale(1.1); }
}
/* Partículas */
.particles{
    position:absolute;
    width:100%;
    height:100%;
    overflow:hidden;
    z-index:2;
}
.particles span{
    position:absolute;
    display:block;
    width:6px;
    height:6px;
    background:white;
    border-radius:50%;
    animation:float 15s linear infinite;
    opacity:.4;
}
@keyframes float{
    0%{ transform:translateY(100vh) scale(0); }
    100%{ transform:translateY(-100px) scale(1); }
}
.login-container{
    position:absolute;
    top:50%;
    left:50%;
    transform:translate(-50%, -50%);
    width:420px;
    padding:40px;
    border-radius:25px;
    background:rgba(255,255,255,.08);
    backdrop-filter:blur(18px);
    -webkit-backdrop-filter:blur(18px);
    border:1px solid rgba(255,255,255,.15);
    box-shadow:
    0 0 25px rgba(0,59,142,.8),
    0 0 50px rgba(227,6,19,.3);
    z-index:10;
    animation:showLogin 1s ease;
}
@keyframes showLogin{
    from{ opacity:0; transform:translate(-50%, -40%); }
    to{ opacity:1; transform:translate(-50%, -50%); }
}
@keyframes neonGlow{
    from{ text-shadow:
        0 0 10px #fff,
        0 0 20px #fff,
        0 0 40px #fff,
        0 0 80px #fff,
        0 0 120px #fff; }
    to{ text-shadow:
        0 0 5px #fff,
        0 0 15px #fff,
        0 0 30px #fff,
        0 0 60px #fff,
        0 0 100px #fff; }
}
.logo{
    text-align:center;
    margin-bottom:0;
}
/* ✨ ANIMACIÓN DE GIRO 360° DEL LOGO */
.logo img{
    width:100px;
    filter: drop-shadow(0 0 3px rgba(255,255,255,0.15));
    animation: giroCompleto 1.2s ease-out;
}
/* 🔄 Giro de 360 grados al entrar la página */
@keyframes giroCompleto {
    0% {
        transform: rotate(0deg) scale(0);
        opacity: 0;
    }
    60% {
        transform: rotate(400deg) scale(1.15); /* gira 360+40 y crece un poquito */
    }
    100% {
        transform: rotate(360deg) scale(1); /* termina derecho y tamaño normal */
        opacity: 1;
    }
}
/* 💡 Si quieres que gire en bucle continuo, descomenta abajo: */
/*
.logo img {
    animation: girarInfinito 4s linear infinite;
}
@keyframes girarInfinito {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
*/
.title{
    text-align:center;
    color:white;
    margin-bottom:25px;
}
.title h2{
    color:white;
    text-shadow:
    0 0 10px #fff,
    0 0 20px #fff,
    0 0 40px #fff,
    0 0 80px #fff,
    0 0 120px #fff;
    animation: neonGlow 1.5s infinite alternate;
}
.title p{
    font-size:14px;
    color:#ddd;
}
.mensaje-error{
    background: rgba(255, 0, 0, 0.15);
    border-left: 3px solid #E30613;
    color: #fff;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 8px;
    text-align: center;
}
.input-box{
    position:relative;
    margin-bottom:20px;
}
.input-box input{
    width:100%;
    padding:15px 50px 15px 50px;
    border:none;
    outline:none;
    border-radius:12px;
    background:rgba(255,255,255,.12);
    color:white;
    font-size:15px;
}
.input-box input::placeholder{
    color:#ddd;
}
.input-box i:first-child{
    position:absolute;
    left:18px;
    top:16px;
    color:#fff;
}
.toggle-password{
    position:absolute;
    right:18px;
    top:16px;
    cursor:pointer;
    transition:.3s;
    color:#fff;
}
.toggle-password:hover{
    color:#E30613;
}
.login-btn{
    width:100%;
    border:none;
    padding:15px;
    border-radius:12px;
    background:linear-gradient(90deg, #003B8E, #E30613);
    color:white;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    transition:.3s;
}
.login-btn:hover{
    transform:translateY(-3px);
    box-shadow: 0 0 20px #003B8E, 0 0 40px #E30613;
}
.footer{
    text-align:center;
    margin-top:20px;
    color:#ddd;
    font-size:12px;
}
.footer a{
    color: #00BFFF;
    text-decoration: none;
}
.line{
    width:100px;
    height:4px;
    margin:15px auto;
    border-radius:10px;
    background:linear-gradient(90deg, #003B8E, #E30613);
}
@media(max-width:480px){
    .login-container{
        width:90%;
        padding:30px;
    }
    .logo img{
        width:140px;
    }
}
</style>
</head>
<body>
<div class="background"></div>
<div class="particles" id="particles"></div>
<div class="login-container">
    <div class="logo">
        <img src="imagenes/logo1.png" alt="IMANOL">
    </div>
    <div class="title">
        <h2>Bienvenido</h2>
        <p>Sistema de Gestión de combustible </p>
        <p> -- IMANOL --</p>
    </div>
    <div class="line"></div>
    <?php if ($mensaje): ?>
        <div class="mensaje-error"><?= $mensaje ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="input-box">
            <i class="fas fa-user"></i>
            <input type="text" name="usuario" placeholder="Usuario" required>
        </div>
        <div class="input-box">
            <i class="fas fa-lock"></i>
            <input
                type="password"
                name="contraseña"
                id="password"
                placeholder="Contraseña"
                required
            >
            <i class="fas fa-eye toggle-password" id="togglePassword"></i>
        </div>
        <button type="submit" class="login-btn">INICIAR SESIÓN</button>
    </form>
    <div class="footer">
        Copyright © 2026 IMANOL - Dios con Nosotros
    </div>
    <div class="footer">
        Desarrollado Por / Yaffar Villanueva y Christian Limpias
    </div>
</div>
<script>
const particles = document.getElementById("particles");
for(let i=0;i<80;i++){
    let span = document.createElement("span");
    span.style.left = Math.random()*100+"%";
    span.style.animationDuration = (Math.random()*10+10)+"s";
    span.style.opacity = Math.random();
    span.style.width = span.style.height = Math.random()*5+2+"px";
    particles.appendChild(span);
}
const togglePassword = document.getElementById("togglePassword");
const password = document.getElementById("password");
togglePassword.addEventListener("click", () => {
    const type = password.getAttribute("type") === "password" ? "text" : "password";
    password.setAttribute("type", type);
    togglePassword.classList.toggle("fa-eye");
    togglePassword.classList.toggle("fa-eye-slash");
});
</script>
</body>
</html>