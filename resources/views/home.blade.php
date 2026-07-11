@extends('layouts.app')

@section('title', 'Dashboard - VINTRACK')

@section('content')
<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Dashboard</h1>

        <div class="row">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Proveedores activos</h5>
                        @forelse ($providers as $p)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>{{ $p->name() }} <small class="text-muted">({{ $p->code()->value() }})</small></span>
                                <span class="badge bg-primary">{{ number_format($p->creditCost(), 2) }} créditos</span>
                            </div>
                        @empty
                            <p class="text-muted">No hay proveedores activos.</p>
                        @endforelse
                    </div>
                </div>
            </div>

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

        @if (count($providers) > 0)
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Nueva consulta</h5>
                        <form id="consultaForm" action="{{ route('consult') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Proveedor</label>
                                <select id="providerSelect" name="provider" class="form-select" required>
                                    @foreach ($providers as $p)
                                        <option value="{{ $p->code()->value() }}" data-type="{{ $p->code()->value() === 'VINDATA' ? 'vin' : 'placa' }}">
                                            {{ $p->name() }} ({{ $p->code()->value() }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tipo</label>
                                <select id="typeSelect" class="form-select" required>
                                    <option value="placa">Placa</option>
                                    <option value="niv">NIV</option>
                                    <option value="vin" style="display:none">VIN</option>
                                </select>
                                <input type="hidden" id="typeHidden" name="type" value="">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Valor</label>
                                <input type="text" name="value" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Servicios</label>
                                <div id="servicesContainer">
                                    @foreach ($providers as $p)
                                        <div class="provider-services" data-provider="{{ $p->code()->value() }}" style="{{ $loop->first ? '' : 'display:none;' }}">
                                            @foreach ($servicesByProvider[$p->id()->value()] as $service)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="services[]" value="{{ $service->key() }}" {{ $loop->first ? 'checked' : '' }}>
                                                    <label class="form-check-label">{{ $service->name() }}</label>
                                                </div>
                                            @endforeach
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

<script>
const providerSelect = document.getElementById('providerSelect');
const typeSelect = document.getElementById('typeSelect');
const typeHidden = document.getElementById('typeHidden');

function escapeHtml(unsafe) {
    return String(unsafe ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function syncType() {
    typeHidden.value = typeSelect.value;
}

function updateProviderUI() {
    const provider = providerSelect.value;
    document.querySelectorAll('.provider-services').forEach(el => {
        el.style.display = el.dataset.provider === provider ? 'block' : 'none';
        el.querySelectorAll('input').forEach(input => {
            input.disabled = el.dataset.provider !== provider;
        });
    });

    if (provider === 'VINDATA') {
        typeSelect.value = 'vin';
        typeSelect.disabled = true;
    } else {
        typeSelect.disabled = false;
        if (typeSelect.value === 'vin') {
            typeSelect.value = 'placa';
        }
    }

    syncType();
}

typeSelect?.addEventListener('change', syncType);
providerSelect?.addEventListener('change', updateProviderUI);
updateProviderUI();

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
            let html = '';
            if (data.banner) {
                const b = data.banner;
                html += '<div class="alert" style="background-color:' + b.bg + ';color:' + b.color + ';border:none;">'
                     + '<strong>' + escapeHtml(b.message) + '</strong></div>';
            } else {
                html += '<div class="alert alert-success">Consulta exitosa.</div>';
            }
            if (data.local_report_url) {
                html += '<div class="mb-2"><a href="' + data.local_report_url + '" class="btn btn-sm btn-outline-primary" target="_blank">Ver reporte VINTrack</a></div>';
            } else if (data.report_url) {
                html += '<div class="mb-2"><a href="' + data.report_url + '" class="btn btn-sm btn-outline-secondary" target="_blank">Ver reporte del proveedor</a></div>';
            }
            resultDiv.innerHTML = html;
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
