<?php
/**
 * Front Controller / Enrutador Principal del Inventario ODF.
 *
 * Centraliza las peticiones públicas de la aplicación web, resuelve la ruta
 * solicitada y despacha la vista correspondiente garantizando el aislamiento
 * de los archivos de negocio en app/ y views/.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

// Cabeceras de seguridad HTTP (Security Headers)
if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("X-XSS-Protection: 1; mode=block");
    header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Extraer ruta normalizada considerando subdirectorios de XAMPP
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = str_replace('\\', '/', dirname($scriptName));
$projectDir = preg_replace('#/public$#', '', $scriptDir);

$route = $uriPath;
if (!empty($scriptDir) && $scriptDir !== '/' && str_starts_with($route, $scriptDir)) {
    $route = substr($route, strlen($scriptDir));
} elseif (!empty($projectDir) && $projectDir !== '/' && str_starts_with($route, $projectDir)) {
    $route = substr($route, strlen($projectDir));
}

if (str_starts_with($route, '/public')) {
    $route = substr($route, 7);
}

$route = trim($route, '/');

// Soporte para parámetros de consulta (?page=... o ?route=...)
if (empty($route) || $route === 'index.php') {
    $route = $_GET['page'] ?? $_GET['route'] ?? '';
    $route = trim($route, '/');
}

// Si la ruta sigue vacía o es index.php, es la página inicial / login
if (empty($route) || $route === 'index.php') {
    $route = 'login';
}

// Normalizar nombres con extensión .php
$routeKey = str_ends_with($route, '.php') ? substr($route, 0, -4) : $route;

// 2. Mapa de enrutamiento a vistas
$routes = [
    'login'                     => 'auth/login.php',
    'auth/login'                => 'auth/login.php',
    'dashboard'                 => 'dashboard/index.php',
    'admin/infraestructura'     => 'admin/infraestructura.php',
    'admin_infraestructura'     => 'admin/infraestructura.php',
    'admin/site_detalle'        => 'admin/site_detalle.php',
    'admin/site-detalle'        => 'admin/site_detalle.php',
    'admin_site_detalle'        => 'admin/site_detalle.php',
    'admin/usuarios'            => 'admin/usuarios.php',
    'admin_usuarios'            => 'admin/usuarios.php',
    'operaciones/asignar_hilos' => 'operaciones/asignar_hilos.php',
    'operaciones/asignar-hilos' => 'operaciones/asignar_hilos.php',
    'asignar_hilos'             => 'operaciones/asignar_hilos.php',
    'operaciones/estadisticas'  => 'operaciones/estadisticas.php',
    'estadisticas'              => 'operaciones/estadisticas.php',
];

// 3. Despacho dinámico de endpoints de API (/api/... y /health)
if (str_starts_with($routeKey, 'api/')) {
    $apiFile = __DIR__ . '/' . $routeKey . '.php';
    if (file_exists($apiFile)) {
        require $apiFile;
        exit;
    }
}
if ($routeKey === 'health') {
    require __DIR__ . '/api/health.php';
    exit;
}

// 4. Manejo de cierre de sesión
if ($routeKey === 'logout') {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    header('Location: index.php');
    exit;
}

// 4. Despacho de la vista
$viewsBaseDir = dirname(__DIR__) . '/views/';

if (isset($routes[$routeKey])) {
    $viewFile = $viewsBaseDir . $routes[$routeKey];
    if (file_exists($viewFile)) {
        require $viewFile;
        exit;
    }
}

// 5. Ruta no encontrada (404 Fallback)
if (isset($_SESSION['usuario'])) {
    header('Location: dashboard.php');
    exit;
}

require $viewsBaseDir . 'auth/login.php';
exit;
