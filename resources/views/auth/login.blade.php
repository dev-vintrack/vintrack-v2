@extends('layouts.auth')

@section('title', 'Iniciar sesión - VINTRACK')

@section('content')
    <h2 class="text-center mb-4">Iniciar sesión</h2>

    <form method="POST" action="{{ route('login.post') }}" id="loginForm">
        @csrf

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="mb-3 position-relative">
            <label class="form-label">Contraseña</label>
            <input type="password" id="password" name="password" class="form-control" required>
            <span onclick="togglePassword()" class="position-absolute" style="right:10px; top:38px; cursor:pointer; color:#999;">
                <i class="bi bi-eye-fill" id="eyeIcon"></i>
            </span>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="remember" class="form-check-input" id="remember" value="1">
            <label class="form-check-label" for="remember">Mantener sesión</label>
        </div>

        <button type="submit" class="btn btn-primary w-100" id="btnLogin">Ingresar</button>

        <div class="text-center mt-3">
            <a href="{{ route('password.request') }}" class="text-light text-decoration-none">¿Olvidaste tu contraseña?</a>
        </div>

        <div class="text-center mt-3">
            <a href="{{ route('register') }}" class="btn btn-outline-light w-100">Nuevo registro</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('bi-eye-fill');
        icon.classList.toggle('bi-eye-slash-fill');
    }

    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('btnLogin');
        btn.disabled = true;
        btn.innerText = 'Validando...';
    });
</script>
@endpush
