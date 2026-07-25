@extends('layouts.app')

@section('title', 'Asignar Paquete - Admin')

@section('content')
<div class="row">
    <div class="col-12">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.packages.index') }}" class="btn btn-sm btn-outline-secondary me-3">← Volver</a>
        <h2 class="mb-0">Asignar Paquete a Usuario</h2>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="alert alert-info" style="font-size:13px;">
                Al asignar un paquete se acreditan automáticamente los créditos correspondientes a cada proveedor en el wallet del usuario, con la vigencia configurada en el paquete.
            </div>

            <form action="{{ route('admin.packages.assign.store') }}" method="POST">
                @csrf

                <div class="alert alert-info" style="font-size:13px;">
                    <strong>Beneficio para el Usuario:</strong> Si el usuario cuenta con Saldo previo y activa un Paquete de créditos Nuevo, su Vigencia se ajustará automáticamente a la duración del paquete que acaba de comprar.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Usuario <span class="text-danger">*</span></label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">— Selecciona un usuario —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="wallet-summary" class="mb-3" style="display:none;">
                    <label class="form-label fw-semibold">Saldos actuales del usuario</label>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre del Servicio</th>
                                    <th>Saldo actual de créditos</th>
                                    <th>Vigencia Inicial</th>
                                    <th>Vigencia Final</th>
                                </tr>
                            </thead>
                            <tbody id="wallet-summary-body">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Paquete <span class="text-danger">*</span></label>
                    <select name="package_id" id="package_id" class="form-select" required onchange="showPackageDetail(this)">
                        <option value="">— Selecciona un paquete —</option>
                        @foreach($packages as $package)
                            <option value="{{ $package->id }}"
                                    data-detail="{{ $package->items->map(fn($i) => ($i->provider->name ?? 'Proveedor #'.$i->provider_id).': '.$i->credits.' créditos')->join(' | ') }}"
                                    data-days="{{ $package->validity_days }}"
                                    {{ old('package_id') == $package->id ? 'selected' : '' }}>
                                {{ $package->name }} — ${{ number_format($package->price, 2) }}
                            </option>
                        @endforeach
                    </select>
                    <div id="package-detail" class="mt-2 text-muted" style="font-size:13px;"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Notas (opcional)</label>
                    <input type="text" name="notes" class="form-control" maxlength="255" value="{{ old('notes') }}"
                           placeholder="Ej: Compra en efectivo, cortesía, etc.">
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Asignar y Acreditar Créditos</button>
                </div>
            </form>
        </div>
    </div>
    </div>
</div>

<script>
const userAllowedPackageIds = @json($userAllowedPackageIds);
const userSelect = document.querySelector('select[name="user_id"]');
const packageSelect = document.getElementById('package_id');

function filterPackagesByUser() {
    const userId = userSelect.value;
    const allowed = userAllowedPackageIds[userId] || [];

    packageSelect.querySelectorAll('option[value]').forEach(option => {
        if (option.value === '') return;
        const allowedForUser = allowed.includes(parseInt(option.value, 10));
        option.hidden = !allowedForUser;
        option.disabled = !allowedForUser;
    });

    const selected = packageSelect.querySelector('option:checked');
    if (selected && selected.value !== '' && !allowed.includes(parseInt(selected.value, 10))) {
        packageSelect.value = '';
    }

    showPackageDetail(packageSelect);
}

function showPackageDetail(sel) {
    var opt = sel.options[sel.selectedIndex];
    var div = document.getElementById('package-detail');

    if (!opt || opt.value === '') {
        div.innerHTML = '';
        return;
    }

    var detail = opt.dataset.detail || '';
    var days   = opt.dataset.days   || '';

    if (detail) {
        div.innerHTML = '<strong>Incluye:</strong> ' + detail + ' &nbsp;|&nbsp; <strong>Vigencia:</strong> ' + days + ' días';
    } else {
        div.innerHTML = '';
    }
}

const walletSummary = document.getElementById('wallet-summary');
const walletSummaryBody = document.getElementById('wallet-summary-body');

async function loadUserWallets() {
    const userId = userSelect.value;
    walletSummaryBody.innerHTML = '';

    if (!userId) {
        walletSummary.style.display = 'none';
        return;
    }

    try {
        const response = await fetch('{{ route('admin.packages.user-wallets') }}?user_id=' + encodeURIComponent(userId), {
            headers: { 'Accept': 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Error al consultar los wallets');
        }

        const data = await response.json();
        const wallets = data.wallets || [];

        if (wallets.length === 0) {
            walletSummaryBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">El usuario no tiene saldos previos registrados.</td></tr>';
        } else {
            wallets.forEach(wallet => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${escapeHtml(wallet.service_name)}</td>
                    <td>${Number(wallet.balance).toFixed(2)}</td>
                    <td>${wallet.validity_start || '—'}</td>
                    <td>${wallet.validity_end || '—'}</td>
                `;
                walletSummaryBody.appendChild(row);
            });
        }

        walletSummary.style.display = 'block';
    } catch (error) {
        console.error(error);
        walletSummaryBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">No se pudieron cargar los saldos del usuario.</td></tr>';
        walletSummary.style.display = 'block';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

userSelect?.addEventListener('change', () => {
    filterPackagesByUser();
    loadUserWallets();
});

filterPackagesByUser();
loadUserWallets();
</script>
@endsection
