<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: loggin.html");
    exit();
}

// ✅ Conexión con InfinityFree
$host = "sql308.infinityfree.com";
$usuarioBD = "if0_39478473";
$contrasenaBD = "6HTBDDoPra";
$baseDeDatos = "if0_39478473_loggin";  // ⚠️ Confirma que este sea el nombre correcto de tu base (parece ser así).

$conexion = new mysqli("sql308.infinityfree.com", "if0_39478473", "6HTBDDoPra", "if0_39478473_loggin");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener correo
$correo = $_POST['correo'] ?? '';

if (empty($correo)) {
    echo "<script>alert('No se recibió el correo.'); window.location.href='mostrar.php';</script>";
    exit();
}

// Eliminar usuario
$sql = "DELETE FROM loggin WHERE correo = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $correo);

if ($stmt->execute()) {
    session_destroy();
    echo "<script>alert('Cuenta eliminada correctamente.'); window.location.href='registro.html';</script>";
} else {
    echo "<script>alert('Error al eliminar la cuenta.'); window.location.href='mostrar.php';</script>";
}

$stmt->close();
$conexion->close();
?>
