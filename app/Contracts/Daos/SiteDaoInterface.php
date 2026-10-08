<?php

namespace App\Contracts\Daos;

use App\Entities\Site;

/**
 * Contrato de acceso a datos para la entidad Sede (Site).
 */
interface SiteDaoInterface
{
    public function findById(int $id): ?Site;

    /**
     * @return Site[]
     */
    public function findAllActive(): array;

    /**
     * Devuelve las sedes activas con totales de ODFs y cables calculados.
     * @return array<int, array{site: Site, total_odfs: int, total_cables: int}>
     */
    public function findAllActiveWithCounts(): array;

    public function create(Site $site): int;

    public function update(Site $site): bool;

    public function delete(int $id): bool;

    public function countOdfs(int $siteId): int;

    public function countCables(int $siteId): int;
}
