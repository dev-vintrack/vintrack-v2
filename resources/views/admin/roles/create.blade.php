@extends('layouts.app')

@section('title', 'Nuevo Rol')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Nuevo Rol</h2>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary btn-sm">Regresar</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-semibold">Nombre (slug) <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre" class="form-control" maxlength="32" value="{{ old('nombre') }}" required>
                        <small class="text-muted">Identificador corto, sin espacios ni acentos. Ej: cliente_ocasional</small>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label fw-semibold">Descripción</label>
                        <input type="text" name="descripcion" id="descripcion" class="form-control" maxlength="255" value="{{ old('descripcion') }}">
                    </div>

                    <div class="mb-3">
                        <label for="role_type_id" class="form-label fw-semibold">Tipo de Rol <span class="text-danger">*</span></label>
                        <select name="role_type_id" id="role_type_id" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            @foreach($roleTypes as $id => $typeName)
                                <option value="{{ $id }}" {{ old('role_type_id') == $id ? 'selected' : '' }}>{{ $typeName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="home_route" class="form-label fw-semibold">Ruta de inicio</label>
                        <input type="text" name="home_route" id="home_route" class="form-control" maxlength="64" value="{{ old('home_route') }}" placeholder="Ej: home.cliente">
                        <small class="text-muted">Nombre de la ruta a la que redirige el usuario. Solo aplica para tipos de rol cliente.</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="requires_approval" id="requires_approval" value="1" {{ old('requires_approval') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="requires_approval">Requiere aprobación del administrador</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="display_order" class="form-label fw-semibold">Orden de visualización <span class="text-danger">*</span></label>
                        <input type="number" name="display_order" id="display_order" class="form-control" min="0" value="{{ old('display_order', 0) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_1" value="1" {{ old('status', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="status_1">Activo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_0" value="0" {{ old('status') == '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="status_0">Inactivo</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Guardar Rol</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
