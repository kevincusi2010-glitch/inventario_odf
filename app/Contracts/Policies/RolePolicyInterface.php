<?php

namespace App\Contracts\Policies;

/**
 * Contrato polimórfico para las políticas de autorización basadas en rol.
 */
interface RolePolicyInterface
{
    /**
     * Determina si el rol puede modificar el estado o detalles de un puerto.
     */
    public function canModifyPort(array $puerto): bool;

    /**
     * Determina si el rol puede editar una sede, ODF o cable existente.
     * Los supervisores solo pueden editar lo que ellos mismos crearon.
     */
    public function canEditInfrastructure(?int $creatorUserId, int $currentUserId): bool;

    /**
     * Determina si el rol puede eliminar una sede, ODF o cable existente.
     */
    public function canDeleteInfrastructure(?int $creatorUserId, int $currentUserId): bool;

    /**
     * Determina si el rol puede gestionar cuentas de usuario del sistema.
     */
    public function canManageUsers(): bool;

    /**
     * Devuelve el nombre del rol asociado a la política.
     */
    public function getRoleName(): string;
}
