<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
@page { margin: 18mm 14mm; }
body { color: #17212b; font-family: DejaVu Sans, sans-serif; font-size: 9pt; line-height: 1.35; }
h1 { color: #123c65; font-size: 20pt; margin: 0 0 3mm; } h2 { color: #123c65; border-bottom: 1px solid #b8c7d8; font-size: 12pt; margin: 6mm 0 3mm; padding-bottom: 1mm; }
.muted { color: #5b6773; } .status { background: #198754; color: #fff; font-weight: bold; padding: 2mm 3mm; } .header, .fields, .documents, .image-grid { border-collapse: collapse; width: 100%; }
.header td { vertical-align: top; } .header .status-cell { text-align: right; } .fields td, .documents th, .documents td { border: 1px solid #cbd5df; padding: 2.2mm; vertical-align: top; }
.fields .label { background: #edf2f7; color: #34495e; font-weight: bold; width: 25%; } .documents th { background: #123c65; color: #fff; text-align: left; }
.documents td { word-break: break-word; } .image-section { page-break-before: always; } .image-page { page-break-after: always; } .image-page:last-child { page-break-after: auto; }
.image-grid td { border: 1px solid #cbd5df; height: 65mm; padding: 2.5mm; text-align: center; vertical-align: middle; } .image-grid img { max-height: 56mm; max-width: 160mm; } .image-name { font-size: 7.5pt; margin-top: 2mm; word-break: break-word; }
.footer { color: #5b6773; font-size: 7.5pt; margin-top: 5mm; } .page-number:after { content: counter(page); }
</style>
</head>
<body>
<table class="header"><tr><td><img src="{{ asset('images/logo-vintrack.png') }}" alt="VINTrack" height="50"><h1>Expediente de notificación</h1><div class="muted">VINTrack - copia documental para impresión o archivo digital</div></td><td class="status-cell"><div class="status">VALIDADO</div><strong>{{ $case->case_number }}</strong></td></tr></table>
<h2>Identificación y responsable</h2>
<table class="fields"><tr><td class="label">VIN</td><td>{{ $case->vin ?: 'NO DISPONIBLE' }}</td><td class="label">Consulta origen</td><td>#{{ $case->consultation_id }}</td></tr><tr><td class="label">Criterio</td><td>{{ strtoupper($case->consultation?->criterio ?? '—') }}</td><td class="label">Responsable</td><td>{{ $case->owner?->name ?? '—' }}<br>{{ $case->owner?->email ?? '' }}</td></tr><tr><td class="label">Apertura</td><td>{{ $case->opened_at?->format('d/m/Y H:i:s') ?? '—' }}</td><td class="label">Validación</td><td>{{ $case->validated_at?->format('d/m/Y H:i:s') ?? '—' }}</td></tr></table>
<h2>Datos capturados</h2>
@php($fields = [['recovered_at','Fecha/hora de recuperación'],['recovery_place','Lugar de recuperación'],['country','País'],['state','Estado'],['municipality','Municipio'],['neighborhood','Colonia'],['postal_code','Código postal'],['street','Calle'],['street_number','Número'],['license_plate','Placas'],['make','Marca'],['model','Modelo'],['model_year','Año'],['engine_number','Motor'],['color','Color'],['origin','Procedencia'],['authority','Autoridad'],['iph','IPH'],['nuc','NUC'],['investigation_file','Carpeta de investigación'],['safekeeping','Resguardo'],['inventory','Inventario'],['notes','Notas']])
<table class="fields">@foreach(array_chunk($fields, 2) as $row)<tr>@foreach($row as [$field, $label])<td class="label">{{ $label }}</td><td>{{ $field === 'recovered_at' ? ($case->recovered_at?->format('d/m/Y H:i:s') ?? '—') : ($case->{$field} ?: '—') }}</td>@endforeach</tr>@endforeach</table>
<h2>Archivos de evidencia documental</h2>
<table class="documents"><thead><tr><th>Archivo</th><th>Tipo</th><th>Tamaño</th><th>Fecha de carga</th><th>Estado</th></tr></thead><tbody>@forelse($case->documents as $document)<tr><td>{{ $document->original_name }}</td><td>{{ strtoupper($document->extension) }}</td><td>{{ number_format($document->size_bytes / 1024, 1) }} KB</td><td>{{ $document->created_at?->format('d/m/Y H:i:s') ?? '—' }}</td><td>{{ $document->malware_scan_status->value }}</td></tr>@empty<tr><td colspan="5">Sin evidencias documentales activas.</td></tr>@endforelse</tbody></table>
@if($images)
<section class="image-section"><h2>Visualización de evidencias de imagen</h2><p class="muted">Se incluyen únicamente imágenes disponibles con análisis CLEAN.</p>
@foreach(array_chunk($images, 3) as $page)
<div class="image-page"><table class="image-grid">
@foreach($page as $image)
<tr><td><img src="{{ $image['src'] }}" alt="{{ $image['name'] }}"><div class="image-name">{{ $image['name'] }}</div></td></tr>
@endforeach
@for($i = count($page); $i < 3; $i++)
<tr><td></td></tr>
@endfor
</table></div>
@endforeach
</section>
@endif
<div class="footer">Expediente {{ $case->case_number }} - página <span class="page-number"></span></div>
</body>
</html>
