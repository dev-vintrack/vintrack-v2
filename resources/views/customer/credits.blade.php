@extends('layouts.app')

@section('title', 'Mis Créditos - VINTRACK')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div>
        <h2 class="mb-1">Mis créditos</h2>
        <p class="text-muted mb-0">Consulta tu saldo y vigencia por servicio.</p>
    </div>
    <span class="badge text-bg-light border px-3 py-2"><i class="bi bi-shield-check text-success me-1"></i> Información privada</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="d-flex justify-content-between"><div><small class="text-muted">Servicios con wallet</small><h3 class="mb-0">{{ number_format($kpis['total']) }}</h3></div><i class="bi bi-wallet2 fs-2 text-primary"></i></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="d-flex justify-content-between"><div><small class="text-muted">Saldo disponible</small><h3 class="mb-0">{{ number_format($kpis['balance'], 2) }}</h3></div><i class="bi bi-coin fs-2 text-success"></i></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="d-flex justify-content-between"><div><small class="text-muted">Wallets activos</small><h3 class="mb-0">{{ number_format($kpis['active']) }}</h3></div><i class="bi bi-check-circle fs-2 text-info"></i></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="d-flex justify-content-between"><div><small class="text-muted">Saldo bajo</small><h3 class="mb-0">{{ number_format($kpis['low_balance']) }}</h3></div><i class="bi bi-exclamation-triangle fs-2 text-warning"></i></div>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('customer.credits') }}" class="row g-3 align-items-end">
            <div class="col-md-9">
                <label class="form-label" for="provider_service_id">Servicio</label>
                <select id="provider_service_id" name="provider_service_id" class="form-select">
                    <option value="">Todos mis servicios</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" {{ (string) request('provider_service_id') === (string) $service->id ? 'selected' : '' }}>
                            {{ $service->provider?->name }} - {{ $service->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Filtrar</button>
                <a href="{{ route('customer.credits') }}" class="btn btn-outline-secondary" aria-label="Limpiar filtros"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Saldo por servicio</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" data-refresh-table="#customerCreditsTable">
            <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="customerCreditsTable" class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light"><tr><th class="ps-4">Servicio</th><th>Saldo</th><th>Vigencia inicial</th><th>Vigencia final</th><th class="pe-4">Estado</th></tr></thead>
                <tbody>
                    @foreach($wallets as $wallet)
                        <tr>
                            <td class="ps-4"><span class="fw-semibold">{{ $wallet->service?->name ?? 'Servicio no disponible' }}</span><small class="d-block text-muted">{{ $wallet->service?->provider?->name }}</small></td>
                            <td data-order="{{ $wallet->balance }}"><span class="badge rounded-pill text-bg-{{ $wallet->balance <= $wallet->min_alert ? 'warning' : 'success' }} px-3 py-2">{{ number_format($wallet->balance, 2) }}</span></td>
                            <td data-order="{{ $wallet->validity_start?->timestamp ?? 0 }}">{{ $wallet->validity_start?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                            <td data-order="{{ $wallet->validity_end?->timestamp ?? 0 }}">{{ $wallet->validity_end?->format('d/m/Y H:i') ?? 'Sin vencimiento' }}</td>
                            <td class="pe-4"><span class="badge text-bg-{{ $wallet->status === 'active' ? 'success' : 'secondary' }}">{{ $wallet->status === 'active' ? 'Activo' : 'Expirado' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@include('customer.partials.datatable', [
    'tableId' => 'customerCreditsTable',
    'order' => [[0, 'asc']],
    'emptyMessage' => 'No tienes créditos para los filtros seleccionados.',
    'exportTitle' => 'Mis créditos',
    'exportFilename' => 'mis-creditos',
    'exportColumns' => [0, 1, 2, 3, 4],
])
