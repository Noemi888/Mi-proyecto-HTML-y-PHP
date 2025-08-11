<?php
// eliminar_usuario.php
ini_set('display_errors','1'); ini_set('display_startup_errors','1'); error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();

// Acepta sesión por id o por correo
$correoSesion = $_SESSION['correo'] ?? null;
$uidSesion    = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;

if (!$correoSesion && !$uidSesion) {
  header("Location: loggin.html");
  exit;
}

require_once __DIR__ . '/config_db.php'; // debe crear $conexion = new mysqli(...)
$conexion->set_charset('utf8mb4');

// Localiza al usuario por id o por correo
if ($uidSesion) {
  $st = $conexion->prepare("SELECT id, nombre, correo FROM loggin WHERE id=? LIMIT 1");
  $st->bind_param("i", $uidSesion);
} else {
  $st = $conexion->prepare("SELECT id, nombre, correo FROM loggin WHERE correo=? LIMIT 1");
  $st->bind_param("s", $correoSesion);
}
$st->execute();
$usr = $st->get_result()->fetch_assoc();
$st->close();

if (!$usr) { echo "Usuario no encontrado."; exit; }

$userId     = (int)$usr['id'];
$userCorreo = (string)$usr['correo'];

// POST: confirmar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (($_POST['confirmar'] ?? '') === 'SI') {
    $conexion->begin_transaction();
    try {
      // Borra reservas por user_id (si existe esa columna)
      $d1 = $conexion->prepare("DELETE FROM base1 WHERE user_id=?");
      $d1->bind_param("i", $userId);
      $d1->execute();
      $d1->close();

      // Borra reservas por correo (para esquemas sin user_id)
      $d1b = $conexion->prepare("DELETE FROM base1 WHERE correo=?");
      $d1b->bind_param("s", $userCorreo);
      $d1b->execute();
      $d1b->close();

      // Borra usuario por id
      $d2 = $conexion->prepare("DELETE FROM loggin WHERE id=? LIMIT 1");
      $d2->bind_param("i", $userId);
      $d2->execute();
      $rowsUser = $d2->affected_rows;
      $d2->close();

      // Si por alguna razón no borró por id, intenta por correo
      if ($rowsUser < 1) {
        $d2b = $conexion->prepare("DELETE FROM loggin WHERE correo=? LIMIT 1");
        $d2b->bind_param("s", $userCorreo);
        $d2b->execute();
        $d2b->close();
      }

      $conexion->commit();

      // Cierra sesión
      $_SESSION = [];
      if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
      }
      session_destroy();

      echo "<script>alert('Tu cuenta y vuelos fueron eliminados.'); location.href='loggin.html';</script>";
      exit;
    } catch (Throwable $e) {
      $conexion->rollback();
      $msg = addslashes($e->getMessage());
      echo "<script>alert('No se pudo eliminar: {$msg}'); history.back();</script>";
      exit;
    }
  } else {
    header("Location: mostrar.php"); exit;
  }
}

// GET: pantalla de confirmación
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Eliminar mi cuenta</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body{font-family:Arial;background:#F5F0E6;color:#0A174E;margin:0}
  .box{max-width:520px;margin:40px auto;background:#fff;padding:24px;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,.08)}
  button{background:#0A174E;color:#fff;border:0;border-radius:8px;padding:12px 16px;margin-top:16px;cursor:pointer}
  .muted{background:#e5e7eb;color:#111}
  .row{display:flex;gap:10px;flex-wrap:wrap}
</style>
</head>
<body>
<div class="box">
  <h2>Eliminar mi cuenta</h2>
  <p><b>Usuario:</b> <?= h($usr['nombre'] ?? '') ?><br>
     <b>Correo:</b> <?= h($userCorreo) ?></p>
  <p>Esta acción borrará tu cuenta y <b>todas tus reservas</b>. No se puede deshacer.</p>
  <form method="post" class="row">
    <button type="submit" name="confirmar" value="SI">Sí, eliminar</button>
    <button type="submit" name="confirmar" value="NO" class="muted">No, cancelar</button>
  </form>
</div>
</body>
</html>
