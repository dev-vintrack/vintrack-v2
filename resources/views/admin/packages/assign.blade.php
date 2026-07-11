@extends('layouts.app')

@section('title', 'Asignar Paquete - Admin')

@section('content')
<div class="container py-4" style="max-width:600px;">
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

                <div class="mb-3">
                    <label class="form-label fw-semibold">Usuario <span class="text-danger">*</span></label>
                    <select name="user_id" class="form-select" required>
                        <option value="">— Selecciona un usuario —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
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

<script>
function showPackageDetail(sel) {
    var opt = sel.options[sel.selectedIndex];
    var detail = opt.dataset.detail || '';
    var days   = opt.dataset.days   || '';
    var div    = document.getElementById('package-detail');
    if (detail) {
        div.innerHTML = '<strong>Incluye:</strong> ' + detail + ' &nbsp;|&nbsp; <strong>Vigencia:</strong> ' + days + ' días';
    } else {
        div.innerHTML = '';
    }
}
</script>
@endsection
