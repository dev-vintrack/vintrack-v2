@extends('layouts.app')

@section('title', 'Notificaciones - VINTRACK')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div>
        <h2 class="mb-1">Notificaciones</h2>
        <p class="text-muted mb-0">Configura políticas por servicio y consulta el seguimiento de correos.</p>
    </div>
    <span class="badge text-bg-light border px-3 py-2"><i class="bi bi-envelope-check text-primary me-1"></i> Control de entregas</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Registradas</small><h3 class="mb-0">{{ number_format($kpis['total']) }}</h3></div></div></div>
    <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Enviadas</small><h3 class="mb-0 text-success">{{ number_format($kpis['sent']) }}</h3></div></div></div>
    <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Fallidas</small><h3 class="mb-0 text-danger">{{ number_format($kpis['failed']) }}</h3></div></div></div>
    <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Omitidas</small><h3 class="mb-0 text-secondary">{{ number_format($kpis['skipped']) }}</h3></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4">
        <h5 class="mb-1">Políticas por servicio</h5>
        <p class="text-muted small mb-0">Saldo cero tiene prioridad sobre saldo bajo; vencimiento tiene prioridad sobre saldo cero.</p>
    </div>
    <div class="card-body">
        <div class="accordion" id="notificationPolicies">
            @foreach($services as $service)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="serviceHeading{{ $service->id }}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#servicePolicies{{ $service->id }}">
                            <span class="fw-semibold">{{ $service->name }}</span>
                            <span class="text-muted ms-2">{{ $service->provider?->name ?? '—' }}</span>
                        </button>
                    </h2>
                    <div id="servicePolicies{{ $service->id }}" class="accordion-collapse collapse" data-bs-parent="#notificationPolicies">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light"><tr><th>Evento</th><th>Activo</th><th>Configuración</th><th class="text-end">Acción</th></tr></thead>
                                    <tbody>
                                        @foreach($service->notificationPolicies->sortBy('event_type') as $policy)
                                            <tr>
                                                    <td>
                                                        <form id="policyForm{{ $policy->id }}" method="POST" action="{{ route('admin.notifications.update', $policy) }}">
                                                            @csrf
                                                            @method('PUT')
                                                        </form>
                                                        <span class="fw-semibold">{{ $eventLabels[$policy->event_type] ?? $policy->event_type }}</span><small class="d-block text-muted">{{ $policy->event_type }}</small>
                                                    </td>
                                                    <td>
                                                        <input form="policyForm{{ $policy->id }}" type="hidden" name="enabled" value="0">
                                                        <div class="form-check form-switch"><input form="policyForm{{ $policy->id }}" class="form-check-input" type="checkbox" name="enabled" value="1" {{ $policy->enabled ? 'checked' : '' }}></div>
                                                    </td>
                                                    <td>
                                                        @if($policy->event_type === \App\Infrastructure\Persistence\Models\NotificationPolicy::LOW_BALANCE)
                                                            <div class="row g-2">
                                                                <div class="col-sm-6"><label class="form-label small">Umbral</label><input form="policyForm{{ $policy->id }}" type="number" step="0.01" min="0" name="low_balance_threshold" value="{{ $policy->low_balance_threshold }}" class="form-control form-control-sm" required></div>
                                                                <div class="col-sm-6"><label class="form-label small">Espera (horas)</label><input form="policyForm{{ $policy->id }}" type="number" min="1" name="cooldown_hours" value="{{ $policy->cooldown_hours }}" class="form-control form-control-sm" required></div>
                                                            </div>
                                                        @elseif($policy->event_type === \App\Infrastructure\Persistence\Models\NotificationPolicy::EXPIRING)
                                                            <label class="form-label small">Días antes, separados por coma</label><input form="policyForm{{ $policy->id }}" type="text" name="expiring_days" value="{{ implode(', ', $policy->expiring_days ?? [7, 3, 1]) }}" class="form-control form-control-sm" required>
                                                        @elseif($policy->event_type === \App\Infrastructure\Persistence\Models\NotificationPolicy::RISK_ALERT)
                                                            <label class="form-label small">BCC administrativo opcional</label><input form="policyForm{{ $policy->id }}" type="email" name="bcc_email" value="{{ $policy->bcc_email }}" placeholder="Usa ADMIN_EMAIL si queda vacío" class="form-control form-control-sm">
                                                        @else
                                                            <span class="text-muted small">Sin parámetros adicionales.</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end"><button form="policyForm{{ $policy->id }}" type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save me-1"></i>Guardar</button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.notifications.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4"><label class="form-label">Servicio</label><select name="provider_service_id" class="form-select"><option value="">Todos</option>@foreach($services as $service)<option value="{{ $service->id }}" {{ (string) request('provider_service_id') === (string) $service->id ? 'selected' : '' }}>{{ $service->provider?->name }} - {{ $service->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Evento</label><select name="event_type" class="form-select"><option value="">Todos</option>@foreach($eventLabels as $event => $label)<option value="{{ $event }}" {{ request('event_type') === $event ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Estado</label><select name="status" class="form-select"><option value="">Todos</option><option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Enviado</option><option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Fallido</option><option value="skipped" {{ request('status') === 'skipped' ? 'selected' : '' }}>Omitido</option><option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente</option></select></div>
            <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Filtrar</button><a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-0">Historial de entregas</h5></div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th class="ps-4">Fecha</th><th>Evento</th><th>Cliente</th><th>Servicio</th><th>Destinatario</th><th>Estado</th><th>Intentos</th><th class="pe-4">Detalle</th></tr></thead>
        <tbody>
            @forelse($deliveries as $delivery)
                @php($statusColor = ['sent' => 'success', 'failed' => 'danger', 'skipped' => 'secondary', 'pending' => 'warning'][$delivery->status] ?? 'secondary')
                <tr>
                    <td class="ps-4 text-nowrap">{{ $delivery->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td><span class="fw-semibold">{{ $eventLabels[$delivery->event_type] ?? $delivery->event_type }}</span><small class="d-block text-muted">{{ $delivery->uuid }}</small></td>
                    <td>{{ $delivery->user?->name ?? '—' }}</td>
                    <td>{{ $delivery->service?->name ?? '—' }}<small class="d-block text-muted">{{ $delivery->service?->provider?->name }}</small></td>
                    <td>{{ $delivery->recipient ?? '—' }}@if($delivery->bcc)<small class="d-block text-muted">BCC: {{ $delivery->bcc }}</small>@endif</td>
                    <td><span class="badge text-bg-{{ $statusColor }}">{{ ucfirst($delivery->status) }}</span></td>
                    <td>{{ $delivery->attempts }}</td>
                    <td class="pe-4"><span>{{ $delivery->subject }}</span>@if($delivery->error)<small class="d-block text-danger">{{ $delivery->error }}</small>@endif</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-5">No existen entregas para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table></div></div>
    @if($deliveries->hasPages())<div class="card-footer bg-white border-0 d-flex justify-content-center pt-3">{{ $deliveries->links() }}</div>@endif
</div>
@endsection
