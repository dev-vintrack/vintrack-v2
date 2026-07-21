@extends('layouts.app')

@section('title', 'Inventario Global - VINTRACK')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <h4 class="mb-3">Inventario Global</h4>

        <div class="row mb-4">
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card shadow border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Total de movimientos</h6>
                                <h2 id="kpi-total-movimientos">{{ number_format($totalMovements, 0) }}</h2>
                            </div>
                            <i class="bi bi-arrow-left-right fs-1 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card shadow border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Créditos totales</h6>
                                <h2 id="kpi-total-creditos">{{ number_format($totalCredits, 2) }}</h2>
                            </div>
                            <i class="bi bi-credit-card fs-1 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card shadow border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Servicios con inventario</h6>
                                <h2 id="kpi-total-servicios">{{ number_format($totalServices, 0) }}</h2>
                            </div>
                            <i class="bi bi-layers fs-1 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            @foreach ($balances as $service)
                <div class="col-md-4 col-sm-6 mb-3">
                    <div class="card shadow border-0 h-100">
                        <div class="card-body">
                            <h6 class="text-muted">{{ $service->name }}</h6>
                            <small class="text-muted d-block">{{ $service->provider?->name ?? '—' }}</small>
                            <h3 class="mt-2">{{ number_format($service->available_credits, 2) }}</h3>
                            <small class="text-muted">créditos disponibles</small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Ajuste manual de inventario</h5>
                        <form method="POST" action="{{ route('admin.inventory.adjustment.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="adjustment_service" class="form-label">Servicio</label>
                                <select name="provider_service_id" id="adjustment_service" class="form-select" required>
                                    <option value="">Selecciona...</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->id }}">
                                            {{ $service->name }} ({{ $service->provider?->name ?? '—' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Cantidad (positiva o negativa)</label>
                                <input type="number" step="0.01" name="quantity" id="quantity" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notas</label>
                                <textarea name="notes" id="notes" class="form-control" rows="2" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning">Registrar ajuste</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column justify-content-center align-items-start">
                        <h5 class="mb-3">Reintegrar créditos vencidos</h5>
                        <p class="text-muted">
                            Ejecuta el proceso que busca wallets con vigencia vencida y saldo positivo, los descuenta del cliente y los regresa al inventario global.
                        </p>
                        <form method="POST" action="{{ route('admin.inventory.return-expired') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary" onclick="return confirm('¿Ejecutar reintegro de créditos vencidos?')">
                                <i class="bi bi-arrow-counterclockwise"></i> Ejecutar reintegro ahora
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Filtros</h5>
                <form method="GET" action="{{ route('admin.inventory.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="provider_service_id" class="form-label">Servicio</label>
                        <select name="provider_service_id" id="provider_service_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" {{ $selectedService == $service->id ? 'selected' : '' }}>
                                    {{ $service->name }} ({{ $service->provider?->name ?? '—' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="type" class="form-label">Tipo</label>
                        <select name="type" id="type" class="form-select">
                            <option value="">Todos</option>
                            <option value="purchase" {{ $selectedType === 'purchase' ? 'selected' : '' }}>Compra</option>
                            <option value="sale" {{ $selectedType === 'sale' ? 'selected' : '' }}>Venta</option>
                            <option value="expiry_return" {{ $selectedType === 'expiry_return' ? 'selected' : '' }}>Reintegro vencido</option>
                            <option value="adjustment" {{ $selectedType === 'adjustment' ? 'selected' : '' }}>Ajuste</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="m-0">Movimientos de inventario</h5>
                    <button id="btnRefrescarInventario" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-clockwise"></i> Refrescar
                    </button>
                </div>
                <div class="table-responsive">
                    <table id="tablaMovimientos" class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Servicio</th>
                                <th>Proveedor</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Admin</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($movements as $movement)
                                <tr>
                                    <td>{{ $movement->id }}</td>
                                    <td>{{ $movement->created_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                                    <td>{{ $movement->service?->name ?? '—' }}</td>
                                    <td>{{ $movement->service?->provider?->name ?? '—' }}</td>
                                    <td>
                                        @switch($movement->type)
                                            @case('purchase') Compra @break
                                            @case('sale') Venta @break
                                            @case('expiry_return') Reintegro vencido @break
                                            @case('adjustment') Ajuste @break
                                            @default {{ $movement->type }}
                                        @endswitch
                                    </td>
                                    <td class="{{ $movement->quantity >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity >= 0 ? '+' : '' }}{{ number_format($movement->quantity, 2) }}
                                    </td>
                                    <td>{{ $movement->admin?->name ?? 'Sistema' }}</td>
                                    <td>{{ $movement->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Sin movimientos registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $movements->links() }}
                </div>
            </div>
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
            const table = $('#tablaMovimientos').DataTable({
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

            $('#btnRefrescarInventario').on('click', function () {
                window.location.reload();
            });
        });
    </script>
@endpush
