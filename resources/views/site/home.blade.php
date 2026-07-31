@extends('layouts.site')

@section('title', 'Inicio - VINTRACK')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home-landing.css') }}">
@endpush

@section('content')
<section class="hero" style="background: linear-gradient(rgba(5,10,25,.62), rgba(5,10,25,.72)), url('/images/Home_principal.png') center/cover no-repeat;">
    <div class="container">
        <div class="row align-items-center min-vh-50">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-4">VINTrack — Historial y decodificación vehicular</h1>
                <p class="lead mb-4">¿Quieres consultar el historial de un vehículo [Nacional, Importado o Extranjero], estatus origen (planta armadora), reporte de robo, fraude, clonación y si está vinculado a un proceso judicial en EE. UU./Canadá/México/Centroamérica?</p>
                <a href="{{ route('site.services') }}" class="btn btn-primary btn-lg me-2">Ver planes</a>
                <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg">Crear cuenta</a>
            </div>
        </div>
    </div>
</section>

<!-- SECCION 1 (3 Tarjetas) Color Blanco -->
<section class="benefits-intro  bg-white">
    <div class="container text-center">
        <h2 class="section-title fade-up">¿Por qué utilizar VINTrack?</h2>

        <div class="row g-4 mt-2">
            <div class="col-md-4 fade-up delay-1">
                <div class="feature-card shadow h-100">
                    <i class="bi bi-geo-fill"></i>
                    <h4>Historial Vehicular</h4>
                    <p>La innovación tecnológica es clave para la seguridad vehicular y prevención de fraudes. "Descubre el estatus de tu vehículo"</p>
                </div>
            </div>

            <div class="col-md-4 fade-up delay-2">
                <div class="feature-card shadow h-100">
                    <i class="bi bi-shield-lock-fill"></i>
                    <h4>Seguridad</h4>
                    <p>Accesos controlados y autenticación segura con OTP.</p>
                </div>
            </div>

            <div class="col-md-4 fade-up delay-3">
                <div class="feature-card shadow h-100">
                    <i class="bi bi-graph-up-arrow"></i>
                    <h4>Análisis de datos</h4>
                    <p>Detección y análisis de riesgo de vehículos de procedencia ilícita. Gestión de información segura, bajo estrictos protocolos de confidencialidad.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCION 2 (VIN Decoder) Color Gris-->
<section class="vin-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <h2 class="fw-bold">Decodificador Vehicular</h2>
                    <p class="text-muted">Ingresa el VIN y obtén información técnica del vehículo en segundos.</p>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form id="vinDecoderForm" action="{{ route('site.decode-vin') }}" method="POST">
                            @csrf
                            <div class="row g-3 align-items-end">
                                <div class="col-md-7">
                                    <label for="vin" class="form-label">Número VIN (también acepta VIN menor a 17 caracteres)</label>
                                    <input type="text" class="form-control form-control-lg" id="vin" name="vin" placeholder="Ej. 5UXWX7C5*BA" maxlength="17" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="year" class="form-label">Año (opcional)</label>
                                    <input type="number" class="form-control" id="year" name="year" placeholder="2011" min="1900" max="{{ date('Y') + 1 }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100" id="decodeBtn">
                                        <span class="spinner-border spinner-border-sm d-none" id="decodeSpinner" role="status" aria-hidden="true"></span>
                                        Decodificar
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div id="decodeError" class="alert alert-danger mt-4 d-none" role="alert"></div>

                        <div id="decodeResult" class="decoder-result mt-4 d-none">
                            <h5 class="mb-3">Resultado</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between" id="res-error-row"><span id="res-error-label">Error</span><strong id="res-text" style="white-space: pre-line;"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>VIN</span><strong id="res-vin"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Marca</span><strong id="res-make"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Modelo</span><strong id="res-model"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Año</span><strong id="res-year"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Fabricante</span><strong id="res-manufacturer"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Versión</span><strong id="res-trim"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Carrocería</span><strong id="res-body"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Tracción</span><strong id="res-drive"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Combustible</span><strong id="res-fuel"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Cilindros</span><strong id="res-cylinders"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Modelo Motor</span><strong id="res-engmod"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>HP</span><strong id="res-hp"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Desplazamiento</span><strong id="res-displacement"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Tipo</span><strong id="res-type"></strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>País de origen</span><strong id="res-country"></strong></li>
                            </ul>

                            <div id="vin-success-promo" class="d-none">
                                <p class="text-success fw-semibold mt-3 mb-3">Para obtener el Historial Completo de tu vehículo:</p>
                                <ul>
                                    <li> Conoce nuestros diferentes planes </li>
                                    <li> Realiza la compra unica de tu reporte completo. (Sin registro · Sin suscripción · Pago único por reporte) </li>
                                </ul>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('site.services') }}" class="btn btn-primary">Ver Planes</a>
                                    <a href="{{ route('site.sales') }}" class="btn btn-success">Solicita Compra Unica (sin registro)</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCION 3 (Carrito con Puertas) Color Negro -->
