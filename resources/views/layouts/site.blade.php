<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'VINTRACK')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    @stack('styles')
    <style>
        :root {
            --vt-primary: #0d6efd;
            --vt-dark: #1a1a2e;
        }
        .navbar-brand { font-weight: 700; letter-spacing: .5px; }
        .hero { background: linear-gradient(135deg, var(--vt-dark) 0%, #16213e 100%); color: #fff; padding: 5rem 0; }
        .stat-card { border: 0; border-radius: 1rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.08); }
        .plan-card { border: 0; border-radius: 1rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.08); transition: transform .15s; }
        .plan-card:hover { transform: translateY(-4px); }
        .vin-section { background: #f8f9fa; }
        .decoder-result .list-group-item { border-left: 0; border-right: 0; }
        footer { background: var(--vt-dark); color: #fff; padding: 2rem 0; }
        .logo-menu {
            height: 42px;
            transition: transform 0.3s ease;
        }
        .logo-menu:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <img src="{{ asset('images/logo-vintrack.png') }}" alt="Vintrack" class="logo-menu">
            <!--<a class="navbar-brand" href="{{ route('site.home') }}">VINTRACK</a>-->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="publicNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('site.home') ? 'active' : '' }}" href="{{ route('site.home') }}">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('site.about') ? 'active' : '' }}" href="{{ route('site.about') }}">Nosotros</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('site.services') ? 'active' : '' }}" href="{{ route('site.services') }}">Servicios</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('site.training') ? 'active' : '' }}" href="{{ route('site.training') }}">Capacitación</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('site.contact') ? 'active' : '' }}" href="{{ route('site.contact') }}">Contacto</a></li>
                    <li class="nav-item ms-lg-3">
                        <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">Iniciar sesión</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Registrarse</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main style="padding-top: 56px;">
        @yield('content')
    </main>

    <footer class="mt-0">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <img src="{{ asset('images/logo-vintrack.png') }}" alt="Vintrack" class="footer-logo">
                    <p>Plataforma inteligente para la gestión vehicular, revisión de historial y prevención de fraudes.</p>
                </div>

                <div class="col-md-4 mb-4">
                    <h5>Enlaces</h5>
                    <ul class="footer-links">
                        <li><a href="{{ route('site.home') }}">Inicio</a></li>
                        <li><a href="{{ route('site.about') }}">Nosotros</a></li>
                        <li><a href="{{ route('site.services') }}">Servicios</a></li>
                        <li><a href="{{ route('site.training') }}">Capacitación</a></li>
                        <li><a href="{{ route('site.contact') }}">Contacto</a></li>
                    </ul>
                </div>

                <div class="col-md-4 mb-4">
                    <h5>Contacto</h5>
                    <p>Email: {{ config('vintrack.contact_email') }}</p>
                </div>
            </div>

            <hr class="footer-divider">

            <div class="container text-center">
                <p class="mb-0">&copy; {{ date('Y') }} VINTRACK. Todos los derechos reservados.</p>
            </div>
        </div>   
    </footer>

    @stack('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
