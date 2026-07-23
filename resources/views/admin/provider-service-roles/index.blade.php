@extends('layouts.app')

@section('title', 'Servicios por Rol')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Servicios por Rol</h2>
        </div>

        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @forelse($roles as $role)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">{{ $role->descripcion ?? ucfirst($role->nombre) }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Proveedor</th>
                                    <th>Servicio</th>
                                    <th style="width:120px;" class="text-center">Permitido</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($services as $service)
                                    @php
                                        $allowed = $rolePermissions[$role->id_rol][$service->id] ?? false;
                                    @endphp
                                    <tr>
                                        <td>{{ $service->provider?->name ?? '—' }}</td>
                                        <td>{{ $service->name }}</td>
                                        <td class="text-center">
                                            <form action="{{ route('admin.provider-service-roles.update', [$role->id_rol, $service->id]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ $allowed ? '0' : '1' }}">
                                                <button type="submit" class="btn btn-sm btn-{{ $allowed ? 'success' : 'secondary' }}">
                                                    {{ $allowed ? 'Sí' : 'No' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info">No hay roles activos configurados.</div>
        @endforelse
    </div>
</div>
@endsection
