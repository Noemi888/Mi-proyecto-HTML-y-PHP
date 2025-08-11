<?php
// diag_login.php — Ejecuta, copia el resultado y luego BORRA este archivo.
ini_set('display_errors','1'); error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require __DIR__ . '/config_db.php';

$EMAIL = 'tu_correo@ejemplo.com';   // <-- CAMBIA ESTO
$PASS  = 'TuContraseña';            // <-- CAMBIA ESTO

$out = [];

try {
  // Info de conexión y BD activa
  $q = $conexion->query("SELECT DATABASE() db, USER() user, @@hostname host");
  $out[] = ['conexion' => $q->fetch_assoc()];

  // ¿Fila del usuario?
  $st = $conexion->prepare("
    SELECT id, correo,
           CHAR_LENGTH(contrasena) AS len,
           contrasena,
           CHAR_LENGTH(TRIM(contrasena)) AS len_trim
    FROM loggin WHERE LOWER(correo)=LOWER(?) LIMIT 1
  ");
  $st->bind_param("s", $EMAIL);
  $st->execute();
  $u = $st->get_result()->fetch_assoc();
  $st->close();

  if (!$u) {
    $out[] = ['usuario_encontrado' => false];
  } else {
    $hash = $u['contrasena'] ?? '';
    $isHash = (bool)preg_match('/^\$(2[aby]|argon2)/i', $hash);
    $verify_hash  = $isHash ? password_verify($PASS, $hash) : null;
    $verify_plain = !$isHash ? hash_equals((string)$hash, (string)$PASS) : null;

    // Muestra solo primeros 12 caracteres para inspección (no completo)
    $mask = substr($hash, 0, 12) . '...';

    $out[] = [
      'usuario_encontrado' => true,
      'id' => (int)$u['id'],
      'correo' => $u['correo'],
      'len_contrasena' => (int)$u['len'],
      'len_contrasena_trim' => (int)$u['len_trim'],
      'contrasena_inicio' => $mask,
      'es_hash' => $isHash ? 'SI':'NO',
      'password_verify' => $verify_hash,
      'igual_texto_plano' => $verify_plain,
      'sugerencia' => 'Si len<60 y es_hash=SI, la columna probablemente truncó el hash. Usa VARCHAR(255).'
    ];
  }
} catch (Throwable $e) {
  $out[] = ['error' => $e->getMessage()];
}

// Salida legible
header('Content-Type: text/plain; charset=utf-8');
echo "=== DIAGNÓSTICO LOGIN ===\n";
foreach ($out as $b) { print_r($b); echo "\n"; }
