<?php
// mostrar.php
ini_set('display_errors','1'); ini_set('display_startup_errors','1'); error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
date_default_timezone_set('America/Mexico_City');

if (!isset($_SESSION['correo'])) { header("Location: loggin.html"); exit; }
$correoSesion = $_SESSION['correo'];

require_once __DIR__ . '/config_db.php'; // $conexion = new mysqli(...)

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function edadDesdeFecha($f){ if(!$f) return null; try{ $fn=new DateTime($f); $h=new DateTime('today'); return $fn->diff($h)->y; }catch(Exception $e){ return null; } }
function moneyMx($n){ if($n===null||$n==='') return ''; $n=preg_replace('/[^0-9.\-]/','',(string)$n); return '$'.number_format((float)$n,2,'.',','); }

// Usuario
$stU=$conexion->prepare("SELECT nombre,apellido,fechadn,correo,numerotel FROM loggin WHERE correo=? LIMIT 1");
$stU->bind_param("s",$correoSesion); $stU->execute();
$usuario=$stU->get_result()->fetch_assoc(); $stU->close();

$nombreCompleto = $usuario ? trim(($usuario['nombre']??'').' '.($usuario['apellido']??'')) : ($_SESSION['usuario'] ?? '');
$edad = $usuario ? edadDesdeFecha($usuario['fechadn']??null) : null;

