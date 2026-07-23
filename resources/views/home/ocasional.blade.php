@extends('layouts.app')

@section('title', 'Cliente Ocasional')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <h3>Cliente Ocasional</h3>
                    <p class="text-muted">
                        Si tienes un crédito ocasional asignado, ingresa el enlace que recibiste por correo para generar tu reporte.
                    </p>
                    <p class="text-muted">
                        ¿No tienes crédito? Solicítalo escribiendo a <a href="mailto:ventas@vintrack.com.mx">ventas@vintrack.com.mx</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @include('home.partials.dashboard_content')
</div>
@endsection
