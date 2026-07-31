@extends('layouts.site')

@section('title', 'Nosotros - VINTRACK')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home-landing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/nosotros.css') }}">
@endpush

@section('content')
<section class="about-hero text-white text-center d-flex align-items-center">
    <div class="container fade-up">
        <h1 class="fw-bold">Sobre VINTrack</h1>
        <p class="lead">Innovación, tecnología y control total en la gestión vehicular, diagnóstico de identificación, interface versátil encriptada con inteligencia operativa y seguridad vial.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-md-6 fade-left">
                <h2 class="section-title mb-3">¿Quiénes somos?</h2>
                <p>
                    VINTrack es una plataforma tecnológica diseñada para optimizar la gestión de investigación de vehículos, ofreciendo consulta en tiempo real, análisis inteligente y seguridad avanzada.
                </p>
                <p>
                    Nuestro objetivo es brindar certeza jurídica para empresas, agencias policiales, peritos y particulares. Implementar un sistema integral con estándares de cifrado encriptado y ciberseguridad que garanticen información confiable, para evaluar una matriz de análisis de riesgos.
                </p>
            </div>

            <div class="col-md-6 fade-right text-center">
                <img src="{{ asset('images/Sistema_oficial.jpeg') }}" class="img-fluid rounded shadow" alt="Sistema VINTrack">
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="mb-5 section-title fade-up">Nuestra esencia</h2>

        <div class="row g-4">
            <div class="col-md-4 fade-left">
                <div class="card p-4 shadow h-100">
                    <i class="bi bi-send-fill fs-1 text-primary mb-3"></i>
                    <h4>Misión</h4>
                    <p>
                        Brindar soluciones tecnológicas innovadoras para la identificación y control vehicular, facilitando la toma de decisiones, mejorando la seguridad de sus activos y reducir costos operativos mediante estrategias digitales y soporte especializado.
                    </p>
                </div>
            </div>

            <div class="col-md-4 fade-right">
                <div class="card p-4 shadow h-100">
                    <i class="bi bi-rocket-takeoff-fill fs-1 text-primary mb-3"></i>
                    <h4>Visión</h4>
                    <p>
                        Ser una plataforma líder en gestión vehicular a nivel internacional, reconocida por su efectividad para optimizar sus operaciones, mejorar la seguridad con sistemas de control y análisis predictivo y alertas virtuales en tiempo real.
                    </p>
                </div>
            </div>

            <div class="col-md-4 fade-left">
                <div class="card p-4 shadow h-100">
                    <i class="bi bi-clipboard-check-fill fs-1 text-primary mb-3"></i>
                    <h4>Beneficios de utilizar VINTrack</h4>
                    <p>
                        Ahorra tiempo y dinero, la forma más fácil y rápida de obtener información en tiempo real sobre el historial de cualquier vehículo.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="values-section py-5">
    <div class="container text-center">
        <h2 class="mb-5 section-title fade-up">Nuestros valores</h2>

        <div class="row g-4">
            <div class="col-md-3 fade-up">
                <div class="value-card h-100">
                    <i class="bi bi-shield-lock-fill"></i>
                    <h5>Seguridad</h5>
                    <p>Protegemos la información y medidas preventivas, técnicas y operativas diseñadas para salvaguardar los activos digitales y físicos contra amenazas, accesos no autorizados.</p>
                </div>
            </div>

            <div class="col-md-3 fade-up delay-1">
                <div class="value-card h-100">
                    <i class="bi bi-lightning-charge-fill"></i>
                    <h5>Innovación</h5>
                    <p>Siempre buscamos mejorar, la innovación no es solo tecnológica; implica fomentar una cultura de seguridad y prevención de riesgos.</p>
                </div>
            </div>

            <div class="col-md-3 fade-up delay-2">
                <div class="value-card h-100">
                    <i class="bi bi-hand-thumbs-up-fill"></i>
                    <h5>Confianza</h5>
                    <p>Relaciones sólidas con nuestros clientes con honestidad generan lealtad, compromiso, reputación y aseguran el éxito a largo plazo.</p>
                </div>
            </div>

            <div class="col-md-3 fade-up delay-3">
                <div class="value-card h-100">
                    <i class="bi bi-bar-chart-fill"></i>
                    <h5>Precisión</h5>
                    <p>Los datos precisos conducen a conclusiones válidas, estrategias efectivas y pronósticos confiables.</p>
                </div>
            </div>
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
