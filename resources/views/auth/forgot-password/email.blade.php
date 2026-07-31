@extends('layouts.auth')

@section('title', 'Recuperar contraseña - VINTRACK')

@section('content')
    <h3 class="text-center mb-3">Recuperar contraseña</h3>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Correo electrónico</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
        </div>

        <button type="submit" class="btn btn-primary w-100">Enviar código</button>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-light text-decoration-none">Volver al login</a>
        </div>
    </form>
@endsection
