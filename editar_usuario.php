<?php
// editar_usuario.php  (editar datos del usuario logueado)
ini_set('display_errors','1'); ini_set('display_startup_errors','1'); error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

if (!isset($_SESSION['correo'])) { 
    header("Location: loggin.html"); 
    exit; 
}
$correoSesion = $_SESSION['correo'];

require_once __DIR__ . '/config_db.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Guardar cambios cuando envían formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido  = trim($_POST['apellido'] ?? '');
    $fechadn   = trim($_POST['fechadn'] ?? '');
    $numerotel = trim($_POST['numerotel'] ?? '');

    if ($nombre === '' || $apellido === '') {
        $error = 'Nombre y Apellido son obligatorios.';
    } else {
        $up = $conexion->prepare(
            "UPDATE loggin SET nombre=?, apellido=?, fechadn=?, numerotel=? WHERE correo=? LIMIT 1"
        );
        $up->bind_param("sssss", $nombre, $apellido, $fechadn, $numerotel, $correoSesion);
        $up->execute();
        $up->close();

        // Guardar nombre en sesión para mostrarlo en otras páginas
        $_SESSION['usuario'] = $nombre;

        header("Location: mostrar.php?ok=usuario_actualizado");
        exit;
    }
}

// Cargar datos actuales del usuario
$st = $conexion->prepare("SELECT nombre, apellido, fechadn, numerotel, correo FROM loggin WHERE correo=? LIMIT 1");
$st->bind_param("s", $correoSesion);
$st->execute();
$res = $st->get_result();
$u = $res->fetch_assoc();
$st->close();

if (!$u) { 
    header("Location: loggin.html"); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar usuario</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body{font-family:Arial,sans-serif;background:#F5F0E6;color:#0A174E;margin:0;padding:20px}
  .card{max-width:520px;margin:0 auto;background:#fff;padding:20px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08)}
  label{display:block;margin-top:10px;font-weight:bold}
  input{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px}
  .actions{margin-top:16px;display:flex;gap:10px}
  .btn{border:none;border-radius:8px;padding:10px 14px;cursor:pointer;text-decoration:none;display:inline-block}
  .primary{background:#0A174E;color:#fff}
  .muted{background:#e5e7eb;color:#111}
  .error{background:#fee;color:#900;border-radius:8px;padding:8px;margin-top:8px}
</style>
</head>
<body>
  <div class="card">
    <h2>Editar mis datos</h2>

    <?php if (!empty($error)): ?>
      <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <label>Correo (no editable)</label>
      <input value="<?= h($u['correo']) ?>" disabled>

      <label>Nombre</label>
      <input name="nombre" required value="<?= h($u['nombre'] ?? '') ?>">

      <label>Apellido</label>
      <input name="apellido" required value="<?= h($u['apellido'] ?? '') ?>">

      <label>Fecha de nacimiento</label>
      <input type="date" name="fechadn" value="<?= h($u['fechadn'] ?? '') ?>">

      <label>Teléfono</label>
      <input name="numerotel" value="<?= h($u['numerotel'] ?? '') ?>">

      <div class="actions">
        <button class="btn primary" type="submit">Guardar cambios</button>
        <a class="btn muted" href="mostrar.php">Cancelar</a>
      </div>
    </form>
  </div>
</body>
</html>
