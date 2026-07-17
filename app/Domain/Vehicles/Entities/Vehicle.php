<?php

namespace App\Domain\Vehicles\Entities;

use DateTimeImmutable;

class Vehicle
{
    private ?string $marca;
    private ?string $modelo;
    private ?string $anio;
    private bool $ultimoStatusRobo;
    private int $totalConsultas;
    private DateTimeImmutable $ultimaConsultaAt;

    public function __construct(
        private readonly ?int $id,
        private readonly int $providerId,
        private readonly string $criterio,
        private readonly string $valor,
        ?string $marca,
        ?string $modelo,
        ?string $anio,
        bool $ultimoStatusRobo,
        int $totalConsultas,
        DateTimeImmutable $ultimaConsultaAt,
        private readonly DateTimeImmutable $createdAt
    ) {
        $this->marca = $marca;
        $this->modelo = $modelo;
        $this->anio = $anio;
        $this->ultimoStatusRobo = $ultimoStatusRobo;
        $this->totalConsultas = $totalConsultas;
        $this->ultimaConsultaAt = $ultimaConsultaAt;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function providerId(): int
    {
        return $this->providerId;
    }

    public function criterio(): string
    {
        return $this->criterio;
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function marca(): ?string
    {
        return $this->marca;
    }

    public function modelo(): ?string
    {
        return $this->modelo;
    }

    public function anio(): ?string
    {
        return $this->anio;
    }

    public function ultimoStatusRobo(): bool
    {
        return $this->ultimoStatusRobo;
    }

    public function totalConsultas(): int
    {
        return $this->totalConsultas;
    }

    public function ultimaConsultaAt(): DateTimeImmutable
    {
        return $this->ultimaConsultaAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updateFromConsultation(
        bool $alertaRobo,
        DateTimeImmutable $consultedAt,
        ?string $marca = null,
        ?string $modelo = null,
        ?string $anio = null
    ): void {
        $this->ultimoStatusRobo = $alertaRobo;
        $this->totalConsultas++;
        $this->ultimaConsultaAt = $consultedAt;

        if ($marca !== null) {
            $this->marca = $marca;
        }
        if ($modelo !== null) {
            $this->modelo = $modelo;
        }
        if ($anio !== null) {
            $this->anio = $anio;
        }
    }
}
