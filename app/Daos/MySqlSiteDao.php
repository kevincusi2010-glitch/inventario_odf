<?php

namespace App\Daos;

use App\Contracts\Daos\SiteDaoInterface;
use App\Entities\Site;
use PDO;

/**
 * Implementación concreta de persistencia para la entidad Site en MySQL.
 */
class MySqlSiteDao implements SiteDaoInterface
{
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?Site
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Site WHERE id_site = ? AND activo = 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Site::fromArray($row) : null;
    }

    public function findAllActive(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM Site WHERE activo = 1 ORDER BY nombre ASC");
        $sites = [];
        foreach ($stmt->fetchAll() as $row) {
            $sites[] = Site::fromArray($row);
        }
        return $sites;
    }

    public function findAllActiveWithCounts(): array
    {
        $stmt = $this->pdo->query("
            SELECT s.*, 
                   (SELECT COUNT(*) FROM ODF WHERE id_site = s.id_site AND activo = 1) as total_odfs,
                   (SELECT COUNT(*) FROM CableAlimentacion WHERE id_site = s.id_site AND activo = 1) as total_cables
            FROM Site s 
            WHERE s.activo = 1 
            ORDER BY s.nombre ASC
        ");

        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'site' => Site::fromArray($row),
                'total_odfs' => (int)$row['total_odfs'],
                'total_cables' => (int)$row['total_cables'],
            ];
        }
        return $results;
    }

    public function create(Site $site): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO Site (nombre, direccion, departamento, distrito, tipo, activo, creado_por) 
            VALUES (?, ?, ?, ?, ?, 1, ?)
        ");
        $stmt->execute([
            $site->nombre,
            $site->direccion,
            $site->departamento,
            $site->distrito,
            $site->tipo,
            $site->creadoPor,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(Site $site): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE Site 
            SET nombre = ?, direccion = ?, departamento = ?, distrito = ?, tipo = ? 
            WHERE id_site = ?
        ");
        return $stmt->execute([
            $site->nombre,
            $site->direccion,
            $site->departamento,
            $site->distrito,
            $site->tipo,
            $site->idSite,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM Site WHERE id_site = ?");
        return $stmt->execute([$id]);
    }

    public function countOdfs(int $siteId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM ODF WHERE id_site = ?");
        $stmt->execute([$siteId]);
        return (int)$stmt->fetchColumn();
    }

    public function countCables(int $siteId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM CableAlimentacion WHERE id_site = ?");
        $stmt->execute([$siteId]);
        return (int)$stmt->fetchColumn();
    }
}
