<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: loggin.html");
    exit();
}

$mensaje = trim($_POST['mensaje']);

if (empty($mensaje)) {
    echo "<script>alert('El mensaje está vacío.'); window.location.href='ayuda.html';</script>";
    exit();
}

$nombre = $_SESSION['usuario'];
$fecha = date("Y-m-d H:i:s");
$registro = "[$fecha] $nombre: $mensaje\n";

// Guardar en un archivo
file_put_contents("mensajes.txt", $registro, FILE_APPEND);

echo "<script>alert('Gracias por tu comentario.'); window.location.href='aeropuerto.php';</script>";
?>

