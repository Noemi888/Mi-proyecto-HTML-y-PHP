<?php
// eliminar_reserva.php (borra la ÚLTIMA reserva del usuario logueado)
ini_set('display_errors','1'); ini_set('display_startup_errors','1'); error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

if (!isset($_SESSION['correo'])) { header("Location: loggin.html"); exit; }
$correo = $_SESSION['correo'];

require_once __DIR__ . '/config_db.php';

// MySQL permite ORDER BY ... LIMIT en DELETE
$del = $conexion->prepare("DELETE FROM base1 
                           WHERE correo=? 
                           ORDER BY fecha1 DESC 
                           LIMIT 1");
$del->bind_param("s", $correo);
$del->execute();
$afectadas = $del->affected_rows;
$del->close();

if ($afectadas < 1) {
  header("Location: mostrar.php?err=no_encontrado");
} else {
  header("Location: mostrar.php?ok=vuelo_eliminado");
}
exit;
