<?php

namespace App\Contracts\Daos;

use App\Entities\Puerto;

/**
 * Contrato de acceso a datos para la entidad Puerto (conector óptico).
 *
 * Define las operaciones de persistencia, consulta, bloqueo pesimista y
 * auditoría de acopladores ópticos desacoplando la lógica de la base de datos física.
 *
 * @package App\Contracts\Daos
 */
interface PuertoDaoInterface
{
    /**
     * Busca un puerto óptico por su identificador unívoco.
     *
     * @param int $id Identificador primario del puerto.
     * @return Puerto|null Entidad hidratada o null si no existe.
     */
    public function findById(int $id): ?Puerto;

    /**
     * Recupera todos los puertos pertenecientes a un distribuidor ODF.
     *
     * @param int $odfId Identificador del ODF.
     * @return Puerto[] Colección de entidades ordenadas por posición física.
     */
    public function findByOdfId(int $odfId): array;

    /**
     * Devuelve puertos con datos expandidos de cable y usuario para la matriz del dashboard.
     *
     * @param int $odfId Identificador del ODF.
     * @return array<int, array<string, mixed>> Registros extendidos con detalles de empalme.
     */
    public function findByOdfIdWithDetails(int $odfId): array;

    /**
     * Bloquea la fila del puerto con SELECT ... FOR UPDATE dentro de una transacción.
     *
     * @param int $id Identificador del puerto a bloquear.
     * @return array<string, mixed>|null Datos del puerto bloqueado o null si no existe.
     */
    public function lockForUpdate(int $id): ?array;

    /**
     * Detecta si un hilo de un cable ya está empalmado en otro ODF distinto.
     *
     * @param int $cableId Identificador del cable de fibra.
     * @param int $threadNumber Número del hilo óptico.
     * @param int $excludeOdfId ID del ODF actual que se excluye de la comprobación.
     * @return array<string, mixed>|null Registro de conflicto si existe colisión.
     */
    public function findThreadConflict(int $cableId, int $threadNumber, int $excludeOdfId): ?array;

    /**
     * Actualiza el estado operativo y bloqueo físico de un puerto.
     *
     * @param int         $id          Identificador del puerto.
     * @param string      $estado      Nuevo estado ('Sin Servicio', 'En Servicio', 'Reservado', 'Malogrado').
     * @param string|null $descripcion Justificación técnica o número de abonado/circuito.
     * @param int|null    $userId      ID del operador responsable de la modificación.
     * @param int         $bloqueado   Banderín de bloqueo administrativo (0 o 1).
     * @return bool True si la fila se actualizó correctamente en la base de datos.
     */
    public function updateEstado(int $id, string $estado, ?string $descripcion, ?int $userId, int $bloqueado): bool;

    /**
     * Asigna o desasigna un hilo óptico de alimentación a una posición específica del ODF.
     *
     * @param int      $odfId        Identificador del ODF.
     * @param int      $posicion     Número de posición física del conector.
     * @param int|null $cableId      ID del cable alimentador (null para liberar).
     * @param int|null $threadNumber Número de hilo óptico (null para liberar).
     * @return bool True si la asignación se confirmó exitosamente.
     */
    public function assignThread(int $odfId, int $posicion, ?int $cableId, ?int $threadNumber): bool;

    /**
     * Aprovisiona un rango consecutivo de puertos vacíos para un ODF.
     *
     * @param int $odfId   ID del distribuidor ODF.
     * @param int $fromPos Posición inicial inclusiva.
     * @param int $toPos   Posición final inclusiva.
     * @return void
     */
    public function provisionRange(int $odfId, int $fromPos, int $toPos): void;

    /**
     * Elimina puertos de un ODF cuyas posiciones superen la nueva capacidad máxima.
     *
     * @param int $odfId  ID del distribuidor ODF.
     * @param int $maxPos Capacidad máxima permitida.
     * @return void
     */
    public function deleteRangeAbove(int $odfId, int $maxPos): void;

    /**
     * Cuenta cuántos puertos activos o en servicio superan un umbral de capacidad.
     *
     * @param int $odfId  ID del ODF.
     * @param int $maxPos Límite de posición evaluado.
     * @return int Cantidad de puertos que impiden una reducción de tamaño del chasis.
     */
    public function countActiveAbove(int $odfId, int $maxPos): int;

    /**
     * Obtiene la bitácora de actividad reciente para un chasis ODF.
     *
     * @param int $odfId Identificador del ODF.
     * @param int $limit Cantidad máxima de registros históricos a recuperar.
     * @return array<int, array<string, mixed>> Listado de cambios de estado y empalmes.
     */
    public function getRecentActivity(int $odfId, int $limit = 15): array;
}
