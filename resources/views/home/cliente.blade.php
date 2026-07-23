@extends('layouts.app')

@section('title', 'Panel de Cliente Registrado')

@section('content')
<div class="container py-4">
    <div class="alert alert-info">
        <strong>Bienvenido, Cliente Registrado.</strong><br>
        Aquí puedes consultar el historial de tu vehículo, utilizando los créditos o paquetes que hayas adquirido.
    </div>

    @include('home.partials.dashboard_content')
</div>
@endsection
