<?php

namespace App\Contracts\Daos;

use App\Entities\CableAlimentacion;

/**
 * Contrato de acceso a datos para la entidad Cable de Alimentación.
 */
interface CableDaoInterface
{
    public function findById(int $id): ?CableAlimentacion;

    /**
     * @return CableAlimentacion[]
     */
    public function findBySiteId(int $siteId): array;

    /**
     * Devuelve mapa asociativo [id_cable => capacidad_hilos].
     * @return array<int, int>
     */
    public function findCapacidadesMap(): array;

    /**
     * Valida si ya existe otro cable con el mismo origen y destino en una sede distinta.
     */
    public function existsInAnotherSite(string $origen, ?string $destino, int $currentSiteId, ?int $excludeCableId = null): bool;

    public function create(CableAlimentacion $cable): int;

    public function update(CableAlimentacion $cable): bool;

    public function delete(int $id): bool;

    public function unlinkFromPorts(int $cableId): bool;
}
