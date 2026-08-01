<?php

namespace App\Domain\Providers\Entities;

use App\Domain\Credits\ValueObjects\Money;

class ProviderService
{
    public function __construct(
        private readonly int $id,
        private readonly int $providerId,
        private readonly string $key,
        private readonly string $serviceCode,
        private readonly ?string $name,
        private readonly bool $enabled,
        private readonly Money $creditCost
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function providerId(): int
    {
        return $this->providerId;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function serviceCode(): string
    {
        return $this->serviceCode;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function creditCost(): Money
    {
        return $this->creditCost;
    }
}
