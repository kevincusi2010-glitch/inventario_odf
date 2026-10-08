<?php

namespace App\Contracts\Daos;

use App\Entities\Odf;

/**
 * Contrato de acceso a datos para la entidad ODF (Distribuidor Óptico).
 */
interface OdfDaoInterface
{
    public function findById(int $id): ?Odf;

    /**
     * @return Odf[]
     */
    public function findBySiteId(int $siteId): array;

    /**
     * Lista ODFs con la información resumida de cables conectados para el dashboard.
     * @return array
     */
    public function findBySiteIdWithConnectedCables(int $siteId): array;

    public function create(Odf $odf): int;

    public function update(Odf $odf): bool;

    public function delete(int $id): bool;

    public function getCapacidad(int $id): int;
}
