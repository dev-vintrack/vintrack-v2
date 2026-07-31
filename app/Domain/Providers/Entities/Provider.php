<?php

namespace App\Domain\Providers\Entities;

use App\Domain\Providers\ValueObjects\ProviderCode;
use App\Domain\Providers\ValueObjects\ProviderId;

class Provider
{
    /**
     * @param array<string, mixed> $policies
     */
    public function __construct(
        private readonly ProviderId $id,
        private readonly ProviderCode $code,
        private readonly string $adapterCode,
        private readonly string $name,
        private readonly ?string $baseUrl,
        private readonly array $policies,
        private readonly bool $enabled
    ) {
    }

    public function id(): ProviderId
    {
        return $this->id;
    }

    public function code(): ProviderCode
    {
        return $this->code;
    }

    public function adapterCode(): string
    {
        return $this->adapterCode;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function baseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function policies(): array
    {
        return $this->policies;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function creditCost(): float
    {
        return (float) ($this->policies['creditCost'] ?? 1.0);
    }

    public function debitTiming(): string
    {
        return $this->policies['debitTiming'] ?? 'pre';
    }
}
