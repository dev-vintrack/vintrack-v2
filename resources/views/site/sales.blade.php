@extends('layouts.site')

@section('title', 'Venta - VINTRACK')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home-landing.css') }}">
@endpush

@section('content')
<section class="hero">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h1 class="display-5 fw-bold mb-3">Venta</h1>
                <p class="lead mb-0">Solicita tu reporte completo de forma única, sin registro ni suscripción.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm p-4">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form action="{{ route('site.sales.send') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Tu nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="tu@correo.com" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Mensaje</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required>Deseo compra un reporte completo por única ocasión de un vehículo Nacional/USA/Ambos</textarea>
                            <p class="form-text text-muted mb-0">En cuanto envíes tu mensaje, Un asesor comercial te indicara la forma y el proceso de pago, para que recibas el PDF de tu reporte completo.</p>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar mensaje</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 h-100">
                    <h5 class="fw-bold mb-3">Información</h5>
                    <p class="text-muted mb-2"><i class="bi bi-envelope me-2"></i>{{ config('vintrack.ventas_email') }}</p>
                    <p class="text-muted mb-2"><i class="bi bi-telephone me-2"></i>+52 000 000 0000</p>
                    <p class="text-muted mb-0"><i class="bi bi-geo-alt me-2"></i>México</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
