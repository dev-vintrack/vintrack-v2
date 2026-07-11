<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte VINTrack - Placas.info</title>
    @php
        use App\Presentation\Support\PlacasReportPresenter as P;
        $titles = P::sectionTitles();
        $bannerBg = $banner['bg'] ?? '#198754';
        $summaryColor = 'green';
        if ($bannerBg === '#dc3545' || str_contains($bannerBg, 'dc3545')) $summaryColor = 'red';
        elseif ($bannerBg === '#fd7e14' || str_contains($bannerBg, 'fd7e14')) $summaryColor = 'orange';
        elseif ($bannerBg === '#ffc107' || str_contains($bannerBg, 'ffc107')) $summaryColor = 'yellow';
        elseif ($bannerBg === '#6f42c1' || str_contains($bannerBg, '6f42c1')) $summaryColor = 'purple';
    @endphp
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #333; }
        .header { border-bottom: 2px solid #0d6efd; padding-bottom: 8px; margin-bottom: 16px; width: 100%; border-collapse: collapse; }
        .header small { color: #666; font-size: 10px; }
        .meta { text-align: right; font-size: 10px; color: #666; vertical-align: top; }
        h2 { font-size: 14px; color: #0d6efd; margin: 16px 0 5px 0; border-bottom: 1px solid #ddd; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ddd; padding: 5px 6px; text-align: left; vertical-align: top; }
        th.k { background-color: #f8f9fa; width: 40%; }
        tr.row-alert td, tr.row-alert th { background-color: rgba(220,53,69,0.12); }
        td.cell-alert { color: #dc3545; font-weight: bold; }
        .banner-box { padding: 8px 12px; margin: 10px 0 14px 0; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .traffic-light { display: inline-block; background: #333; border-radius: 24px; padding: 8px 14px; margin: 6px 0 10px 0; }
        .traffic-light span { display: inline-block; width: 16px; height: 16px; border-radius: 50%; margin: 0 3px; background: #555; vertical-align: middle; }
        .traffic-light span.on-red    { background: #dc3545; box-shadow: 0 0 6px #dc3545; }
        .traffic-light span.on-orange { background: #fd7e14; box-shadow: 0 0 6px #fd7e14; }
        .traffic-light span.on-yellow { background: #ffc107; box-shadow: 0 0 6px #ffc107; }
        .traffic-light span.on-purple { background: #6f42c1; box-shadow: 0 0 6px #6f42c1; }
        .traffic-light span.on-green  { background: #198754; box-shadow: 0 0 6px #198754; }
        @page { margin: 6mm 10mm 38mm 10mm; }
        body { margin-bottom: 15mm; }
        .pdf-footer { position: fixed; bottom: -38mm; left: -10mm; right: -10mm; height: 38mm; box-sizing: border-box; border-top: 2px solid #0d6efd; padding: 3mm 5mm 2mm 5mm; font-size: 6.5pt; color: #333; background: #fff; }
        .footer-inner { width: 100%; border-collapse: collapse; }
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

    <div class="pdf-footer">
        <table class="footer-inner">
            <tr class="footer-top-row">
                <td class="footer-disclaimer-cell">
                    Este reporte ha sido generado utilizando datos provenientes del Sistema Nacional de Consulta Vehicular, integrando fuentes como REPUVE, FGE, Aviso Judicial y Ministerial, AMIS, VINCheck USA/CAN, ANAM y Procedencia Ilícita. VINTrack no asume responsabilidad por la exactitud o integridad de los datos proporcionados por terceros. &copy; {{ date('Y') }} VINTrack. Todos los derechos reservados.
                </td>
                <td class="footer-qr-cell">
                    <img src="{{ $qrUrl }}" alt="QR VINTrack">
                </td>
            </tr>
        </table>
        <hr class="footer-divider">
        <table class="footer-inner">
            <tr class="footer-meta-row">
                <td class="footer-meta-left"><strong>ID Reporte:</strong> {{ $reportId }}</td>
                <td class="footer-meta-center"><strong>Fecha de generación:</strong> {{ $consultation->createdAt()->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    <table class="header">
        <tr>
            <td style="vertical-align:middle;">
                <img src="https://dev.vintrack.com.mx/images/logo-vintrack.png" alt="VINTrack" style="height:40px; vertical-align:middle; margin-right:8px;">
                <span style="font-size:16px; color:#0d6efd; font-weight:700; vertical-align:middle;">Reporte de consulta vehicular</span>
            </td>
            <td class="meta">
                <div>Reporte #{{ $consultation->id() }}</div>
                <div>{{ $consultation->createdAt()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    @php
        $repuve = $sections['repuve'] ?? [];
        $placasYear  = $repuve['Año Modelo'] ?? ($repuve['ANIO_MODELO'] ?? ($repuve['AÑO_MODELO'] ?? null));
        $placasMake  = $repuve['Marca'] ?? ($repuve['MARCA'] ?? null);
        $placasModel = $repuve['Modelo'] ?? ($repuve['MODELO'] ?? null);
        $vehicleTitle = trim(implode(' ', array_filter([$placasYear, $placasMake, $placasModel])));
    @endphp

    <p style="font-size:22px; color:#0d6efd; margin:0 0 2px 0;"><strong>{{ $vehicleTitle ?: strtoupper($consultation->valor()) }}</strong></p>
    <p style="margin:0 0 6px 0; font-size:11px; color:#666;">{{ strtoupper($consultation->criterio()) }}: <strong style="color:#333;">{{ strtoupper($consultation->valor()) }}</strong></p>

    <div class="traffic-light">
        <span class="{{ $summaryColor === 'red'    ? 'on-red'    : '' }}"></span>
        <span class="{{ $summaryColor === 'orange' ? 'on-orange' : '' }}"></span>
        <span class="{{ $summaryColor === 'yellow' ? 'on-yellow' : '' }}"></span>
        <span class="{{ $summaryColor === 'purple' ? 'on-purple' : '' }}"></span>
        <span class="{{ $summaryColor === 'green'  ? 'on-green'  : '' }}"></span>
    </div>

    <div class="banner-box" style="background-color: {{ $banner['bg'] }}; color: {{ $banner['color'] }}; border-left: 4px solid {{ $banner['color'] }};">
        {{ $banner['message'] }}
    </div>

    @php $anyContent = false; @endphp
    @foreach($titles as $key => $title)
        @php
            $data = $sections[$key] ?? null;
            $rows = P::flatten($data);
        @endphp
        @if(!empty($rows))
            @php $anyContent = true; @endphp
            <h2>{{ $title }}</h2>
            <table>
                <tbody>
                    @foreach($rows as [$rawKey, $value])
                        @php
                            $label = P::prettyKey($rawKey);
                            $isAlert = P::isAlertRow($title, $label, (string) $value);
                        @endphp
                        <tr class="{{ $isAlert ? 'row-alert' : '' }}">
                            <th class="k">{{ $label }}</th>
                            <td class="{{ $isAlert ? 'cell-alert' : '' }}">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    @unless($anyContent)
        <p>Sin datos para mostrar en las secciones esperadas.</p>
    @endunless

</body>
</html>
