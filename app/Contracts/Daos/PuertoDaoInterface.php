<?php

namespace App\Contracts\Daos;

use App\Entities\Puerto;

/**
 * Contrato de acceso a datos para la entidad Puerto (conector óptico).
 */
interface PuertoDaoInterface
{
    public function findById(int $id): ?Puerto;

    /**
     * @return Puerto[]
     */
    public function findByOdfId(int $odfId): array;

    /**
     * Devuelve puertos con datos expandidos de cable y usuario para la matriz del dashboard.
     */
    public function findByOdfIdWithDetails(int $odfId): array;

    /**
     * Bloquea la fila del puerto con SELECT ... FOR UPDATE dentro de una transacción.
     */
    public function lockForUpdate(int $id): ?array;

    /**
     * Detecta si un hilo de un cable ya está empalmado en otro ODF distinto.
     */
    public function findThreadConflict(int $cableId, int $threadNumber, int $excludeOdfId): ?array;

    public function updateEstado(int $id, string $estado, ?string $descripcion, ?int $userId, int $bloqueado): bool;

    public function assignThread(int $odfId, int $posicion, ?int $cableId, ?int $threadNumber): bool;

    public function provisionRange(int $odfId, int $fromPos, int $toPos): void;

    public function deleteRangeAbove(int $odfId, int $maxPos): void;

    public function countActiveAbove(int $odfId, int $maxPos): int;

    public function getRecentActivity(int $odfId, int $limit = 15): array;
}
