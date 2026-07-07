<?php

namespace App\Domain\Consultas\ValueObjects;

class ConsultationResponse
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, int> $theftFlags
     */
    public function __construct(
        private readonly bool $success,
        private readonly int $httpStatus,
        private readonly ?string $errorMessage,
        private readonly array $data,
        private readonly ?string $apiId,
        private readonly array $theftFlags,
        private readonly ?int $creditsApi = null
    ) {
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function apiId(): ?string
    {
        return $this->apiId;
    }

    public function theftFlags(): array
    {
        return $this->theftFlags;
    }

    public function creditsApi(): ?int
    {
        return $this->creditsApi;
    }
}
