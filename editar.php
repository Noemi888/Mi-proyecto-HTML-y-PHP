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

// Obtener datos del formulario
$correo = $_POST['correo'] ?? '';
$nuevoNombre = $_POST['nombre'] ?? '';
$nuevoApellido = $_POST['apellido'] ?? '';
$nuevoTelefono = $_POST['telefono'] ?? '';

// Validar
if (empty($correo) || empty($nuevoNombre) || empty($nuevoApellido) || empty($nuevoTelefono)) {
    echo "<script>alert('Por favor completa todos los campos'); window.location.href='mostrar.php';</script>";
    exit();
}

// Actualizar datos
$sql = "UPDATE loggin SET nombre = ?, apellido = ?, numerotel = ? WHERE correo = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssss", $nuevoNombre, $nuevoApellido, $nuevoTelefono, $correo);

if ($stmt->execute()) {
    $_SESSION['usuario'] = $nuevoNombre; // actualizar nombre de sesión
    echo "<script>alert('Datos actualizados correctamente'); window.location.href='mostrar.php';</script>";
} else {
    echo "<script>alert('Error al actualizar datos'); window.location.href='mostrar.php';</script>";
}

$stmt->close();
$conexion->close();
?>
