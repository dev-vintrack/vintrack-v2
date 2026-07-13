<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'VINTRACK')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">VINTRACK</a>
            @auth
                @php
                    $user = Auth::user();
                    $isAdmin = \App\Presentation\Support\RoleHelper::canAccessAdmin($user);
                @endphp
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link" href="{{ \App\Presentation\Support\RoleHelper::homeRoute($user) }}">Inicio</a></li>
                        @if($isAdmin)
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.providers.index') }}">Proveedores</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.packages.index') }}">Paquetes</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.credits.purchase') }}">Comprar Créditos</a></li>
                        @endif
                        @if(\App\Presentation\Support\RoleHelper::canManageUsers($user))
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}">Usuarios</a></li>
                        @endif
                    </ul>
                    <div class="d-flex align-items-center text-white">
                        <span class="me-3">{{ $user->name }} ({{ \App\Presentation\Support\RoleHelper::label($user->rol) }})</span>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            @endauth
            @guest
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">Iniciar sesión</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Registrarse</a>
                </div>
            @endguest
        </div>
    </nav>

    <main class="container">
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

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
