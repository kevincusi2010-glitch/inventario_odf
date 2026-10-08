<?php
require_once __DIR__ . '/Database.php';

/**
 * Modelo/DAO de la tabla Puerto (conectores del ODF).
 *
 * Concentra las reglas de negocio de la operación en campo: quién puede
 * modificar un puerto, cuándo queda bloqueado automáticamente y qué datos se
 * auditan. El "SELECT ... FOR UPDATE" obliga a que el llamador
 * (api/update_puerto.php) abra una transacción antes de invocar updateEstado().
 */
class Puerto {
    /** @var PDO Conexión compartida a la base de datos. */
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Cambia el estado de un puerto aplicando las reglas de negocio.
     *
     * @param int    $id_puerto   Conector que se va a modificar.
     * @param string $estado      'Sin Servicio' | 'En Servicio' | 'Reservado' | 'Malogrado'.
     * @param string $descripcion Descripción del servicio (cliente, orden de trabajo, ancho de banda).
     * @param int    $id_usuario  Usuario que ejecuta el cambio (queda registrado en la bitácora).
     * @param string $rol         'Administrador' | 'Supervisor' | 'Tecnico'.
     * @throws Exception 404 si el puerto no existe; 403 si el rol no tiene permisos.
     */
    public function updateEstado($id_puerto, $estado, $descripcion, $id_usuario, $rol) {
        $stmt = $this->pdo->prepare('SELECT * FROM Puerto WHERE id_puerto = ? FOR UPDATE');
        $stmt->execute([$id_puerto]);
        $puerto = $stmt->fetch();

        if (!$puerto) {
            throw new Exception("Puerto no encontrado", 404);
        }

        if ($puerto['bloqueado'] == 1 && $rol !== 'Administrador') {
            throw new Exception("El puerto está bloqueado y solo puede ser modificado por un Administrador", 403);
        }

        if ($rol === 'Tecnico') {
            if ($puerto['estado'] === 'En Servicio') {
                throw new Exception("Los técnicos no tienen permisos para modificar un puerto que ya se encuentra 'En Servicio'.", 403);
            }
        }

        $bloqueado = $puerto['bloqueado'];
        if ($estado === 'Malogrado') {
            $bloqueado = 1;
        } else if ($rol === 'Administrador') {
            $bloqueado = 0; 
        }

        $updateStmt = $this->pdo->prepare('UPDATE Puerto SET estado = ?, bloqueado = ?, modificado_por = ?, ultima_modificacion = NOW(), descripcion = ? WHERE id_puerto = ?');
        return $updateStmt->execute([$estado, $bloqueado, $id_usuario, $descripcion, $id_puerto]);
    }
}
?>