<section class="vintrack-benefits">
    <div class="benefits-header fade-up">
        <span class="benefits-badge">BENEFICIOS</span>
        <h2>¿Por qué utilizar VINTRACK?</h2>
        <p>Obtén información clave del vehículo antes de comprar, vender o recuperar un automóvil.</p>
    </div>

    <div class="benefits-container">
        <div class="car-wrapper">
            <img src="{{ asset('images/vintrack-auto4.png') }}" alt="VINTRACK" class="car-image">

            <button class="info-point point-1" data-bs-toggle="modal" data-bs-target="#modalHistorial"><i class="bi bi-shield-check"></i></button>
            <button class="info-point point-2" data-bs-toggle="modal" data-bs-target="#modalAutoCertificado"><i class="bi bi-car-front"></i></button>
            <button class="info-point point-3" data-bs-toggle="modal" data-bs-target="#modalCompraSegura"><i class="bi bi-file-earmark-text"></i></button>
            <button class="info-point point-4" data-bs-toggle="modal" data-bs-target="#modalInversion"><i class="bi bi-credit-card"></i></button>
            <button class="info-point point-5" data-bs-toggle="modal" data-bs-target="#modalControlAcceso"><i class="bi bi-speedometer2"></i></button>
            <button class="info-point point-6" data-bs-toggle="modal" data-bs-target="#modalConsulta"><i class="bi bi-search"></i></button>
        </div>
    </div>
</section>

<!-- 6 MODALS DE LA SECCION 3 (Puertas del Carrito) -->
<!-- MODAL HISTORIAL -->
<div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-primary text-white border-0 rounded-4">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-4 text-center">
                    <i class="bi bi-shield-check" style="font-size:35px;"></i>
                    <h4>Historial VIN</h4>
                    <p>Detrás de cada vehículo hay una historia. Es importante <strong><span class="texto-azul">consultar historial de origen</span></strong> para evitar fraudes, alteraciones en medio de identificación, problemas mecánicos y daños ocultos. VINTrack te revelará en segundos: <strong><span class="texto-azul">Estatus Legal - Registro de Origen - Procedencia Internacional:</span></strong> Vinculación con posibles reportes de robo y/o procedencia ilícita en Estados Unidos, México o Canadá.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL AUTO CERTIFICADO -->
<div class="modal fade" id="modalAutoCertificado" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-primary text-white border-0 rounded-4">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-4 text-center">
                    <i class="bi bi-car-front" style="font-size:35px;"></i>
                    <h4>Auto Certificado</h4>
                    <p><strong><span class="texto-azul">Validación física y pericial:</span></strong> Para evitar fraudes, vicios ocultos y problemas legales (evitar comprar un auto remarcado o con reporte de robo). Acude a módulos oficiales o contrata a un perito certificado para descartar alteraciones en el motor o números de serie.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL COMPRA SEGURA -->
<div class="modal fade" id="modalCompraSegura" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-primary text-white border-0 rounded-4">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-4 text-center">
                    <i class="bi bi-file-earmark-text" style="font-size:35px;"></i>
                    <h4>Compra Segura</h4>
                    <p>Para asegurar una compra totalmente segura, puedes tomar decisiones inteligentes, consejos clave: <strong><span class="texto-azul">Historial Legal y Administrativo - Guía de Precios y Valor Comercial - Inspección de Documentos</span></strong> asegúrate de revisar la factura original, titulo o sus cadenas de endoso, tarjeta de circulación, pagos de tenencia y verificación.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL INVERSION -->
<div class="modal fade" id="modalInversion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-primary text-white border-0 rounded-4">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-4 text-center">
                    <i class="bi bi-credit-card" style="font-size:35px;"></i>
                    <h4>Protege tu inversión</h4>
                    <p>Para proteger tu patrimonio al comprar un auto usado, <strong><span class="texto-azul">revisa el historial completo del vehículo.</span></strong> Conocer los antecedentes legales, físicos y administrativos te evitará la pérdida total de tu dinero o problemas legales.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CONTROL ACCESO -->
