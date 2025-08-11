<?php
// delete_account.php — elimina CUENTA + todas sus reservas (pidiendo contraseña)
ini_set('display_errors','1'); ini_set('display_startup_errors','1'); error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

require_once __DIR__ . '/config_db.php';

$uid    = (int)($_SESSION['uid'] ?? 0);
$correo = $_SESSION['correo'] ?? ($_POST['correo'] ?? '');
$pass   = $_POST['pass'] ?? '';

if ($pass === '') { header("Location: mostrar.php?e=badpass"); exit; }

try {
  // Completar uid/correo si falta alguno
  if ($uid <= 0 && $correo !== '') {
    $st = $conexion->prepare("SELECT id FROM loggin WHERE correo=? LIMIT 1");
    $st->bind_param("s", $correo); $st->execute();
    if ($r = $st->get_result()->fetch_assoc()) $uid = (int)$r['id'];
    $st->close();
  } elseif ($uid > 0 && $correo === '') {
    $st = $conexion->prepare("SELECT correo FROM loggin WHERE id=? LIMIT 1");
    $st->bind_param("i", $uid); $st->execute();
    if ($r = $st->get_result()->fetch_assoc()) $correo = $r['correo'];
    $st->close();
  }

  if ($uid <= 0 || $correo === '') { header("Location: mostrar.php?e=dbctx"); exit; }

  // Verificar contraseña
  $st = $conexion->prepare("SELECT contrasena FROM loggin WHERE id=? LIMIT 1");
  $st->bind_param("i", $uid);
  $st->execute();
  $usr = $st->get_result()->fetch_assoc();
  $st->close();

  if (!$usr) { header("Location: mostrar.php?e=notfound"); exit; }

  $hash   = $usr['contrasena'] ?? '';
  $isHash = (bool)preg_match('/^\$(2[aby]|argon2)/i', $hash);
  $ok     = $isHash ? password_verify($pass, $hash)
                    : hash_equals((string)$hash, (string)$pass);
  if (!$ok) { header("Location: mostrar.php?e=badpass"); exit; }

  // Transacción: borra reservas (por correo) y la cuenta (por id)
  $conexion->begin_transaction();

  $st = $conexion->prepare("DELETE FROM base1 WHERE correo=?");
  $st->bind_param("s", $correo);
  $st->execute();
  $st->close();

  $st = $conexion->prepare("DELETE FROM loggin WHERE id=? LIMIT 1");
  $st->bind_param("i", $uid);
  $st->execute();
  $affected = $st->affected_rows;
  $st->close();

  if ($affected !== 1) {
    $conexion->rollback();
    header("Location: mostrar.php?e=nodel"); exit;
  }

  $conexion->commit();

  // Cerrar sesión
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();

  echo "<!doctype html><html lang='es'><meta charset='utf-8'><title>Cuenta eliminada</title>
        <body style='font-family:Arial;background:#F5F0E6;color:#0A174E'>
        <div style='max-width:520px;margin:40px auto;background:#fff;padding:24px;border-radius:16px;box-shadow:0 10px 25px rgba(0,0,0,.08)'>
          <h2>Cuenta eliminada</h2>
          <p>Tu cuenta y todas tus reservas se eliminaron correctamente.</p>
          <p><a href='loggin.html' style='color:#0A174E;font-weight:bold'>Volver a iniciar sesión</a></p>
        </div></body></html>";
  exit;

} catch (Throwable $e) {
  if ($conexion->errno) { $conexion->rollback(); }
  header("Location: mostrar.php?e=dberr"); exit;
}
