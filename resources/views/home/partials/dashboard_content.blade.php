<div class="row">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Costo de la Consulta por proveedor</h5>
                @forelse ($providers as $p)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ $p->name() }} <small class="text-muted">({{ $p->adapterCode() }})</small></span>
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
                    @php
                        $serviceName = $serviceNames[$wallet->providerServiceId()] ?? 'Servicio #' . $wallet->providerServiceId();
                        $minAlertClient = (float) ($serviceMinAlerts[$wallet->providerServiceId()] ?? 0);
                        $balance = (float) $wallet->balance()->amount();
                        $isLowClient = $balance <= $minAlertClient;
                        $validityEnd = $wallet->validityEnd();
                        if ($validityEnd) {
                            $daysRemaining = ceil(($validityEnd->getTimestamp() - now()->timestamp) / 86400);
                            $validityFormatted = $validityEnd->format('d/m/Y H:i');
                            if ($daysRemaining > 1) {
                                $daysText = $daysRemaining . ' días restantes';
                            } elseif ($daysRemaining === 1.0) {
                                $daysText = '1 día restante';
                            } elseif ($daysRemaining === 0.0) {
                                $daysText = 'Vence hoy';
                            } else {
                                $daysText = 'Vencido';
                            }
                        }
                    @endphp
                    <div class="d-flex justify-content-between align-items-start mb-2 p-2 rounded {{ $isLowClient ? 'bg-danger text-white' : 'bg-success text-white' }}">
                        <div>
                            <span class="fw-bold">{{ $serviceName }}</span>
                            @if($isLowClient)
                                <br><small class="text-white">Saldo próximo a agotarse</small>
                            @endif
                            @if($validityEnd)
                                <br><small class="text-white">Vigencia: {{ $validityFormatted }} ({{ $daysText }})</small>
                            @endif
                        </div>
                        <span class="badge {{ $isLowClient ? 'bg-white text-danger' : 'bg-white text-success' }} fs-6">{{ number_format($balance, 2) }} créditos</span>
                    </div>
                @empty
                    <p class="text-muted">No tienes saldos registrados.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if (count($serviceOptions) > 0)
<div class="row mt-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Nueva consulta</h5>
                <form id="consultaForm" action="{{ route('consult') }}" method="POST">
                    @csrf
                    <input type="hidden" id="providerHidden" name="provider_id" value="">
                    <input type="hidden" id="serviceKeyHidden" name="services[]" value="">
                    <div class="mb-3">
                        <label class="form-label">Servicio</label>
                        <select id="serviceSelect" class="form-select" required>
                            <option value="">Selecciona un servicio...</option>
                            @foreach ($serviceOptions as $option)
                                <option value="{{ $option['id'] }}"
                                        data-provider-id="{{ $option['provider_id'] }}"
                                        data-provider-adapter-code="{{ $option['provider_adapter_code'] }}"
                                        data-key="{{ $option['key'] }}">
                                    {{ $option['provider_name'] }} - {{ $option['name'] }}
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
                    <button type="submit" class="btn btn-primary">Consultar</button>
                </form>
                <div id="consultaResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
const serviceSelect = document.getElementById('serviceSelect');
const providerHidden = document.getElementById('providerHidden');
const serviceKeyHidden = document.getElementById('serviceKeyHidden');
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

function updateServiceUI() {
    const option = serviceSelect.options[serviceSelect.selectedIndex];
    const providerId = option?.dataset.providerId ?? '';
    const providerAdapterCode = option?.dataset.providerAdapterCode ?? '';
    const serviceKey = option?.dataset.key ?? '';

    providerHidden.value = providerId;
    serviceKeyHidden.value = serviceKey;

    if (providerAdapterCode === 'vindata') {
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
serviceSelect?.addEventListener('change', updateServiceUI);
updateServiceUI();

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
@endif
