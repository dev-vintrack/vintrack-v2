@extends('layouts.app')

@section('title', 'Movimientos de Wallet')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Movimientos de Wallet</h2>
    </div>

    <h4 class="mb-3">Resumen</h4>
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total de Movimientos</h6>
                            <h2 id="kpi-total">{{ number_format($kpis['total'], 0) }}</h2>
                        </div>
                        <i class="bi bi-arrow-left-right fs-1 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Créditos Totales</h6>
                            <h2 id="kpi-creditos">+{{ number_format($kpis['credits'], 2) }}</h2>
                        </div>
                        <i class="bi bi-currency-dollar fs-1 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Débitos Totales</h6>
                            <h2 id="kpi-debitos">-{{ number_format($kpis['debits'], 2) }}</h2>
                        </div>
                        <i class="bi bi-credit-card fs-1 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Balance Neto</h6>
                            <h2 id="kpi-balance">{{ number_format($kpis['balance'], 2) }}</h2>
                        </div>
                        <i class="bi bi-wallet2 fs-1 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.wallets.movements') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Usuario</label>
                    <select name="user_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Proveedor</label>
                    <select name="provider_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach($providers as $p)
                            <option value="{{ $p->id }}" {{ request('provider_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="{{ route('admin.wallets.movements') }}" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tablaMovimientos" class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Proveedor</th>
                            <th>Wallet ID</th>
                            <th>Delta</th>
                            <th>Razón</th>
                            <th>Meta</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $movement)
                            <tr>
                                <td>{{ $movement->id }}</td>
                                <td>{{ $movement->wallet?->user?->name ?? '—' }}</td>
                                <td>{{ $movement->wallet?->provider?->name ?? '—' }}</td>
                                <td>{{ $movement->wallet_id }}</td>
                                <td data-order="{{ $movement->delta }}">
                                    <span class="badge bg-{{ $movement->delta >= 0 ? 'success' : 'danger' }}">
                                        {{ $movement->delta >= 0 ? '+' : '' }}{{ number_format($movement->delta, 2) }}
                                    </span>
                                </td>
                                <td>{{ $movement->reason }}</td>
                                <td>
                                    @if($movement->meta)
                                        @foreach($movement->meta as $key => $value)
                                            <small class="d-block text-muted">{{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}</small>
                                        @endforeach
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-order="{{ $movement->created_at }}">{{ \Carbon\Carbon::parse($movement->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
            $('#tablaMovimientos').DataTable({
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
        });
    </script>
@endpush
