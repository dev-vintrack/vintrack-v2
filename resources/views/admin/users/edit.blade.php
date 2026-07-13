@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
<div class="container py-4" style="max-width:680px;">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary me-3">← Volver</a>
        <h2 class="mb-0">Editar Usuario: {{ $user->name }}</h2>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $user->telefono) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Rol</label>
                    <select name="rol" class="form-select" required>
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}" {{ old('rol', $user->rol) === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Estado de aprobación</label>
                        <select name="status" class="form-select" required>
                            <option value="active"  {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Activo</option>
                            <option value="pending" {{ old('status', $user->status) === 'pending' ? 'selected' : '' }}>Pendiente</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Usuario activo</label>
                        <select name="activo" class="form-select" required>
                            <option value="1" {{ old('activo', $user->activo) ? 'selected' : '' }}>Sí</option>
                            <option value="0" {{ old('activo', $user->activo) ? '' : 'selected' }}>No</option>
                        </select>
                    </div>
                </div>

                @if($user->approved_at)
                    <div class="mb-3 text-muted" style="font-size:13px;">
                        Aprobado el: {{ $user->approved_at->format('d/m/Y H:i') }}
                    </div>
                @endif

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
