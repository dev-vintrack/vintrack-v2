@extends('layouts.app')

@section('title', 'Solicitud en Revisión')

@section('content')
<div class="container py-5" style="max-width:600px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 text-center">
            <h3>Solicitud enviada</h3>
            <div class="alert alert-warning mt-3">
                Tu solicitud para ser <strong>{{ \App\Presentation\Support\RoleHelper::label(auth()->user()->rol) }}</strong> está en revisión.
                Un administrador validará tu información y activará tu cuenta. Te notificaremos por correo.
            </div>
            <p class="text-muted">
                Si tienes dudas, escribe a <a href="mailto:ventas@vintrack.com.mx">ventas@vintrack.com.mx</a>.
            </p>
        </div>
    </div>
</div>
@endsection
