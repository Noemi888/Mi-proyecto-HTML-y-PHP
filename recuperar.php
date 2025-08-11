<?php
session_start();
require __DIR__ . '/config_db.php';

$mensaje = '';
$link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $correo = trim($_POST['correo'] ?? '');
  if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $mensaje = 'Correo inválido';
  } else {
    // ¿Existe el usuario?
    $stmt = $conexion->prepare("SELECT id FROM loggin WHERE correo = ? LIMIT 1");
    $stmt->bind_param("s", $correo);
    $stmt->execute(); $stmt->store_result();

    if ($stmt->num_rows === 0) {
      $mensaje = 'Si el correo existe, recibirás instrucciones.'; // no revelar existencia
    } else {
      $stmt->close();
      $token   = bin2hex(random_bytes(32));
      $expira  = date('Y-m-d H:i:s', time() + 3600); // 1 hora
      $upd = $conexion->prepare("UPDATE loggin SET reset_token=?, reset_expires=? WHERE correo=? LIMIT 1");
      $upd->bind_param("sss", $token, $expira, $correo);
      $upd->execute(); $upd->close();

      // En producción: enviar por email. Por ahora mostramos el enlace:
      $link = "https://aerolineamexicana.rf.gd/restablecer.php?token=".$token;
      $mensaje = 'Generamos un enlace para restablecer tu contraseña (válido 1 hora).';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recuperar contraseña</title>
  <style>
    body{background:#F5F0E6;color:#0A174E;font-family:Arial,sans-serif;margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh}
    .card{background:#fff;width:min(520px,94vw);padding:24px;border-radius:18px;box-shadow:0 10px 25px rgba(0,0,0,.1)}
    h1{margin:0 0 14px;text-align:center}
    label{display:block;margin-top:10px;font-weight:bold}
    input{width:100%;padding:12px;border:2px solid #0A174E;border-radius:12px;margin-top:6px}
    button{width:100%;margin-top:16px;padding:12px;border:none;border-radius:12px;background:#0A174E;color:#fff;font-weight:700}
    .msg{margin-top:12px}
    a{color:#0A174E}
  </style>
</head>
<body>
  <div class="card">
    <h1>Recuperar contraseña</h1>
    <form method="post">
      <label>Correo</label>
      <input type="email" name="correo" required>
      <button type="submit">Generar enlace</button>
    </form>
    <?php if($mensaje): ?>
      <div class="msg"><?php echo htmlspecialchars($mensaje); ?></div>
      <?php if($link): ?>
        <p>Enlace (pruebas): <a href="<?php echo htmlspecialchars($link); ?>"><?php echo htmlspecialchars($link); ?></a></p>
      <?php endif; ?>
    <?php endif; ?>
    <p style="text-align:center;margin-top:10px;"><a href="loggin.html">Volver al inicio de sesión</a></p>
  </div>
</body>
</html>
