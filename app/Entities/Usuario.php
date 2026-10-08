<?php

namespace App\Entities;

/**
 * Entidad pura de Dominio / DTO: Usuario del sistema.
 */
class Usuario
{
    public const ROLES_VALIDOS = ['Administrador', 'Tecnico', 'Supervisor'];

    public function __construct(
        public ?int $idUsuario,
        public string $nombre,
        public string $correo,
        public string $rol,
        public int $activo = 1
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id_usuario']) ? (int)$data['id_usuario'] : null,
            (string)($data['nombre'] ?? ''),
            (string)($data['correo'] ?? ''),
            (string)($data['rol'] ?? 'Tecnico'),
            isset($data['activo']) ? (int)$data['activo'] : 1
        );
    }

    public function toArray(): array
    {
        return [
            'id_usuario' => $this->idUsuario,
            'nombre' => $this->nombre,
            'correo' => $this->correo,
            'rol' => $this->rol,
            'activo' => $this->activo,
        ];
    }
}
