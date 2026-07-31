@extends('layouts.site')

@section('title', 'Servicios - VINTRACK')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home-landing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/servicios.css') }}">
@endpush

@section('content')
<section class="about-hero text-white text-center d-flex align-items-center">
    <div class="container fade-up">
        <h1 class="fw-bold">Nuestros Servicios</h1>
        <p class="lead">Soluciones inteligentes, plataforma ORION para el control vehicular. Tecnología avanzada que te permite consultar en tiempo real el estatus de tu vehículo. El sistema analiza miles de datos encriptados, para detectar patrones de riesgo, historial de origen, registro placas, alertas por siniestro, fraude, robo y procedencia ilícita.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="section-title mb-5 fade-up">¿Qué ofrecemos?</h2>

        <p class="mb-5" style="color:#000000;">
            Es la única plataforma con conexión directa a la base de datos de proveedores confiables como NMVTIS, AutoDetective, ShieldVIN, Digipol, Agencias de seguridad y tecnología con más de 80 millones de registros de vehículos, ideal para autos importados, decreto o procedencia extranjera.
        </p>

        <div class="row g-4">
            <div class="col-md-4 fade-up">
                <div class="service-card h-100">
                    <i class="bi bi-geo-alt-fill service-icon"></i>
                    <h4>Cobertura internacional exclusiva</h4>
                    <p>Accede al historial vehicular en México, Estados Unidos y Canadá.</p>
                </div>
            </div>

            <div class="col-md-4 fade-up delay-1">
                <div class="service-card h-100">
                    <i class="bi bi-shield-lock-fill service-icon"></i>
                    <h4>Seguridad</h4>
                    <p>Protección de accesos y autenticación con OTP, IMSI</p>
                </div>
            </div>

            <div class="col-md-4 fade-up delay-2">
                <div class="service-card h-100">
                    <i class="bi bi-bar-chart-fill service-icon"></i>
                    <h4>Analítica</h4>
                    <p>Reportes inteligentes para toma de decisiones. Visualiza estadísticas con precisión total.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 services-dark">
    <div class="container">
        <div class="row align-items-center mb-5 g-4">
            <div class="col-md-6 fade-left">
                <img src="{{ asset('images/vintrack-nosotros.jpeg') }}" class="img-fluid rounded shadow" alt="Control en una plataforma">
            </div>
            <div class="col-md-6 fade-right text-white">
                <h3>Control en una sola plataforma</h3>
                <p>Consulta tu reporte en cualquier momento, desde cualquier PC, Smartphone. Solo necesitas el número de serie (VIN) - (PLACAS) del vehículo para obtener información al instante.</p>
                <ul class="list-unstyled">
                    <li>✔ Historial vehiculo</li>
                    <li>✔ Estatus Fraude-Robo</li>
                    <li>✔ Alertas inteligentes</li>
                </ul>
            </div>
        </div>

        <div class="row align-items-center flex-md-row-reverse g-4">
            <div class="col-md-6 fade-right">
                <img src="{{ asset('images/Robados_vs_Recuperados_2022-2026.jpeg') }}" class="img-fluid rounded shadow" alt="Decisiones basadas en datos">
            </div>
            <div class="col-md-6 fade-left text-white">
                <h3>Decisiones basadas en datos</h3>
                <p>Convierte información en estrategias reales.</p>
                <ul class="list-unstyled">
                    <li>✔ Reportes automáticos</li>
                    <li>✔ Decisiones inteligentes.</li>
                    <li>✔ Dashboard avanzado</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="pricing-section py-5">
    <div class="container text-center">
        <h2 class="section-title mb-3 fade-up">Planes que se adaptan a ti</h2>
        <p class="mb-5 text-muted fade-up">Elige el plan ideal para tu operación</p>

        @if($packages->isEmpty())
            <div class="alert alert-info text-center">Próximamente publicaremos nuestros planes disponibles.</div>
        @else
            <div class="row g-4 justify-content-center">
                @foreach($packages as $package)
                    <div class="col-md-4 fade-up delay-{{ $loop->index }}">
                        <div class="pricing-card @if($loop->index == 1) featured @endif">
                            @if($loop->index == 1)
                                <div class="badge-popular">Más popular</div>
                            @endif
                            <h4>{{ $package->name }}</h4>
                            <p class="text-muted">{{ $package->description ?? 'Paquete de créditos VINTRACK.' }}</p>

                            <h2 class="price">${{ number_format($package->price, 0) }} <span>/ {{ $package->validity_days ?? '30' }} días</span></h2>

                            @if($package->items && $package->items->isNotEmpty())
                                <ul>
                                    @foreach($package->items as $item)
                                        <li>✔ {{ $item->service->name ?? 'Servicio' }} ({{ number_format($item->credits, 0) }} créditos)</li>
                                    @endforeach
                                </ul>
                            @else
                                <ul>
                                    <li>✔ Créditos para consultas</li>
                                </ul>
                            @endif

                            <a href="{{ route('register') }}" class="btn @if($loop->index == 1) btn-primary w-100 glow-btn @else btn-outline-primary w-100 @endif">
                                Comenzar
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<section class="services-cta text-center text-white">
    <div class="container fade-up">
        <h2>¿Listo para optimizar tu operación?</h2>
        <a href="{{ route('register') }}" class="btn-comenzar mt-3">
            Comenzar ahora
        </a>
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
