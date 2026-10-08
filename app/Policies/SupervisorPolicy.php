<?php

namespace App\Policies;

use App\Contracts\Policies\RolePolicyInterface;

/**
 * Política polimórfica para el rol Supervisor.
 * Puede modificar puertos no bloqueados y solo puede editar/eliminar
 * la infraestructura física que él mismo haya registrado (persistencia en BD).
 */
class SupervisorPolicy implements RolePolicyInterface
{
    public function canModifyPort(array $puerto): bool
    {
        return (int)($puerto['bloqueado'] ?? 0) === 0;
    }

    public function canEditInfrastructure(?int $creatorUserId, int $currentUserId): bool
    {
        return $creatorUserId !== null && $creatorUserId === $currentUserId;
    }

    public function canDeleteInfrastructure(?int $creatorUserId, int $currentUserId): bool
    {
        return $creatorUserId !== null && $creatorUserId === $currentUserId;
    }

    public function canManageUsers(): bool
    {
        return false;
    }

    public function getRoleName(): string
    {
        return 'Supervisor';
    }
}
