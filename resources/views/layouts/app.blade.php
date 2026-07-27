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
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 70px;
        }

        #sidebarMenu.sidebar {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 70px;
            background-color: var(--bs-dark, #212529) !important;
            padding-top: 1rem;
            overflow-y: auto;
        }

        #sidebarMenu.sidebar .offcanvas-body {
            display: block !important;
        }

        #sidebarMenu.sidebar .nav-link {
            border-radius: 0.375rem;
            transition: background-color 0.2s ease, color 0.2s ease;
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        #sidebarMenu.sidebar .nav-link:hover,
        #sidebarMenu.sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
        }

        #sidebarMenu.sidebar .nav-link.active {
            font-weight: 600;
        }

        /* Escritorio: sidebar ancho */
        @media (min-width: 992px) {
            #sidebarMenu.sidebar {
                position: fixed !important;
                top: 56px;
                bottom: 0;
                left: 0;
                z-index: 1000;
                width: var(--sidebar-width) !important;
                display: block !important;
                visibility: visible !important;
                transform: none !important;
                transition: width 0.25s ease;
            }
            #mainContent {
                margin-left: var(--sidebar-width);
                margin-right: 0;
                margin-top: 56px;
                width: calc(100% - var(--sidebar-width));
                max-width: 100%;
            }
        }

        /* Tablet: sidebar colapsado (solo iconos) */
        @media (min-width: 768px) and (max-width: 991.98px) {
            #sidebarMenu.sidebar {
                position: fixed !important;
                top: 56px;
                bottom: 0;
                left: 0;
                z-index: 1000;
                width: var(--sidebar-collapsed-width) !important;
                display: block !important;
                visibility: visible !important;
                transform: none !important;
                transition: width 0.25s ease;
                padding-top: 0.75rem;
            }
            #sidebarMenu.sidebar .menu-text {
                display: none !important;
            }
            #sidebarMenu.sidebar .nav-link {
                justify-content: center;
                padding: 0.75rem 0.25rem;
            }
            #sidebarMenu.sidebar .nav-link i {
                margin-right: 0 !important;
                font-size: 1.25rem;
            }
            #mainContent {
                margin-left: var(--sidebar-collapsed-width);
                margin-right: 0;
                margin-top: 56px;
                width: calc(100% - var(--sidebar-collapsed-width));
                max-width: 100%;
            }
        }

        /* Smartphone: offcanvas (hamburguesa) */
        @media (max-width: 767.98px) {
            #mainContent {
                margin-left: 0;
                margin-top: 56px;
            }
            #sidebarMenu.sidebar {
                --bs-offcanvas-width: var(--sidebar-width);
            }
        }

        /* Estados manuales colapsar/expandir (escritorio/tablet) */
        @media (min-width: 768px) {
            body.sidebar-collapsed-layout #sidebarMenu.sidebar {
                width: var(--sidebar-collapsed-width) !important;
                padding-top: 0.75rem !important;
            }
            body.sidebar-collapsed-layout #sidebarMenu.sidebar .menu-text {
                display: none !important;
            }
            body.sidebar-collapsed-layout #sidebarMenu.sidebar .nav-link {
                justify-content: center !important;
                padding: 0.75rem 0.25rem !important;
            }
            body.sidebar-collapsed-layout #sidebarMenu.sidebar .nav-link i {
                margin-right: 0 !important;
                font-size: 1.25rem !important;
            }
            body.sidebar-collapsed-layout #mainContent {
                margin-left: var(--sidebar-collapsed-width) !important;
                width: calc(100% - var(--sidebar-collapsed-width)) !important;
            }

            body.sidebar-expanded-layout #sidebarMenu.sidebar {
                width: var(--sidebar-width) !important;
                padding-top: 1rem !important;
            }
            body.sidebar-expanded-layout #sidebarMenu.sidebar .menu-text {
                display: inline !important;
            }
            body.sidebar-expanded-layout #sidebarMenu.sidebar .nav-link {
                justify-content: flex-start !important;
                padding: 0.5rem 1rem !important;
            }
            body.sidebar-expanded-layout #sidebarMenu.sidebar .nav-link i {
                margin-right: 0.5rem !important;
                font-size: 1rem !important;
            }
            body.sidebar-expanded-layout #mainContent {
                margin-left: var(--sidebar-width) !important;
                width: calc(100% - var(--sidebar-width)) !important;
            }
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <button class="navbar-toggler d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="{{ route('home') }}">VINTRACK</a>

            @auth
                <div class="d-flex align-items-center text-white ms-auto">
                    <span class="me-3 d-none d-sm-inline">{{ Auth::user()->name }} ({{ \App\Presentation\Support\RoleHelper::label(Auth::user()->rol) }})</span>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">Cerrar sesión</button>
                    </form>
                </div>
            @endauth
            @guest
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">Iniciar sesión</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Registrarse</a>
                </div>
            @endguest
        </div>
    </nav>

    @auth
        @php($user = Auth::user())
        @php($menuItems = \App\Presentation\Support\RoleHelper::menuItemsFor($user))
        <div class="offcanvas-md offcanvas-start bg-dark text-white sidebar" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
            <div class="offcanvas-header d-md-none">
                <h5 class="offcanvas-title" id="sidebarMenuLabel">Menú</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="px-2 mb-3 d-none d-md-block">
                    <button id="sidebarToggle" class="btn btn-outline-light btn-sm w-100" type="button">
                        <i class="bi bi-chevron-double-left"></i>
                        <span class="menu-text ms-1">Colapsar</span>
                    </button>
                </div>
                <ul class="nav flex-column">
                    @if(! \App\Presentation\Support\RoleHelper::isAdmin($user))
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white {{ request()->routeIs('home', 'home.*') ? 'active' : '' }}" href="{{ \App\Presentation\Support\RoleHelper::homeRoute($user) }}" title="Inicio">
                                <i class="bi bi-house-door me-2"></i>
                                <span class="menu-text">Inicio</span>
                            </a>
                        </li>
                    @endif
                    @foreach($menuItems as $item)
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white {{ request()->routeIs($item['route_name']) ? 'active' : '' }}"
                               href="{{ route($item['route_name']) }}" title="{{ $item['label'] }}">
                                @if($item['icon'])
                                    <i class="bi bi-{{ $item['icon'] }} me-2"></i>
                                @endif
                                <span class="menu-text">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endauth

    <main id="mainContent" class="container py-4">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const sidebar = document.getElementById('sidebarMenu');
            const toggleBtn = document.getElementById('sidebarToggle');
            if (!sidebar || !toggleBtn) return;

            const setState = (state) => {
                document.body.classList.remove('sidebar-collapsed-layout', 'sidebar-expanded-layout');
                if (state === 'collapsed') document.body.classList.add('sidebar-collapsed-layout');
                if (state === 'expanded') document.body.classList.add('sidebar-expanded-layout');
            };

            const getViewportDefault = () => window.innerWidth >= 992 ? 'expanded' : 'collapsed';

            const updateButton = (state) => {
                const icon = toggleBtn.querySelector('i');
                const text = toggleBtn.querySelector('.menu-text');
                if (state === 'collapsed') {
                    icon.className = 'bi bi-chevron-double-right';
                    if (text) text.textContent = 'Expandir';
                } else {
                    icon.className = 'bi bi-chevron-double-left';
                    if (text) text.textContent = 'Colapsar';
                }
            };

            const apply = () => {
                const stored = localStorage.getItem('sidebarState');
                const state = stored || getViewportDefault();
                setState(state);
                updateButton(state);
            };

            apply();

            toggleBtn.addEventListener('click', () => {
                const current = document.body.classList.contains('sidebar-collapsed-layout') ? 'collapsed'
                              : document.body.classList.contains('sidebar-expanded-layout') ? 'expanded'
                              : getViewportDefault();
                const next = current === 'collapsed' ? 'expanded' : 'collapsed';
                localStorage.setItem('sidebarState', next);
                setState(next);
                updateButton(next);
            });

            window.addEventListener('resize', () => {
                if (!localStorage.getItem('sidebarState')) {
                    apply();
                }
            });
        })();
    </script>
</body>
</html>
