<?php

namespace App\Domain\NotificationCases\Services;

use InvalidArgumentException;

final class TextNormalizer
{
    public function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value)) {
            throw new InvalidArgumentException('El texto contiene caracteres de control no permitidos.');
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        if ($value === '') {
            return null;
        }

        if (class_exists(\Normalizer::class)) {
            $value = \Normalizer::normalize($value, \Normalizer::FORM_D) ?: $value;
            $value = preg_replace('/\p{Mn}+/u', '', $value) ?? $value;
        } else {
            $value = strtr($value, [
                'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
                'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            ]);
        }

        return mb_strtoupper($value, 'UTF-8');
    }

    /** @param array<string, mixed> $fields */
    public function normalizeFields(array $fields): array
    {
        foreach ($fields as $key => $value) {
            if (is_string($value) || $value === null) {
                $fields[$key] = $this->normalize($value);
            }
        }

        return $fields;
    }
}
