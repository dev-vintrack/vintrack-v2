@extends('layouts.app')

@section('title', 'Proceso de Notificaciones')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Proceso de Notificaciones</h1><p class="text-muted mb-0">Expedientes bajo su responsabilidad.</p></div>
</div>

<div class="card shadow-sm"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Folio</th><th>VIN</th><th>Vehículo</th><th>Fecha límite</th><th>Estado general</th><th>Actualizado</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse($cases as $item)
        @php($needsAction = in_array($item->status->value, ['PENDING', 'REJECTED'], true))
        <tr class="{{ $needsAction ? 'table-danger' : '' }}">
            <td class="fw-semibold">{{ $item->case_number }}</td>
            <td>{{ $item->vin ?: 'PENDIENTE DE ASIGNACIÓN' }}</td>
            <td>{{ $item->license_plate ?: '—' }} / {{ $item->make ?: '—' }} {{ $item->model ?: '' }} {{ $item->model_year ?: '' }}</td>
            <td>{{ $item->notification_deadline_at->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</td>
            <td><span class="badge text-bg-{{ $needsAction ? 'danger' : 'secondary' }}">{{ $item->status->label() }}</span></td>
            <td>{{ $item->updated_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
            <td><a class="btn btn-sm btn-primary" href="{{ route('customer.notification-cases.show', $item) }}">{{ $needsAction ? 'Completar' : 'Ver' }}</a></td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-5">No tiene expedientes de notificación.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div></div>
<div class="mt-3">{{ $cases->links() }}</div>
@endsection
