@extends('layouts.site')

@section('title', 'Capacitación - VINTRACK')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home-landing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/capacitacion.css') }}">
@endpush

@section('content')
<section class="about-hero text-white text-start d-flex align-items-center">
    <div class="container fade-up">
        <h1 class="fw-bold">Capacitación VINTrack</h1>
        <p class="lead">Descubre el "ADN Vehicular" esencia tecnológica y estructural de tu vehículo.<br>Brindamos asesoría, consultoría y capacitación oficial para fortalecer las capacidades técnicas periciales avanzadas de funcionarios en materia de identificación vehicular, peritos y autoridades, lectura de VIN mediante tecnología de Imagen Magneto-Óptica (MOI), y el uso de medios electrónicos de identificación. Número de motor y puntos de seguridad, además de actuar con sustento legal, consolida reportes y estadísticas clave sobre robo de vehículos, nacionales y extranjeros.</p>
    </div>
</section>

<section class="features-section">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-md-6 fade-left">
                <h2 class="fw-bold mb-4">Capacitación Oficial</h2>
                <p>
                    La capacitación para autoridades gubernamentales en identificación vehicular y detección de procedencia ilícita es fundamental para combatir el robo de autos y delitos conexos. Los programas de formación suelen enfocarse en la revisión física de los vehículos (números de identificación).
                </p>
            </div>

            <div class="col-md-6 fade-right text-center">
                <img src="{{ asset('images/Plataforma_Oficial.png') }}" class="img-fluid rounded shadow" alt="Plataforma Oficial">
            </div>
        </div>
    </div>
</section>

<section class="features-section">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-md-6 fade-right text-center order-md-1">
                <img src="{{ asset('images/Inspection_VINTRACK2.jpeg') }}" class="img-fluid rounded shadow" alt="Inspección VINTrack">
            </div>

            <div class="col-md-6 fade-left order-md-2">
                <h2 class="fw-bold mb-4">Identificación Vehicular</h2>
                <ul class="list-unstyled">
                    <li>✔ <strong>Identificación (NIV):</strong> Capacitación en el Número de Identificación Vehicular (17 dígitos) y sus normas (NOM-001-SSP-2008), incluyendo la decodificación para verificar la autenticidad del fabricante.</li>
                    <li>✔ <strong>Reidentificación:</strong> Técnicas aplicadas por peritos para restaurar la numeración original tras alteraciones (injertos, remarcado, borrado con esmeril).</li>
                    <li>✔ <strong>Documentoscopía:</strong> Análisis de documentos (facturas, títulos de propiedad) para detectar falsificaciones relacionadas con los vehículos.</li>
                    <li>✔ <strong>Revenido Químico (Restauración de Metales):</strong> Es la técnica más sensible para recuperar números de serie borrados, basada en la restauración químico-metalográfico. Uso de mordientes químicos (ej. Ácido Nítrico HNO3, Reactivo Fry).</li>
                    <li>✔ <strong>Matriz de Análisis Vehicular:</strong> Utilización de matrices estructuradas para evaluar la originalidad de los componentes y detectar alteraciones.</li>
                    <li>✔ <strong>Análisis de Seguridad:</strong> Revisión de sellos de seguridad, etiquetas de fabricante, pintura original y remaches.</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="features-section">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-md-6 fade-left">
                <h2 class="fw-bold mb-4">Instituciones y Capacitadores</h2>
                <p>
                    Las capacitaciones suelen ser impartidas por peritos especializados en Mexico, EE.UU, Latinoamérica, en colaboración con agencias como OCRA (Oficina Coordinadora de Riesgos Asegurados). Dirigido a: Fiscalías, policías de investigación, agentes de tránsito y peritos judiciales.
                </p>
            </div>

            <div class="col-md-6 fade-right text-center">
                <img src="{{ asset('images/Perito_Policia_VINTrack.png') }}" class="img-fluid rounded shadow" alt="Perito Policía VINTrack">
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
