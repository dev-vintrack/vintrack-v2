@extends('layouts.app')

@section('title', 'Paquetes de Créditos - Admin')

@section('content')
<div class="row">
    <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Paquetes de Créditos</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.packages.assign') }}" class="btn btn-success">
                Asignar Paquete a Usuario
            </a>
            <a href="{{ route('admin.packages.create') }}" class="btn btn-primary">
                + Nuevo Paquete
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($packages->isEmpty())
        <div class="alert alert-info">No hay paquetes creados aún.</div>
    @else
        <div class="row g-3">
            @foreach($packages as $package)
            <div class="col-12">
                <div class="card border-0 shadow-sm {{ $package->active ? '' : 'opacity-75' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1">
                                    {{ $package->name }}
                                    <span class="badge bg-{{ $package->active ? 'success' : 'secondary' }} ms-2" style="font-size:11px;">
                                        {{ $package->active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </h5>
                                @if($package->description)
                                    <p class="text-muted mb-2" style="font-size:13px;">{{ $package->description }}</p>
                                @endif
                                <div class="d-flex gap-3 text-muted" style="font-size:13px;">
                                    <span><strong>Precio:</strong> ${{ number_format($package->price, 2) }}</span>
                                    <span><strong>Vigencia:</strong> {{ $package->validity_days }} días</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2 ms-3">
                                <a href="{{ route('admin.packages.edit', $package->id) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                <form action="{{ route('admin.packages.destroy', $package->id) }}" method="POST"
                                      onsubmit="return confirm('¿Eliminar este paquete? Esta acción no se puede deshacer.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>

                        @if($package->items->isNotEmpty())
                        <div class="mt-3">
                            <strong style="font-size:13px;">Créditos incluidos:</strong>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @foreach($package->items as $item)
                                <span class="badge bg-primary" style="font-size:13px; padding:6px 12px;">
                                    {{ $item->service->provider->name ?? 'Proveedor' }} - {{ $item->service->name ?? 'Servicio' }}:
                                    <strong>{{ number_format($item->credits, 0) }} créditos</strong>
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
</div>
@endsection
