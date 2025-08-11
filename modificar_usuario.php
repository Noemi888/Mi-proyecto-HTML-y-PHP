<?php
// modificar_usuario.php
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
if (!isset($_SESSION['usuario']) && !isset($_SESSION['correo'])) {
  header("Location: loggin.html");
  exit;
}
$correoSesion = $_SESSION['correo'] ?? $_SESSION['usuario'] ?? '';

require_once __DIR__ . '/config_db.php';

$errores = [];
$ok = isset($_GET['ok']);

/* === Cargar datos actuales del usuario (por correo de sesión) === */
$usuario = [
  'nombre'    => '',
  'apellido'  => '',
  'fechadn'   => '',
  'correo'    => $correoSesion,
  'numerotel' => '',
];

try {
  $st = $conexion->prepare("SELECT nombre, apellido, fechadn, correo, numerotel
                            FROM loggin
                            WHERE correo = ?
                            LIMIT 1");
  $st->bind_param("s", $correoSesion);
  $st->execute();
  $res = $st->get_result();
  if ($row = $res->fetch_assoc()) {
    $usuario['nombre']    = $row['nombre']    ?? '';
    $usuario['apellido']  = $row['apellido']  ?? '';
    $usuario['fechadn']   = $row['fechadn']   ?? '';
    $usuario['correo']    = $row['correo']    ?? $correoSesion;
    $usuario['numerotel'] = $row['numerotel'] ?? '';
  } else {
    $errores[] = "Usuario no encontrado.";
  }
  $st->close();
} catch (Throwable $e) {
  $errores[] = "No fue posible cargar tus datos.";
}

/* === Guardar cambios === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errores)) {
  $nombre     = mb_substr(trim($_POST['nombre']    ?? ''), 0, 100);
  $apellido   = mb_substr(trim($_POST['apellido']  ?? ''), 0, 100);
  $fechadn    = trim($_POST['fechadn']   ?? '');
  $numerotel  = mb_substr(trim($_POST['numerotel'] ?? ''), 0, 20);
  $pass_act   = $_POST['pass_actual']        ?? '';
  $pass_new   = $_POST['pass_nueva']         ?? '';
  $pass_conf  = $_POST['pass_conf']          ?? '';

  if ($nombre === '' || $apellido === '') {
    $errores[] = "Nombre y apellido son obligatorios.";
  }

  // Validar fecha (opcional)
  if ($fechadn !== '') {
    $dt = DateTime::createFromFormat('Y-m-d', $fechadn);
    if (!$dt || $dt->format('Y-m-d') !== $fechadn) {
      $errores[] = "La fecha de nacimiento no es válida (AAAA-MM-DD).";
    }
  }

  // Unicidad de teléfono en otros usuarios
  try {
    $q = $conexion->prepare("SELECT id FROM loggin
                             WHERE REPLACE(REPLACE(REPLACE(REPLACE(numerotel,' ',''),'-',''),'(',')'),')','') =
                                   REPLACE(REPLACE(REPLACE(REPLACE(?, ' ', ''),'-',''),'(',')'),')','')
                               AND correo <> ?
                             LIMIT 1");
    $q->bind_param("ss", $numerotel, $correoSesion);
    $q->execute();
    if ($q->get_result()->num_rows > 0) {
      $errores[] = "Ese teléfono ya está en uso por otro usuario.";
    }
    $q->close();
  } catch (Throwable $e) {
    $errores[] = "No fue posible validar el teléfono.";
  }

  // Cambio de contraseña (opcional)
  $hashNuevo = null;
  if ($pass_act !== '' || $pass_new !== '' || $pass_conf !== '') {
    if ($pass_new === '' || $pass_conf === '') {
      $errores[] = "Para cambiar la contraseña escribe la nueva y confírmala.";
    } elseif ($pass_new !== $pass_conf) {
      $errores[] = "La nueva contraseña y su confirmación no coinciden.";
    } elseif (strlen($pass_new) < 6) {
      $errores[] = "La nueva contraseña debe tener al menos 6 caracteres.";
    } else {
      // Traer hash actual
      try {
        $h = $conexion->prepare("SELECT contrasena FROM loggin WHERE correo=? LIMIT 1");
        $h->bind_param("s", $correoSesion);
        $h->execute();
        $row = $h->get_result()->fetch_assoc();
        $h->close();
        $hashActual = $row['contrasena'] ?? '';
        $isHash = (bool)preg_match('/^\$(2[aby]|argon2)/i', $hashActual);
        $okPass = $isHash ? password_verify($pass_act, $hashActual)
                          : hash_equals((string)$hashActual, (string)$pass_act);
        if (!$okPass) {
          $errores[] = "La contraseña actual no es correcta.";
        } else {
          $hashNuevo = password_hash($pass_new, PASSWORD_DEFAULT);
        }
      } catch (Throwable $e) {
        $errores[] = "No fue posible validar la contraseña actual.";
      }
    }
  }

  if (empty($errores)) {
    try {
      if ($hashNuevo !== null) {
        $sql = "UPDATE loggin
                  SET nombre=?, apellido=?, fechadn=?, numerotel=?, contrasena=?
                WHERE correo=? LIMIT 1";
        $st = $conexion->prepare($sql);
        $st->bind_param("ssssss", $nombre, $apellido, $fechadn, $numerotel, $hashNuevo, $correoSesion);
      } else {
        $sql = "UPDATE loggin
                  SET nombre=?, apellido=?, fechadn=?, numerotel=?
                WHERE correo=? LIMIT 1";
        $st = $conexion->prepare($sql);
        $st->bind_param("sssss", $nombre, $apellido, $fechadn, $numerotel, $correoSesion);
      }
      $st->execute();
      $st->close();

      // refrescar sesión (¡NO mover el correo!)
      $_SESSION['nombre'] = trim("$nombre $apellido");

      header("Location: modificar_usuario.php?ok=1");
      exit;
    } catch (Throwable $e) {
      $errores[] = "No se pudieron guardar los cambios.";
    }
  }

  // repintar con lo enviado si hubo errores
  $usuario['nombre']    = $nombre;
  $usuario['apellido']  = $apellido;
  $usuario['fechadn']   = $fechadn;
  $usuario['numerotel'] = $numerotel;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar datos del usuario</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root{--bg:#F5F0E6;--ink:#0A174E;--card:#fff;--brand:#0A174E;--muted:#6b7280;--shadow:0 10px 25px rgba(0,0,0,.08);--r:18px;}
    *{box-sizing:border-box}
    body{margin:0;font-family:Arial,Helvetica,sans-serif;background:var(--bg);color:var(--ink)}
    header{display:flex;align-items:center;gap:14px;padding:16px 20px;background:var(--bg);box-shadow:0 2px 6px rgba(0,0,0,.06)}
    header img{width:52px;height:52px;object-fit:cover;border-radius:8px;background:#eee}
    header h1{margin:0;font-weight:800;font-size:32px}

    /* Menú igual que en aeropuerto/mostrar */
    .menu { background-color: #333; }
    .menu button { background-color:#333;color:#fff;border:none;padding:15px 20px;font-size:18px;cursor:pointer;width:100%;text-align:left }
    .menu button:hover { background-color:#444 }
    .menu-items { display:none;flex-direction:column;background-color:#000 }
    .menu-items a { color:#fff;padding:12px 20px;text-decoration:none }
    .menu-items a:hover { background-color:#555 }

    .wrap{max-width:900px;margin:24px auto;padding:0 16px}
    .card{background:var(--card);border-radius:var(--r);padding:20px;margin:18px 0;box-shadow:var(--shadow)}
    h2{margin:0 0 12px;text-align:center}
    label{display:block;margin-top:10px;font-weight:700}
    input{width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;margin-top:6px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .actions{margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
    .btn{background:var(--brand);color:#fff;border:none;border-radius:10px;padding:10px 14px;text-decoration:none;display:inline-block;font-weight:600;cursor:pointer}
    .btn.secondary{background:#0e2559}
    .alert{background:#e8f5e9;border:1px solid #b7e1bd;color:#1b5e20;padding:10px;border-radius:10px;margin-bottom:10px}
    .error{background:#fdecec;border:1px solid #f7b4b4;color:#8b1c1c;padding:10px;border-radius:10px;margin-bottom:10px}
    .muted{color:var(--muted)}
    @media(max-width:640px){.row{grid-template-columns:1fr}}
  </style>
</head>
<body>

<header>
  <img src="Avion.jpg" alt="Logo">
  <h1>Aerolínea Mexicana</h1>
</header>

<div class="menu">
  <button onclick="toggleMenu()">☰ Menú</button>
  <div class="menu-items" id="menuItems">
    <a href="aeropuerto.php">Inicio</a>
    <a href="mostrar.php">Mis datos</a>
    <a href="ofertas.html">Ofertas</a>
    <a href="ayuda.html">Ayuda</a>
    <a href="logout.php">Cerrar Sesión</a>
  </div>
</div>

<main class="wrap">
  <section class="card">
    <h2>Editar mis datos</h2>

    <?php if (!empty($errores)): ?>
      <div class="error">
        <ul style="margin:0;padding-left:18px">
          <?php foreach ($errores as $e): ?><li><?=h($e)?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php elseif ($ok): ?>
      <div class="alert">¡Datos actualizados correctamente!</div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="row">
        <div>
          <label for="nombre">Nombre</label>
          <input type="text" id="nombre" name="nombre" required maxlength="100" value="<?=h($usuario['nombre'])?>">
        </div>
        <div>
          <label for="apellido">Apellido</label>
          <input type="text" id="apellido" name="apellido" required maxlength="100" value="<?=h($usuario['apellido'])?>">
        </div>
      </div>

      <div class="row">
        <div>
          <label for="fechadn">Fecha de nacimiento</label>
          <input type="date" id="fechadn" name="fechadn" value="<?=h($usuario['fechadn'])?>">
        </div>
        <div>
          <label for="numerotel">Teléfono</label>
          <input type="text" id="numerotel" name="numerotel" maxlength="20" value="<?=h($usuario['numerotel'])?>" placeholder="Ej. 5512345678">
        </div>
      </div>

      <hr style="border:none;border-top:1px solid #eee;margin:18px 0">
      <p class="muted" style="margin:0 0 10px"><strong>Cambiar contraseña</strong> (opcional):</p>

      <div class="row">
        <div>
          <label for="pass_actual">Contraseña actual</label>
          <input type="password" id="pass_actual" name="pass_actual" placeholder="Necesaria para cambiar la contraseña">
        </div>
        <div>
          <label for="pass_nueva">Nueva contraseña</label>
          <input type="password" id="pass_nueva" name="pass_nueva" placeholder="Mínimo 6 caracteres">
        </div>
      </div>

      <div class="row">
        <div>
          <label for="pass_conf">Confirmar nueva contraseña</label>
          <input type="password" id="pass_conf" name="pass_conf">
        </div>
        <div></div>
      </div>

      <div class="actions">
        <a class="btn secondary" href="mostrar.php">Cancelar</a>
        <button class="btn" type="submit">Guardar cambios</button>
      </div>
    </form>
  </section>
</main>

<footer style="background:#132a25;color:#fff;text-align:center;padding:16px;margin-top:26px">Contactos</footer>

<script>
  function toggleMenu() {
    const menu = document.getElementById("menuItems");
    menu.style.display = (menu.style.display === "flex") ? "none" : "flex";
  }
</script>

</body>
</html>