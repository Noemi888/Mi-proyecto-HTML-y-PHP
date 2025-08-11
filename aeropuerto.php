<?php
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();

// Redirigir si no hay sesión iniciada
if (!isset($_SESSION['usuario'])) {
    header("Location: loggin.html");
    exit();
}

// Datos de sesión
$cliente      = $_SESSION['usuario'];                   // En tu flujo clásico, 'usuario' es el correo
$correoSesion = $_SESSION['usuario'];
$nombreSesion = $_SESSION['nombre'] ?? $correoSesion;

// Conexión a BD
require_once __DIR__ . '/config_db.php';

// Obtener teléfono del usuario (si existe)
$numerotel = null;
try {
    $q = $conexion->prepare("SELECT numerotel FROM loggin WHERE correo = ? LIMIT 1");
    $q->bind_param("s", $correoSesion);
    $q->execute();
    $resTel = $q->get_result();
    if ($rowTel = $resTel->fetch_assoc()) {
        $numerotel = $rowTel['numerotel'] ?? null;
    }
    $q->close();
} catch (Throwable $e) {
    $numerotel = null;
}

// Variables para repintar el formulario tras errores
$origen     = '';
$destino    = '';
$fecha1     = '';
$pasajeros  = 0;
$edadesPOST = [];

$errores = [];

// Procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $origen    = trim($_POST['origen'] ?? '');
    $destino   = trim($_POST['destino'] ?? '');
    $fecha1    = trim($_POST['fecha1'] ?? '');
    $pasajeros = isset($_POST['pasajeros']) ? (int)$_POST['pasajeros'] : 0;

    // Edades: recibimos arreglo y lo convertimos a CSV
    $edadCSV = '';
    if (isset($_POST['edades']) && is_array($_POST['edades'])) {
        $edadesLimpias = [];
        foreach ($_POST['edades'] as $ed) {
            $ed = (int)$ed;
            if ($ed < 0)   $ed = 0;
            if ($ed > 120) $ed = 120;
            $edadesLimpias[] = (string)$ed;
        }
        $edadesPOST = $edadesLimpias; // para repintar si hay error
        $edadCSV    = implode(',', $edadesLimpias);
    }

    // Validaciones
    if ($origen === '' || $destino === '') {
        $errores[] = "Selecciona origen y destino.";
    } elseif ($origen === $destino) {
        $errores[] = "El origen y el destino no pueden ser iguales.";
    }

    if ($fecha1 === '') {
        $errores[] = "Selecciona la fecha de partida.";
    } else {
        $hoy = date('Y-m-d');
        if ($fecha1 < $hoy) {
            $errores[] = "La fecha de partida no puede ser en el pasado.";
        }
    }

    if ($pasajeros < 1 || $pasajeros > 9) {
        $errores[] = "Selecciona una cantidad válida de pasajeros (1 a 9).";
    }

    if (!isset($_POST['edades']) || !is_array($_POST['edades']) || count($_POST['edades']) !== $pasajeros) {
        $errores[] = "Debes indicar la edad de cada pasajero.";
    }

    // Insertar y redirigir
    if (empty($errores)) {
        // Precio simple: 1200 + 350 por pasajero (ajústalo si quieres)
        $precio = 1200 + (350 * $pasajeros);
        $precio = (float)$precio;

        $stmt = $conexion->prepare(
            "INSERT INTO base1 (cliente, correo, numerotel, origen, destino, pasajeros, edad, fecha1, precio)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        // Tipos correctos (9 columnas): sssss i s s d  => "sssssissd"
        $types = "sssssissd";
        $stmt->bind_param(
            $types,
            $cliente,       // cliente (s)
            $correoSesion,  // correo  (s)
            $numerotel,     // numerotel (s, puede ser null/empty)
            $origen,        // origen (s)
            $destino,       // destino (s)
            $pasajeros,     // pasajeros (i)
            $edadCSV,       // edad (s) - CSV de edades
            $fecha1,        // fecha1 (s) - YYYY-mm-dd
            $precio         // precio (d)
        );
        $stmt->execute();
        $stmt->close();

        header("Location: mostrar.php");
        exit();
    }
}