// Última reserva (usa id; si no tienes id, cambia a ORDER BY fecha1 DESC)
$stR=$conexion->prepare("SELECT id,cliente,correo,numerotel,origen,destino,pasajeros,edad,fecha1,precio
                         FROM base1 WHERE correo=? ORDER BY id DESC LIMIT 1");
$stR->bind_param("s",$correoSesion); $stR->execute();
$ultima=$stR->get_result()->fetch_assoc(); $stR->close();

$ok  = $_GET['ok']  ?? '';
$err = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mis datos y última reserva</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root{ --bg:#F5F0E6; --primary:#0A174E; --white:#fff; }
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;background:var(--bg);color:var(--primary);margin:0}

/* ===== Encabezado con logo, título y saludo ===== */
.topbar{
  background:var(--bg);
  display:flex; align-items:center; justify-content:space-between;
  padding:14px 18px; border-bottom:1px solid #e9e6de;
}
.top-left{display:flex; align-items:center; gap:12px}
.logo{height:36px; width:auto}
.brand{font-size:28px; font-weight:800; letter-spacing:.3px}
.welcome{font-weight:600}

/* ===== Barra gris "Menú" + panel negro desplegable ===== */
.menubar{background:#4b4b4b; color:#fff; padding:10px 16px; cursor:pointer; user-select:none}
.menubar span{font-weight:700}
.menu-panel{background:#000; color:#fff; display:none}
.menu-panel a{
  display:block; color:#fff; text-decoration:none; padding:12px 16px; border-bottom:1px solid #1a1a1a;
}
.menu-panel a:hover{ background:#111; }

/* ===== Contenido ===== */
main{max-width:1000px;margin:24px auto;padding:0 16px}
.card{background:var(--white); border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.08); margin:16px 0; padding:20px}
.section-title{margin:0 0 12px 0; font-size:24px}
.grid-2{display:grid; grid-template-columns:1fr 1fr; gap:16px}
.row{display:flex; gap:8px; align-items:center; margin:6px 0}
.label{min-width:140px; color:#1f2937; font-weight:700}
.value{color:#111827}
.actions{display:flex; flex-wrap:wrap; gap:12px; margin-top:14px}
.btn{display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:10px; border:none; cursor:pointer; text-decoration:none; font-weight:600}
.btn-primary{background:var(--primary); color:#fff}
.btn-danger{background:#c0392b; color:#fff}
.btn-outline{background:#e5e7eb; color:#111827}

table{width:100%; border-collapse:collapse; margin-top:6px}
th,td{border:1px solid #e5e7eb; padding:12px; text-align:left}
th{background:#f3f4f6}

.muted{color:#6b7280}
.alert{padding:10px 12px; border-radius:10px; margin:10px 0}
.alert-ok{background:#e8f5e9; color:#256029}
.alert-err{background:#fdecea; color:#a12622}

footer{padding:40px 0; text-align:center; color:#6b7280}
@media (max-width:700px){ .grid-2{grid-template-columns:1fr} }
</style>
<script>
function toggleMenu(){
  var el = document.getElementById('menu-panel');
  el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}
</script>
</head>
<body>

<!-- Header -->
<div class="topbar">
  <div class="top-left">
    <img src="logo.png" class="logo" alt="Logo Avión" onerror="this.style.display='none'">
    <div class="brand">Aerolínea Mexicana</div>
  </div>
  <div class="welcome">Bienvenido, <?= h($nombreCompleto ?: $correoSesion) ?></div>
</div>

<!-- Barra menú -->
<div class="menubar" onclick="toggleMenu()">☰ <span>Menú</span></div>
<div id="menu-panel" class="menu-panel">
  <a href="aeropuerto.php">Inicio</a>
  <a href="mostrar.php">Mis datos</a>
  <a href="ofertas.html">Ofertas Especiales</a>
  <a href="ayuda.html">Ayuda</a>
  <a href="logout.php">Cerrar Sesión</a>
</div>

<main>
  <?php if ($ok): ?>
    <div class="alert alert-ok">
      <?= $ok === 'usuario_actualizado' ? 'Tus datos se actualizaron correctamente.' : '' ?>
      <?= $ok === 'vuelo_actualizado'   ? 'Tu vuelo se actualizó correctamente.' : '' ?>
      <?= $ok === 'vuelo_eliminado'     ? 'Tu vuelo fue eliminado correctamente.' : '' ?>
    </div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="alert alert-err">
      <?= $err === 'no_encontrado' ? 'No se encontró la reserva.' : h($err) ?>
    </div>
  <?php endif; ?>

  <!-- Datos del usuario -->
  <section class="card">
    <h2 class="section-title">Mis datos</h2>
    <div class="grid-2">
      <div class="row"><div class="label">Nombre:</div><div class="value"><?= h($nombreCompleto ?: '—') ?></div></div>
      <div class="row"><div class="label">Correo:</div><div class="value"><?= h($usuario['correo'] ?? $correoSesion) ?></div></div>
      <div class="row"><div class="label">Teléfono:</div><div class="value"><?= h($usuario['numerotel'] ?? '—') ?></div></div>
      <div class="row"><div class="label">Edad:</div><div class="value"><?= $edad !== null ? h($edad . ' años') : '—' ?></div></div>
    </div>

    <div class="actions">
      <a class="btn btn-outline" href="editar_usuario.php">Editar</a>
      <a class="btn btn-danger" href="eliminar_usuario.php" onclick="return confirm('¿Eliminar tu cuenta y todos tus datos? Esta acción no se puede deshacer.');">Eliminar cuenta</a>
    </div>
  </section>

  <!-- Última reserva -->
  <section class="card">
    <h2 class="section-title">Última reserva</h2>

    <?php if (!$ultima): ?>
      <p class="muted">No tienes reservas registradas.</p>
    <?php else: ?>
      <table>
        <tr><th style="width:260px">Nombre del usuario</th><td><?= h($ultima['cliente'] ?: $nombreCompleto) ?></td></tr>
        <tr><th>Origen</th><td><?= h($ultima['origen']) ?></td></tr>
        <tr><th>Destino</th><td><?= h($ultima['destino']) ?></td></tr>
        <tr><th>Pasajeros</th><td><?= h($ultima['pasajeros']) ?></td></tr>
        <tr><th>Edades</th><td><?= h($ultima['edad']) ?></td></tr>
        <tr><th>Fecha de partida</th><td><?= h($ultima['fecha1']) ?></td></tr>
        <tr><th>Precio</th><td><?= h(moneyMx($ultima['precio'])) ?></td></tr>
      </table>

      <div class="actions">
        <a class="btn btn-primary" href="editar_reserva.php">Editar vuelo</a>
        <a class="btn btn-danger"  href="eliminar_reserva.php" onclick="return confirm('¿Seguro que quieres eliminar este vuelo?');">Eliminar vuelo</a>
      </div>
    <?php endif; ?>
  </section>
</main>

  <footer class="footer">
    <h3>Contacto</h3>
    <p><strong>Email:</strong> noemiherp880@gmail.com</p>
    <p><strong>Redes Sociales:</strong>
      <a href="https://www.facebook.com/share/1JVca8wJn4/" target="_blank" rel="noopener">Facebook</a>
      <a href="https://www.instagram.com/karen.noemihd?utm_source=qr&igsh=bWV1M3MxaTMya3p0" target="_blank" rel="noopener">Instagram</a>
      <a href="https://vt.tiktok.com/ZSStE2HgH/" target="_blank" rel="noopener">TikTok</a>
    </p>
    <p style="margin-top:14px;">Aerolínea Mexicana.</p>
  </footer>
</body>
</html>
