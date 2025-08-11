<?php
// procesar_loggin.php
ini_set('display_errors','1'); ini_set('display_startup_errors','1'); error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

require_once __DIR__ . '/config_db.php'; // crea $conexion

$correo     = trim($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

if ($correo === '' || $contrasena === '') {
  fallo("Ingresa correo y contraseña.");
}

try {
  // Buscar por correo (insensible a mayúsculas)
  $sql = "SELECT id, nombre, apellido, correo, numerotel, contrasena
          FROM loggin
          WHERE LOWER(correo) = LOWER(?)
          LIMIT 1";
  $st = $conexion->prepare($sql);
  $st->bind_param("s", $correo);
  $st->execute();
  $res = $st->get_result();
  if (!$res || $res->num_rows === 0) {
    fallo("Usuario no encontrado.");
  }
  $u = $res->fetch_assoc();
  $st->close();

  // Validar contraseña (hash o texto plano por compatibilidad)
  $hash = $u['contrasena'] ?? '';
  $esHash = (bool)preg_match('/^\$(2[aby]|argon2)/i', $hash); // bcrypt/argon
  $ok = $esHash ? password_verify($contrasena, $hash)
                : hash_equals((string)$hash, (string)$contrasena);

  if (!$ok) {
    fallo("Contraseña incorrecta.");
  }

  // Sesión consistente con el resto del sitio
  session_regenerate_id(true);
  $_SESSION['uid']     = (int)$u['id'];
  $_SESSION['usuario'] = $u['correo']; // en tu proyecto se usa como correo
  $_SESSION['correo']  = $u['correo'];
  $_SESSION['nombre']  = trim(($u['nombre'] ?? '').' '.($u['apellido'] ?? ''));
  $_SESSION['tel']     = $u['numerotel'] ?? '';

  header("Location: aeropuerto.php");
  exit;

} catch (Throwable $e) {
  // Mensaje limpio (sin exponer detalles)
  fallo("Error al iniciar sesión. Inténtalo nuevamente.");
}

function fallo(string $msg){
  // Página mínima con enlace de regreso
  echo "<!doctype html><html lang='es'><meta charset='utf-8'>
        <title>Inicio de sesión</title>
        <body style='font-family:Arial; background:#F5F0E6; color:#0A174E'>
        <div style='max-width:520px;margin:40px auto;background:#fff;padding:24px;border-radius:16px;
                    box-shadow:0 10px 25px rgba(0,0,0,.08)'>
        <h2>Inicio de sesión</h2>
        <p>$msg</p>
        <p><a href='loggin.html' style='color:#0A174E;font-weight:bold'>Volver</a></p>
        </div></body></html>";
  exit;
}
