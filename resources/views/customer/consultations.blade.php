@extends('layouts.app')

@section('title', 'Mis Consultas - VINTRACK')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div><h2 class="mb-1">Mis consultas</h2><p class="text-muted mb-0">Encuentra tus consultas y vuelve a abrir sus reportes.</p></div>
    <span class="badge text-bg-light border px-3 py-2"><i class="bi bi-shield-check text-success me-1"></i> Información privada</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Consultas</small><div class="d-flex justify-content-between align-items-end"><h3 class="mb-0">{{ number_format($kpis['total']) }}</h3><i class="bi bi-clock-history fs-2 text-primary"></i></div></div></div></div>
    <div class="col-6 col-xl"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Hoy</small><div class="d-flex justify-content-between align-items-end"><h3 class="mb-0">{{ number_format($kpis['today']) }}</h3><i class="bi bi-calendar-check fs-2 text-info"></i></div></div></div></div>
    <div class="col-6 col-xl"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Exitosas</small><div class="d-flex justify-content-between align-items-end"><h3 class="mb-0 text-success">{{ number_format($kpis['success']) }}</h3><i class="bi bi-check-circle fs-2 text-success"></i></div></div></div></div>
    <div class="col-6 col-xl"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">No completadas</small><div class="d-flex justify-content-between align-items-end"><h3 class="mb-0 text-danger">{{ number_format($kpis['failure']) }}</h3><i class="bi bi-x-circle fs-2 text-danger"></i></div></div></div></div>
    <div class="col-6 col-xl"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Alertas</small><div class="d-flex justify-content-between align-items-end"><h3 class="mb-0 text-warning">{{ number_format($kpis['alerts']) }}</h3><i class="bi bi-exclamation-triangle fs-2 text-warning"></i></div></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <form method="GET" action="{{ route('customer.consultations') }}" class="row g-3 align-items-end">
        <div class="col-md-4 col-xl-3"><label class="form-label" for="provider_id">Proveedor</label><select id="provider_id" name="provider_id" class="form-select"><option value="">Todos</option>@foreach($providers as $provider)<option value="{{ $provider->id }}" {{ (string) request('provider_id') === (string) $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>@endforeach</select></div>
        <div class="col-md-4 col-xl-2"><label class="form-label" for="criterio">Tipo</label><select id="criterio" name="criterio" class="form-select"><option value="">Todos</option>@foreach($criteria as $key => $label)<option value="{{ $key }}" {{ request('criterio') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-4 col-xl-2"><label class="form-label" for="date_from">Desde</label><input id="date_from" type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
        <div class="col-md-4 col-xl-2"><label class="form-label" for="date_to">Hasta</label><input id="date_to" type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
        <div class="col-md-4 col-xl-1"><div class="form-check mb-2"><input type="hidden" name="alerts_only" value="0"><input id="alerts_only" type="checkbox" name="alerts_only" value="1" class="form-check-input" {{ request()->boolean('alerts_only') ? 'checked' : '' }}><label class="form-check-label" for="alerts_only">Alertas</label></div></div>
        <div class="col-md-4 col-xl-2 d-flex gap-2"><button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Filtrar</button><a href="{{ route('customer.consultations') }}" class="btn btn-outline-secondary" aria-label="Limpiar filtros"><i class="bi bi-x-lg"></i></a></div>
    </form>
</div></div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Historial</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" data-refresh-table="#customerConsultationsTable"><i class="bi bi-arrow-clockwise me-1"></i> Actualizar</button>
    </div>
    <div class="card-body p-0"><div class="table-responsive"><table id="customerConsultationsTable" class="table table-hover align-middle mb-0 w-100">
        <thead class="table-light"><tr><th class="ps-4">Fecha</th><th>Proveedor</th><th>Consulta</th><th>Servicios</th><th>Costo</th><th>Resultado</th><th class="pe-4 text-end">Reporte</th></tr></thead>
        <tbody>
            @foreach($consultations as $consultation)
                <tr class="{{ $consultation->alerta_robo ? 'table-warning' : '' }}"><td class="ps-4 text-nowrap" data-order="{{ $consultation->created_at->timestamp }}">{{ $consultation->created_at->format('d/m/Y H:i') }}</td><td>{{ $consultation->provider?->name ?? '—' }}</td><td><span class="badge text-bg-light border">{{ strtoupper($consultation->criterio) }}</span><span class="d-block fw-semibold mt-1">{{ $consultation->valor }}</span></td><td>@forelse($consultation->services ?? [] as $service)<span class="badge text-bg-secondary me-1">{{ $service }}</span>@empty<span class="text-muted">—</span>@endforelse</td><td data-order="{{ $consultation->costo_credito }}">{{ number_format($consultation->costo_credito, 2) }}</td><td>@if($consultation->alerta_robo)<span class="badge text-bg-warning"><i class="bi bi-exclamation-triangle me-1"></i>Alerta</span>@elseif($consultation->success)<span class="badge text-bg-success">Completada</span>@else<span class="badge text-bg-secondary">No completada</span>@endif</td><td class="pe-4 text-end">@if($consultation->success)<a href="{{ route('reports.show', $consultation->id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-text me-1"></i>Ver</a>@else<span class="text-muted">No disponible</span>@endif</td></tr>
            @endforeach
        </tbody>
    </table></div></div>
</div>
@endsection

@include('customer.partials.datatable', [
    'tableId' => 'customerConsultationsTable',
    'order' => [[0, 'desc']],
    'emptyMessage' => 'No hay consultas para los filtros seleccionados.',
    'exportTitle' => 'Mis consultas',
    'exportFilename' => 'mis-consultas',
    'exportColumns' => [0, 1, 2, 3, 4, 5],
])
