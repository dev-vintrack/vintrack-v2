@extends('layouts.site')

@section('title', 'Contacto - VINTRACK')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home-landing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contacto.css') }}">
@endpush

@section('content')
<section class="about-hero text-white text-center d-flex align-items-center">
    <div class="container fade-up">
        <h1 class="fw-bold">CONTACTO VINTRACK</h1>
        <p class="lead">Estamos listos para ayudarte</p>
        <p>
            Nuestro equipo puede ayudarte con consultas, monitoreo vehicular, validaciones VIN y soporte.
        </p>
    </div>
</section>

<section class="contact-section">
    <div class="contact-container">
        <div class="contact-info fade-left">
            <span class="section-tag">INFORMACIÓN</span>
            <h2>CONTACTO VINTRACK</h2>
            <p>Estamos listos para ayudarte</p>
            <p>Nuestro equipo puede ayudarte con consultas, estatus vehicular, identificación VIN y soporte.</p>

            <div class="info-card">
                <h3>Correo electrónico</h3>
                <p>{{ config('vintrack.contact_email') }}</p>
            </div>

            <div class="info-card">
                <h3>Horario</h3>
                <p>Lunes a Viernes<br>9:00 AM - 6:00 PM</p>
            </div>
        </div>

        <div class="contact-form-wrapper fade-right">
            @if (session('status'))
                <div class="alert-success">
                    Mensaje enviado correctamente ✅
                </div>
            @endif

            <p class="form-required-text">Todos los campos marcados con * son obligatorios.</p>

            <form action="{{ route('site.contact.send') }}" method="POST" class="contact-form">
                @csrf
                <div class="form-group">
                    <input type="text" name="nombre" placeholder="Nombre completo *" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" required>
                </div>

                <div class="form-group">
                    <input type="email" name="correo" placeholder="Correo electrónico *" required>
                </div>

                <div class="form-group">
                    <input type="text" name="telefono" placeholder="Teléfono" pattern="[0-9]+" maxlength="10">
                </div>

                <div class="form-group">
                    <textarea name="mensaje" rows="6" placeholder="Escribe tu mensaje... *" required></textarea>
                </div>

                <button type="submit" class="btn-contact">Enviar mensaje</button>
            </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const elements = document.querySelectorAll('.fade-up, .fade-left, .fade-right');
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
            }
        });
    }, { threshold: 0.1 });
    elements.forEach(el => observer.observe(el));
});
</script>
@endpush
