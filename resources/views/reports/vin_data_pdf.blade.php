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
        .summary-box { background-color: #f8f9fa; border-left: 4px solid #0d6efd; padding: 10px; margin: 15px 0; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
        .logo { height: 40px; }
    </style>
</head>
<body>
    @php
        $info = $vehicleInfo;
        $productName = $info['productName'] ?? $info['productCode'] ?? 'Reporte VINData';
        $vin = $info['vin'] ?? $consultation->valor();
        $year = $info['year'] ?? ($summary['year'] ?? null);
        $make = $info['make'] ?? ($summary['make'] ?? null);
        $model = $info['model'] ?? ($summary['model'] ?? null);
        $color = $info['color'] ?? null;
    @endphp

    <table class="header">
        <tr>
            <td>
                <h1>VINTrack</h1>
                <small>Reporte de historial vehicular</small>
            </td>
            <td class="meta">
                <div>Reporte #{{ $consultation->id() }}</div>
                <div>{{ $consultation->createdAt()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <h2>{{ $productName }}</h2>
    <p><strong>{{ $year }} {{ $make }} {{ $model }}</strong></p>
    <p>VIN: <strong>{{ $vin }}</strong></p>
    @if($color)
        <p>Color: {{ $color }}</p>
    @endif

    @if(!empty($reportSummary['message']))
        <div class="summary-box">
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
        <div class="summary-box" style="border-left-color: #dc3545;">
            <strong>Marca de Junk / Salvage / Total Loss registrada.</strong>
        </div>
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

    <div class="footer">
        © {{ date('Y') }} VINTrack. Reporte generado con datos de VINData.
    </div>
</body>
</html>
