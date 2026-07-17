@extends('layouts.app')

@section('title', 'Historial de Consultas')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Historial de Consultas</h2>
    </div>

    <h4 class="mb-3">Totales</h4>
    <div class="row mb-4">
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total de Consultas</h6>
                            <h2 id="kpi-total">{{ number_format($kpis['total'], 0) }}</h2>
                        </div>
                        <i class="bi bi-graph-up fs-1 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Consultas de Hoy</h6>
                            <h2 id="kpi-hoy">{{ number_format($kpis['today'], 0) }}</h2>
                        </div>
                        <i class="bi bi-calendar-check-fill fs-1 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Consultas Anteriores</h6>
                            <h2 id="kpi-anteriores">{{ number_format($kpis['previous'], 0) }}</h2>
                        </div>
                        <i class="bi bi-calendar fs-1 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Consultas Exitosas</h6>
                            <h2 id="kpi-exitosas">{{ number_format($kpis['success'], 0) }}</h2>
                        </div>
                        <i class="bi bi-check-circle-fill fs-1 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Consultas Fallidas</h6>
                            <h2 id="kpi-fallidas">{{ number_format($kpis['failure'], 0) }}</h2>
                        </div>
                        <i class="bi bi-x-circle-fill fs-1 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Alertas de Robo</h6>
                            <h2 id="kpi-alertas">{{ number_format($kpis['alerts'], 0) }}</h2>
                        </div>
                        <i class="bi bi-exclamation-triangle-fill fs-1 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.consultations.index') }}" class="row g-3 align-items-end">
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
                    <label class="form-label">Criterio</label>
                    <select name="criterio" class="form-select">
                        <option value="">Todos</option>
                        @foreach($criteria as $key => $label)
                            <option value="{{ $key }}" {{ request('criterio') == $key ? 'selected' : '' }}>
                                {{ $label }}
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
                <div class="col-md-3">
                    <div class="form-check">
                        <input type="hidden" name="alerts_only" value="0">
                        <input type="checkbox" class="form-check-input" id="alerts_only" name="alerts_only" value="1"
                               {{ request('alerts_only') ? 'checked' : '' }}>
                        <label class="form-check-label" for="alerts_only">Solo alertas de robo</label>
                    </div>
                </div>
                <div class="col-md-9 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="{{ route('admin.consultations.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="m-0">Últimas consultas</h5>
                <button id="btnRefrescarConsultas" class="btn btn-sm btn-outline-primary" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Refrescar
                </button>
            </div>
            <div class="table-responsive">
                <table id="tablaConsultas" class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Proveedor</th>
                            <th>Criterio</th>
                            <th>Placa / VIN</th>
                            <th>Servicios</th>
                            <th>Fecha</th>
                            <th>Costo</th>
                            <th>Status</th>
                            <th>Alerta Robo</th>
                            <th>Reporte</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($consultations as $consultation)
                            <tr class="{{ $consultation->alerta_robo ? 'table-danger' : '' }}">
                                <td>{{ $consultation->id }}</td>
                                <td>{{ $consultation->user?->name ?? '—' }}</td>
                                <td>{{ $consultation->user?->email ?? '—' }}</td>
                                <td>{{ $consultation->provider?->name ?? '—' }}</td>
                                <td>{{ strtoupper($consultation->criterio) }}</td>
                                <td>{{ $consultation->valor }}</td>
                                <td>
                                    @if($consultation->services)
                                        @foreach($consultation->services as $service)
                                            <span class="badge bg-secondary">{{ $service }}</span>
                                        @endforeach
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $consultation->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ number_format($consultation->costo_credito, 2) }}</td>
                                <td>
                                    @if($consultation->success)
                                        <span class="badge bg-success">Éxito</span>
                                    @else
                                        <span class="badge bg-danger">Fallo</span>
                                        @if($consultation->http_status_get)
                                            <small class="text-muted d-block">HTTP {{ $consultation->http_status_get }}</small>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($consultation->alerta_robo)
                                        <span class="badge bg-danger">Sí</span>
                                    @else
                                        <span class="badge bg-success">No</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('reports.show', $consultation->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        Ver
                                    </a>
                                </td>
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
        $(document).ready(function(){
            $('#tablaConsultas').DataTable({
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
