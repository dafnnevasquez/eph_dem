<?php
// Incluir funciones globales de SIGEM-UV (conexión a BD, sesiones, etc.)
include "../funciones_sigemuv.php";

// ─────────────────────────────────────────────────────────────
// Detecta qué vista de Vue debe cargarse inicialmente.
// La vista puede venir de dos fuentes:
//   1. La URL path:  /__v2/modulo_eph/front/login
//   2. Query string: /__v2/modulo_eph/front/?view=login
// Se prioriza el path sobre el query string.
// Si no se detecta ninguna, carga 'login' por defecto.
// ─────────────────────────────────────────────────────────────
function detectRequestedView(): string
{
    // Leer el parámetro ?view= si existe
    $queryView = strtolower(trim((string)($_GET['view'] ?? '')));

    // Leer y limpiar la URL completa
    $requestPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $requestPath = strtolower(trim($requestPath, '/'));

    // Dividir la URL en segmentos separados por "/"
    // Ej: "/__v2/modulo_eph/front/login" → ['__v2', 'modulo_eph', 'front', 'login']
    $segments = array_values(array_filter(explode('/', $requestPath), 'strlen'));

    // Buscar el segmento "front" y tomar el siguiente como nombre de vista
    // Ej: [..., 'front', 'login'] → vista = 'login'
    $frontIndex = array_search('front', $segments, true);
    $pathView = '';
    if ($frontIndex !== false && isset($segments[$frontIndex + 1])) {
        $pathView = $segments[$frontIndex + 1];
    }

    // Si se encontró una vista en el path (y no es el propio index.php), usarla
    if ($pathView !== '' && $pathView !== 'index.php') {
        return $pathView;
    }

    // Si no hay vista en el path, intentar con el query string
    if ($queryView !== '') {
        return $queryView;
    }

    // Vista por defecto si no se detecta ninguna
    return 'login';
}

// Obtener la vista a renderizar
$view = detectRequestedView();

// ─────────────────────────────────────────────────────────────
// Rutas de los assets compilados (actualizar hash tras cada build)
// Ejecutar: npm run build
// ─────────────────────────────────────────────────────────────
define('BASE_URL',   '/__v2/modulo_eph/front');
define('ASSETS_URL', BASE_URL . '/assets');

// Archivos de entrada principal (hashes cambian con cada build)
define('JS_MAIN', ASSETS_URL . '/js/index-wTBAYPEG.js');
define('JS_ROUTER', ASSETS_URL . '/js/AppLayout-Bcqddd7d.js');
define('CSS_MAIN', ASSETS_URL . '/css/index-h2JvQ_ce.css');

// Chunk compartido de vue-router (preload para evitar waterfall)


// Detección de entorno
$isDev = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true);
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= BASE_URL ?>/favicon-sigem.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
    <title>EPHDEM | SIGEM-UV </title>
<?php if ($isDev): ?>
    <!-- Desarrollo: Vite dev server con HMR -->
    <script type="module" src="http://localhost:5173/@vite/client"></script>
    <script type="module" src="http://localhost:5173/src/main.ts"></script>
<?php else: ?>
    <!-- Producción: archivos compilados por "npm run build" -->
    <script type="module" crossorigin src="<?= JS_MAIN ?>"></script>
    <link rel="modulepreload" crossorigin href="<?= JS_ROUTER ?>">
    <link rel="stylesheet" crossorigin href="<?= CSS_MAIN ?>">
<?php endif; ?>
  </head>
  <body>
    <!--
      Punto de montaje de la aplicación Vue.
      El atributo data-view le dice a Vue qué vista mostrar al iniciar,
      sin necesidad de leer la URL nuevamente desde JavaScript.
    -->
    <div id="app" data-view="<?= htmlspecialchars($view, ENT_QUOTES, 'UTF-8') ?>"></div>
  </body>
</html>