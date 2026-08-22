@extends('layouts.app')

@section('title', 'Reporte VINTrack - VINTRACK')

@section('content')
<div class="container py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="{{ asset('images/logo-vintrack.png') }}" alt="VINTrack" height="50" class="me-3">
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
                $productName = $info['productName'] ?? $info['productCode'] ?? 'Reporte VINTrack';
                $vin = $info['vin'] ?? $consultation->valor();
                $year = $info['year'] ?? ($summary['year'] ?? null);
                $make = $info['make'] ?? ($summary['make'] ?? null);
                $model = $info['model'] ?? ($summary['model'] ?? null);
                $color = $info['color'] ?? null;
                $statusColor = strtolower((string) $color);
                $status = match($statusColor) {
                    'red' => 'Warning',
                    'yellow' => 'Caution',
                    'green' => 'Clean',
                    default => $color,
                };
                $statusBadgeClass = match($statusColor) {
                    'red' => 'bg-danger',
                    'yellow' => 'bg-warning text-dark',
                    'green' => 'bg-success',
                    default => 'bg-secondary',
                };
            @endphp

            @php
                $summaryColor = $reportSummary['color'] ?? 'green';
                $borderColor  = $summaryColor === 'red' ? '#dc3545' : ($summaryColor === 'yellow' ? '#ffc107' : '#198754');
                $bgColor      = $summaryColor === 'red' ? '#fff8f8'  : ($summaryColor === 'yellow' ? '#fffdf0'  : '#f0fff4');
            @endphp

            <div class="row mb-3">
                <div class="col-md-12">
                    <h2 class="mb-1">{{ $year }} {{ $make }} {{ $model }}</h2>
                    <p class="text-muted mb-1">VIN: <strong>{{ $vin }}</strong></p>
                    @if($status)
                        <p class="text-muted mb-2">Status: <span class="badge rounded-pill {{ $statusBadgeClass }}">{{ $status }}</span></p>
                    @endif

                    <div style="display:inline-flex; align-items:center; background:#333; border-radius:24px; padding:5px 14px; margin:8px 0 14px 0; gap:8px;">
                        <span style="display:inline-block; width:22px; height:22px; border-radius:50%; background:{{ $summaryColor === 'red' ? '#dc3545' : '#555' }}; {{ $summaryColor === 'red' ? 'box-shadow:0 0 8px #dc3545;' : '' }}"></span>
                        <span style="display:inline-block; width:22px; height:22px; border-radius:50%; background:{{ $summaryColor === 'yellow' ? '#ffc107' : '#555' }}; {{ $summaryColor === 'yellow' ? 'box-shadow:0 0 8px #ffc107;' : '' }}"></span>
                        <span style="display:inline-block; width:22px; height:22px; border-radius:50%; background:{{ $summaryColor === 'green' ? '#198754' : '#555' }}; {{ $summaryColor === 'green' ? 'box-shadow:0 0 8px #198754;' : '' }}"></span>
                    </div>
                </div>
            </div>

            @if(!empty($reportSummary['message']))
                <div style="border-left: 4px solid {{ $borderColor }}; background: {{ $bgColor }}; padding: 10px 14px; margin-bottom: 16px; border-radius: 0 4px 4px 0;">
                    <strong>Resumen:</strong> {{ $reportSummary['message'] }}
                </div>
            @endif

            @if(!empty($otherInformation))
                <h6 class="mt-4">Alertas detectadas</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
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
                                    <td>{{ $item['date'] ?? '' }}</td>
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
                <h6 class="mt-4">Junk / Salvage / Total Loss</h6>
                <table class="table table-bordered table-sm mt-2">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Entidad reportante</th>
                            <th>Tipo de entidad</th>
                            <th>Disposición</th>
                            <th>Fuente</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($junkSalvageTotalLoss as $junk)
                            @php $rowClass = ($junk['color'] ?? '') === 'red' ? 'table-danger' : (($junk['color'] ?? '') === 'yellow' ? 'table-warning' : ''); @endphp
                            <tr class="{{ $rowClass }}">
                                <td>{{ isset($junk['date']) ? \Carbon\Carbon::parse($junk['date'])->format('d/m/Y') : 'N/A' }}</td>
                                <td>{{ $junk['reportedEntity'] ?? 'N/A' }}</td>
                                <td>{{ $junk['reportedEntityType'] ?? 'N/A' }}</td>
                                <td>{{ $junk['disposition'] ?? 'N/A' }}</td>
                                <td>{{ $junk['source'] ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if(!empty($titleBrandReported))
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <h6 class="mb-0">Marcas de título reportadas</h6>
                    <small class="text-muted">Fuente: NMVTIS</small>
                </div>
                <div class="mt-2 mb-3" style="border-left: 4px solid #dc3545; background: #fff8f8; padding: 10px 14px;">
                    Advertencia: se reportaron una o más marcas de título DMV negativas o preventivas.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha de emisión</th>
                                <th>Estado</th>
                                <th>Marca</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                    @foreach($titleBrandReported as $brand)
                        @php
                            $brandName = $brand['brand'] ?? $brand['title'] ?? $brand['name'] ?? json_encode($brand);
                            $brandColor = strtolower($brand['color'] ?? '');
                            $brandFlag = strtolower($brand['flag'] ?? '');
                            $rowClass = match($brandColor) {
                                'red' => 'table-danger',
                                'yellow' => 'table-warning',
                                'green' => 'table-success',
                                default => ''
                            };
                            $borderColor = match($brandColor) {
                                'red' => '#dc3545',
                                'yellow' => '#ffc107',
                                'green' => '#198754',
                                default => '#6c757d'
                            };
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td style="border-left: 4px solid {{ $borderColor }};">{{ isset($brand['date']) ? \Carbon\Carbon::parse($brand['date'])->format('d/m/Y') : 'N/A' }}</td>
                            <td>{{ $brand['state'] ?? 'N/A' }}</td>
                            <td>
                                {{ $brandName }}
                                @if($brandFlag !== '')
                                    <span class="badge text-bg-{{ $brandColor === 'red' ? 'danger' : ($brandColor === 'yellow' ? 'warning' : ($brandColor === 'green' ? 'success' : 'secondary')) }} ms-1">{{ strtoupper($brandFlag) }}</span>
                                @endif
                            </td>
                            <td>{{ $brand['description'] ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                        </tbody>
                    </table>
                </div>
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

            <style>
                .disclaimer { margin-top: 30px; padding: 20px; background-color: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 0.85rem; color: #444; line-height: 1.5; }
                .disclaimer-title { font-size: 1rem; font-weight: 700; color: #222; margin-top: 22px; margin-bottom: 10px; }
                .disclaimer-title:first-child { margin-top: 0; }
                .disclaimer-text { margin-bottom: 10px; text-align: justify; }
                .disclaimer-list { margin-top: 10px; margin-bottom: 14px; padding-left: 20px; }
                .disclaimer-list li { margin-bottom: 6px; }
                .disclaimer a { color: #0d6efd; text-decoration: none; }
            </style>

            @include('reports.partials._vin_data_disclaimer')
        </div>

        <div class="card-footer bg-white text-center text-muted" style="font-size:12px">
            © {{ date('Y') }} VINTrack. Reporte generado desde el sitio VINTrack.com.mx
        </div>
    </div>
</div>
@endsection
