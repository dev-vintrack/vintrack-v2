@extends('layouts.auth')

@section('title', 'Registro exitoso - VINTRACK')

@push('styles')
<style>
    .loader {
        margin: 30px auto;
        width: 60px;
        height: 60px;
        border: 6px solid #334155;
        border-top: 6px solid #22c55e;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        100% { transform: rotate(360deg); }
    }
</style>
@endpush

@section('content')
    <h3 class="text-center mb-3 text-success">Registro exitoso</h3>

    <p class="text-center">Tu cuenta fue creada correctamente.</p>
    <p class="text-center text-white-50">Hemos preparado un mensaje de WhatsApp para completar la validación de tu registro.</p>

    <div class="text-center">
        <a href="{{ $whatsappUrl }}" target="_blank" id="btnWhatsapp" class="btn btn-success w-100 mt-3" onclick="mostrarLogin()">
            <i class="bi bi-whatsapp me-2"></i> Abrir WhatsApp
        </a>

        <div class="loader" id="loader"></div>

        <p id="textoLogin" class="mt-3" style="display: none;">
            Si ya enviaste el mensaje, ahora puedes iniciar sesión.
        </p>

        <a href="{{ route('login') }}" id="linkLogin" class="btn btn-primary w-100" style="display: none;">
            Iniciar sesión
        </a>
    </div>
@endsection

@push('scripts')
<script>
    function mostrarLogin() {
        document.getElementById('loader').style.display = 'none';
        document.getElementById('textoLogin').style.display = 'block';
        document.getElementById('linkLogin').style.display = 'inline-block';
    }
</script>
@endpush
