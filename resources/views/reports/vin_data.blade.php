@extends('layouts.app')

@section('title', 'Reporte VINData - VINTRACK')

@section('content')
<div class="container py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="https://vintrack.com.mx/images/vintrack-auto5.jpeg" alt="VINTrack" height="50" class="me-3">
                <div>
                    <h4 class="mb-0">VINTrack</h4>
                    <small class="text-muted">Reporte de historial vehicular</small>
                </div>
            </div>
            <div class="text-end">
                <div class="text-muted" style="font-size:12px">Reporte #{{ $consultation->id() }}</div>
                <div class="text-muted" style="font-size:12px">{{ $consultation->createdAt()->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <div class="card-body">
            @php
                $info = $vehicleInfo;
                $productName = $info['productName'] ?? $info['productCode'] ?? 'Reporte VINData';
                $vin = $info['vin'] ?? $consultation->valor();
                $year = $info['year'] ?? ($summary['year'] ?? null);
                $make = $info['make'] ?? ($summary['make'] ?? null);
                $model = $info['model'] ?? ($summary['model'] ?? null);
                $color = $info['color'] ?? null;
            @endphp

            <div class="row mb-4">
                <div class="col-md-12">
                    <h5 class="text-primary">{{ $productName }}</h5>
                    <h2 class="mb-1">{{ $year }} {{ $make }} {{ $model }}</h2>
                    <p class="text-muted mb-2">VIN: <strong>{{ $vin }}</strong></p>
                    @if($color)
                        <p class="text-muted mb-0">Color: {{ $color }}</p>
                    @endif
                </div>
            </div>

            @if(!empty($reportSummary['message']))
                @php $alertColor = ($reportSummary['color'] ?? 'yellow') === 'red' ? 'danger' : 'warning'; @endphp
                <div class="alert alert-{{ $alertColor }}">
                    <strong>Resumen:</strong> {{ $reportSummary['message'] }}
                </div>
            @endif

            @if(!empty($otherInformation))
                <h6 class="mt-4">Alertas detectadas</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Evento</th>
                                <th>Ubicación</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($otherInformation as $item)
                                @php
                                    $rowClass = match($item['color'] ?? '') {
                                        'red' => 'table-danger',
                                        'yellow' => 'table-warning',
                                        'green' => 'table-success',
                                        default => ''
                                    };
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td>{{ $item['event'] ?? 'N/A' }}</td>
                                    <td>{{ $item['location'] ?? 'N/A' }}</td>
                                    <td>
                                        @foreach($item['detailsList'] ?? [] as $detail)
                                            <div>{{ $detail }}</div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(!empty($titleInformation))
                <h6 class="mt-4">Historial de títulos</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Tipo</th>
                                <th>Evento</th>
                                <th>Odómetro</th>
                                <th>Fuente</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($titleInformation as $title)
                                <tr>
                                    <td>{{ isset($title['date']) ? \Carbon\Carbon::parse($title['date'])->format('d/m/Y') : 'N/A' }}</td>
                                    <td>{{ $title['state'] ?? 'N/A' }}</td>
                                    <td>{{ $title['type'] ?? 'N/A' }}</td>
                                    <td>{{ $title['event'] ?? 'N/A' }}</td>
                                    <td>{{ ($title['reportedOdometer'] ?? '') . ' ' . ($title['measure'] ?? '') }}</td>
                                    <td>{{ $title['source'] ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(!empty($odometerInformation))
                <h6 class="mt-4">Historial de odómetro</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Fuente</th>
                                <th>Odómetro</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($odometerInformation as $odometer)
                                <tr>
                                    <td>{{ isset($odometer['date']) ? \Carbon\Carbon::parse($odometer['date'])->format('d/m/Y') : 'N/A' }}</td>
                                    <td>{{ $odometer['type'] ?? 'N/A' }}</td>
                                    <td>{{ $odometer['source'] ?? 'N/A' }}</td>
                                    <td>{{ ($odometer['reportedOdometer'] ?? '') . ' ' . ($odometer['measure'] ?? '') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(!empty($junkSalvageTotalLoss))
                <div class="alert alert-danger mt-4">
                    <strong>Marca de Junk / Salvage / Total Loss registrada.</strong>
                </div>
            @endif

            @if(!empty($titleBrandReported))
                <h6 class="mt-4">Marcas de título reportadas</h6>
                <ul class="list-group list-group-flush">
                    @foreach($titleBrandReported as $brand)
                        <li class="list-group-item">{{ $brand['name'] ?? json_encode($brand) }}</li>
                    @endforeach
                </ul>
            @endif

            @if(!empty($trimLevels))
                <h6 class="mt-4">Especificaciones del vehículo</h6>
                @foreach($trimLevels as $trim => $sections)
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong>{{ $trim }}</strong>
                        </div>
                        <div class="card-body">
                            @foreach($sections as $sectionName => $values)
                                <h6 class="text-muted mt-3" style="font-size:14px">{{ $sectionName }}</h6>
                                <div class="row">
                                    @foreach($values as $key => $value)
                                        <div class="col-md-4 mb-2">
                                            <div class="text-muted" style="font-size:12px">{{ $key }}</div>
                                            <div>{{ is_array($value) ? json_encode($value) : $value }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif

            <div class="text-center mt-4">
                <a href="{{ route('reports.pdf', $consultation->id()) }}" class="btn btn-outline-primary" target="_blank">
                    Descargar PDF
                </a>
            </div>
        </div>

        <div class="card-footer bg-white text-center text-muted" style="font-size:12px">
            © {{ date('Y') }} VINTrack. Reporte generado con datos de VINData.
        </div>
    </div>
</div>
@endsection
