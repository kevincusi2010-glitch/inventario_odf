<?php

namespace App\Daos;

use App\Contracts\Daos\OdfDaoInterface;
use App\Entities\Odf;
use PDO;

/**
 * Implementación concreta de persistencia para la entidad ODF en MySQL.
 */
class MySqlOdfDao implements OdfDaoInterface
{
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?Odf
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ODF WHERE id_odf = ? AND activo = 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Odf::fromArray($row) : null;
    }

    public function findBySiteId(int $siteId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, s.nombre as site_nombre 
            FROM ODF o 
            JOIN Site s ON o.id_site = s.id_site 
            WHERE o.id_site = ? AND o.activo = 1 
            ORDER BY o.id_odf ASC
        ");
        $stmt->execute([$siteId]);

        $odfs = [];
        $index = 1;
        foreach ($stmt->fetchAll() as $row) {
            $odf = Odf::fromArray($row);
            $row['local_index'] = $index++;
            $odfs[] = [
                'odf' => $odf,
                'raw' => $row,
            ];
        }
        return $odfs;
    }

    public function findBySiteIdWithConnectedCables(int $siteId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*,
                   (SELECT GROUP_CONCAT(DISTINCT CONCAT_WS(' → ', c.origen, c.destino) SEPARATOR ', ') 
                    FROM Puerto p 
                    JOIN CableAlimentacion c ON p.id_cable_conectado = c.id_cable 
                    WHERE p.id_odf = o.id_odf) as cables_conectados
            FROM ODF o 
            WHERE o.id_site = ? AND o.activo = 1 
            ORDER BY o.id_odf ASC
        ");
        $stmt->execute([$siteId]);
        $rows = $stmt->fetchAll();

        $index = 1;
        foreach ($rows as &$r) {
            $r['local_index'] = $index++;
        }
        return $rows;
    }

    public function create(Odf $odf): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO ODF (id_site, origen, destino, capacidad_puertos, ubicacion_rack, activo, creado_por) 
            VALUES (?, ?, ?, ?, ?, 1, ?)
        ");
        $stmt->execute([
            $odf->idSite,
            $odf->origen,
            $odf->destino,
            $odf->capacidadPuertos,
            $odf->ubicacionRack,
            $odf->creadoPor,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(Odf $odf): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE ODF 
            SET id_site = ?, origen = ?, destino = ?, capacidad_puertos = ?, ubicacion_rack = ? 
            WHERE id_odf = ?
        ");
        return $stmt->execute([
            $odf->idSite,
            $odf->origen,
            $odf->destino,
            $odf->capacidadPuertos,
            $odf->ubicacionRack,
            $odf->idOdf,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM ODF WHERE id_odf = ?");
        return $stmt->execute([$id]);
    }

    public function getCapacidad(int $id): int
    {
        $stmt = $this->pdo->prepare("SELECT capacidad_puertos FROM ODF WHERE id_odf = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn();
    }
}
