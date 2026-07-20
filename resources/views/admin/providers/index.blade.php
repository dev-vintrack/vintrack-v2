@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
    <h1>Administrar Proveedores y Servicios</h1>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @foreach ($providers as $provider)
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="card-title mb-0">{{ $provider->name }} ({{ $provider->code }})</h4>
                <form action="{{ route('admin.providers.update', $provider->id) }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="enabled" value="{{ $provider->enabled ? '0' : '1' }}">
                    <button type="submit" class="btn btn-{{ $provider->enabled ? 'danger' : 'success' }}">
                        {{ $provider->enabled ? 'Desactivar' : 'Activar' }}
                    </button>
                </form>
            </div>
            <p class="text-muted">Estado: <strong>{{ $provider->enabled ? 'Activo' : 'Inactivo' }}</strong></p>

            <h5>Servicios</h5>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Nombre</th>
                        <th>Costo crédito</th>
                        <th>Inventario</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($provider->services as $service)
                    <tr>
                        <td>{{ $service->key }}</td>
                        <td>{{ $service->name }}</td>
                        <td>
                            <form action="{{ route('admin.services.update', [$provider->id, $service->id]) }}" method="POST" class="row g-2 align-items-center">
                                @csrf
                                <div class="col-auto">
                                    <input type="number" step="0.01" min="0" name="credit_cost" value="{{ $service->credit_cost }}" class="form-control form-control-sm" style="width:100px">
                                </div>
                                <input type="hidden" name="enabled" value="{{ $service->enabled ? '0' : '1' }}">
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-{{ $service->enabled ? 'danger' : 'success' }} btn-sm">
                                        {{ $service->enabled ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </div>
                            </form>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark">{{ number_format($service->available_credits, 2) }}</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $service->enabled ? 'success' : 'secondary' }}">
                                {{ $service->enabled ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                    </tr>
                    @if($service->sections->isNotEmpty())
                    <tr>
                        <td colspan="5" class="p-0">
                            <div class="bg-light p-3 m-2 rounded">
                                <h6 class="fw-bold mb-3">Secciones de {{ $service->name }}</h6>
                                <table class="table table-sm table-bordered bg-white mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:120px;">Sección</th>
                                            <th style="width:120px;">Global</th>
                                            @foreach($roles as $roleKey => $roleLabel)
                                                <th class="text-center" style="font-size:12px;">{{ $roleLabel }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($service->sections as $section)
                                        <tr>
                                            <td>{{ $section->section_name }}</td>
                                            <td>
                                                <form action="{{ route('admin.sections.update', [$provider->id, $service->id, $section->id]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="{{ $section->status ? '0' : '1' }}">
                                                    <button type="submit" class="btn btn-{{ $section->status ? 'success' : 'secondary' }} btn-sm">
                                                        {{ $section->status ? 'Activa' : 'Inactiva' }}
                                                    </button>
                                                </form>
                                            </td>
                                            @foreach($roles as $roleKey => $roleLabel)
                                                @php
                                                    $roleSetting = $section->roleSettings->firstWhere('role', $roleKey);
                                                    $roleStatus = $roleSetting ? $roleSetting->status : $section->status;
                                                @endphp
                                            <td class="text-center">
                                                <form action="{{ route('admin.section-roles.update', [$provider->id, $service->id, $section->id, $roleKey]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="{{ $roleStatus ? '0' : '1' }}">
                                                    <button type="submit" class="btn btn-sm btn-{{ $roleStatus ? 'success' : 'secondary' }}">
                                                        {{ $roleStatus ? 'Sí' : 'No' }}
                                                    </button>
                                                </form>
                                            </td>
                                            @endforeach
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>
</div>
@endsection
