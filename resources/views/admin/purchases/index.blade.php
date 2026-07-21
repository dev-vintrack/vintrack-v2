@extends('layouts.app')

@section('title', 'Compras a Proveedor - Admin')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endpush

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

        <div class="row mb-4">
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card shadow border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Total de compras</h6>
                                <h2 id="kpi-total-compras">{{ number_format($totalPurchases, 0) }}</h2>
                            </div>
                            <i class="bi bi-cart fs-1 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card shadow border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Compras activas</h6>
                                <h2 id="kpi-compras-activas">{{ number_format($activePurchases, 0) }}</h2>
                            </div>
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card shadow border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Costo total</h6>
                                <h2 id="kpi-costo-total">${{ number_format($totalCost, 2) }}</h2>
                            </div>
                            <i class="bi bi-currency-dollar fs-1 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                <h5 class="m-0">Listado de compras</h5>
                <button id="btnRefrescarCompras" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-clockwise"></i> Refrescar
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tablaCompras" class="table table-striped table-bordered table-hover mb-0 align-middle">
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
                            @foreach($purchases as $purchase)
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
                            @endforeach
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

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#tablaCompras').DataTable({
                responsive: true,
                pageLength: 10,
                ordering: true,
                searching: true,
                lengthMenu: [10, 25, 50, 100],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                dom: 'Blfrtip',
                buttons: [
                    { extend: 'excelHtml5', text: 'Excel' },
                    { extend: 'pdfHtml5', text: 'PDF', orientation: 'landscape', pageSize: 'A3' },
                    { extend: 'copyHtml5', text: 'Copiar' },
                    { extend: 'print', text: 'Imprimir' }
                ]
            });

            $('#btnRefrescarCompras').on('click', function () {
                window.location.reload();
            });
        });
    </script>
@endpush
