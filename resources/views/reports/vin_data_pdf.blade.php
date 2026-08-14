<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte VINTrack</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #333; }
        .header { border-bottom: 2px solid #0d6efd; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 22px; color: #0d6efd; }
        .header small { color: #666; }
        .meta { text-align: right; font-size: 10px; color: #666; }
        h2 { font-size: 16px; color: #0d6efd; margin-top: 20px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        h3 { font-size: 14px; color: #333; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f8f9fa; }
        .alert-red { background-color: #f8d7da; }
        .alert-yellow { background-color: #fff3cd; }
        .alert-green { background-color: #d1e7dd; }
        .summary-box { background-color: #f8f9fa; padding: 10px; margin: 15px 0; border-left: 4px solid #0d6efd; }
        .summary-box.red { border-left-color: #dc3545; background-color: #fff8f8; }
        .summary-box.yellow { border-left-color: #ffc107; background-color: #fffdf0; }
        .summary-box.green { border-left-color: #198754; background-color: #f0fff4; }
        .traffic-light { display: inline-block; background: #333; border-radius: 24px; padding: 8px 14px; margin: 8px 0 12px 0; }
        .traffic-light span { display: inline-block; width: 18px; height: 18px; border-radius: 50%; margin: 0 3px; background: #555; vertical-align: middle; }
        .traffic-light span.on-red { background: #dc3545; box-shadow: 0 0 6px #dc3545; }
        .traffic-light span.on-yellow { background: #ffc107; box-shadow: 0 0 6px #ffc107; }
        .traffic-light span.on-green { background: #198754; box-shadow: 0 0 6px #198754; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
        .logo { height: 40px; }
        .disclaimer { margin-top: 30px; padding: 15px; background-color: #f9f9f9; border: 1px solid #e0e0e0; font-size: 10px; color: #444; line-height: 1.5; }
        .disclaimer-title { font-size: 12px; font-weight: bold; color: #222; margin-top: 18px; margin-bottom: 8px; }
        .disclaimer-title:first-child { margin-top: 0; }
        .disclaimer-text { margin-bottom: 8px; text-align: justify; }
        .disclaimer-list { margin-top: 8px; margin-bottom: 12px; padding-left: 20px; }
        .disclaimer-list li { margin-bottom: 4px; }
        .disclaimer a { color: #0d6efd; text-decoration: none; }
        @page { margin: 6mm 10mm 38mm 10mm; }
        .pdf-footer { position: fixed; bottom: -38mm; left: -10mm; right: -10mm; height: 38mm; box-sizing: border-box; border-top: 2px solid #0d6efd; padding: 3mm 5mm 2mm 5mm; font-size: 6.5pt; color: #333; background: #fff; }
        .disclaimer-wrapper { page-break-before: always; margin-bottom: 5mm; }
        .disclaimer-vintrack { page-break-inside: avoid; }
        .footer-inner { width: 100%; }
        .footer-top-row td { vertical-align: top; border: none !important; padding: 0 2mm 0 0; }
        .footer-disclaimer-cell { font-style: italic; line-height: 1.3; text-align: justify; }
        .footer-qr-cell { width: 18mm; text-align: center; }
        .footer-qr-cell img { width: 16mm; height: 16mm; }
        .footer-divider { width: 100%; border: none; border-top: 0.5pt solid #ccc; margin: 2mm 0 1mm 0; }
        .footer-meta-row td { border: none !important; font-size: 6.5pt; padding: 0 1mm; vertical-align: middle; }
        .footer-meta-left { width: 50%; }
        .footer-meta-center { width: 50%; text-align: right; }
    </style>
</head>
<body>
    @php
        $info = $vehicleInfo;
        $productName = $info['productName'] ?? $info['productCode'] ?? 'Reporte VINTrack';
        $vin = $info['vin'] ?? $consultation->valor();
        $year = $info['year'] ?? ($summary['year'] ?? null);
        $make = $info['make'] ?? ($summary['make'] ?? null);
        $model = $info['model'] ?? ($summary['model'] ?? null);
        $color = $info['color'] ?? null;
    @endphp

    <div class="pdf-footer">
        <table class="footer-inner" style="border-collapse:collapse;">
            <tr class="footer-top-row">
                <td class="footer-disclaimer-cell">
                    The compilation and assembly of VINTrack vehicle history and title reports requires data from multiple third-party data suppliers. Data supplied by NMVTIS or its reporting sources is supplemented with additional various data sources to provide the information contained herein. VINTrack relies on its data sources for the accuracy and reliability of its information. Therefore, VINTrack assumes no responsibility for errors or omissions in this report. In addition, VINTrack expressly disclaims all warranties, express or implied, including any implied warranties of merchantability or fitness for a particular purpose. &copy; 2026 VINTrack. All Rights Reserved.
                </td>
                <td class="footer-qr-cell">
                    <img src="{{ $qrUrl }}" alt="QR VINTrack">
                </td>
            </tr>
        </table>
        <hr class="footer-divider">
        <table class="footer-inner" style="border-collapse:collapse;">
            <tr class="footer-meta-row">
                <td class="footer-meta-left"><strong>Report ID:</strong> {{ $reportId }}</td>
                <td class="footer-meta-center"><strong>Date Generated:</strong> {{ $generatedAt }}</td>
            </tr>
        </table>
    </div>

    <table class="header">
        <tr>
            <td style="vertical-align:middle;">
                <img src="{{ public_path('images/logo-vintrack.png') }}" alt="VINTrack" style="height:45px; vertical-align:middle; margin-right:10px;">
                <span style="font-size:16px; color:#0d6efd; font-weight:700; vertical-align:middle;">Reporte de historial vehicular</span>
            </td>
            <td class="meta">
                <div>Reporte #{{ $consultation->id() }}</div>
                <div>{{ $consultation->createdAt()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    @php
        $summaryColor = $reportSummary['color'] ?? 'green';
    @endphp

    <h2 style="font-size:24px; color:#0d6efd; margin-bottom:2px;">{{ $year }} {{ $make }} {{ $model }}</h2>
    <p style="margin:2px 0;">VIN: <strong>{{ $vin }}</strong></p>
    @if($color)
        <p style="margin:2px 0;">Color: {{ $color }}</p>
    @endif

    <div class="traffic-light">
        <span class="{{ $summaryColor === 'red' ? 'on-red' : '' }}"></span>
        <span class="{{ $summaryColor === 'yellow' ? 'on-yellow' : '' }}"></span>
        <span class="{{ $summaryColor === 'green' ? 'on-green' : '' }}"></span>
    </div>

    @if(!empty($reportSummary['message']))
        <div class="summary-box {{ $summaryColor }}">
            <strong>Resumen:</strong> {{ $reportSummary['message'] }}
        </div>
    @endif

    @if(!empty($otherInformation))
        <h2>Alertas detectadas</h2>
        <table>
            <thead>
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
                            'red' => 'alert-red',
                            'yellow' => 'alert-yellow',
                            'green' => 'alert-green',
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
    @endif

    @if(!empty($titleInformation))
        <h2>Historial de títulos</h2>
        <table>
            <thead>
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
    @endif

    @if(!empty($odometerInformation))
        <h2>Historial de odómetro</h2>
        <table>
            <thead>
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
    @endif

    @if(!empty($junkSalvageTotalLoss))
        <h2>Junk / Salvage / Total Loss</h2>
        <table>
            <thead>
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
                    @php $rowColor = ($junk['color'] ?? '') === 'red' ? '#f8d7da' : (($junk['color'] ?? '') === 'yellow' ? '#fff3cd' : ''); @endphp
                    <tr style="background-color: {{ $rowColor }};">
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
        <h2>Marcas de título reportadas</h2>
        <ul>
            @foreach($titleBrandReported as $brand)
                <li>{{ $brand['name'] ?? json_encode($brand) }}</li>
            @endforeach
        </ul>
    @endif

    @if(!empty($trimLevels))
        <h2>Especificaciones del vehículo</h2>
        @foreach($trimLevels as $trim => $sections)
            <h3>{{ $trim }}</h3>
            @foreach($sections as $sectionName => $values)
                <h4 style="color:#666; margin-bottom:5px;">{{ $sectionName }}</h4>
                <table>
                    <tbody>
                        @foreach($values as $key => $value)
                            <tr>
                                <td width="40%">{{ $key }}</td>
                                <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        @endforeach
    @endif

    <div class="disclaimer-wrapper">
        @include('reports.partials._vin_data_disclaimer')
    </div>
</body>
</html>
