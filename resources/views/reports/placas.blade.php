@extends('layouts.app')

@section('title', 'Reporte Placas.info - VINTRACK')

@php
    $titles = \App\Presentation\Support\PlacasReportPresenter::sectionTitles();
@endphp

@section('content')
<div class="container py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="https://vintrack.com.mx/images/vintrack-auto5.jpeg" alt="VINTrack" height="50" class="me-3">
                <div>
                    <h4 class="mb-0">VINTrack</h4>
                    <small class="text-muted">Reporte de consulta vehicular</small>
                </div>
            </div>
            <div class="text-end">
                <div class="text-muted" style="font-size:12px">Reporte #{{ $consultation->id() }}</div>
                <div class="text-muted" style="font-size:12px">{{ $consultation->createdAt()->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-12">
                    <h2 class="mb-1 text-uppercase">{{ $consultation->valor() }}</h2>
                    <p class="text-muted mb-0">
                        Criterio: <strong>{{ strtoupper($consultation->criterio()) }}</strong>
                    </p>
                </div>
            </div>

            @php
                $bannerBg = $banner['bg'] ?? '#198754';
                $trafficColor = 'green';
                if (str_contains($bannerBg, 'dc3545')) $trafficColor = 'red';
                elseif (str_contains($bannerBg, 'fd7e14')) $trafficColor = 'orange';
                elseif (str_contains($bannerBg, 'ffc107')) $trafficColor = 'yellow';
                elseif (str_contains($bannerBg, '6f42c1')) $trafficColor = 'purple';
                elseif (str_contains($bannerBg, '6c757d')) $trafficColor = 'gray';
            @endphp

            <div style="display:inline-flex; align-items:center; background:#333; border-radius:24px; padding:5px 14px; margin:6px 0 12px 0; gap:8px;">
                <span style="display:inline-block; width:22px; height:22px; border-radius:50%; background:{{ $trafficColor === 'red'    ? '#dc3545' : '#555' }}; {{ $trafficColor === 'red'    ? 'box-shadow:0 0 8px #dc3545;' : '' }}"></span>
                <span style="display:inline-block; width:22px; height:22px; border-radius:50%; background:{{ $trafficColor === 'orange' ? '#fd7e14' : '#555' }}; {{ $trafficColor === 'orange' ? 'box-shadow:0 0 8px #fd7e14;' : '' }}"></span>
                <span style="display:inline-block; width:22px; height:22px; border-radius:50%; background:{{ $trafficColor === 'yellow' ? '#ffc107' : '#555' }}; {{ $trafficColor === 'yellow' ? 'box-shadow:0 0 8px #ffc107;' : '' }}"></span>
                <span style="display:inline-block; width:22px; height:22px; border-radius:50%; background:{{ $trafficColor === 'purple' ? '#6f42c1' : '#555' }}; {{ $trafficColor === 'purple' ? 'box-shadow:0 0 8px #6f42c1;' : '' }}"></span>
                <span style="display:inline-block; width:22px; height:22px; border-radius:50%; background:{{ $trafficColor === 'green'  ? '#198754' : '#555' }}; {{ $trafficColor === 'green'  ? 'box-shadow:0 0 8px #198754;' : '' }}"></span>
                <span style="display:inline-block; width:22px; height:22px; border-radius:50%; background:{{ $trafficColor === 'gray'   ? '#6c757d' : '#555' }}; {{ $trafficColor === 'gray'   ? 'box-shadow:0 0 8px #6c757d;' : '' }}"></span>
            </div>

            <div style="border-left: 4px solid {{ $banner['color'] }}; background-color: {{ $banner['bg'] }}; color: {{ $banner['color'] }}; padding: 10px 14px; margin-bottom: 16px; border-radius: 0 4px 4px 0;">
                <strong>{{ $banner['message'] }}</strong>
            </div>

            @php $anyContent = false; @endphp
            <div class="row" style="gap:0;">
                @foreach($titles as $key => $title)
                    @php
                        $data = $sections[$key] ?? null;
                        $rows = \App\Presentation\Support\PlacasReportPresenter::flatten($data);
                    @endphp
                    @if(!empty($rows))
                        @php $anyContent = true; @endphp
                        <div class="col-12 col-lg-6 mb-3">
                            <div class="card h-100" style="overflow:hidden;">
                                <div class="card-header" style="font-weight:600;">{{ $title }}</div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-sm mb-0">
                                            <tbody>
                                                @foreach($rows as [$rawKey, $value])
                                                    @php
                                                        $label = \App\Presentation\Support\PlacasReportPresenter::prettyKey($rawKey);
                                                        $isAlert = \App\Presentation\Support\PlacasReportPresenter::isAlertRow($title, $label, (string) $value);
                                                    @endphp
                                                    <tr @if($isAlert) style="background: rgba(220,53,69,0.12);" @endif>
                                                        <th style="width:40%">{{ $label }}</th>
                                                        <td @if($isAlert) style="color:#dc3545;font-weight:700;" @endif>{{ $value }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            @unless($anyContent)
                <div class="alert alert-info">Sin datos para mostrar en las secciones esperadas.</div>
            @endunless

            <div class="text-center mt-4">
                <a href="{{ route('reports.pdf', $consultation->id()) }}" class="btn btn-outline-primary" target="_blank">
                    Descargar PDF
                </a>
            </div>
        </div>

        <div class="card-footer bg-white text-center text-muted" style="font-size:12px">
            Sistema Nacional: [ REPUVE - FGE - Aviso Judicial y Ministerial - AMIS - VINCheck USA/CAN - ANAM - Procedencia Ilícita ]<br>
            © {{ date('Y') }} VINTrack. Reporte generado desde el sitio VINTrack.com.mx
        </div>
    </div>
</div>
@endsection
