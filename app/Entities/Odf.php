<?php

namespace App\Entities;

/**
 * Entidad pura de Dominio / DTO: Distribuidor Óptico (ODF).
 */
class Odf
{
    public function __construct(
        public ?int $idOdf,
        public int $idSite,
        public string $origen,
        public ?string $destino,
        public int $capacidadPuertos,
        public ?string $ubicacionRack,
        public int $activo = 1,
        public ?int $creadoPor = null,
        public ?string $creadoEn = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id_odf']) ? (int)$data['id_odf'] : null,
            (int)($data['id_site'] ?? 0),
            (string)($data['origen'] ?? ''),
            isset($data['destino']) ? (string)$data['destino'] : null,
            (int)($data['capacidad_puertos'] ?? 0),
            isset($data['ubicacion_rack']) ? (string)$data['ubicacion_rack'] : null,
            isset($data['activo']) ? (int)$data['activo'] : 1,
            isset($data['creado_por']) ? (int)$data['creado_por'] : null,
            isset($data['creado_en']) ? (string)$data['creado_en'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id_odf' => $this->idOdf,
            'id_site' => $this->idSite,
            'origen' => $this->origen,
            'destino' => $this->destino,
            'capacidad_puertos' => $this->capacidadPuertos,
            'ubicacion_rack' => $this->ubicacionRack,
            'activo' => $this->activo,
            'creado_por' => $this->creadoPor,
            'creado_en' => $this->creadoEn,
        ];
    }
}
