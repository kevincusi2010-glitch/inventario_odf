<?php

namespace App\Policies;

use App\Contracts\Policies\RolePolicyInterface;

/**
 * Política polimórfica para el rol Administrador.
 * Posee privilegios totales sobre la operación, inventario y cuentas de usuario.
 */
class AdminPolicy implements RolePolicyInterface
{
    public function canModifyPort(array $puerto): bool
    {
        return true;
    }

    public function canEditInfrastructure(?int $creatorUserId, int $currentUserId): bool
    {
        return true;
    }

    public function canDeleteInfrastructure(?int $creatorUserId, int $currentUserId): bool
    {
        return true;
    }

    public function canManageUsers(): bool
    {
        return true;
    }

    public function getRoleName(): string
    {
        return 'Administrador';
    }
}
