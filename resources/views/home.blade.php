@extends('layouts.app')

@section('title', 'Dashboard - VINTRACK')

@section('content')
<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Dashboard</h1>

        <div class="row">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted" style="font-size:13px">Proveedor activo</div>
                                <div style="font-size:28px; font-weight:700">{{ $provider?->name() ?? 'N/A' }}</div>
                            </div>
                            <i class="bi bi-hdd-network" style="font-size:36px; color:#0d6efd"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted" style="font-size:13px">Código</div>
                                <div style="font-size:28px; font-weight:700">{{ $provider?->code()->value() ?? 'N/A' }}</div>
                            </div>
                            <i class="bi bi-upc-scan" style="font-size:36px; color:#198754"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted" style="font-size:13px">Costo por consulta</div>
                                <div style="font-size:28px; font-weight:700">{{ $provider ? number_format($provider->creditCost(), 2) : 'N/A' }}</div>
                            </div>
                            <i class="bi bi-credit-card-2-front" style="font-size:36px; color:#dc3545"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-4">
            <strong>Sprint 1 activo.</strong> La estructura de dominio, proveedores y autenticación está funcionando. El frontend original se irá portando progresivamente.
        </div>
    </div>
</div>
@endsection
