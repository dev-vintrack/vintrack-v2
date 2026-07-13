@extends('layouts.app')

@section('title', 'Registro de Cliente')

@section('content')
<div class="container py-5" style="max-width:520px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h3 class="mb-3">Crear cuenta</h3>
            <p class="text-muted mb-4">Regístrate para consultar y comprar créditos.</p>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required maxlength="128">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Correo electrónico <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Teléfono <span class="text-danger">*</span></label>
                    <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}" required maxlength="32">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Contraseña <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Confirmar contraseña <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Tipo de cuenta <span class="text-danger">*</span></label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="role_type" id="role_cliente" value="cliente_registrado" checked>
                        <label class="form-check-label" for="role_cliente">
                            <strong>Cliente Registrado</strong> — consultas múltiples a Placas
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="role_type" id="role_perito" value="perito" {{ old('role_type') === 'perito' ? 'checked' : '' }}>
                        <label class="form-check-label" for="role_perito">
                            <strong>Solicitar Perito</strong> — acceso a todos los proveedores (requiere aprobación)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="role_type" id="role_oficial" value="oficial" {{ old('role_type') === 'oficial' ? 'checked' : '' }}>
                        <label class="form-check-label" for="role_oficial">
                            <strong>Solicitar Oficial</strong> — acceso a todos los proveedores (requiere aprobación)
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Registrarse</button>
            </form>

            <div class="mt-3 text-center text-muted" style="font-size:14px;">
                ¿Ya tienes cuenta? <a href="{{ route('login') }}">Iniciar sesión</a>
            </div>
        </div>
    </div>
</div>
@endsection
