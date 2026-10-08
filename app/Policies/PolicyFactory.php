<?php

namespace App\Policies;

use App\Contracts\Policies\RolePolicyInterface;
use InvalidArgumentException;

/**
 * Fábrica de políticas polimórficas según el rol del usuario.
 */
class PolicyFactory
{
    public static function forRole(string $role): RolePolicyInterface
    {
        return match ($role) {
            'Administrador' => new AdminPolicy(),
            'Supervisor' => new SupervisorPolicy(),
            'Tecnico' => new TecnicoPolicy(),
            default => throw new InvalidArgumentException("Rol de usuario no reconocido: '{$role}'"),
        };
    }
}
