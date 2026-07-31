@extends('layouts.auth')

@section('title', 'Completar registro - VINTRACK')

@section('content')
    <h3 class="text-center mb-3">Completar registro</h3>

    <form method="POST" action="{{ route('register.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}" maxlength="10" required>
        </div>

        <div class="mb-3 position-relative">
            <label class="form-label">Contraseña</label>
            <input type="password" id="password" name="password" class="form-control" required>
            <span onclick="togglePassword('password', 'eye1')" class="position-absolute" style="right:10px; top:38px; cursor:pointer; color:#999;">
                <i class="bi bi-eye-fill" id="eye1"></i>
            </span>
        </div>

        <div class="mb-3 position-relative">
            <label class="form-label">Confirmar contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
            <span onclick="togglePassword('password_confirmation', 'eye2')" class="position-absolute" style="right:10px; top:38px; cursor:pointer; color:#999;">
                <i class="bi bi-eye-fill" id="eye2"></i>
            </span>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="es_oficial" name="es_oficial" value="1" {{ old('es_oficial') ? 'checked' : '' }}>
            <label class="form-check-label" for="es_oficial">Soy oficial</label>
        </div>

        <div class="mb-3" id="entidadDiv" style="display: {{ old('es_oficial') ? 'block' : 'none' }};">
            <label class="form-label">Corporación</label>
            <input type="text" name="entidad" class="form-control" value="{{ old('entidad') }}">
        </div>

        <button type="submit" class="btn btn-success w-100">Registrarse</button>
    </form>
@endsection

@push('scripts')
<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('bi-eye-fill');
        icon.classList.toggle('bi-eye-slash-fill');
    }

    document.getElementById('es_oficial').addEventListener('change', function () {
        document.getElementById('entidadDiv').style.display = this.checked ? 'block' : 'none';
    });
</script>
@endpush
