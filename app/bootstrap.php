<?php
/**
 * Bootstrap general de la aplicación Inventario ODF.
 *
 * Inicializa el autoloader PSR-4, la carga de configuración .env
 * y el capturador global de errores para resiliencia SRE.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\ErrorHandler;

// 1. Cargar variables de entorno
Env::load();

// 2. Registrar capturador global de excepciones y errores
ErrorHandler::register();

// 3. Alias globales de compatibilidad y servicios transversales
class_alias(\App\Core\Csrf::class, 'Csrf');
class_alias(\App\Core\DatabaseConnection::class, 'Database');
class_alias(\App\Services\UsuarioService::class, 'Usuario');
class_alias(\App\Services\InfraestructuraService::class, 'Infraestructura');

// 4. Endurecimiento de seguridad en cookies de sesión (SameSite=Lax, HttpOnly=true, StrictMode)
if (PHP_SAPI !== 'cli') {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    if ($isSecure) {
        ini_set('session.cookie_secure', '1');
    }
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}
