@extends('layouts.app')

@section('title', 'Mis Movimientos - VINTRACK')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div><h2 class="mb-1">Mis movimientos</h2><p class="text-muted mb-0">Revisa entradas y consumos de créditos de tu cuenta.</p></div>
    <span class="badge text-bg-light border px-3 py-2"><i class="bi bi-shield-check text-success me-1"></i> Información privada</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Movimientos</small><div class="d-flex justify-content-between align-items-end"><h3 class="mb-0">{{ number_format($kpis['total']) }}</h3><i class="bi bi-arrow-left-right fs-2 text-primary"></i></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Créditos recibidos</small><div class="d-flex justify-content-between align-items-end"><h3 class="mb-0 text-success">+{{ number_format($kpis['credits'], 2) }}</h3><i class="bi bi-arrow-down-circle fs-2 text-success"></i></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Créditos consumidos</small><div class="d-flex justify-content-between align-items-end"><h3 class="mb-0 text-danger">-{{ number_format($kpis['debits'], 2) }}</h3><i class="bi bi-arrow-up-circle fs-2 text-danger"></i></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Balance del periodo</small><div class="d-flex justify-content-between align-items-end"><h3 class="mb-0">{{ number_format($kpis['balance'], 2) }}</h3><i class="bi bi-activity fs-2 text-info"></i></div></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <form method="GET" action="{{ route('customer.movements') }}" class="row g-3 align-items-end">
        <div class="col-lg-4"><label class="form-label" for="provider_service_id">Servicio</label><select id="provider_service_id" name="provider_service_id" class="form-select"><option value="">Todos mis servicios</option>@foreach($services as $service)<option value="{{ $service->id }}" {{ (string) request('provider_service_id') === (string) $service->id ? 'selected' : '' }}>{{ $service->provider?->name }} - {{ $service->name }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label" for="date_from">Desde</label><input id="date_from" type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label" for="date_to">Hasta</label><input id="date_to" type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
        <div class="col-lg-2 d-flex gap-2"><button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Filtrar</button><a href="{{ route('customer.movements') }}" class="btn btn-outline-secondary" aria-label="Limpiar filtros"><i class="bi bi-x-lg"></i></a></div>
    </form>
</div></div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Actividad de créditos</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" data-refresh-table="#customerMovementsTable"><i class="bi bi-arrow-clockwise me-1"></i> Actualizar</button>
    </div>
    <div class="card-body p-0"><div class="table-responsive"><table id="customerMovementsTable" class="table table-hover align-middle mb-0 w-100">
        <thead class="table-light"><tr><th class="ps-4">Fecha</th><th>Servicio</th><th>Movimiento</th><th class="pe-4">Concepto</th></tr></thead>
        <tbody>
            @foreach($movements as $movement)
                <tr><td class="ps-4 text-nowrap" data-order="{{ $movement->created_at ? \Carbon\Carbon::parse($movement->created_at)->timestamp : 0 }}">{{ $movement->created_at ? \Carbon\Carbon::parse($movement->created_at)->format('d/m/Y H:i') : '—' }}</td><td><span class="fw-semibold">{{ $movement->wallet?->service?->name ?? 'Servicio no disponible' }}</span><small class="d-block text-muted">{{ $movement->wallet?->service?->provider?->name }}</small></td><td data-order="{{ $movement->delta }}"><span class="badge rounded-pill text-bg-{{ $movement->delta >= 0 ? 'success' : 'danger' }} px-3 py-2">{{ $movement->delta >= 0 ? '+' : '' }}{{ number_format($movement->delta, 2) }}</span></td><td class="pe-4">{{ $movement->reason }}</td></tr>
            @endforeach
        </tbody>
    </table></div></div>
</div>
@endsection

@include('customer.partials.datatable', [
    'tableId' => 'customerMovementsTable',
    'order' => [[0, 'desc']],
    'emptyMessage' => 'No hay movimientos para los filtros seleccionados.',
    'exportTitle' => 'Mis movimientos',
    'exportFilename' => 'mis-movimientos',
    'exportColumns' => [0, 1, 2, 3],
])
