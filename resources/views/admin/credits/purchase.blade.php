@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <h1>Agregar Créditos a Usuario</h1>

        <div class="alert alert-info">
            <i class="bi bi-info-circle me-1"></i>
            Si el usuario cuenta con un Paquete activo (vigente), el sistema no te permite vender/asigna creditos directos.
        </div>

        <div id="dashboardSection" class="card mb-3 d-none">
            <div class="card-header">Resumen del crédito seleccionado</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card text-center h-100 border-primary">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Saldo actual</h6>
                                <p class="card-text fs-4 fw-bold" id="balanceValue">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center h-100 border-secondary">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Vigencia Inicial</h6>
                                <p class="card-text" id="validityStartValue">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center h-100 border-secondary">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Vigencia Final</h6>
                                <p class="card-text" id="validityEndValue">-</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="activePackageAlert" class="alert alert-warning mt-3 mb-0 d-none">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    El usuario tiene al menos un paquete activo vigente. No se pueden agregar créditos directos.
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.credits.purchase.store') }}" method="POST" id="purchaseForm">
                    @csrf
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Usuario</label>
                        <select name="user_id" id="user_id" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="provider_service_id" class="form-label">Servicio / Producto</label>
                        <select name="provider_service_id" id="provider_service_id" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" {{ old('provider_service_id') == $service->id ? 'selected' : '' }}>{{ $service->provider->name }} - {{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Cantidad de créditos</label>
                        <div class="form-text text-muted">Mínimo de compra: {{ $config->min_purchase_user }}</div>
                        <input type="number"
                               step="{{ (float) $config->step_purchase_input }}"
                               min="{{ (float) $config->min_purchase_user }}"
                               max="{{ (float) $config->max_purchase_user }}"
                               value="{{ old('amount', $config->min_purchase_user) }}"
                               name="amount" id="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-block">Días de vigencia</label>
                        @foreach ($validityOptions as $days)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="validity_days" id="validity_{{ $days }}" value="{{ $days }}" {{ old('validity_days', $validityOptions[0] ?? null) == $days ? 'checked' : '' }} required>
                                <label class="form-check-label" for="validity_{{ $days }}">{{ $days }} días</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Motivo</label>
                        <input type="text" name="reason" id="reason" class="form-control" value="{{ old('reason') }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Agregar créditos</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const userAllowedServices = @json($userAllowedServices);
    const walletInfoUrl = @json(route('admin.credits.wallet-info'));
    const userSelect = document.getElementById('user_id');
    const serviceSelect = document.getElementById('provider_service_id');
    const dashboardSection = document.getElementById('dashboardSection');
    const balanceValue = document.getElementById('balanceValue');
    const validityStartValue = document.getElementById('validityStartValue');
    const validityEndValue = document.getElementById('validityEndValue');
    const activePackageAlert = document.getElementById('activePackageAlert');
    const submitBtn = document.getElementById('submitBtn');
    const purchaseForm = document.getElementById('purchaseForm');

    function filterServicesByUser() {
        const userId = userSelect.value;
        const allowed = userAllowedServices[userId] || [];

        serviceSelect.querySelectorAll('option[value]').forEach(option => {
            if (option.value === '') return;
            const allowedForUser = allowed.includes(parseInt(option.value, 10));
            option.hidden = !allowedForUser;
            option.disabled = !allowedForUser;
        });

        const selected = serviceSelect.querySelector('option:checked');
        if (selected && selected.value !== '' && !allowed.includes(parseInt(selected.value, 10))) {
            serviceSelect.value = '';
        }

        updateDashboard();
    }

    function updateDashboard() {
        const userId = userSelect.value;
        const serviceId = serviceSelect.value;
        const selectedOption = serviceSelect.querySelector('option:checked');

        if (!userId || !serviceId || selectedOption?.disabled) {
            dashboardSection.classList.add('d-none');
            submitBtn.disabled = false;
            activePackageAlert.classList.add('d-none');
            return;
        }

        fetch(`${walletInfoUrl}?user_id=${encodeURIComponent(userId)}&provider_service_id=${encodeURIComponent(serviceId)}`)
            .then(response => response.json())
            .then(data => {
                dashboardSection.classList.remove('d-none');
                balanceValue.textContent = data.balance.toLocaleString('es-MX');
                validityStartValue.textContent = data.validity_start ?? 'Sin vigencia';
                validityEndValue.textContent = data.validity_end ?? 'Sin vigencia';

                if (data.has_active_package) {
                    activePackageAlert.classList.remove('d-none');
                    submitBtn.disabled = true;
                } else {
                    activePackageAlert.classList.add('d-none');
                    submitBtn.disabled = false;
                }
            })
            .catch(() => {
                dashboardSection.classList.add('d-none');
            });
    }

    purchaseForm?.addEventListener('submit', function (event) {
        if (submitBtn.disabled) {
            event.preventDefault();
            return false;
        }
    });

    userSelect?.addEventListener('change', filterServicesByUser);
    serviceSelect?.addEventListener('change', updateDashboard);
    filterServicesByUser();
</script>
@endpush
@endsection
