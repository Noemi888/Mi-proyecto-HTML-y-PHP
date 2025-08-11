<?php
// guardar_vuelo.php
ini_set('display_errors','1'); ini_set('display_startup_errors','1'); error_reporting(E_ALL);
session_start();
require_once 'config.php';

if (!isset($_SESSION['uid'])) { header("Location: loggin.html"); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: aeropuerto.php'); exit; }

$user_id   = (int)$_SESSION['uid'];
$nombre    = $_SESSION['usuario'];
$correo    = $_SESSION['correo'];
$tel       = $_SESSION['tel'];

$origen    = $_POST['origen']   ?? '';
$destino   = $_POST['destino']  ?? '';
$pasajeros = (int)($_POST['pasajeros'] ?? 0);
$edadesArr = $_POST['edad'] ?? [];
$edad      = is_array($edadesArr) ? implode(',', array_map('intval', $edadesArr))
                                  : (string)$edadesArr;
$fecha1    = $_POST['fecha1']   ?? null;
$precio    = isset($_POST['precio']) ? (float)$_POST['precio'] : 0;

if ($origen==='' || $destino==='' || $pasajeros<=0 || !$fecha1) {
  echo "<script>alert('Completa origen, destino, pasajeros y fecha.'); history.back();</script>"; exit;
}

$cn = db();

$sql = "INSERT INTO base1
        (user_id, nombre, correo, numerotel, origen, destino, pasajeros, edad, fecha1, precio)
        VALUES (?,?,?,?,?,?,?,?,?,?)";
$stmt = $cn->prepare($sql);
$stmt->bind_param("isssssissd",
  $user_id, $nombre, $correo, $tel, $origen, $destino, $pasajeros, $edad, $fecha1, $precio
);
$stmt->execute();

echo "<script>alert('Vuelo guardado.'); window.location='mostrar.php';</script>";
