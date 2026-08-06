@extends('layouts.app')

@section('title', 'Panel de Oficial')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/role-home.css') }}">
@endpush

@section('content')
<section class="rh-hero" style="background-image: url('{{ asset('images/vintrack-policeOfficer.png') }}');">
    <div class="rh-hero-overlay"></div>
    <div class="rh-hero-content">
        <div class="rh-hero-toptitle">
            <span class="rh-hero-toptitle-white">CONSULTA VEHICULAR</span>
            <span class="rh-hero-toptitle-blue">PARA OFICIALES</span>
        </div>
        <span class="rh-hero-badge">Consulta información relacionada con robos de vehículos, reportes y estatus en tiempo real</span>
    </div>
</section>

<section class="rh-dashboard">
    <div class="container-fluid">
        @include('home.partials.dashboard_content', ['hideConsulta' => true])
    </div>
</section>

<section class="rh-carvana">
    <div class="container-fluid">
        <div class="rh-carvana-header">
            <h2>Obtener VIN</h2>
            <p>Consulta las placas de un vehículo para obtener su VIN</p>
        </div>
        <div class="rh-carvana-wrapper" id="carvanaWrapper">
            <iframe
                id="carvanaFrame"
                src="https://www.carvana.com/value-tracker/get-value/entry"
                loading="lazy"
                allowfullscreen
                title="Consulta Placa"
                sandbox="allow-scripts allow-same-origin allow-forms"
            ></iframe>
        </div>
        <div class="carvana-reset-container">
            <button type="button"
                    class="btn-carvana-reset"
                    onclick="volverBuscarVehiculo()">
                Limpiar datos
            </button>
        </div>
    </div>
</section>

<section class="rh-vin">
    <div class="container-fluid">
        <div class="rh-vin-header">
            <h2>Consulta VIN Vehicular</h2>
            <p>Realiza búsquedas y validaciones relacionadas con vehículos y configuraciones VIN.</p>
        </div>
        <div class="rh-iframe-wrapper">
            <!-- <iframe src="https://www.nisrinc.com/apps/cmvid/" loading="lazy" allowfullscreen></iframe> -->
            <iframe src="https://cartrack.com.mx/vincheck" loading="lazy" allowfullscreen></iframe>
        </div>
    </div>
</section>

