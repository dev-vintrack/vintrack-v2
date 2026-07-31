@extends('layouts.auth')

@section('title', 'Verificar código - VINTRACK')

@section('content')
    <h3 class="text-center mb-3">Verificar código</h3>
    <p class="text-center text-white-50">Ingresa el código que recibiste en tu correo.</p>

    <form method="POST" action="{{ route('login.verify.post') }}" id="otpForm">
        @csrf

        <div class="d-flex justify-content-center mb-3">
            <input type="text" maxlength="1" class="otp-input" required>
            <input type="text" maxlength="1" class="otp-input" required>
            <input type="text" maxlength="1" class="otp-input" required>
            <input type="text" maxlength="1" class="otp-input" required>
            <input type="text" maxlength="1" class="otp-input" required>
            <input type="text" maxlength="1" class="otp-input" required>
        </div>

        <input type="hidden" name="otp" id="codigoCompleto">

        <button type="submit" class="btn btn-success w-100">Verificar</button>
    </form>

    <form method="POST" action="{{ route('login.resend') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-warning w-100">Reenviar código</button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="text-light text-decoration-none">Volver al login</a>
    </div>
@endsection

@push('scripts')
<script>
    const inputs = document.querySelectorAll('.otp-input');

    inputs.forEach((input, index) => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                inputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, inputs.length - index);
            for (let i = 0; i < pasted.length; i++) {
                if (inputs[index + i]) inputs[index + i].value = pasted[i];
            }
            const next = index + pasted.length;
            if (next < inputs.length) inputs[next].focus();
            else inputs[inputs.length - 1].focus();
        });
    });

    document.getElementById('otpForm').addEventListener('submit', function () {
        let codigo = '';
        inputs.forEach(input => codigo += input.value);
        document.getElementById('codigoCompleto').value = codigo;
    });
</script>
@endpush
