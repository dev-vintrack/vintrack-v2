<?php

namespace App\Presentation\Support;

/**
 * Presentacion de resultados del proveedor Placas.info.
 *
 * Porta la logica de aplanado de datos, etiquetas legibles, resaltado por
 * servicio y el banner de alerta de 5 niveles del sitio anterior (home.php).
 */
class PlacasReportPresenter
{
    private const ALERT_SECTIONS = ['pgj', 'aviso', 'carfax', 'repuve', 'ocra', 'rapi'];

    private const ACRONYMS = ['VIN', 'NIV', 'RFC', 'CURP', 'ID', 'CP', 'NRPV'];

    /**
     * Extrae las secciones esperadas del cuerpo crudo de la API.
     *
     * @param array<string, mixed> $rawData
     * @return array<string, mixed>
     */
    public static function sections(array $rawData): array
    {
        $sections = [];
        foreach (self::ALERT_SECTIONS as $key) {
            $sections[$key] = $rawData[$key] ?? null;
        }

        return $sections;
    }

    /**
     * Titulos legibles por seccion, en el orden de despliegue.
     *
     * @return array<string, string>
     */
    public static function sectionTitles(): array
    {
        return [
            'pgj' => 'PGJ',
            'aviso' => 'AVISO',
            'carfax' => 'CARFAX',
            'repuve' => 'REPUVE',
            'ocra' => 'OCRA',
            'rapi' => 'RAPI',
        ];
    }

    /**
     * Aplana una estructura de datos a pares [rutaClave, valorTexto].
     *
     * @param mixed $data
     * @return array<int, array{0:string,1:string}>
     */
    public static function flatten($data, string $prefix = ''): array
    {
        $rows = [];
        if ($data === null) {
            return $rows;
        }

        if (is_array($data) && self::isList($data)) {
            if (count($data) === 1 && is_array($data[0]) && !self::isList($data[0])) {
                return self::flatten($data[0], $prefix);
            }
        }

        if (!is_array($data)) {
            return $rows;
        }

        foreach ($data as $key => $value) {
            $keyPath = $prefix !== '' ? $prefix . '.' . $key : (string) $key;

            if (is_array($value) && !self::isList($value)) {
                if (count($value) === 0) {
                    $rows[] = [$keyPath, '{}'];
                    continue;
                }
                foreach ($value as $childKey => $childValue) {
                    $childPath = $keyPath . '.' . $childKey;
                    if (is_array($childValue) && !self::isList($childValue)) {
                        $rows[] = [$childPath, count($childValue) . ' atributos'];
                    } elseif (is_array($childValue)) {
                        $rows[] = [$childPath, self::formatValue($childValue)];
                    } else {
                        $rows[] = [$childPath, self::formatValue($childValue)];
                    }
                }
            } else {
                $rows[] = [$keyPath, self::formatValue($value)];
            }
        }

        return $rows;
    }

    /**
     * @param mixed $value
     */
    public static function formatValue($value): string
    {
        if ($value === null) {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }
        if (is_array($value)) {
            if (self::isList($value)) {
                $primitives = true;
                foreach ($value as $item) {
                    if (!(is_null($item) || is_bool($item) || is_string($item) || is_numeric($item))) {
                        $primitives = false;
                        break;
                    }
                }
                if ($primitives) {
                    return implode(', ', array_map(
                        static fn ($item) => $item === null ? '—' : (is_bool($item) ? ($item ? 'Sí' : 'No') : (string) $item),
                        $value
                    ));
                }
                return count($value) . ' elementos';
            }
            return count($value) . ' atributos';
        }

        return (string) $value;
    }

    /**
     * Convierte una clave cruda en una etiqueta legible (Title Case,
     * conservando acronimos y traduciendo ANIO -> año).
     */
    public static function prettyKey(string $key): string
    {
        $segments = explode('.', $key);
        $part = end($segments);
        $part = str_replace('_', ' ', (string) $part);
        $part = preg_replace('/\bANIO\b/i', 'año', $part) ?? $part;

        $words = array_filter(preg_split('/\s+/', mb_strtolower($part)) ?: []);
        $out = [];
        foreach ($words as $word) {
            $upper = mb_strtoupper($word);
            if (in_array($upper, self::ACRONYMS, true)) {
                $out[] = $upper;
            } else {
                $out[] = mb_strtoupper(mb_substr($word, 0, 1)) . mb_substr($word, 1);
            }
        }

        return implode(' ', $out);
    }

