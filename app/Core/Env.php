<?php

namespace App\Core;

/**
 * Gestor de variables de entorno (.env).
 *
 * Carga pares clave-valor de configuración sin requerir librerías externas,
 * garantizando portabilidad y eliminando credenciales quemadas en el código fuente.
 */
class Env
{
    private static bool $loaded = false;

    /**
     * Carga el archivo .env en las superglobales $_ENV, $_SERVER y getenv().
     */
    public static function load(?string $filePath = null): void
    {
        if ($filePath === null) {
            $filePath = dirname(__DIR__, 2) . '/.env';
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            // Ignorar comentarios y líneas vacías
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Eliminar comillas envolventes si existen
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("$key=$value");
            }
        }

        self::$loaded = true;
    }

    /**
     * Obtiene el valor de una variable de entorno con soporte para valores por defecto y tipado booleano.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string)$value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}
