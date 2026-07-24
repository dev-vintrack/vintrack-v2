@extends('layouts.app')

@section('title', 'Nueva Compra - Admin')

@section('content')
<div class="row">
    <div class="col-12">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.purchases.index') }}" class="btn btn-sm btn-outline-secondary me-3">← Volver</a>
        <h2 class="mb-0">Nueva Compra a Proveedor</h2>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.purchases.store') }}" method="POST" onsubmit="return confirm('¿Esta seguro de que ya se efectuo la compra real?, ¿Ya tiene su comprobante?')">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Proveedor <span class="text-danger">*</span></label>
                    <select name="provider_id" id="provider_id" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        @foreach($providers as $provider)
                            <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Servicio / Producto <span class="text-danger">*</span></label>
                    <select name="provider_service_id" id="provider_service_id" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" data-provider="{{ $service->provider_id }}">
                                {{ $service->provider->name }} - {{ $service->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" value="{{ old('quantity') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Costo Unitario <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="unit_cost" class="form-control" value="{{ old('unit_cost', 0) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Fecha de compra <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', now()->format('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Activa</option>
                        <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Notas</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="500">{{ old('notes') }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Compra</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('provider_id').addEventListener('change', function () {
        const providerId = this.value;
        const serviceSelect = document.getElementById('provider_service_id');
        serviceSelect.querySelectorAll('option[data-provider]').forEach(option => {
            option.style.display = option.dataset.provider === providerId || option.value === '' ? 'block' : 'none';
        });
        if (serviceSelect.querySelector('option:checked').style.display === 'none') {
            serviceSelect.value = '';
        }
    });
</script>
@endsection
