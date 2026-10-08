<?php

namespace App\Daos;

use App\Contracts\Daos\UsuarioDaoInterface;
use App\Entities\Usuario;
use PDO;

/**
 * Implementación concreta de persistencia para la entidad Usuario en MySQL.
 */
class MySqlUsuarioDao implements UsuarioDaoInterface
{
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?Usuario
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Usuario WHERE id_usuario = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Usuario::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Usuario WHERE correo = ? AND activo = 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM Usuario ORDER BY activo DESC, nombre ASC");
        $usuarios = [];
        foreach ($stmt->fetchAll() as $row) {
            $usuarios[] = Usuario::fromArray($row);
        }
        return $usuarios;
    }

    public function create(Usuario $usuario, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO Usuario (nombre, correo, contrasena_hash, rol, activo) 
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $usuario->nombre,
            $usuario->correo,
            $passwordHash,
            $usuario->rol,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(Usuario $usuario): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE Usuario 
            SET nombre = ?, correo = ?, rol = ? 
            WHERE id_usuario = ?
        ");
        return $stmt->execute([
            $usuario->nombre,
            $usuario->correo,
            $usuario->rol,
            $usuario->idUsuario,
        ]);
    }

    public function updateStatus(int $id, int $status): bool
    {
        $stmt = $this->pdo->prepare("UPDATE Usuario SET activo = ? WHERE id_usuario = ?");
        return $stmt->execute([$status, $id]);
    }

    public function updatePasswordHash(int $id, string $hash): bool
    {
        $stmt = $this->pdo->prepare("UPDATE Usuario SET contrasena_hash = ? WHERE id_usuario = ?");
        return $stmt->execute([$hash, $id]);
    }

    public function delete(int $id): bool
    {
        $this->unlinkUserReferences($id);
        $stmt = $this->pdo->prepare("DELETE FROM Usuario WHERE id_usuario = ?");
        return $stmt->execute([$id]);
    }

    public function countAdminsExcluding(int $excludeId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Usuario WHERE rol = 'Administrador' AND id_usuario != ?");
        $stmt->execute([$excludeId]);
        return (int)$stmt->fetchColumn();
    }

    public function unlinkUserReferences(int $userId): void
    {
        $stmt = $this->pdo->prepare("UPDATE Puerto SET modificado_por = NULL WHERE modificado_por = ?");
        $stmt->execute([$userId]);

        $stmt = $this->pdo->prepare("UPDATE HistorialCambiosPuerto SET id_usuario = NULL WHERE id_usuario = ?");
        $stmt->execute([$userId]);
    }
}
