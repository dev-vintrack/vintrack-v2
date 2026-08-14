@extends('layouts.app')

@section('title', 'Administración de Vehículos - VINTRACK')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/vintrack/datatables-1.13.6.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/vintrack/datatables-buttons-2.4.2.bootstrap5.min.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
    <h4 class="mb-3">Resultados Vehiculares</h4>

    <div class="row mb-4">
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Vehículos consultados</h6>
                            <h2 id="kpi-veh-consultados">{{ number_format($totalVehicles, 0) }}</h2>
                        </div>
                        <i class="bi bi-car-front fs-1 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Vehículos sin reporte de Robo</h6>
                            <h2 id="kpi-veh-sin-robo">{{ number_format($sinRobo, 0) }}</h2>
                        </div>
                        <i class="bi bi-shield-check fs-1 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card shadow border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Vehículos con reporte de Robo</h6>
                            <h2 id="kpi-veh-con-robo">{{ number_format($conRobo, 0) }}</h2>
                        </div>
                        <i class="bi bi-shield-exclamation fs-1 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="m-0">Vehículos registrados</h5>
                <button id="btnRefrescarVehiculos" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-clockwise"></i> Refrescar
                </button>
            </div>
            <div class="table-responsive">
                <table id="tablaVehiculos" class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Servicio</th>
                            <th>Criterio</th>
                            <th>Valor</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Año</th>
                            <th>Status Robo</th>
                            <th>Total Consultas</th>
                            <th>Última Consulta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vehicles as $vehicle)
                            <tr class="{{ $vehicle->ultimo_status_robo ? 'table-danger' : '' }}">
                                <td>{{ $vehicle->id }}</td>
                                <td>
                                    @if ($vehicle->service)
                                        {{ $vehicle->service->name }}
                                        <small class="text-muted d-block">({{ $vehicle->service->provider?->name ?? '—' }})</small>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ strtoupper($vehicle->criterio) }}</td>
                                <td>{{ $vehicle->valor }}</td>
                                <td>{{ $vehicle->marca ?? '—' }}</td>
                                <td>{{ $vehicle->modelo ?? '—' }}</td>
                                <td>{{ $vehicle->anio ?? '—' }}</td>
                                <td>{{ $vehicle->ultimo_status_robo ? 'Sí' : 'No' }}</td>
                                <td>{{ $vehicle->total_consultas }}</td>
                                <td>{{ $vehicle->ultima_consulta_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $vehicles->links() }}
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/vintrack/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('vendor/vintrack/datatables-1.13.6.min.js') }}"></script>
    <script src="{{ asset('vendor/vintrack/datatables-1.13.6.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/vintrack/datatables-buttons-2.4.2.min.js') }}"></script>
    <script src="{{ asset('vendor/vintrack/datatables-buttons-2.4.2.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/vintrack/jszip-3.10.1.min.js') }}"></script>
    <script src="{{ asset('vendor/vintrack/pdfmake-0.2.7.min.js') }}"></script>
    <script src="{{ asset('vendor/vintrack/pdfmake-0.2.7-vfs_fonts.js') }}"></script>
    <script src="{{ asset('vendor/vintrack/datatables-buttons-2.4.2.html5.min.js') }}"></script>
    <script src="{{ asset('vendor/vintrack/datatables-buttons-2.4.2.print.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            const table = $('#tablaVehiculos').DataTable({
                responsive: true,
                pageLength: 10,
                ordering: true,
                searching: true,
                lengthMenu: [10, 25, 50, 100],
                language: { url: '{{ asset('vendor/vintrack/datatables-es-ES-1.13.6.json') }}' },
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'excelHtml5', text: 'Excel' },
                    { extend: 'pdfHtml5', text: 'PDF', orientation: 'landscape', pageSize: 'A3' },
                    { extend: 'copyHtml5', text: 'Copiar' },
                    { extend: 'print', text: 'Imprimir' }
                ]
            });

            $('#btnRefrescarVehiculos').on('click', function () {
                window.location.reload();
            });
        });
    </script>
@endpush