    /**
     * Determina si una fila debe resaltarse como alerta segun el servicio.
     */
    public static function isAlertRow(string $service, string $keyLabel, string $value): bool
    {
        $svc = strtoupper($service);
        $u = mb_strtoupper($value);
        $kl = mb_strtolower($keyLabel);
        $trimmed = trim($value);

        switch ($svc) {
            case 'PGJ':
            case 'REPUVE':
                return (bool) preg_match('/RECUPERADO|ROBO|ROBADO|RECUP/', $u);
            case 'OCRA':
                $valueHit = (bool) preg_match('/RECUPERADO|ROBO|ROBADO|RECUP|TRUE/', $u);
                $keyHit = (bool) preg_match('/(con.?reporte.*recupera|estatus\s*vehiculo|estatus\s*reporte|estatus)/i', $kl);
                $truthy = (bool) preg_match('/^(true|1|si|sí)$/i', $trimmed);
                return $valueHit || ($keyHit && $truthy);
            case 'CARFAX':
                return (bool) preg_match('/THEFT|STOLEN|ROBO/', $u);
            case 'RAPI':
                if (str_contains($kl, 'tiene delito')) {
                    return (bool) preg_match('/^(true|1|si|sí)$/i', $trimmed);
                }
                return false;
            default:
                return false;
        }
    }

    /**
     * Calcula el banner de alerta de 5 niveles.
     *
     * @param array<string, mixed> $sections
     * @return array{level:string, bg:string, color:string, message:string}
     */
    public static function computeBanner(array $sections, bool $alertaRobo): array
    {
        if ($alertaRobo) {
            return [
                'level' => 'robo',
                'bg' => '#dc3545',
                'color' => '#ffffff',
                'message' => 'ALERTA: Posible Reporte Robo o Recuperado en fuentes oficiales.',
            ];
        }

        $repuve = self::firstObject($sections['repuve'] ?? null);
        if (is_array($repuve)) {
            $senas = $repuve['senas'] ?? $repuve['Senas'] ?? $repuve['SENAS'] ?? null;
            $msg = $repuve['Message'] ?? $repuve['message'] ?? $repuve['MESSAGE'] ?? null;

            if ($senas !== null && trim((string) $senas) !== '') {
                return [
                    'level' => 'senas',
                    'bg' => '#fd7e14',
                    'color' => '#ffffff',
                    'message' => trim((string) $senas),
                ];
            }

            if ($msg !== null && strtolower(trim((string) $msg)) === 'sin datos.') {
                return [
                    'level' => 'no_inscrito',
                    'bg' => '#ffc107',
                    'color' => '#000000',
                    'message' => 'Vehículo No Inscrito: No cuenta con el registro oficial de seguridad pública.',
                ];
            }
        }

        $aviso = self::firstObject($sections['aviso'] ?? null);
        if (is_array($aviso)) {
            $delito = $aviso['Delito'] ?? $aviso['delito'] ?? $aviso['DELITO'] ?? null;
            $tipoDelito = $aviso['Tipo Delito'] ?? $aviso['tipo_delito'] ?? $aviso['TipoDelito'] ?? $aviso['TIPO_DELITO'] ?? null;

            if ($delito !== null && trim((string) $delito) !== '' && $tipoDelito !== null && trim((string) $tipoDelito) !== '') {
                return [
                    'level' => 'aviso',
                    'bg' => '#6f42c1',
                    'color' => '#ffffff',
                    'message' => trim((string) $tipoDelito),
                ];
            }
        }

        return [
            'level' => 'ok',
            'bg' => '#198754',
            'color' => '#ffffff',
            'message' => 'Registro Público Vehícular: Sin reportes de robo o recuperación.',
        ];
    }

    /**
     * @param mixed $section
     * @return mixed
     */
    private static function firstObject($section)
    {
        if (is_array($section) && self::isList($section) && count($section) > 0) {
            return $section[0];
        }

        return $section;
    }

    /**
     * @param array<mixed> $array
     */
    private static function isList(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }
}
