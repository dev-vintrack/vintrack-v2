@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endpush

@section('title', 'Créditos por Usuario')

@section('content')
<div class="row">
    <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Créditos por Usuario</h2>
    </div>

    <h4 class="mb-3">Resumen</h4>
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Wallets</h6>
                            <h2 id="kpi-total">{{ number_format($kpis['total'], 0) }}</h2>
                        </div>
                        <i class="bi bi-wallet fs-1 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Usuarios con Créditos</h6>
                            <h2 id="kpi-users">{{ number_format($kpis['users'], 0) }}</h2>
                        </div>
                        <i class="bi bi-people fs-1 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Saldo Total</h6>
                            <h2 id="kpi-balance">{{ number_format($kpis['balance'], 2) }}</h2>
                        </div>
                        <i class="bi bi-cash-coin fs-1 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Créditos Bajos</h6>
                            <h2 id="kpi-low">{{ number_format($kpis['low_balance'], 0) }}</h2>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.wallets.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
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
                <div class="col-md-5">
                    <label class="form-label">Servicio</label>
                    <select name="provider_service_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach($services as $s)
                            <option value="{{ $s->id }}" {{ request('provider_service_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->provider->name }} - {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="{{ route('admin.wallets.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tablaWallets" class="table table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Servicio</th>
                            <th>Saldo</th>
                            <th>Vigencia Inicio</th>
                            <th>Vigencia Fin</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($wallets as $wallet)
                            <tr>
                                <td>{{ $wallet->user?->name ?? '—' }}</td>
                                <td>{{ $wallet->user?->email ?? '—' }}</td>
                                <td>{{ $wallet->service?->provider?->name ?? '—' }} - {{ $wallet->service?->name ?? '—' }}</td>
                                <td data-order="{{ $wallet->balance }}">
                                    <span class="badge bg-{{ $wallet->balance <= $wallet->min_alert ? 'warning text-dark' : 'success' }}">
                                        {{ number_format($wallet->balance, 2) }}
                                    </span>
                                </td>
                                <td data-order="{{ $wallet->validity_start?->toDateTimeString() }}">
                                    {{ $wallet->validity_start?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td data-order="{{ $wallet->validity_end?->toDateTimeString() }}">
                                    {{ $wallet->validity_end?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ $wallet->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ $wallet->status === 'active' ? 'Activo' : 'Expirado' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 d-flex justify-content-center">
            {{ $wallets->links() }}
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
            $('#tablaWallets').DataTable({
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
                ],
                columnDefs: [
                    { targets: [3], type: 'num' }
                ]
            });
        });
    </script>
@endpush
