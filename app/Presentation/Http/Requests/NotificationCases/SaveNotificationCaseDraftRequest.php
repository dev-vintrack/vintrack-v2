<?php

namespace App\Presentation\Http\Requests\NotificationCases;

use App\Application\NotificationCases\Services\NotificationCaseSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

final class SaveNotificationCaseDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $this->user() !== null && $case !== null && $case->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'request_key' => ['required', 'string', 'max:128', 'ascii'],
            'recovery_place' => ['nullable', 'string', 'max:191'],
            'country' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'municipality' => ['nullable', 'string', 'max:150'],
            'neighborhood' => ['nullable', 'string', 'max:150'],
            'postal_code' => ['nullable', 'string', 'max:16'],
            'street' => ['nullable', 'string', 'max:191'],
            'street_number' => ['nullable', 'string', 'max:32'],
            'recovered_at' => ['nullable', 'date_format:Y-m-d\\TH:i'],
            'license_plate' => ['nullable', 'string', 'max:20'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'model_year' => ['nullable', 'integer', 'between:1886,2100'],
            'engine_number' => ['nullable', 'string', 'max:64'],
            'color' => ['nullable', 'string', 'max:64'],
            'origin' => ['nullable', 'string', 'max:100'],
            'authority' => ['nullable', 'string', 'max:191'],
            'iph' => ['nullable', 'string', 'max:100'],
            'nuc' => ['nullable', 'string', 'max:100'],
            'investigation_file' => ['nullable', 'string', 'max:150'],
            'safekeeping' => ['nullable', 'string', 'max:191'],
            'inventory' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if (! $this->filled('recovered_at')) {
                return;
            }
            $timezone = app(NotificationCaseSettings::class)->timezone();
            $value = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', (string) $this->input('recovered_at'), $timezone);
            if ($value && $value->greaterThan(CarbonImmutable::now($timezone))) {
                $validator->errors()->add('recovered_at', 'La fecha y hora de recuperación no puede estar en el futuro.');
            }
        }];
    }

    public function draftFields(): array
    {
        return $this->safe()->except(['lock_version', 'request_key']);
    }
}
