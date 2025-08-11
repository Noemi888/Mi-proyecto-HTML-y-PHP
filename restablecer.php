<?php
session_start();
require __DIR__ . '/config_db.php';

$token = trim($_GET['token'] ?? '');
$valido = false;

if ($token) {
  $now = date('Y-m-d H:i:s');
  $stmt = $conexion->prepare("SELECT correo FROM loggin WHERE reset_token=? AND reset_expires > ? LIMIT 1");
  $stmt->bind_param("ss", $token, $now);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && $res->num_rows === 1) {
    $row = $res->fetch_assoc();
    $correo = $row['correo'];
    $valido = true;
  }
  $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['token'] ?? '';
  $pass1 = $_POST['pass1'] ?? '';
  $pass2 = $_POST['pass2'] ?? '';
  if ($pass1 !== $pass2 || strlen($pass1) < 6) {
    $error = "Las contraseñas no coinciden o son muy cortas (mínimo 6).";
  } else {
    $now = date('Y-m-d H:i:s');
    $stmt = $conexion->prepare("SELECT correo FROM loggin WHERE reset_token=? AND reset_expires > ? LIMIT 1");
    $stmt->bind_param("ss", $token, $now);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
      $row = $res->fetch_assoc();
      $correo = $row['correo'];
      $stmt->close();

      $hash = password_hash($pass1, PASSWORD_DEFAULT);
      $upd = $conexion->prepare("UPDATE loggin SET contrasena=?, reset_token=NULL, reset_expires=NULL WHERE correo=? LIMIT 1");
      $upd->bind_param("ss", $hash, $correo);
      $upd->execute(); $upd->close();

      echo "<script>alert('Contraseña actualizada.'); window.location='loggin.html';</script>";
      exit;
    } else {
      $error = "El enlace no es válido o ha expirado.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Restablecer contraseña</title>
  <style>
    body{background:#F5F0E6;color:#0A174E;font-family:Arial,sans-serif;margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh}
    .card{background:#fff;width:min(520px,94vw);padding:24px;border-radius:18px;box-shadow:0 10px 25px rgba(0,0,0,.1)}
    h1{margin:0 0 14px;text-align:center}
    label{display:block;margin-top:10px;font-weight:bold}
    input{width:100%;padding:12px;border:2px solid #0A174E;border-radius:12px;margin-top:6px}
    button{width:100%;margin-top:16px;padding:12px;border:none;border-radius:12px;background:#0A174E;color:#fff;font-weight:700}
    .error{margin-top:10px;color:#8b0000;background:#ffe9e9;border:1px solid #f5b5b5;padding:10px;border-radius:10px}
    a{color:#0A174E}
  </style>
</head>
<body>
  <div class="card">
    <h1>Restablecer contraseña</h1>
    <?php if(!$token || !$valido): ?>
      <p class="error">El enlace no es válido o ha expirado.</p>
      <p><a href="recuperar.php">Generar uno nuevo</a></p>
    <?php else: ?>
      <?php if(!empty($error)): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <label>Nueva contraseña</label>
        <input type="password" name="pass1" required>
        <label>Repite la contraseña</label>
        <input type="password" name="pass2" required>
        <button type="submit">Guardar</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
