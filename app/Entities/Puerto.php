<?php

namespace App\Entities;

/**
 * Entidad pura de Dominio / DTO: Puerto (conector óptico).
 */
class Puerto
{
    public const ESTADOS_VALIDOS = ['Sin Servicio', 'En Servicio', 'Reservado', 'Malogrado'];

    public function __construct(
        public ?int $idPuerto,
        public int $idOdf,
        public int $posicion,
        public string $estado,
        public ?string $descripcion = null,
        public ?int $idCableConectado = null,
        public ?int $numeroHilo = null,
        public int $bloqueado = 0,
        public ?int $modificadoPor = null,
        public ?string $ultimaModificacion = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id_puerto']) ? (int)$data['id_puerto'] : null,
            (int)($data['id_odf'] ?? 0),
            (int)($data['posicion'] ?? 0),
            (string)($data['estado'] ?? 'Sin Servicio'),
            isset($data['descripcion']) ? (string)$data['descripcion'] : null,
            isset($data['id_cable_conectado']) && $data['id_cable_conectado'] !== null ? (int)$data['id_cable_conectado'] : null,
            isset($data['numero_hilo']) && $data['numero_hilo'] !== null ? (int)$data['numero_hilo'] : null,
            isset($data['bloqueado']) ? (int)$data['bloqueado'] : 0,
            isset($data['modificado_por']) ? (int)$data['modificado_por'] : null,
            isset($data['ultima_modificacion']) ? (string)$data['ultima_modificacion'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id_puerto' => $this->idPuerto,
            'id_odf' => $this->idOdf,
            'posicion' => $this->posicion,
            'estado' => $this->estado,
            'descripcion' => $this->descripcion,
            'id_cable_conectado' => $this->idCableConectado,
            'numero_hilo' => $this->numeroHilo,
            'bloqueado' => $this->bloqueado,
            'modificado_por' => $this->modificadoPor,
            'ultima_modificacion' => $this->ultimaModificacion,
        ];
    }
}
