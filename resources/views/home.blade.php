@extends('layouts.app')

@section('title', 'Dashboard - VINTRACK')

@section('content')
<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Dashboard</h1>

        <div class="row">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted" style="font-size:13px">Proveedor activo</div>
                                <div style="font-size:28px; font-weight:700">{{ $provider?->name() ?? 'N/A' }}</div>
                            </div>
                            <i class="bi bi-hdd-network" style="font-size:36px; color:#0d6efd"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted" style="font-size:13px">Código</div>
                                <div style="font-size:28px; font-weight:700">{{ $provider?->code()->value() ?? 'N/A' }}</div>
                            </div>
                            <i class="bi bi-upc-scan" style="font-size:36px; color:#198754"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted" style="font-size:13px">Costo por consulta</div>
                                <div style="font-size:28px; font-weight:700">{{ $provider ? number_format($provider->creditCost(), 2) : 'N/A' }}</div>
                            </div>
                            <i class="bi bi-credit-card-2-front" style="font-size:36px; color:#dc3545"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Mis saldos</h5>
                        @forelse ($wallets as $wallet)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Proveedor #{{ $wallet->providerId() }}</span>
                                <span class="badge bg-primary fs-6">{{ number_format($wallet->balance()->amount(), 2) }} créditos</span>
                            </div>
                        @empty
                            <p class="text-muted">No tienes saldos registrados.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        @if ($provider && $provider->isEnabled())
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Nueva consulta</h5>
                        <form id="consultaForm" action="{{ route('consult') }}" method="POST">
                            @csrf
                            <input type="hidden" name="provider" value="{{ $provider->code()->value() }}">
                            <div class="mb-3">
                                <label class="form-label">Tipo</label>
                                <select name="type" class="form-select" required>
                                    <option value="placa">Placa</option>
                                    <option value="niv">NIV</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Valor</label>
                                <input type="text" name="value" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Servicios</label>
                                <div>
                                    @foreach ($services as $service)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="services[]" value="{{ $service->key() }}" checked>
                                            <label class="form-check-label">{{ $service->name() }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Consultar</button>
                        </form>
                        <div id="consultaResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="alert alert-info mt-4">
            <strong>Sprint 2 activo.</strong> Wallet, consultas y activación de proveedores funcionando. El frontend original se irá portando progresivamente.
        </div>
    </div>
</div>

<script>
document.getElementById('consultaForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const resultDiv = document.getElementById('consultaResult');
    resultDiv.innerHTML = '<div class="alert alert-secondary">Consultando...</div>';
    try {
        const response = await fetch(this.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.querySelector('[name="_token"]').value
            },
            body: new FormData(this)
        });
        const data = await response.json();
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success">Consulta exitosa. Revisa la consola para detalles.</div>';
            console.log(data.data);
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Error') + '</div>';
        }
    } catch (err) {
        resultDiv.innerHTML = '<div class="alert alert-danger">Error de red: ' + err.message + '</div>';
    }
});
</script>
@endsection
