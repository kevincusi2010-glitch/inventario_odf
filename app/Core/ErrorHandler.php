<?php

namespace App\Core;

use Throwable;
use ErrorException;

/**
 * Capturador global de errores y excepciones del sistema (Resiliencia SRE).
 *
 * Garantiza que ninguna excepción no controlada quede sin registrar ni exponga
 * trazas internas al usuario final, manteniendo la disponibilidad y telemetría.
 */
class ErrorHandler
{
    private static ?string $logFile = null;

    /**
     * Registra los manejadores de excepciones, errores y parada fatal de PHP.
     */
    public static function register(?string $logPath = null): void
    {
        if ($logPath === null) {
            $logDir = dirname(__DIR__, 2) . '/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            self::$logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
        } else {
            self::$logFile = $logPath;
        }

        // Convertir advertencias y noticias de PHP en ErrorException
        set_error_handler([self::class, 'handleError']);

        // Capturar excepciones no atrapadas
        set_exception_handler([self::class, 'handleException']);

        // Capturar errores fatales en el shutdown
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Convierte errores nativos de PHP en excepciones.
     */
    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
        return false;
    }

    /**
     * Procesa y registra cualquier excepción no atrapada.
     */
    public static function handleException(Throwable $e): void
    {
        self::log($e);

        $isJson = self::isJsonRequest();
        $code = (int)$e->getCode();
        $httpStatus = ($code >= 400 && $code < 600) ? $code : 500;

        http_response_code($httpStatus);

        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => Env::get('APP_DEBUG', false) ? $e->getMessage() : 'Ocurrió un error inesperado en el servidor.',
                'code' => $httpStatus
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $debug = (bool)Env::get('APP_DEBUG', false);
            self::renderErrorHtml($e, $debug, $httpStatus);
        }
    }

    /**
     * Captura errores fatales de fin de script (E_ERROR, E_PARSE, E_CORE_ERROR).
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $exception = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            self::handleException($exception);
        }
    }

    /**
     * Escribe la traza del error en el archivo de logs.
     */
    public static function log(Throwable $e): void
    {
        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $targetFile = self::$logFile ?? ($logDir . '/app-' . date('Y-m-d') . '.log');

        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? 'CLI';
        $userId = $_SESSION['usuario']['id_usuario'] ?? 'unauthenticated';

        $entry = sprintf(
            "[%s] [%s] [%s %s] [User:%s] [IP:%s] %s in %s on line %d\nStack Trace:\n%s\n%s\n",
            $timestamp,
            get_class($e),
            $method,
            $uri,
            $userId,
            $ip,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
            str_repeat('-', 80)
        );

        @file_put_contents($targetFile, $entry, FILE_APPEND | LOCK_EX);
    }

    private static function isJsonRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || str_contains($uri, '/api/');
    }

    private static function renderErrorHtml(Throwable $e, bool $debug, int $status): void
    {
        $title = $status === 404 ? 'Recurso No Encontrado' : 'Error en la Aplicación';
        $message = $debug ? htmlspecialchars($e->getMessage()) : 'Se ha presentado un inconveniente en el sistema. El incidente ha sido registrado por el equipo técnico.';
        $trace = $debug ? '<pre class="bg-dark text-light p-3 rounded mt-3" style="font-size:0.8rem; overflow:auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>' : '';

        echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{$title} - ODF Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container text-center" style="max-width: 600px;">
        <div class="card shadow-sm border-0 rounded-4 p-4">
            <h1 class="display-6 fw-bold text-danger mb-3">{$title}</h1>
            <p class="text-secondary">{$message}</p>
            {$trace}
            <div class="mt-4">
                <a href="dashboard.php" class="btn btn-primary px-4 rounded-pill">Volver al Inicio</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
