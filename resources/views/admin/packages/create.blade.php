@extends('layouts.app')

@section('title', 'Nuevo Paquete - Admin')

@section('content')
<div class="row">
    <div class="col-12">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.packages.index') }}" class="btn btn-sm btn-outline-secondary me-3">← Volver</a>
        <h2 class="mb-0">Nuevo Paquete de Créditos</h2>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.packages.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre del paquete <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required maxlength="100">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea name="description" class="form-control" rows="2" maxlength="500">{{ old('description') }}</textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Precio ($) <span class="text-danger">*</span>
                            <small class="text-muted ms-2">Precio Mínimo: ${{ number_format((float) $config->min_price_package, 2) }}</small>
                        </label>
                        <input type="number" name="price" class="form-control"
                               step="{{ $config->step_price_package }}" min="{{ $config->min_price_package }}" max="{{ $config->max_price_package }}"
                               value="{{ old('price', $config->min_price_package) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold d-block">Vigencia (días) <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($validityOptions as $days)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="validity_days" id="validity_days_{{ $days }}"
                                       value="{{ $days }}" {{ (int) old('validity_days', $validityOptions[0]) === $days ? 'checked' : '' }} required>
                                <label class="form-check-label" for="validity_days_{{ $days }}">{{ $days }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="active" id="active" value="1"
                               {{ old('active', '1') ? 'checked' : '' }}>
                        <label class="form-check-label" for="active">Paquete activo</label>
                    </div>
                </div>

                <hr>
                <h6 class="fw-bold mb-3">Créditos por servicio</h6>
                <p class="text-muted" style="font-size:13px;">Deja en blanco para no incluir créditos de ese servicio.</p>

                @foreach($services as $service)
                <div class="mb-3 d-flex align-items-center gap-3">
                    <label class="form-label mb-0 fw-semibold" style="min-width:220px;">{{ $service->provider->name }} - {{ $service->name }}</label>
                    <input type="number" name="credits[{{ $service->id }}]" class="form-control"
                           step="{{ $config->step_purchase_input }}" min="{{ $config->min_purchase_user }}" max="{{ $config->max_purchase_user }}"
                           value="{{ old('credits.'.$service->id) }}" style="max-width:140px;">
                    <small class="text-muted">créditos</small>
                </div>
                @endforeach

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Crear Paquete</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
