<?php

namespace App\Policies;

use App\Contracts\Policies\RolePolicyInterface;

/**
 * Política polimórfica para el rol Técnico de Campo.
 * No puede alterar puertos que ya se encuentren "En Servicio" ni bloqueados,
 * y no posee privilegios para mutar la infraestructura física ni usuarios.
 */
class TecnicoPolicy implements RolePolicyInterface
{
    public function canModifyPort(array $puerto): bool
    {
        if ((int)($puerto['bloqueado'] ?? 0) === 1) {
            return false;
        }

        if (($puerto['estado'] ?? '') === 'En Servicio') {
            return false;
        }

        return true;
    }

    public function canEditInfrastructure(?int $creatorUserId, int $currentUserId): bool
    {
        return false;
    }

    public function canDeleteInfrastructure(?int $creatorUserId, int $currentUserId): bool
    {
        return false;
    }

    public function canManageUsers(): bool
    {
        return false;
    }

    public function getRoleName(): string
    {
        return 'Tecnico';
    }
}
