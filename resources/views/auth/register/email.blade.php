@extends('layouts.auth')

@section('title', 'Crear cuenta - VINTRACK')

@section('content')
    <h3 class="text-center mb-3">Crear cuenta</h3>

    <form method="POST" action="{{ route('register.otp') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
        </div>

        <button type="submit" class="btn btn-warning w-100">Enviar código</button>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-light text-decoration-none">¿Ya tienes cuenta? Inicia sesión</a>
        </div>
    </form>
@endsection
