<?php
require_once __DIR__ . '/Database.php';

/**
 * Modelo/DAO de la tabla Usuario.
 *
 * Centraliza la autenticación, el alta y edición de cuentas, el cambio de
 * contraseña y la eliminación segura de usuarios. Las contraseñas nunca se
 * guardan en texto plano: se almacenan con password_hash() (BCrypt).
 */
class Usuario {
    private $pdo;

    /** Roles válidos del sistema (debe coincidir con el ENUM de la tabla Usuario). */
    const ROLES_VALIDOS = ['Administrador', 'Tecnico', 'Supervisor'];
    public $id_usuario;

    public $nombre;
    public $correo;
    public $rol;
    public $activo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /** Lista todas las cuentas: primero las activas y luego por nombre. */
    public function getAllUsuarios() {
        $stmt = $this->pdo->query("SELECT * FROM Usuario ORDER BY activo DESC, nombre ASC");
        return $stmt->fetchAll();
    }

    /**
     * Verifica las credenciales de un usuario activo.
     *
     * @return array|false Datos del usuario si la contraseña es correcta; false en caso contrario.
     */
    public function authenticate($correo, $password) {
        $stmt = $this->pdo->prepare('SELECT * FROM Usuario WHERE correo = ? AND activo = 1');
        $stmt->execute([$correo]);
        $user = $stmt->fetch();

        if ($user) {
            $isValid = false;
            if (password_verify($password, $user['contrasena_hash'])) {
                $isValid = true;
            } elseif ($password === $user['contrasena_hash']) {
                $isValid = true;
                // Compatibilidad con instalaciones antiguas: si el hash guardado
                // venía en texto plano, se acepta una sola vez y se re-cifra.
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $this->pdo->prepare('UPDATE Usuario SET contrasena_hash = ? WHERE id_usuario = ?');
                $update->execute([$newHash, $user['id_usuario']]);
            }

            if ($isValid) {
                return $user;
            }
        }
        return false;
    }

    /** Crea una cuenta con contraseña provisional (debe cambiarla al ingresar). */
    public function createUsuario($nombre, $correo, $rol) {
        if (!in_array($rol, self::ROLES_VALIDOS, true)) {
            throw new Exception("El rol seleccionado no es válido.");
        }

        $stmtCheck = $this->pdo->prepare('SELECT COUNT(*) FROM Usuario WHERE correo = ?');

        $stmtCheck->execute([$correo]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception("El correo electrónico ya está registrado.");
        }

        $password = password_hash('telefonica123', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('INSERT INTO Usuario (nombre, correo, contrasena_hash, rol, activo) VALUES (?, ?, ?, ?, 1)');
        return $stmt->execute([$nombre, $correo, $password, $rol]);
    }

    /** Actualiza nombre, correo y rol; valida que el correo no lo use otro usuario. */
    public function updateUsuario($id_usuario, $nombre, $correo, $rol) {
        if (!in_array($rol, self::ROLES_VALIDOS, true)) {
            throw new Exception("El rol seleccionado no es válido.");
        }

        $stmtCheck = $this->pdo->prepare('SELECT COUNT(*) FROM Usuario WHERE correo = ? AND id_usuario != ?');

        $stmtCheck->execute([$correo, $id_usuario]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception("El correo electrónico ya pertenece a otro usuario.");
        }

        $stmt = $this->pdo->prepare('UPDATE Usuario SET nombre = ?, correo = ?, rol = ? WHERE id_usuario = ?');
        return $stmt->execute([$nombre, $correo, $rol, $id_usuario]);
    }

    /** Activa (1) o desactiva (0) una cuenta sin borrarla. */
    public function changeStatus($id_usuario, $status) {
        $stmt = $this->pdo->prepare('UPDATE Usuario SET activo = ? WHERE id_usuario = ?');
        return $stmt->execute([$status, $id_usuario]);
    }

    /**
     * Elimina definitivamente un usuario de la base de datos (DELETE real en la tabla Usuario).
     *
     * Antes de borrar libera las referencias que otras tablas mantienen hacia el usuario
     * (Puerto.modificado_por e HistorialCambiosPuerto.id_usuario). Así la eliminación nunca
     * falla por restricciones de clave foránea y se conserva el inventario y su historial.
     *
     * Salvaguardas:
     *  - No se puede eliminar al último Administrador del sistema.
     *  - No se puede eliminar un usuario inexistente.
     */
    public function deleteUsuario($id_usuario) {
        $stmt = $this->pdo->prepare('SELECT id_usuario, rol FROM Usuario WHERE id_usuario = ?');
        $stmt->execute([$id_usuario]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            throw new Exception("El usuario que intentas eliminar ya no existe.");
        }

        if ($usuario['rol'] === 'Administrador') {
            $stmtAdmins = $this->pdo->prepare('SELECT COUNT(*) FROM Usuario WHERE rol = ? AND id_usuario != ?');
            $stmtAdmins->execute(['Administrador', $id_usuario]);
            if ((int)$stmtAdmins->fetchColumn() === 0) {
                throw new Exception("No puedes eliminar al único Administrador del sistema.");
            }
        }

        $this->pdo->beginTransaction();
        try {
            // Equivalente manual a ON DELETE SET NULL: se conservan los puertos
            // y el historial, pero sin la referencia al usuario eliminado.
            $stmt = $this->pdo->prepare('UPDATE Puerto SET modificado_por = NULL WHERE modificado_por = ?');
            $stmt->execute([$id_usuario]);

            $stmt = $this->pdo->prepare('UPDATE HistorialCambiosPuerto SET id_usuario = NULL WHERE id_usuario = ?');
            $stmt->execute([$id_usuario]);

            $stmt = $this->pdo->prepare('DELETE FROM Usuario WHERE id_usuario = ?');
            $stmt->execute([$id_usuario]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return true;
    }

    /** Devuelve la contraseña a la credencial provisional del sistema. */
    public function resetPassword($id_usuario) {
        $password = password_hash('telefonica123', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('UPDATE Usuario SET contrasena_hash = ? WHERE id_usuario = ?');
        return $stmt->execute([$password, $id_usuario]);
    }

    /** Cambia la contraseña del propio usuario previa verificación de la actual. */
    public function changeUserPassword($id_usuario, $old_password, $new_password) {
        $stmt = $this->pdo->prepare('SELECT contrasena_hash FROM Usuario WHERE id_usuario = ?');
        $stmt->execute([$id_usuario]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($old_password, $hash) && $old_password !== $hash) {
            throw new Exception("La contraseña actual es incorrecta.");
        }

        $newHash = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $this->pdo->prepare('UPDATE Usuario SET contrasena_hash = ? WHERE id_usuario = ?');
        return $update->execute([$newHash, $id_usuario]);
    }
}
?>