<div class="modal fade" id="modalControlAcceso" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-primary text-white border-0 rounded-4">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-4 text-center">
                    <i class="bi bi-speedometer2" style="font-size:35px;"></i>
                    <h4>Control de acceso</h4>
                    <p>Consulta la información que necesitas desde cualquier lugar y en solo segundos, desde tu celular o computadora. Utiliza nuestro Decodificador de VIN para acceder a un informe completo del historial del vehículo en tiempo real. <strong><span class="texto-azul">Control de acceso OTP</span></strong> (One-Time Password).</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CONSULTA -->
<div class="modal fade" id="modalConsulta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-primary text-white border-0 rounded-4">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-4 text-center">
                    <i class="bi bi-search" style="font-size:35px;"></i>
                    <h4>Consulta Base de Datos</h4>
                    <p>Servicio de verificación vehicular en línea. consulta el estatus de cualquier vehículo antes de comprar. Cobertura Nacional - Internacional. La base de datos incluye más de 75 millones de eventos vehiculares registrados. Evita sorpresas desagradables y toma decisiones con seguridad.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECCION 4 (GALERÍA / VISUAL) Color Blanco -->
<section class="py-5 bg-white">
    <div class="container text-center">
        <h2 class="mb-5 section-title fade-up">Explora VINTrack</h2>

        <div class="row g-4">
            <div class="col-md-3 fade-left">
                <div class="image-card">
                    <img src="{{ asset('images/VIN_validation.jpeg') }}" class="img-fluid" alt="VIN validation">
                    <div class="overlay">
                        <h5>Historial Vehicular</h5>
                        <p>Consulta Vehicular VINTrack, nosotros detectamos lo que otros no ven.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 fade-right">
                <div class="image-card">
                    <img src="{{ asset('images/Estadistica_Robo.png') }}" class="img-fluid" alt="Análisis inteligente">
                    <div class="overlay">
                        <h5>Análisis inteligente</h5>
                        <p>La inteligencia estratégica para la toma de decisiones (VIN) combina datos, análisis, IA y experiencia humana para guiar las decisiones en tiempo real.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 fade-left">
                <div class="image-card">
                    <img src="{{ asset('images/Explora_VINTrack.png') }}" class="img-fluid" alt="Tecnología Avanzada">
                    <div class="overlay">
                        <h5>Tecnología Avanzada</h5>
                        <p>Sistema de identificación vehicular y metodologías de ciencia pericial, enfocada en la detección, análisis y verificación de la autenticidad de vehículos a nivel global.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 fade-right">
                <div class="image-card">
                    <img src="{{ asset('images/vintrack-seguridad.png') }}" class="img-fluid" alt="Seguridad total">
                    <div class="overlay">
                        <h5>Seguridad total</h5>
                        <p>Protección con autenticación y control de acceso</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCION 5 (Dashboard Contadores) Color Gris -->
<section class="py-5 bg-black">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <div class="col-md-3">
                <div class="card stat-card text-center p-4 h-100">
                    <div class="card-body">
                        <i class="bi bi-car-front fs-1 text-primary mb-3"></i>
                        <h3 class="display-5 fw-bold" id="counter-services" data-target="24">0</h3>
                        <p class="text-muted fs-5 mb-0">Servicio 24/7</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-center p-4 h-100">
                    <div class="card-body">
                        <i class="bi bi-car-front fs-1 text-primary mb-3"></i>
                        <h3 class="display-5 fw-bold" id="counter-vehicles" data-target="{{ $vehicles }}">0</h3>
                        <p class="text-muted fs-5 mb-0">Vehículos identificados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-center p-4 h-100">
                    <div class="card-body">
                        <i class="bi bi-car-front fs-1 text-primary mb-3"></i>
                        <h3 class="display-5 fw-bold" id="counter-efectivity" data-target="98">0</h3>
                        <p class="text-muted fs-5 mb-0">Efectividad (%)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-center p-4 h-100">
                    <div class="card-body">
                        <i class="bi bi-people fs-1 text-success mb-3"></i>
                        <h3 class="display-5 fw-bold" id="counter-users" data-target="{{ $active_users }}">0</h3>
                        <p class="text-muted fs-5 mb-0">Usuarios activos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCION 6 (FEATURES AVANZADAS) Color Negro-->
