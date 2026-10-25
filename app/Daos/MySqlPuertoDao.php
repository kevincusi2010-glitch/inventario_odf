<?php

namespace App\Daos;

use App\Contracts\Daos\PuertoDaoInterface;
use App\Entities\Puerto;
use PDO;

/**
 * Implementación concreta de persistencia para la entidad Puerto en MySQL.
 */
class MySqlPuertoDao implements PuertoDaoInterface
{
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?Puerto
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Puerto WHERE id_puerto = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Puerto::fromArray($row) : null;
    }

    public function findByOdfId(int $odfId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Puerto WHERE id_odf = ? ORDER BY posicion ASC");
        $stmt->execute([$odfId]);

        $puertos = [];
        foreach ($stmt->fetchAll() as $row) {
            $puertos[] = Puerto::fromArray($row);
        }
        return $puertos;
    }

    public function findByOdfIdWithDetails(int $odfId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*, 
                   u.nombre as modificado_por_nombre, 
                   CONCAT_WS(' → ', c.origen, c.destino) AS tipo_cable 
            FROM Puerto p 
            LEFT JOIN Usuario u ON p.modificado_por = u.id_usuario 
            LEFT JOIN CableAlimentacion c ON p.id_cable_conectado = c.id_cable 
            WHERE p.id_odf = ? 
            ORDER BY p.posicion ASC
        ");
        $stmt->execute([$odfId]);
        return $stmt->fetchAll();
    }

    public function lockForUpdate(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Puerto WHERE id_puerto = ? FOR UPDATE");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findThreadConflict(int $cableId, int $threadNumber, int $excludeOdfId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id_odf, posicion 
            FROM Puerto 
            WHERE id_cable_conectado = ? AND numero_hilo = ? AND id_odf != ?
            LIMIT 1
        ");
        $stmt->execute([$cableId, $threadNumber, $excludeOdfId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateEstado(int $id, string $estado, ?string $descripcion, ?int $userId, int $bloqueado): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE Puerto 
            SET estado = ?, bloqueado = ?, modificado_por = ?, ultima_modificacion = NOW(), descripcion = ? 
            WHERE id_puerto = ?
        ");
        return $stmt->execute([$estado, $bloqueado, $userId, $descripcion, $id]);
    }

    public function assignThread(int $odfId, int $posicion, ?int $cableId, ?int $threadNumber): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE Puerto 
            SET id_cable_conectado = ?, numero_hilo = ? 
            WHERE id_odf = ? AND posicion = ?
        ");
        return $stmt->execute([$cableId, $threadNumber, $odfId, $posicion]);
    }

    public function provisionRange(int $odfId, int $fromPos, int $toPos): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO Puerto (id_odf, posicion, estado) VALUES (?, ?, 'Sin Servicio')");
        for ($i = $fromPos; $i <= $toPos; $i++) {
            $stmt->execute([$odfId, $i]);
        }
    }

    public function deleteRangeAbove(int $odfId, int $maxPos): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM Puerto WHERE id_odf = ? AND posicion > ?");
        $stmt->execute([$odfId, $maxPos]);
    }

    public function countActiveAbove(int $odfId, int $maxPos): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM Puerto 
            WHERE id_odf = ? AND posicion > ? AND (estado != 'Sin Servicio' OR id_cable_conectado IS NOT NULL)
        ");
        $stmt->execute([$odfId, $maxPos]);
        return (int)$stmt->fetchColumn();
    }

    public function getRecentActivity(int $odfId, int $limit = 15): array
    {
        $stmt = $this->pdo->prepare("
            SELECT h.estado_anterior,
                   h.estado_nuevo, 
                   h.observacion, 
                   h.fecha_cambio, 
                   p.posicion, 
                   u.nombre as usuario,
                   CONCAT_WS(' → ', c.origen, c.destino) AS cable_enlace
            FROM HistorialCambiosPuerto h 
            JOIN Puerto p ON h.id_puerto = p.id_puerto 
            LEFT JOIN Usuario u ON h.id_usuario = u.id_usuario 
            LEFT JOIN CableAlimentacion c ON p.id_cable_conectado = c.id_cable
            WHERE p.id_odf = ? 
            ORDER BY h.fecha_cambio DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $odfId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
