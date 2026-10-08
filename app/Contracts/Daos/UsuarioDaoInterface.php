<?php

namespace App\Contracts\Daos;

use App\Entities\Usuario;

/**
 * Contrato de acceso a datos para la entidad Usuario.
 */
interface UsuarioDaoInterface
{
    public function findById(int $id): ?Usuario;

    public function findByEmail(string $email): ?array;

    /**
     * @return Usuario[]
     */
    public function findAll(): array;

    public function create(Usuario $usuario, string $passwordHash): int;

    public function update(Usuario $usuario): bool;

    public function updateStatus(int $id, int $status): bool;

    public function updatePasswordHash(int $id, string $hash): bool;

    public function delete(int $id): bool;

    public function countAdminsExcluding(int $excludeId): int;

    public function unlinkUserReferences(int $userId): void;
}
