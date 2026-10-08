<?php

namespace App\Entities;

/**
 * Entidad pura de Dominio / DTO: Cable de Alimentación.
 */
class CableAlimentacion
{
    public function __construct(
        public ?int $idCable,
        public int $idSite,
        public string $origen,
        public ?string $destino,
        public int $capacidadHilos,
        public ?string $rack,
        public ?string $fechaInstalacion = null,
        public int $activo = 1,
        public ?int $creadoPor = null,
        public ?string $creadoEn = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id_cable']) ? (int)$data['id_cable'] : null,
            (int)($data['id_site'] ?? 0),
            (string)($data['origen'] ?? ''),
            isset($data['destino']) ? (string)$data['destino'] : null,
            (int)($data['capacidad_hilos'] ?? 0),
            isset($data['rack']) ? (string)$data['rack'] : null,
            isset($data['fecha_instalacion']) ? (string)$data['fecha_instalacion'] : null,
            isset($data['activo']) ? (int)$data['activo'] : 1,
            isset($data['creado_por']) ? (int)$data['creado_por'] : null,
            isset($data['creado_en']) ? (string)$data['creado_en'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id_cable' => $this->idCable,
            'id_site' => $this->idSite,
            'origen' => $this->origen,
            'destino' => $this->destino,
            'capacidad_hilos' => $this->capacidadHilos,
            'rack' => $this->rack,
            'fecha_instalacion' => $this->fechaInstalacion,
            'activo' => $this->activo,
            'creado_por' => $this->creadoPor,
            'creado_en' => $this->creadoEn,
        ];
    }
}
