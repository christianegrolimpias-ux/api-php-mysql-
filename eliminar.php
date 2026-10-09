<?php
include 'conexion.php';
verificarSesion();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

// Eliminar de forma segura
$stmt = $conn->prepare("DELETE FROM movimientos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Volver al dashboard
header("Location: index.php");
exit;
?>