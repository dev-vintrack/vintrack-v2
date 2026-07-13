@extends('layouts.app')

@section('title', 'Panel de Oficial')

@section('content')
<div class="container py-4">
    <div class="alert alert-dark" style="color:#fff; background:#212529;">
        <strong>Bienvenido, personal de Corporación Oficial.</strong><br>
        Tienes acceso a todos los servicios de todos los proveedores. Más adelante se agregará contenido especial para corporaciones.
    </div>

    @include('home.partials.dashboard_content')
</div>
@endsection
