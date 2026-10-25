<?php

namespace App\Services;

use App\Core\DatabaseConnection;
use Exception;
use PDO;

/**
 * Servicio de dominio para gestión y autenticación de usuarios.
 */
class UsuarioService
{
    private PDO $pdo;

    const ROLES_VALIDOS = ['Administrador', 'Tecnico', 'Supervisor'];

    public $id_usuario;
    public $nombre;
    public $correo;
    public $rol;
    public $activo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DatabaseConnection::getSharedInstance();
    }

    public function getAllUsuarios(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM Usuario ORDER BY activo DESC, nombre ASC");
        return $stmt->fetchAll();
    }

    public function authenticate(string $correo, string $password): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM Usuario WHERE correo = ? AND activo = 1');
        $stmt->execute([$correo]);
        $user = $stmt->fetch();

        if ($user) {
            $isValid = false;
            if (password_verify($password, $user['contrasena_hash'])) {
                $isValid = true;
            } elseif ($password === $user['contrasena_hash']) {
                $isValid = true;
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

    public function createUsuario(string $nombre, string $correo, string $rol): bool
    {
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

    public function updateUsuario($id_usuario, string $nombre, string $correo, string $rol): bool
    {
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

    public function changeStatus($id_usuario, int $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE Usuario SET activo = ? WHERE id_usuario = ?');
        return $stmt->execute([$status, $id_usuario]);
    }

    public function deleteUsuario($id_usuario): bool
    {
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

        DatabaseConnection::transaction($this->pdo, function () use ($id_usuario) {
            $stmt = $this->pdo->prepare('UPDATE Puerto SET modificado_por = NULL WHERE modificado_por = ?');
            $stmt->execute([$id_usuario]);

            $stmt = $this->pdo->prepare('UPDATE HistorialCambiosPuerto SET id_usuario = NULL WHERE id_usuario = ?');
            $stmt->execute([$id_usuario]);

            $stmt = $this->pdo->prepare('DELETE FROM Usuario WHERE id_usuario = ?');
            $stmt->execute([$id_usuario]);
        });

        return true;
    }

    public function resetPassword($id_usuario): bool
    {
        $password = password_hash('telefonica123', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('UPDATE Usuario SET contrasena_hash = ? WHERE id_usuario = ?');
        return $stmt->execute([$password, $id_usuario]);
    }

    public function changeUserPassword($id_usuario, string $old_password, string $new_password): bool
    {
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
