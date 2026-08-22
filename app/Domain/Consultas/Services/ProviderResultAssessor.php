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
        $unavailable = [];

        $pgj = $payload['pgj'] ?? null;
        if ($this->hasProviderError($pgj)) {
            $unavailable[] = 'pgj';
        }
        foreach ($this->records($pgj) as $index => $record) {
            $status = $this->integer($record['ID_ESTATUS_VHI_ROBO'] ?? null);
            if ($status === 1) {
                $active[] = "pgj.$index.ID_ESTATUS_VHI_ROBO=1";
            } elseif (in_array($status, [4, 12], true)) {
                $historical[] = "pgj.$index.ID_ESTATUS_VHI_ROBO=$status";
            } elseif ($status !== null) {
                $warnings[] = "pgj.$index.ID_ESTATUS_VHI_ROBO=$status";
            }
        }

        $ocra = $payload['ocra'] ?? null;
        if ($this->hasProviderError($ocra)) {
            $unavailable[] = 'ocra';
        } elseif (is_array($ocra)) {
            $reported = $this->boolean($ocra['conReporteRoboRecuperacion'] ?? null);
            $status = $this->integer($ocra['reporte']['roboORecuperacion'] ?? null);
            if ($reported === true && $status === 1) {
                $active[] = 'ocra.conReporteRoboRecuperacion=true+reporte.roboORecuperacion=1';
            } elseif ($reported === true && $status === 2) {
                $historical[] = 'ocra.conReporteRoboRecuperacion=true+reporte.roboORecuperacion=2';
            } elseif ($reported === true || $status !== null) {
                $warnings[] = 'ocra.reported_or_status_unmapped';
            }
        }

        $aviso = $payload['aviso'] ?? null;
        if ($this->hasProviderError($aviso)) {
            $unavailable[] = 'aviso';
        }
        foreach ($this->records($aviso) as $index => $record) {
            if (empty($record['NIV']) && empty($record['TIPO_DELITO'])) {
                continue;
            }
            $movement = $this->integer($record['ID_MOVIMIENTO'] ?? null);
            if (in_array($movement, [1, 3], true)) {
                $active[] = "aviso.$index.ID_MOVIMIENTO=$movement";
            } elseif (in_array($movement, [0, 2], true)) {
                $historical[] = "aviso.$index.ID_MOVIMIENTO=$movement";
            } elseif ($movement !== null) {
                $warnings[] = "aviso.$index.ID_MOVIMIENTO=$movement";
            }
        }

        $rapi = $payload['rapi'] ?? null;
        if ($this->hasProviderError($rapi)) {
            $unavailable[] = 'rapi';
        } elseif (is_array($rapi) && $this->boolean($rapi['tiene_delito'] ?? null) === true) {
            $status = $this->normalize((string) ($rapi['estado_vehiculo'] ?? ''));
            if (in_array($status, ['procedencia ilicita', 'robado'], true)) {
                $active[] = 'rapi.estado_vehiculo='.$status;
            } elseif (in_array($status, ['recuperado', 'entregado'], true)) {
                $historical[] = 'rapi.estado_vehiculo='.$status;
            } else {
                $warnings[] = 'rapi.tiene_delito=true_without_documented_current_state';
            }
        }

        $carfax = $payload['carfax'] ?? null;
        if ($this->hasProviderError($carfax)) {
            $unavailable[] = 'carfax';
        }
        $carfax = is_array($carfax) ? $carfax : [];
        $carfaxData = is_array($carfax['data'] ?? null) ? $carfax['data'] : [];
        if ($this->boolean($carfaxData['robo'] ?? null) === true) {
            $active[] = 'carfax.data.robo=true';
        }

        $repuve = $payload['repuve'] ?? null;
        if ($this->hasProviderError($repuve)) {
            $unavailable[] = 'repuve';
        }
        foreach ($this->records($repuve) as $index => $record) {
            if ($this->integer($record['TIPO_MOVIMIENTO'] ?? null) === 2) {
                $historical[] = "repuve.$index.TIPO_MOVIMIENTO=2";
                break;
            }
        }

        if ($active !== []) {
            return new ProviderResultAssessment('placas_service', ProviderResultAssessment::ACTIVE_QUALIFYING, $active, array_merge($active, $historical, $warnings, $this->unavailableEvidence($unavailable)));
        }
        if ($historical !== []) {
            return new ProviderResultAssessment('placas_service', ProviderResultAssessment::HISTORICAL_RECORD, $historical, array_merge($historical, $warnings, $this->unavailableEvidence($unavailable)));
        }
        if ($unavailable !== []) {
            return new ProviderResultAssessment('placas_service', ProviderResultAssessment::INDETERMINATE, $this->unavailableEvidence($unavailable), $this->unavailableEvidence($unavailable));
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

    /** @return array<int, array<string, mixed>> */
    private function records(mixed $section): array
    {
        if (is_array($section) && isset($section['XCURSOR']) && is_array($section['XCURSOR'])) {
            $section = $section['XCURSOR'];
        } elseif (is_array($section) && isset($section['data']['XCURSOR']) && is_array($section['data']['XCURSOR'])) {
            $section = $section['data']['XCURSOR'];
        }

        if (is_array($section) && array_is_list($section)) {
            return array_values(array_filter($section, 'is_array'));
        }

        return is_array($section) && $section !== [] && ! $this->hasProviderError($section) ? [$section] : [];
    }

    private function hasProviderError(mixed $section): bool
    {
        return is_array($section) && (
            array_key_exists('error', $section)
            || (array_key_exists('statusCode', $section) && ! array_key_exists('data', $section))
            || (array_key_exists('path', $section) && array_key_exists('status', $section))
        );
    }

    /** @param array<int, string> $sources
     * @return array<int, string>
     */
    private function unavailableEvidence(array $sources): array
    {
        return array_map(fn (string $source): string => "source_unavailable.$source", array_values(array_unique($sources)));
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
