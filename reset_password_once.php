<?php
// reset_password_once.php  — úsalo una vez y bórralo
ini_set('display_errors','1'); error_reporting(E_ALL);
require __DIR__ . '/config_db.php';

$EMAIL = 'tu_correo@ejemplo.com';   // <-- pon aquí el correo del usuario
$NEW   = 'NuevaClave123';           // <-- nueva contraseña

$hash = password_hash($NEW, PASSWORD_DEFAULT);

$stmt = $conexion->prepare("UPDATE loggin SET contrasena=? WHERE LOWER(correo)=LOWER(?) LIMIT 1");
$stmt->bind_param('ss', $hash, $EMAIL);
$stmt->execute();

if ($stmt->affected_rows > 0) {
  echo "OK: contraseña actualizada para $EMAIL";
} else {
  echo "No se actualizó nada. ¿Correo correcto?";
}
