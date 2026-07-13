@extends('layouts.app')

@section('title', 'Panel de Perito')

@section('content')
<div class="container py-4">
    <div class="alert alert-primary">
        <strong>Bienvenido, Perito.</strong><br>
        Tienes acceso a todos los servicios de todos los proveedores. Más adelante se agregará contenido especial de peritaje.
    </div>

    @include('home.partials.dashboard_content')
</div>
@endsection
