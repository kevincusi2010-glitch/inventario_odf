<?php

namespace App\Entities;

/**
 * Entidad pura de Dominio / DTO: Sede (Site).
 */
class Site
{
    public const TIPOS_VALIDOS = ['PAG', 'AGG', 'SITE', 'AGG/PAG'];

    public function __construct(
        public ?int $idSite,
        public string $nombre,
        public ?string $direccion,
        public ?string $departamento,
        public ?string $distrito,
        public string $tipo,
        public int $activo = 1,
        public ?int $creadoPor = null,
        public ?string $creadoEn = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id_site']) ? (int)$data['id_site'] : null,
            (string)($data['nombre'] ?? ''),
            isset($data['direccion']) ? (string)$data['direccion'] : null,
            isset($data['departamento']) ? (string)$data['departamento'] : null,
            isset($data['distrito']) ? (string)$data['distrito'] : null,
            (string)($data['tipo'] ?? 'SITE'),
            isset($data['activo']) ? (int)$data['activo'] : 1,
            isset($data['creado_por']) ? (int)$data['creado_por'] : null,
            isset($data['creado_en']) ? (string)$data['creado_en'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id_site' => $this->idSite,
            'nombre' => $this->nombre,
            'direccion' => $this->direccion,
            'departamento' => $this->departamento,
            'distrito' => $this->distrito,
            'tipo' => $this->tipo,
            'activo' => $this->activo,
            'creado_por' => $this->creadoPor,
            'creado_en' => $this->creadoEn,
        ];
    }
}