// Listado de estados para no repetir opciones
$estados = [
  "aguascalientes" => "Aguascalientes",
  "baja_california" => "Baja California",
  "baja_california_sur" => "Baja California Sur",
  "campeche" => "Campeche",
  "cdmx" => "Ciudad de México",
  "coahuila" => "Coahuila",
  "colima" => "Colima",
  "chiapas" => "Chiapas",
  "chihuahua" => "Chihuahua",
  "durango" => "Durango",
  "guanajuato" => "Guanajuato",
  "guerrero" => "Guerrero",
  "hidalgo" => "Hidalgo",
  "jalisco" => "Jalisco",
  "mexico" => "México",
  "michoacan" => "Michoacán",
  "morelos" => "Morelos",
  "nayarit" => "Nayarit",
  "nuevo_leon" => "Nuevo León",
  "oaxaca" => "Oaxaca",
  "puebla" => "Puebla",
  "queretaro" => "Querétaro",
  "quintana_roo" => "Quintana Roo",
  "san_luis_potosi" => "San Luis Potosí",
  "sinaloa" => "Sinaloa",
  "sonora" => "Sonora",
  "tabasco" => "Tabasco",
  "tamaulipas" => "Tamaulipas",
  "tlaxcala" => "Tlaxcala",
  "veracruz" => "Veracruz",
  "yucatan" => "Yucatán",
  "zacatecas" => "Zacatecas"
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reserva de Vuelos</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #F5F0E6;
      color: #0A174E;
      margin: 0;
      padding: 0;
    }
    header {
      background-color: #F5F0E6;
      color: #0A174E;
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      display:flex; align-items:center; gap:16px;
    }
    .header-content { display:flex; align-items:center; width:100%; gap:15px; }
    .logo { width: 50px; height: auto; }
    .header-right { margin-left:auto; font-weight:bold; }

    .menu { background-color: #333; }
    .menu button {
      background-color: #333; color: white; border: none;
      padding: 15px 20px; font-size: 18px; cursor: pointer; width: 100%; text-align: left;
    }
    .menu button:hover { background-color: #444; }
    .menu-items { display: none; flex-direction: column; background-color: #000; }
    .menu-items a { color: white; padding: 12px 20px; text-decoration: none; }
    .menu-items a:hover { background-color: #555; }

    .container {
      max-width: 900px;
      margin: 20px auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    h2 { color: #0A174E; }
    label { display: block; margin-top: 10px; font-weight: bold; }

    input, select {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    button[type="submit"] {
      margin-top: 15px;
      background-color: #0A174E;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    button[type="submit"]:hover { background-color: #08123b; }

    .tabla1 { width: 100%; border-collapse: collapse; border: 2px solid #333; margin: 20px 0; }
    .tabla1 td { border: 1px solid #999; text-align: center; vertical-align: middle; width: 20%; }
    .tabla1 img { max-width: 100%; height: auto; object-fit: contain; }

    footer {
      background-color: #1c3738; color: #F5F0E6; padding: 30px 20px; text-align: center;
    }
    footer a { color: #F5F0E6; text-decoration: none; margin-right: 15px; }
    footer a:hover { text-decoration: underline; }

    .errores { background:#ffe9e9; border:1px solid #f5b5b5; color:#7a0000; padding:12px; border-radius:8px; margin-bottom:16px; }

    @media (max-width: 600px) {
      .header-content { flex-direction: column; gap: 10px; align-items:flex-start; }
      .header-right { margin-left:0; }
    }
  </style>
</head>
<body>
<header>
  <div class="header-content">
    <img class="logo" src="Avion.jpg" alt="Logo Avión">
    <h1 style="margin:0">Aerolínea Mexicana</h1>
    <div class="header-right">Bienvenido, <?php echo htmlspecialchars($nombreSesion); ?></div>
  </div>
</header>

<div class="menu">
  <button onclick="toggleMenu()">☰ Menú</button>
  <div class="menu-items" id="menuItems">
    <a href="aeropuerto.php">Inicio</a>
    <a href="mostrar.php">Mis datos</a>
    <a href="ofertas.html">Ofertas Especiales</a>
    <a href="ayuda.html">Ayuda</a>
    <a href="logout.php">Cerrar Sesión</a>
  </div>
</div>

<div class="container">
  <h2>Buscar Vuelo</h2>

  <?php if (!empty($errores)): ?>
    <div class="errores">
      <ul style="margin:0; padding-left:18px">
        <?php foreach ($errores as $e): ?>
          <li><?php echo htmlspecialchars($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <!-- Origen -->
    <label for="origen">Origen:</label>
    <select id="origen" name="origen" required>
      <option value="">Selecciona origen</option>
      <?php foreach ($estados as $val => $label): ?>
        <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($origen === $val ? 'selected' : ''); ?>>
          <?php echo htmlspecialchars($label); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Destino -->
    <label for="destino">Destino:</label>
    <select id="destino" name="destino" required>
      <option value="">Selecciona destino</option>
      <?php foreach ($estados as $val => $label): ?>
        <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($destino === $val ? 'selected' : ''); ?>>
          <?php echo htmlspecialchars($label); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Pasajeros -->
    <label for="pasajeros">Pasajeros:</label>
    <select id="pasajeros" name="pasajeros" required>
      <option value="">Selecciona el número de pasajeros</option>
      <?php for ($i=1; $i<=9; $i++): ?>
        <option value="<?php echo $i; ?>" <?php echo ($pasajeros === $i ? 'selected' : ''); ?>><?php echo $i; ?></option>
      <?php endfor; ?>
    </select>

    <!-- Edades dinámicas -->
    <div id="contenedorEdades"></div>

    <!-- Fecha -->
    <label for="fecha1">Fecha de partida:</label>
    <input type="date" id="fecha1" name="fecha1" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($fecha1); ?>">

    <button type="submit">Buscar</button>
  </form>
</div>

<table class="tabla1">
  <tr>
    <td><img src="im1.jpg" alt="Imagen 1"></td>
    <td><img src="im2.jpeg" alt="Imagen 2"></td>
    <td><img src="im3.jpeg" alt="Imagen 3"></td>
    <td><img src="im4.jpeg" alt="Imagen 4"></td>
    <td><img src="im5.jpeg" alt="Imagen 5"></td>
  </tr>
</table>

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

<script>
  function toggleMenu() {
    const menu = document.getElementById("menuItems");
    menu.style.display = (menu.style.display === "flex") ? "none" : "flex";
  }

  const selPasajeros = document.getElementById('pasajeros');
  const contEdades   = document.getElementById('contenedorEdades');

  function renderEdades(n, valores) {
    contEdades.innerHTML = '';
    for (let i = 1; i <= n; i++) {
      const label = document.createElement('label');
      label.textContent = 'Edad de la persona ' + i + ':';
      const input = document.createElement('input');
      input.type = 'number';
      input.name = 'edades[]';
      input.min = '0';
      input.max = '120';
      input.required = true;
      if (Array.isArray(valores) && typeof valores[i-1] !== 'undefined') {
        input.value = valores[i-1];
      }
      contEdades.appendChild(label);
      contEdades.appendChild(input);
    }
  }

  selPasajeros.addEventListener('change', function() {
    const n = parseInt(this.value || '0', 10);
    if (n > 0) renderEdades(n); else contEdades.innerHTML = '';
  });

  // Repintar si venimos de un error en POST
  <?php if ($pasajeros > 0): ?>
    renderEdades(<?php echo (int)$pasajeros; ?>, <?php echo json_encode($edadesPOST, JSON_UNESCAPED_UNICODE); ?>);
  <?php endif; ?>
</script>

</body>
</html>
