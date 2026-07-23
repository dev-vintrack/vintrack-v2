@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
    <h1>Agregar Créditos a Usuario</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.credits.purchase.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="user_id" class="form-label">Usuario</label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="provider_service_id" class="form-label">Servicio / Producto</label>
                    <select name="provider_service_id" id="provider_service_id" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}">{{ $service->provider->name }} - {{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">Cantidad de créditos</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="validity_days" class="form-label">Días de vigencia</label>
                    <input type="number" min="1" name="validity_days" id="validity_days" class="form-control" placeholder="Ej. 30">
                    <div class="form-text">Dejar en blanco para no aplicar vigencia.</div>
                </div>
                <div class="mb-3">
                    <label for="reason" class="form-label">Motivo</label>
                    <input type="text" name="reason" id="reason" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Agregar créditos</button>
            </form>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
    const userAllowedServices = @json($userAllowedServices);
    const userSelect = document.getElementById('user_id');
    const serviceSelect = document.getElementById('provider_service_id');

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
    }

    userSelect?.addEventListener('change', filterServicesByUser);
    filterServicesByUser();
</script>
@endpush
@endsection