<!--<section class="features-section">-->
<section class="py-5">    
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-md-6 fade-left">
                <h2 class="fw-bold mb-4">Control total en una sola plataforma</h2>
                <p>VINTrack está diseñado con tecnología moderna y segura, para simplificar el proceso de verificación del VIN de cualquier vehículo, permite decodificar y analizar el historial de origen mediante su Número VIN 17 dígitos alfanuméricos.</p>
                <ul class="list-unstyled mt-4">
                    <li>✔ Control acceso en tiempo real.</li>
                    <li>✔ Alertas inteligentes</li>
                    <li>✔ Reportes automatizados</li>
                    <li>✔ Seguridad avanzada con OTP</li>
                </ul>
            </div>

            <div class="col-md-6 fade-right text-center">
                <img src="{{ asset('images/VINTrack_Police.png') }}" class="img-fluid rounded shadow" alt="Control total">
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
(function () {
    function animateCounter(id) {
        const el = document.getElementById(id);
        if (!el) return;
        const target = parseInt(el.dataset.target, 10) || 0;
        const duration = 1500;
        const start = performance.now();

        function step(now) {
            const progress = Math.min((now - start) / duration, 1);
            el.textContent = Math.floor(progress * target).toLocaleString();
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    document.addEventListener('DOMContentLoaded', function () {
        animateCounter('counter-vehicles');
        animateCounter('counter-users');
        animateCounter('counter-services');
        animateCounter('counter-efectivity');

        const form = document.getElementById('vinDecoderForm');
        const btn = document.getElementById('decodeBtn');
        const spinner = document.getElementById('decodeSpinner');
        const errorBox = document.getElementById('decodeError');
        const resultBox = document.getElementById('decodeResult');

        const fields = {
            vin: 'res-vin', make: 'res-make', model: 'res-model', year: 'res-year',
            trim: 'res-trim', body_class: 'res-body', drive_type: 'res-drive',
            fuel_type_primary: 'res-fuel', engine_cylinders: 'res-cylinders',
            engine_hp: 'res-hp', displacement_l: 'res-displacement',
            vehicle_type: 'res-type', plant_country: 'res-country',
            error_text: 'res-text', manufacturer: 'res-manufacturer', engine_model: 'res-engmod'
        };

        function setText(id, value) {
            const el = document.getElementById(id);
            if (el) el.textContent = value || '—';
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const yearInput = document.getElementById('year');
            if (yearInput && yearInput.value.trim() === '') {
                yearInput.placeholder = '';
            }

            errorBox.classList.add('d-none');
            resultBox.classList.add('d-none');
            spinner.classList.remove('d-none');
            btn.disabled = true;

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok) {
                    errorBox.textContent = data.error || 'Ocurrió un error al decodificar el VIN.';
                    errorBox.classList.remove('d-none');
                    return;
                }

                for (const [key, id] of Object.entries(fields)) {
                    setText(id, data.data?.[key]);
                }

                const errorRow = document.getElementById('res-error-row');
                const errorCodeRaw = data.data?.error_code;
                let errorCode = 0;
                if (errorCodeRaw !== '' && errorCodeRaw !== null && errorCodeRaw !== undefined) {
                    errorCode = parseInt(errorCodeRaw, 10);
                    if (isNaN(errorCode)) errorCode = 0;
                }
                const maxError = 7;
                const colorErrorCode = Math.min(Math.max(errorCode, 0), maxError);
                let hue;
                if (colorErrorCode === 0) {
                    hue = 120; // verde claro
                } else {
                    const t = (colorErrorCode - 1) / (maxError - 1);
                    hue = 60 - (t * 60); // amarillo (60) -> rojo (0)
                }
                if (errorRow) errorRow.style.backgroundColor = `hsl(${hue}, 80%, 85%)`;

                const resErrorLabel = document.getElementById('res-error-label');
                if (resErrorLabel) resErrorLabel.textContent = errorCode === 0 ? 'Correcto' : 'Error';

                const resText = document.getElementById('res-text');
                if (resText) {
                    const rawError = data.data?.error_text || '—';
                    resText.textContent = rawError.includes(';') ? rawError.split(';').map(s => s.trim()).join('\n') : rawError;
                }

                const promo = document.getElementById('vin-success-promo');
                if (promo) promo.classList.toggle('d-none', errorCode !== 0);

                resultBox.classList.remove('d-none');
            } catch (err) {
                errorBox.textContent = 'No se pudo contactar al servicio de decodificación. Intenta más tarde.';
                errorBox.classList.remove('d-none');
            } finally {
                spinner.classList.add('d-none');
                btn.disabled = false;
            }
        });
    });
})();
</script>
@endpush

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
