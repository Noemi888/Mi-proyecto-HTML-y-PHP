<?php
// config_db.php

// Mostrar errores (desactívalo en producción)
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('America/Mexico_City');

/* ====== CREDENCIALES (InfinityFree) ====== */
$DB_HOST = "sql308.infinityfree.com";
$DB_USER = "if0_39478473";
$DB_PASS = "6HTBDDoPra";              // <- tu contraseña del panel
$DB_NAME = "if0_39478473_loggin";     // tu base

/* ====== CONEXIÓN ====== */
try {
  $conexion = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
  $conexion->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
  exit("Error de conexión a MySQL: " . $e->getMessage());
}
