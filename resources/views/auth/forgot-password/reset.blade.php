@extends('layouts.auth')

@section('title', 'Nueva contraseña - VINTRACK')

@section('content')
    <h3 class="text-center mb-3">Nueva contraseña</h3>

    <form method="POST" action="{{ route('password.reset.post') }}">
        @csrf

        <div class="mb-3 position-relative">
            <label class="form-label">Nueva contraseña</label>
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

        <button type="submit" class="btn btn-success w-100">Actualizar contraseña</button>
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
</script>
@endpush
