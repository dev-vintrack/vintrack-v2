<?php

namespace App\Presentation\Http\Requests\NotificationCases;

use App\Application\NotificationCases\Services\NotificationCaseAuthorizationService;
use Illuminate\Foundation\Http\FormRequest;

final class AdminTransitionNotificationCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && app(NotificationCaseAuthorizationService::class)->canReview($this->user());
    }

    public function rules(): array
    {
        $rules = ['lock_version' => ['required', 'integer', 'min:0'], 'request_key' => ['required', 'string', 'max:128', 'ascii']];
        if ($this->routeIs('admin.notification-cases.reject')) {
            $rules['reason'] = ['required', 'string', 'max:2000', 'not_regex:/^\\s*$/u'];
        }

        return $rules;
    }
}
