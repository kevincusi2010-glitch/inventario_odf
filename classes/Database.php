<?php
/**
 * Conexión única (patrón Singleton) a la base de datos MySQL del inventario ODF.
 *
 * Todos los modelos y APIs obtienen la conexión con Database::getInstance()->getConnection(),
 * de modo que una misma petición reutiliza una sola conexión PDO y, por lo tanto,
 * una única transacción (commit / rollBack) para el inventario y su bitácora.
 */
class Database {
    /** @var Database|null Instancia única de la clase. */
    private static $instance = null;

    /** @var PDO Conexión activa a MySQL. */
    private $pdo;
    
    private $host = '127.0.0.1';
    private $db   = 'inventario_odf';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4';

    /**
     * Constructor privado: obliga a obtener la instancia mediante getInstance().
     * Configura PDO con excepciones activas, fetch asociativo y prepares nativos
     * (sin emulación) para proteger las consultas frente a inyección SQL.
     */
    private function __construct() {
        require_once __DIR__ . '/../app/bootstrap.php';
        $this->pdo = \App\Core\DatabaseConnection::getSharedInstance();
    }

    /** Devuelve la instancia única de la conexión (la crea la primera vez). */
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /** Devuelve el objeto PDO compartido por toda la aplicación. */
    public function getConnection() {
        return $this->pdo;
    }
}
?>
