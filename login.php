<?php
session_start();
require __DIR__ . '/config_db.php';

$correo     = trim($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

if(!$correo || !$contrasena){
  echo "<script>alert('Completa correo y contraseña');history.back();</script>"; exit;
}

$stmt = $conexion->prepare("SELECT nombre, apellido, contrasena FROM loggin WHERE correo = ? LIMIT 1");
$stmt->bind_param("s", $correo);
$stmt->execute();
$res = $stmt->get_result();

if(!$res || $res->num_rows === 0){
  $stmt->close();
  echo "<script>alert('Usuario no encontrado');history.back();</script>"; exit;
}
$row = $res->fetch_assoc(); $stmt->close();

if(!password_verify($contrasena, $row['contrasena'])){
  echo "<script>alert('Contraseña incorrecta');history.back();</script>"; exit;
}

$_SESSION['usuario'] = $correo;
$_SESSION['nombre']  = trim(($row['nombre'] ?? '').' '.($row['apellido'] ?? ''));

header("Location: aeropuerto.php");
exit;
