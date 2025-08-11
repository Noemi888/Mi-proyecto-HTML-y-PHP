<?php
// registro.php
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: registro.html');
  exit;
}

require_once __DIR__ . '/config_db.php'; // $conexion

// === Captura segura ===
$nombre     = trim($_POST['nombre']     ?? '');
$apellido   = trim($_POST['apellido']   ?? '');
$fechadn    = trim($_POST['fechadn']    ?? '');
$correo     = trim($_POST['correo']     ?? '');
$numerotel  = trim($_POST['numerotel']  ?? '');
$contrasena =        $_POST['contrasena'] ?? '';

// Recortes por si el navegador no limita longitud
$nombre     = mb_substr($nombre, 0, 100);
$apellido   = mb_substr($apellido, 0, 100);
$correo     = mb_substr($correo, 0, 100);
$numerotel  = mb_substr($numerotel, 0, 20);

// === Validaciones ===
if ($nombre==='' || $apellido==='' || $fechadn==='' || $correo==='' || $numerotel==='' || $contrasena==='') {
  echo "<script>alert('Completa todos los campos.'); history.back();</script>"; exit;
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
  echo "<script>alert('Correo inválido'); history.back();</script>"; exit;
}
// Fecha válida (YYYY-MM-DD)
$dt = DateTime::createFromFormat('Y-m-d', $fechadn);
if (!$dt || $dt->format('Y-m-d') !== $fechadn) {
  echo "<script>alert('Fecha de nacimiento inválida'); history.back();</script>"; exit;
}
// Contraseña mínima
if (strlen($contrasena) < 6) {
  echo "<script>alert('La contraseña debe tener al menos 6 caracteres.'); history.back();</script>"; exit;
}

// Normaliza teléfono para verificar duplicados (solo dígitos)
$telNorm = preg_replace('/\D+/', '', $numerotel);

// === ¿Correo o teléfono ya existen? ===
$sqlDup = "SELECT id FROM loggin
           WHERE LOWER(correo)=LOWER(?)
              OR REPLACE(REPLACE(REPLACE(REPLACE(numerotel,' ',''),'-',''),'(',''),')','') = ?
           LIMIT 1";
$st = $conexion->prepare($sqlDup);
$st->bind_param("ss", $correo, $telNorm);
$st->execute();
$st->store_result();
if ($st->num_rows > 0) {
  $st->close();
  echo "<script>alert('Ya existe una cuenta con ese correo o teléfono.'); history.back();</script>"; exit;
}
$st->close();

// === Insertar usuario (guardando el teléfono tal como lo escribió) ===
$hash = password_hash($contrasena, PASSWORD_DEFAULT);
$sqlIns = "INSERT INTO loggin (nombre, apellido, fechadn, correo, numerotel, contrasena)
           VALUES (?,?,?,?,?,?)";
$ins = $conexion->prepare($sqlIns);
$ins->bind_param("ssssss", $nombre, $apellido, $fechadn, $correo, $numerotel, $hash);
$ins->execute();
$ins->close();

// === Login automático y redirección a aeropuerto ===
session_regenerate_id(true);
$_SESSION['usuario'] = $correo;                   // aeropuerto.php usa el correo aquí
$_SESSION['correo']  = $correo;
$_SESSION['nombre']  = trim("$nombre $apellido");

header("Location: aeropuerto.php");
exit;
