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
