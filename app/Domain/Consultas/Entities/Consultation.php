<?php

namespace App\Domain\Consultas\Entities;

use App\Domain\Consultas\ValueObjects\ConsultationResponse;
use DateTimeImmutable;

class Consultation
{
    /**
     * @param string[] $services
     * @param array<string, mixed> $responseJson
     */
    public function __construct(
        private readonly ?int $id,
        private readonly int $userId,
        private readonly int $providerId,
        private readonly string $criterio,
        private readonly string $valor,
        private readonly ?string $apiId,
        private readonly array $services,
        private readonly float $costoCredito,
        private readonly ?int $httpStatusPost,
        private readonly ?int $httpStatusGet,
        private readonly bool $success,
        private readonly ?string $errorMessage,
        private readonly bool $alertaRobo,
        private readonly array $theftFlags,
        private readonly array $responseJson,
        private readonly ?int $creditsApi,
        private readonly DateTimeImmutable $createdAt
    ) {
    }

    public static function fromResponse(
        int $userId,
        int $providerId,
        string $criterio,
        string $valor,
        array $services,
        float $costoCredito,
        ConsultationResponse $response,
        DateTimeImmutable $createdAt
    ): self {
        return new self(
            null,
            $userId,
            $providerId,
            $criterio,
            $valor,
            $response->apiId(),
            $services,
            $costoCredito,
            $response->httpStatus(),
            null,
            $response->success(),
            $response->errorMessage(),
            (bool) ($response->theftFlags()['alerta_robo'] ?? 0),
            $response->theftFlags(),
            $response->data(),
            $response->creditsApi(),
            $createdAt
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
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

    public function apiId(): ?string
    {
        return $this->apiId;
    }

    public function services(): array
    {
        return $this->services;
    }

    public function costoCredito(): float
    {
        return $this->costoCredito;
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function alertaRobo(): bool
    {
        return $this->alertaRobo;
    }

    public function theftFlags(): array
    {
        return $this->theftFlags;
    }

    public function responseJson(): array
    {
        return $this->responseJson;
    }

    public function creditsApi(): ?int
    {
        return $this->creditsApi;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
