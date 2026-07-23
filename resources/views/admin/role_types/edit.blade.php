@extends('layouts.app')

@section('title', 'Editar Tipo de Rol')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Editar Tipo de Rol</h2>
            <a href="{{ route('admin.role-types.index') }}" class="btn btn-outline-secondary btn-sm">Regresar</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="{{ route('admin.role-types.update', $roleType->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="type_name" class="form-label fw-semibold">Nombre del tipo <span class="text-danger">*</span></label>
                        <input type="text" name="type_name" id="type_name" class="form-control" maxlength="32" value="{{ old('type_name', $roleType->type_name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Descripción</label>
                        <input type="text" name="description" id="description" class="form-control" maxlength="255" value="{{ old('description', $roleType->description) }}">
                    </div>

                    <div class="mb-3">
                        <label for="color" class="form-label fw-semibold">Color (clase Bootstrap)</label>
                        <input type="text" name="color" id="color" class="form-control" maxlength="32" value="{{ old('color', $roleType->color) }}" placeholder="Ej: danger, info, primary, warning">
                        <small class="text-muted">Se usa como <code>bg-{color}</code> en los badges.</small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_admin" id="is_admin" value="1" {{ old('is_admin', (string) $roleType->is_admin) == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_admin">Es tipo administrativo</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_customer" id="is_customer" value="1" {{ old('is_customer', (string) $roleType->is_customer) == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_customer">Es tipo cliente</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="display_order" class="form-label fw-semibold">Orden de visualización <span class="text-danger">*</span></label>
                        <input type="number" name="display_order" id="display_order" class="form-control" min="0" value="{{ old('display_order', $roleType->display_order) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_1" value="1" {{ old('status', (string) $roleType->status) == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="status_1">Activo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_0" value="0" {{ old('status', (string) $roleType->status) == '0' ? 'checked' : '' }}>
                            <label class="form-check-label" for="status_0">Inactivo</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Actualizar Tipo de Rol</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
