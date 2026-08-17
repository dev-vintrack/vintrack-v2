<?php

namespace App\Domain\Consultas\ValueObjects;

class ConsultationRequest
{
    /**
     * @param  string[]  $services
     */
    public function __construct(
        private readonly int $userId,
        private readonly string $adapterCode,
        private readonly string $value,
        private readonly string $type,
        private readonly array $services,
        private readonly ?string $serviceCode = null,
    ) {}

    public function userId(): int
    {
        return $this->userId;
    }

    public function adapterCode(): string
    {
        return $this->adapterCode;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function services(): array
    {
        return $this->services;
    }

    public function serviceCode(): ?string
    {
        return $this->serviceCode;
    }
}
