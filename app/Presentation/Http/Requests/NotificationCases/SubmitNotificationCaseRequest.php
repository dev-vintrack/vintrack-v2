<?php

namespace App\Presentation\Http\Requests\NotificationCases;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitNotificationCaseRequest extends FormRequest
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
            'confirm_submission' => ['accepted'],
        ];
    }
}
