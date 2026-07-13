@extends('layouts.app')

@section('title', 'Cliente Ocasional')

@section('content')
<div class="container py-5" style="max-width:600px;">
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
@endsection
