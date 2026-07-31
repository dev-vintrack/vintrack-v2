<?php

namespace App\Presentation\Http\Controllers\Web\Admin;

use App\Infrastructure\Persistence\Models\NotificationDelivery;
use App\Infrastructure\Persistence\Models\NotificationPolicy;
use App\Infrastructure\Persistence\Models\ProviderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminNotificationController
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'event_type' => ['nullable', Rule::in(NotificationPolicy::EVENTS)],
            'status' => ['nullable', Rule::in(['pending', 'sent', 'failed', 'skipped'])],
            'provider_service_id' => 'nullable|integer|exists:provider_services,id',
        ]);

        $services = ProviderService::with(['provider', 'notificationPolicies'])
            ->orderBy('provider_id')
            ->orderBy('name')
            ->get();

        foreach ($services as $service) {
            NotificationPolicy::createDefaultsFor($service);
        }
        $services->load('notificationPolicies');

        $deliveries = NotificationDelivery::with(['user', 'service.provider'])
            ->when($filters['event_type'] ?? null, fn ($query, $event) => $query->where('event_type', $event))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['provider_service_id'] ?? null, fn ($query, $serviceId) => $query->where('provider_service_id', $serviceId))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $kpis = [
            'total' => NotificationDelivery::count(),
            'sent' => NotificationDelivery::where('status', 'sent')->count(),
            'failed' => NotificationDelivery::where('status', 'failed')->count(),
            'skipped' => NotificationDelivery::where('status', 'skipped')->count(),
        ];

        return view('admin.notifications.index', [
            'services' => $services,
            'deliveries' => $deliveries,
            'eventLabels' => NotificationPolicy::labels(),
            'kpis' => $kpis,
        ]);
    }

    public function update(Request $request, NotificationPolicy $policy)
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'low_balance_threshold' => 'nullable|numeric|min:0|max:99999999.99',
            'expiring_days' => ['nullable', 'string', 'max:100', 'regex:/^\s*\d+\s*(,\s*\d+\s*)*$/'],
            'cooldown_hours' => 'nullable|integer|min:0|max:8760',
            'bcc_email' => 'nullable|email|max:255',
        ]);

        $days = $policy->event_type === NotificationPolicy::EXPIRING
            ? collect(explode(',', (string) ($data['expiring_days'] ?? '')))
                ->map(fn ($day) => (int) trim($day))
                ->filter(fn ($day) => $day > 0 && $day <= 365)
                ->unique()
                ->sortDesc()
                ->values()
                ->all()
            : null;

        if ($policy->event_type === NotificationPolicy::EXPIRING && empty($days)) {
            return back()->withErrors(['expiring_days' => 'Captura al menos un día de aviso válido.']);
        }

        $policy->update([
            'enabled' => (bool) $data['enabled'],
            'low_balance_threshold' => $policy->event_type === NotificationPolicy::LOW_BALANCE
                ? $data['low_balance_threshold']
                : null,
            'expiring_days' => $days,
            'cooldown_hours' => $policy->event_type === NotificationPolicy::LOW_BALANCE
                ? (int) ($data['cooldown_hours'] ?? 72)
                : 0,
            'bcc_email' => $policy->event_type === NotificationPolicy::RISK_ALERT
                ? ($data['bcc_email'] ?? null)
                : null,
        ]);

        return back()->with('status', 'Política de notificación actualizada.');
    }
}
