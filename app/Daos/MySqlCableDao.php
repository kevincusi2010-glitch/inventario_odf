<?php

namespace App\Daos;

use App\Contracts\Daos\CableDaoInterface;
use App\Entities\CableAlimentacion;
use PDO;

/**
 * Implementación concreta de persistencia para la entidad CableAlimentacion en MySQL.
 */
class MySqlCableDao implements CableDaoInterface
{
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?CableAlimentacion
    {
        $stmt = $this->pdo->prepare("SELECT * FROM CableAlimentacion WHERE id_cable = ? AND activo = 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? CableAlimentacion::fromArray($row) : null;
    }

    public function findBySiteId(int $siteId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *, CONCAT_WS(' → ', origen, destino) AS tipo_cable 
            FROM CableAlimentacion 
            WHERE id_site = ? AND activo = 1 
            ORDER BY id_cable ASC
        ");
        $stmt->execute([$siteId]);
        return $stmt->fetchAll();
    }

    public function findCapacidadesMap(): array
    {
        return $this->pdo->query("
            SELECT id_cable, capacidad_hilos 
            FROM CableAlimentacion
        ")->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function existsInAnotherSite(string $origen, ?string $destino, int $currentSiteId, ?int $excludeCableId = null): bool
    {
        $sql = "SELECT id_site FROM CableAlimentacion WHERE origen = ? AND destino <=> ?";
        $params = [$origen, $destino];

        if ($excludeCableId !== null) {
            $sql .= " AND id_cable != ?";
            $params[] = $excludeCableId;
        }
        $sql .= " LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $existingSite = $stmt->fetchColumn();

        return $existingSite !== false && (int)$existingSite !== $currentSiteId;
    }

    public function create(CableAlimentacion $cable): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO CableAlimentacion (id_site, origen, destino, capacidad_hilos, rack, fecha_instalacion, activo, creado_por) 
            VALUES (?, ?, ?, ?, ?, ?, 1, ?)
        ");
        $stmt->execute([
            $cable->idSite,
            $cable->origen,
            $cable->destino,
            $cable->capacidadHilos,
            $cable->rack,
            $cable->fechaInstalacion,
            $cable->creadoPor,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(CableAlimentacion $cable): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE CableAlimentacion 
            SET id_site = ?, origen = ?, destino = ?, capacidad_hilos = ?, rack = ?, fecha_instalacion = ? 
            WHERE id_cable = ?
        ");
        return $stmt->execute([
            $cable->idSite,
            $cable->origen,
            $cable->destino,
            $cable->capacidadHilos,
            $cable->rack,
            $cable->fechaInstalacion,
            $cable->idCable,
        ]);
    }

    public function delete(int $id): bool
    {
        $this->unlinkFromPorts($id);
        $stmt = $this->pdo->prepare("DELETE FROM CableAlimentacion WHERE id_cable = ?");
        return $stmt->execute([$id]);
    }

    public function unlinkFromPorts(int $cableId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE Puerto SET id_cable_conectado = NULL, numero_hilo = NULL WHERE id_cable_conectado = ?");
        return $stmt->execute([$cableId]);
    }
}