<section class="rh-info">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="rh-info-card mb-4">
                    <span class="rh-info-tag">VINTrack</span>
                    <p>
                        La consulta del VIN (Número de Identificación del Vehículo) es de carácter <strong>informativo</strong> y no constituye
                        <strong>un documento oficial, una certificación legal de propiedad, ni una garantía absoluta del estado del vehículo.</strong>
                        <br/><br/>
                    Aquí te detallo que implica esto:
                    </p>

                    <div class="mt-4">
                        <div class="rh-point-item">
                            <div class="rh-point-dot"></div>
                            <div>
                                <span>Finalidad Informativa:</span>
                                <p>La consulta del VIN sirve para conocer datos técnicos, características del vehículo y su trazabilidad histórica desde planta armadora.</p>
                            </div>
                        </div>
                        <div class="rh-point-item">
                            <div class="rh-point-dot"></div>
                            <div>
                                <span>No es un título de propiedad:</span>
                                <p>Aunque ayuda a verificar la documentación, el informe del VIN no reemplaza la factura, título de propiedad o registro vehicular oficial.</p>
                            </div>
                        </div>
                        <div class="rh-point-item">
                            <div class="rh-point-dot"></div>
                            <div>
                                <span>Limitaciones:</span>
                                <p>La información depende de los reportes que hayan sido notificados a las bases de datos (seguros, autoridades tránsito, seguridad pública,
                            agencias privadas, talleres). Si un accidente no fue reportado, no aparecerá en el informe.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rh-info-grid">
                    <div class="rh-info-card">
                        <span class="rh-info-tag">Marco legal nacional sobre delitos informáticos</span>
                        <p>
                            Leyes federales vigentes: México incorporó por primera vez figuras delictivas informáticas en su legislación 
                        federal en 1999. El Código Penal Federal (CPF) es el pilar normativo principal contra los ciberdelitos, 
                        incluyendo disposiciones específicas para sancionar el acceso ilícito a sistemas de cómputo, la sabotaje o 
                        destrucción de datos y otras conductas relacionadas con tecnologías de la información. Por ejemplo, 
                        el artículo 211 bis del CPF tipifica como delito el acceso no autorizado a sistemas informáticos, penalizando a 
                        quien modifique, destruya o provoque pérdida de información en sistemas protegidos. Asimismo, los artículos 211 bis 1 
                        y 211 bis 2 agravan las penas cuando estas intrusiones afectan sistemas del Estado o de seguridad pública. 
                        En tales casos, las sanciones pueden alcanzar hasta 10 años de prisión, especialmente si el responsable es un 
                        servidor público y se comprometen infraestructuras críticas. Estas reformas, introducidas desde finales de los 90 y 
                        fortalecidas en décadas posteriores, han permitido perseguir delitos como el hacking (acceso indebido a datos), 
                        el phishing (fraude digital) y el sabotaje informático dentro del marco penal mexicano.
                        </p>
                    </div>

                    <div class="rh-info-card">
                        <span class="rh-info-tag">Privacidad de consulta</span>
                        <p>
                            En términos de los artículos 8 de la Constitución Política de los Estado Unidos Mexicanos, 18 A y 19 del Código Fiscal 
                        de la Federación, los contribuyentes propietarios de un vehículo de procedencia extranjera pueden solicitar la 
                        búsqueda en el Sistema Electrónico Aduanero del pedimento con el que se importó al país, o en su caso, verificar 
                        el registro del pedimento que ampara la importación, obteniendo un oficio informativo con los datos relevantes del 
                        pedimento. La extracción de pedimento tiene carácter informativo, no sustituye al pedimento y de conformidad con lo 
                        establecido en el artículo 146 de la ley aduanera, no tiene validez para acreditar la legal estancia en el país de su 
                        mercancía.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    (function () {
        const wrapper = document.getElementById('carvanaWrapper');
        if (!wrapper) return;

        // Escucha mensajes postMessage de Carvana. Si el sitio externo envía
        // información de altura o navegación, se actualiza el recorte visual.
        // Nota: no es posible inspeccionar el DOM interno del iframe por SOP.
        window.addEventListener('message', function (event) {
            if (event.origin !== 'https://www.carvana.com') return;

            const data = event.data;
            if (!data || typeof data !== 'object') return;

            // Ejemplo: si Carvana envía { type: 'carvana:navigation', page: 'found' }
            if (data.page === 'found' || data.type === 'carvana:found') {
                wrapper.classList.add('is-found');
            }

            // Ejemplo: si Carvana envía { type: 'carvana:height', height: 900 }
            if (typeof data.height === 'number' && data.height > 0) {
                wrapper.style.height = data.height + 'px';
            }
        });
    })();
    function volverBuscarVehiculo() {
        const iframe = document.getElementById('carvanaFrame');
        iframe.src = "https://www.carvana.com/value-tracker/get-value/entry";
    }
</script>

@push('scripts')
<script>
    (function () {
        let userInteracted = false;
        const markInteraction = () => { userInteracted = true; };
        ['wheel', 'touchstart', 'mousedown', 'keydown'].forEach(evt =>
            window.addEventListener(evt, markInteraction, { once: true, passive: true })
        );

        const graceUntil = Date.now() + 1800;
        const enforceTop = () => {
            if (userInteracted || Date.now() > graceUntil) return;
            if (window.scrollY > 0) window.scrollTo(0, 0);
            requestAnimationFrame(enforceTop);
        };
        requestAnimationFrame(enforceTop);
    })();
</script>
@endpush
@endsection
