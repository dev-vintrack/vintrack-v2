@extends('layouts.app')

@section('title', 'Compras a Proveedor - Admin')

@section('content')
<div class="row">
    <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Compras a Proveedor</h2>
        <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">+ Nueva Compra</a>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Servicio</th>
                            <th>Cantidad</th>
                            <th>Costo Unit.</th>
                            <th>Costo Total</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            <tr>
                                <td>{{ $purchase->id }}</td>
                                <td>{{ $purchase->provider->name ?? '—' }}</td>
                                <td>{{ $purchase->service->name ?? '—' }}</td>
                                <td>{{ number_format($purchase->quantity, 2) }}</td>
                                <td>${{ number_format($purchase->unit_cost, 2) }}</td>
                                <td>${{ number_format($purchase->total_cost, 2) }}</td>
                                <td>{{ $purchase->purchase_date?->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $purchase->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ $purchase->status === 'active' ? 'Activa' : 'Cancelada' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.purchases.edit', $purchase->id) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <form action="{{ route('admin.purchases.destroy', $purchase->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta compra?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No se encontraron compras.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($purchases->hasPages())
            <div class="card-footer bg-white border-top-0 d-flex justify-content-center">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</div>
</div>
@endsection
