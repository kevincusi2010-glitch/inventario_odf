<?php

namespace App\Core;

use PDO;
use PDOException;
use Throwable;

/**
 * Conexión resiliente a MySQL con política de reintentos automáticos (Retry Policy).
 *
 * Absorbe micro-cortes de red o latencia de inicio en el socket MySQL,
 * configura PDO bajo estándares seguros y permite inyección en los DAOs.
 */
class DatabaseConnection
{
    private static ?PDO $sharedPdo = null;

    /**
     * Crea una nueva conexión PDO aplicando reintentos si la primera falla.
     *
     * @param array|null $customConfig Sobrescribe parámetros para pruebas o conexiones alternas.
     * @throws PDOException Si se agotan los reintentos permitidos.
     */
    public static function create(?array $customConfig = null): PDO
    {
        $host       = $customConfig['host'] ?? Env::get('DB_HOST', '127.0.0.1');
        $port       = $customConfig['port'] ?? Env::get('DB_PORT', 3306);
        $database   = $customConfig['database'] ?? Env::get('DB_DATABASE', 'inventario_odf');
        $username   = $customConfig['username'] ?? Env::get('DB_USERNAME', 'root');
        $password   = $customConfig['password'] ?? Env::get('DB_PASSWORD', '');
        $charset    = $customConfig['charset'] ?? Env::get('DB_CHARSET', 'utf8mb4');
        $maxRetries = (int)($customConfig['max_retries'] ?? Env::get('DB_MAX_RETRIES', 3));
        $delayMs    = (int)($customConfig['retry_delay_ms'] ?? Env::get('DB_RETRY_DELAY_MS', 200));

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                return new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                $lastException = $e;
                if ($attempt < $maxRetries) {
                    // Pausa progresiva entre reintentos (backoff)
                    usleep($delayMs * 1000 * $attempt);
                }
            }
        }

        throw new PDOException(
            sprintf("Fallo de conexión a la base de datos tras %d intentos: %s", $maxRetries, $lastException?->getMessage()),
            (int)($lastException?->getCode() ?? 0),
            $lastException
        );
    }

    /**
     * Devuelve una instancia compartida para compatibilidad de transición.
     */
    public static function getSharedInstance(): PDO
    {
        if (self::$sharedPdo === null) {
            self::$sharedPdo = self::create();
        }
        return self::$sharedPdo;
    }

    /**
     * Ejecuta una acción dentro de una transacción segura con commit y rollback automático.
     */
    public static function transaction(PDO $pdo, callable $callback): mixed
    {
        $isExistingTransaction = $pdo->inTransaction();

        if (!$isExistingTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback($pdo);

            if (!$isExistingTransaction) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if (!$isExistingTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
