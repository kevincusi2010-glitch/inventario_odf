<?php

namespace App\Services;

use PDO;

/**
 * Servicio de Dominio: Agregación de estadísticas de ocupación e infraestructura óptica.
 */
class StatisticsService
{
    public function __construct(private PDO $pdo) {}

    public function getSiteList(): array
    {
        return $this->pdo->query("SELECT id_site, nombre, departamento FROM Site WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
    }

    public function getOdfsForSite(int $siteId): array
    {
        $stmt = $this->pdo->prepare("SELECT id_odf, origen, destino, ubicacion_rack FROM ODF WHERE id_site = ? AND activo = 1 ORDER BY id_odf ASC");
        $stmt->execute([$siteId]);
        $rows = $stmt->fetchAll();
        $index = 1;
        foreach ($rows as &$r) {
            $r['local_index'] = $index++;
        }
        return $rows;
    }

    public function calculateStats(int $siteId, ?int $odfId = null): array
    {
        $stats = [
            'total_cables' => 0,
            'total_odfs' => 0,
            'capacidad_hilos' => 0,
            'puertos_totales' => 0,
            'estado_libre' => 0,
            'estado_ocupado' => 0,
            'estado_reservado' => 0,
            'estado_malogrado' => 0,
        ];

        if ($siteId <= 0) {
            return $stats;
        }

        if ($odfId !== null && $odfId > 0) {
            // Cables conectados al ODF específico
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT c.id_cable) as c
                FROM CableAlimentacion c 
                JOIN Puerto p ON c.id_cable = p.id_cable_conectado 
                WHERE p.id_odf = ? AND c.activo = 1
            ");
            $stmt->execute([$odfId]);
            $res = $stmt->fetch();
            $stats['total_cables'] = (int)($res['c'] ?? 0);

            $stmt2 = $this->pdo->prepare("
                SELECT SUM(capacidad_hilos) as h FROM (
                    SELECT DISTINCT c.id_cable, c.capacidad_hilos 
                    FROM CableAlimentacion c 
                    JOIN Puerto p ON c.id_cable = p.id_cable_conectado 
                    WHERE p.id_odf = ? AND c.activo = 1
                ) as distinct_cables
            ");
            $stmt2->execute([$odfId]);
            $stats['capacidad_hilos'] = (int)$stmt2->fetchColumn();
            $stats['total_odfs'] = 1;

            // Puertos del ODF
            $stmtP = $this->pdo->prepare("
                SELECT p.estado, COUNT(p.id_puerto) as total 
                FROM Puerto p 
                WHERE p.id_odf = ?
                GROUP BY p.estado
            ");
            $stmtP->execute([$odfId]);
        } else {
            // Toda la sede
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as c, SUM(capacidad_hilos) as h FROM CableAlimentacion WHERE id_site = ? AND activo = 1");
            $stmt->execute([$siteId]);
            $res = $stmt->fetch();
            $stats['total_cables'] = (int)($res['c'] ?? 0);
            $stats['capacidad_hilos'] = (int)($res['h'] ?? 0);

            $stmtO = $this->pdo->prepare("SELECT COUNT(*) as c FROM ODF WHERE id_site = ? AND activo = 1");
            $stmtO->execute([$siteId]);
            $stats['total_odfs'] = (int)($stmtO->fetch()['c'] ?? 0);

            // Puertos de toda la sede
            $stmtP = $this->pdo->prepare("
                SELECT p.estado, COUNT(p.id_puerto) as total 
                FROM Puerto p 
                JOIN ODF o ON p.id_odf = o.id_odf 
                WHERE o.id_site = ? AND o.activo = 1 
                GROUP BY p.estado
            ");
            $stmtP->execute([$siteId]);
        }

        foreach ($stmtP->fetchAll() as $p) {
            $cant = (int)$p['total'];
            $stats['puertos_totales'] += $cant;
            if ($p['estado'] === 'Sin Servicio') $stats['estado_libre'] += $cant;
            if ($p['estado'] === 'En Servicio') $stats['estado_ocupado'] += $cant;
            if ($p['estado'] === 'Reservado') $stats['estado_reservado'] += $cant;
            if ($p['estado'] === 'Malogrado') $stats['estado_malogrado'] += $cant;
        }

        return $stats;
    }
}
