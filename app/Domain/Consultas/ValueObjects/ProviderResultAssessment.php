<?php

namespace App\Domain\Consultas\ValueObjects;

final class ProviderResultAssessment
{
    public const VERSION = 'cr-004-v1';

    public const ACTIVE_QUALIFYING = 'ACTIVE_QUALIFYING';

    public const HISTORICAL_RECORD = 'HISTORICAL_RECORD';

    public const NON_QUALIFYING_WARNING = 'NON_QUALIFYING_WARNING';

    public const CLEAR = 'CLEAR';

    public const INDETERMINATE = 'INDETERMINATE';

    /**
     * @param  array<int, string>  $predicates
     * @param  array<int, string>  $evidencePaths
     */
    public function __construct(
        private readonly string $serviceCode,
        private readonly string $classification,
        private readonly array $predicates,
        private readonly array $evidencePaths,
    ) {}

    public function qualifies(): bool
    {
        return $this->classification === self::ACTIVE_QUALIFYING;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'version' => self::VERSION,
            'service_code' => $this->serviceCode,
            'classification' => $this->classification,
            'qualifies_notification_case' => $this->qualifies(),
            'predicates' => $this->predicates,
            'evidence_paths' => $this->evidencePaths,
        ];
    }
}
