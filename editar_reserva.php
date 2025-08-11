<?php
// editar_reserva.php
ini_set('display_errors','1'); ini_set('display_startup_errors','1'); error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

if (!isset($_SESSION['correo'])) { header("Location: loggin.html"); exit; }
$correoSesion = $_SESSION['correo'];

require_once __DIR__ . '/config_db.php'; // Debe crear $conexion = new mysqli(...)

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* 1) Resolver ID de la reserva:
      - Si viene por GET -> usarlo.
      - Si no viene -> tomar la última reserva del usuario (por id DESC).
*/
$reservaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($reservaId <= 0) {
  $q = $conexion->prepare("SELECT id FROM base1 WHERE correo=? ORDER BY id DESC LIMIT 1");
  $q->bind_param("s", $correoSesion);
  $q->execute();
  $r = $q->get_result()->fetch_assoc();
  $q->close();
  if (!$r) { header("Location: mostrar.php?err=no_encontrado"); exit; }
  $reservaId = (int)$r['id'];
}

/* 2) Cargar datos actuales */
$st = $conexion->prepare("SELECT id, origen, destino, pasajeros, edad, fecha1, precio
                          FROM base1
                          WHERE id=? AND correo=?
                          LIMIT 1");
$st->bind_param("is", $reservaId, $correoSesion);
$st->execute();
$vuelo = $st->get_result()->fetch_assoc();
$st->close();

if (!$vuelo) { header("Location: mostrar.php?err=no_encontrado"); exit; }

/* 3) Guardar cambios (sin tocar precio) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $origen    = trim($_POST['origen'] ?? '');
  $destino   = trim($_POST['destino'] ?? '');
  $pasajeros = (int)($_POST['pasajeros'] ?? 1);
  $edad      = trim($_POST['edad'] ?? '');
  $fecha1    = trim($_POST['fecha1'] ?? '');

  if ($origen === '' || $destino === '' || $fecha1 === '') {
    $error = 'Faltan datos obligatorios.';
  } else {
    $up = $conexion->prepare("UPDATE base1
                              SET origen=?, destino=?, pasajeros=?, edad=?, fecha1=?
                              WHERE id=? AND correo=?");
    $up->bind_param("ssissis", $origen, $destino, $pasajeros, $edad, $fecha1, $reservaId, $correoSesion);
    $up->execute();
    $up->close();

    header("Location: mostrar.php?ok=vuelo_actualizado");
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar reserva</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  :root { --bg:#F5F0E6; --primary:#0A174E; --white:#fff; }
  *{box-sizing:border-box}
  body{font-family:Arial,Helvetica,sans-serif;background:var(--bg);color:var(--primary);margin:0;padding:20px}
  .card{max-width:640px;margin:0 auto;background:var(--white);padding:24px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08)}
  h2{margin:0 0 16px 0}
  label{display:block;margin-bottom:6px;font-weight:700;font-size:14px}
  input{width:100%;padding:8px 10px;border:1px solid #ccc;border-radius:6px;font-size:14px;margin-bottom:16px}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:20px}
  .readonly-box{background:#f0f0f0;padding:8px 10px;border:1px solid #ccc;border-radius:6px;font-size:14px;margin-bottom:16px}
  .actions{display:flex;gap:12px;margin-top:8px;flex-wrap:wrap}
  .btn{padding:10px 14px;border:none;border-radius:8px;cursor:pointer;font-weight:700;text-decoration:none}
  .primary{background:#0A174E;color:#fff}
  .muted{background:#ddd;color:#000}
  .error{background:#fee;color:#900;border-radius:6px;padding:8px;margin-bottom:12px}
</style>
</head>
<body>
  <div class="card">
    <h2>Editar reserva (última o seleccionada)</h2>

    <?php if (!empty($error)): ?>
      <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <label>Origen</label>
      <input name="origen" required value="<?= h($vuelo['origen']) ?>">

      <label>Destino</label>
      <input name="destino" required value="<?= h($vuelo['destino']) ?>">

      <div class="row">
        <div>
          <label>Pasajeros</label>
          <input type="number" name="pasajeros" min="1" required value="<?= h($vuelo['pasajeros']) ?>">
        </div>
        <div>
          <label>Edad(es)</label>
          <input name="edad" value="<?= h($vuelo['edad']) ?>">
        </div>
      </div>

      <div class="row">
        <div>
          <label>Fecha de partida</label>
          <input type="date" name="fecha1" required value="<?= h($vuelo['fecha1']) ?>">
        </div>
        <div>
          <label>Precio (no editable)</label>
          <div class="readonly-box"><?= h($vuelo['precio']) ?></div>
        </div>
      </div>

      <div class="actions">
        <button class="btn primary" type="submit">Guardar cambios</button>
        <a class="btn muted" href="mostrar.php">Cancelar</a>
      </div>
    </form>
  </div>
</body>
</html>
