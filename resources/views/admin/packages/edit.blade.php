@extends('layouts.app')

@section('title', 'Editar Paquete - Admin')

@section('content')
<div class="container py-4" style="max-width:680px;">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.packages.index') }}" class="btn btn-sm btn-outline-secondary me-3">← Volver</a>
        <h2 class="mb-0">Editar Paquete: {{ $package->name }}</h2>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.packages.update', $package->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre del paquete <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $package->name) }}" required maxlength="100">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea name="description" class="form-control" rows="2" maxlength="500">{{ old('description', $package->description) }}</textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Precio ($) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" value="{{ old('price', $package->price) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Vigencia (días) <span class="text-danger">*</span></label>
                        <input type="number" name="validity_days" class="form-control" min="1" value="{{ old('validity_days', $package->validity_days) }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="active" id="active" value="1"
                               {{ old('active', $package->active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="active">Paquete activo</label>
                    </div>
                </div>

                <hr>
                <h6 class="fw-bold mb-3">Créditos por proveedor</h6>
                <p class="text-muted" style="font-size:13px;">Ingresa 0 para no incluir créditos de ese proveedor.</p>

                @foreach($providers as $provider)
                @php $currentCredits = $itemsByProvider[$provider->id]->credits ?? 0; @endphp
                <div class="mb-3 d-flex align-items-center gap-3">
                    <label class="form-label mb-0 fw-semibold" style="min-width:140px;">{{ $provider->name }}</label>
                    <input type="number" name="credits[{{ $provider->id }}]" class="form-control"
                           step="1" min="0" value="{{ old('credits.'.$provider->id, $currentCredits) }}" style="max-width:140px;">
                    <small class="text-muted">créditos</small>
                </div>
                @endforeach

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
