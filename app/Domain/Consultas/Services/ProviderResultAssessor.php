<?php

namespace App\Domain\Consultas\Services;

use App\Domain\Consultas\ValueObjects\ProviderResultAssessment;

final class ProviderResultAssessor
{
    /** @param array<string, mixed> $payload */
    public function assess(string $serviceCode, array $payload): ProviderResultAssessment
    {
        return match (strtolower(trim($serviceCode))) {
            'placas_service' => $this->assessPlacas($payload),
            'nmvtis_plus' => $this->assessNmvtisPlus($payload),
            default => new ProviderResultAssessment(
                strtolower(trim($serviceCode)),
                ProviderResultAssessment::INDETERMINATE,
                ['UNMAPPED_SERVICE_CODE'],
                [],
            ),
        };
    }

    /** @param array<string, mixed> $payload */
    private function assessPlacas(array $payload): ProviderResultAssessment
    {
        $active = [];
        $historical = [];
        $warnings = [];

        if (is_array($payload['pgj'] ?? null)) {
            $status = $this->integer($payload['pgj']['ID_ESTATUS_VHI_ROBO'] ?? null);
            if ($status === 1) {
                $active[] = 'pgj.ID_ESTATUS_VHI_ROBO=1';
            } elseif (in_array($status, [4, 12], true)) {
                $historical[] = 'pgj.ID_ESTATUS_VHI_ROBO='.$status;
            } elseif ($status !== null) {
                $warnings[] = 'pgj.ID_ESTATUS_VHI_ROBO='.$status;
            }
        }

        if (is_array($payload['ocra'] ?? null)) {
            $reported = $this->boolean($payload['ocra']['conReporteRoboRecuperacion'] ?? null);
            $status = $this->integer($payload['ocra']['reporte']['roboORecuperacion'] ?? null);
            if ($reported === true && $status === 1) {
                $active[] = 'ocra.conReporteRoboRecuperacion=true+reporte.roboORecuperacion=1';
            } elseif ($reported === true && $status === 2) {
                $historical[] = 'ocra.conReporteRoboRecuperacion=true+reporte.roboORecuperacion=2';
            } elseif ($reported === true || $status !== null) {
                $warnings[] = 'ocra.reported_or_status_unmapped';
            }
        }

        if (is_array($payload['aviso'] ?? null)) {
            $movement = $this->integer($payload['aviso']['ID_MOVIMIENTO'] ?? null);
            if (in_array($movement, [1, 3], true)) {
                $active[] = 'aviso.ID_MOVIMIENTO='.$movement;
            } elseif (in_array($movement, [0, 2], true)) {
                $historical[] = 'aviso.ID_MOVIMIENTO='.$movement;
            } elseif ($movement !== null) {
                $warnings[] = 'aviso.ID_MOVIMIENTO='.$movement;
            }
        }

        if (is_array($payload['rapi'] ?? null) && $this->boolean($payload['rapi']['tiene_delito'] ?? null) === true) {
            $warnings[] = 'rapi.tiene_delito=true_without_documented_current_state';
        }

        $carfax = is_array($payload['carfax'] ?? null) ? $payload['carfax'] : [];
        $carfaxData = is_array($carfax['data'] ?? null) ? $carfax['data'] : [];
        if ($this->boolean($carfaxData['robo'] ?? null) === true) {
            $active[] = 'carfax.data.robo=true';
        }

        if ($active !== []) {
            return new ProviderResultAssessment('placas_service', ProviderResultAssessment::ACTIVE_QUALIFYING, $active, $active);
        }
        if ($historical !== []) {
            return new ProviderResultAssessment('placas_service', ProviderResultAssessment::HISTORICAL_RECORD, $historical, $historical);
        }
        if ($warnings !== []) {
            return new ProviderResultAssessment('placas_service', ProviderResultAssessment::NON_QUALIFYING_WARNING, $warnings, $warnings);
        }
        if ($this->hasAnyKnownPlacasSource($payload)) {
            return new ProviderResultAssessment('placas_service', ProviderResultAssessment::CLEAR, ['KNOWN_SOURCES_WITHOUT_QUALIFYING_SIGNAL'], []);
        }

        return new ProviderResultAssessment('placas_service', ProviderResultAssessment::INDETERMINATE, ['NO_RECOGNIZED_PLACAS_SOURCE'], []);
    }

    /** @param array<string, mixed> $payload */
    private function assessNmvtisPlus(array $payload): ProviderResultAssessment
    {
        $active = [];
        $historical = [];
        $warnings = [];
        $events = $payload['otherInformation'] ?? null;

        if (is_array($events)) {
            foreach ($events as $index => $event) {
                if (! is_array($event)) {
                    continue;
                }
                $name = $this->normalize((string) ($event['event'] ?? ''));
                if ($name === 'active theft') {
                    $active[] = "otherInformation.$index.event=Active Theft";
                } elseif ($name === 'recovered theft') {
                    $historical[] = "otherInformation.$index.event=Recovered Theft";
                } elseif ($name !== '') {
                    $warnings[] = "otherInformation.$index.event";
                }
            }
        }

        if (! empty($payload['titleBrandReported']) || ! empty($payload['junkSalvageTotalLoss'])) {
            $warnings[] = 'title_brand_or_junk_salvage_total_loss';
        }

        if ($active !== []) {
            return new ProviderResultAssessment('nmvtis_plus', ProviderResultAssessment::ACTIVE_QUALIFYING, $active, $active);
        }
        if ($historical !== []) {
            return new ProviderResultAssessment('nmvtis_plus', ProviderResultAssessment::HISTORICAL_RECORD, $historical, $historical);
        }
        if ($warnings !== []) {
            return new ProviderResultAssessment('nmvtis_plus', ProviderResultAssessment::NON_QUALIFYING_WARNING, $warnings, $warnings);
        }
        if (array_key_exists('otherInformation', $payload) || array_key_exists('reportSummary', $payload)) {
            return new ProviderResultAssessment('nmvtis_plus', ProviderResultAssessment::CLEAR, ['DOCUMENTED_REPORT_WITHOUT_QUALIFYING_SIGNAL'], []);
        }

        return new ProviderResultAssessment('nmvtis_plus', ProviderResultAssessment::INDETERMINATE, ['NO_RECOGNIZED_NMVTIS_PLUS_SECTION'], []);
    }

    /** @param array<string, mixed> $payload */
    private function hasAnyKnownPlacasSource(array $payload): bool
    {
        foreach (['pgj', 'ocra', 'aviso', 'rapi', 'carfax', 'repuve'] as $source) {
            if (array_key_exists($source, $payload)) {
                return true;
            }
        }

        return false;
    }

    private function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function boolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1 ? true : ($value === 0 ? false : null);
        }
        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                'true', '1', 'si', 'sí' => true,
                'false', '0', 'no' => false,
                default => null,
            };
        }

        return null;
    }

    private function normalize(string $value): string
    {
        return strtolower(trim(preg_replace('/\\s+/', ' ', $value) ?? ''));
    }
}
