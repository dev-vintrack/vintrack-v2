@extends('layouts.app')
@section('title', 'Expediente '.$case->case_number)
@section('content')
@php
$fields = [['recovery_place','Lugar de recuperación'],['country','País'],['state','Estado'],['municipality','Municipio'],['neighborhood','Colonia'],['postal_code','Código postal'],['street','Calle'],['street_number','Número'],['license_plate','Placas'],['make','Marca'],['model','Modelo'],['model_year','Año'],['engine_number','Motor'],['color','Color'],['origin','Procedencia'],['authority','Autoridad'],['iph','IPH'],['nuc','NUC'],['investigation_file','Carpeta de investigación'],['safekeeping','Resguardo'],['inventory','Inventario']];
$rejections = $case->events->where('event_type', 'CASE_REJECTED')->sortByDesc('occurred_at');
@endphp
<div class="d-flex justify-content-between mb-3"><div><a href="{{ route('admin.notification-cases.index') }}">← Volver</a><h1 class="h3 mt-2">{{ $case->case_number }}</h1></div><div class="d-flex align-items-center gap-2">@if($case->status->value === 'VALIDATED')<button class="btn btn-outline-primary" type="button" id="exportPdfButton" data-url="{{ route('admin.notification-cases.export-pdf', $case) }}"><span class="button-label">Exportar expediente PDF</span><span class="button-loading d-none"><span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Se está generando el archivo PDF...</span></button>@endif<span class="badge text-bg-secondary fs-6">{{ $case->status->label() }}</span></div></div>
<div class="row g-3 mb-4"><div class="col-md-4"><div class="card h-100"><div class="card-body"><h2 class="h6">Identificación inmutable</h2><p><strong>VIN:</strong> {{ $case->vin ?: 'NO DISPONIBLE' }}<br><strong>Consulta:</strong> #{{ $case->consultation_id }}<br><strong>Criterio:</strong> {{ strtoupper($case->consultation?->criterio ?? '') }}</p></div></div></div><div class="col-md-4"><div class="card h-100"><div class="card-body"><h2 class="h6">Responsable</h2><p>{{ $case->owner?->name }}<br>{{ $case->owner?->email }}</p></div></div></div><div class="col-md-4"><div class="card h-100"><div class="card-body"><h2 class="h6">Fechas</h2><p>Apertura: {{ $case->opened_at?->format('d/m/Y H:i:s') }}<br>Límite: {{ $case->notification_deadline_at->format('d/m/Y H:i:s') }}<br>Envío: {{ $case->last_submitted_at?->format('d/m/Y H:i:s') ?: '—' }}</p></div></div></div></div>
<form method="POST" action="{{ route('admin.notification-cases.update', $case) }}" class="card shadow-sm mb-4">@csrf @method('PUT')<input type="hidden" name="lock_version" value="{{ $case->lock_version }}"><input type="hidden" name="request_key" value="{{ (string) Str::uuid() }}"><div class="card-body"><h2 class="h5">Datos del expediente</h2><div class="row g-3"><div class="col-md-6"><label class="form-label" for="recovered_at">Fecha/hora de recuperación</label><input type="datetime-local" class="form-control" id="recovered_at" name="recovered_at" value="{{ old('recovered_at', $case->recovered_at?->format('Y-m-d\\TH:i')) }}" max="{{ now(config('app.timezone'))->format('Y-m-d\\TH:i') }}" @disabled(!$editable)></div>@foreach($fields as [$name,$label])<div class="col-md-6"><label class="form-label" for="{{ $name }}">{{ $label }}</label><input class="form-control" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $case->{$name}) }}" @disabled(!$editable)></div>@endforeach<div class="col-12"><label class="form-label" for="notes">Notas</label><textarea class="form-control" id="notes" name="notes" rows="3" @disabled(!$editable)>{{ old('notes', $case->notes) }}</textarea></div></div></div>@if($editable)<div class="card-footer text-end"><button class="btn btn-primary">Guardar correcciones auditadas</button></div>@endif</form>
<div class="card shadow-sm mb-4"><div class="card-body"><h2 class="h5">Evidencias (solo revisión)</h2><ul class="list-group">@forelse($documents as $document)<li class="list-group-item d-flex justify-content-between"><span>{{ $document['original_name'] }} ({{ number_format($document['size_bytes']/1024, 1) }} KB) <span class="badge text-bg-{{ $document['malware_scan_status'] === 'CLEAN' ? 'success' : ($document['malware_scan_status'] === 'INFECTED' ? 'danger' : 'warning') }}">{{ $document['malware_scan_status'] }}</span></span>@if($document['download_available'])<a class="btn btn-sm btn-outline-primary" href="{{ route('notification-cases.documents.show', [$case, $document['id']]) }}">Descargar</a>@endif</li>@empty<li class="list-group-item text-muted">Sin evidencias activas.</li>@endforelse</ul></div></div>
<div class="card shadow-sm mb-4"><div class="card-body"><h2 class="h5">Historial funcional</h2>@if($rejections->isNotEmpty())<div class="alert alert-warning"><strong>Motivos de rechazo</strong><ul class="mb-0">@foreach($rejections as $event)<li>{{ $event->occurred_at->format('d/m/Y H:i:s') }} — {{ $event->reason }}</li>@endforeach</ul></div>@endif<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Fecha</th><th>Acción</th><th>Actor</th><th>Transición/cambio</th></tr></thead><tbody>@foreach($case->events->sortByDesc('occurred_at') as $event)<tr><td>{{ $event->occurred_at->format('d/m/Y H:i:s') }}</td><td>{{ $event->event_type }}</td><td>{{ $event->actor?->name ?? $event->actor_type }}</td><td>@if($event->field_name){{ $event->field_name }}: {{ $event->old_value ?? '∅' }} → {{ $event->new_value ?? '∅' }}@else{{ $event->from_status }} @if($event->to_status)→ {{ $event->to_status }}@endif @endif</td></tr>@endforeach</tbody></table></div></div></div>
<div class="card border-primary"><div class="card-body"><h2 class="h5">Acciones administrativas</h2>@if($case->status->value === 'SUBMITTED')<form method="POST" action="{{ route('admin.notification-cases.start-review', $case) }}">@csrf<input type="hidden" name="lock_version" value="{{ $case->lock_version }}"><input type="hidden" name="request_key" value="{{ (string) Str::uuid() }}"><button class="btn btn-primary">Iniciar revisión</button></form>@elseif($case->status->value === 'UNDER_REVIEW')<div class="d-flex flex-wrap gap-3"><form method="POST" action="{{ route('admin.notification-cases.validate', $case) }}" onsubmit="return confirm('¿Validar definitivamente?')">@csrf<input type="hidden" name="lock_version" value="{{ $case->lock_version }}"><input type="hidden" name="request_key" value="{{ (string) Str::uuid() }}"><button class="btn btn-success">Validar</button></form><form method="POST" action="{{ route('admin.notification-cases.reject', $case) }}" onsubmit="return confirm('¿Rechazar y devolver al Cliente?')" class="flex-grow-1">@csrf<input type="hidden" name="lock_version" value="{{ $case->lock_version }}"><input type="hidden" name="request_key" value="{{ (string) Str::uuid() }}"><label class="form-label" for="reason">Motivo obligatorio</label><div class="input-group"><textarea class="form-control" id="reason" name="reason" maxlength="2000" required></textarea><button class="btn btn-danger">Rechazar</button></div></form></div>@else<p class="text-muted mb-0">No hay transiciones administrativas disponibles.</p>@endif</div></div>
@endsection

@push('scripts')
<script>
(() => {
    const button = document.getElementById('exportPdfButton');
    if (!button) return;

    const label = button.querySelector('.button-label');
    const loading = button.querySelector('.button-loading');
    button.addEventListener('click', async () => {
        button.disabled = true;
        label.classList.add('d-none');
        loading.classList.remove('d-none');
        try {
            const response = await fetch(button.dataset.url, { credentials: 'same-origin' });
            if (!response.ok || !response.headers.get('content-type')?.includes('application/pdf')) {
                throw new Error('No fue posible generar el archivo PDF.');
            }
            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const download = document.createElement('a');
            download.href = url;
            download.download = response.headers.get('content-disposition')?.match(/filename="?([^";]+)"?/)?.[1] || 'expediente.pdf';
            document.body.appendChild(download);
            download.click();
            download.remove();
            URL.revokeObjectURL(url);
        } catch (error) {
            window.alert(error.message);
        } finally {
            button.disabled = false;
            label.classList.remove('d-none');
            loading.classList.add('d-none');
        }
    });
})();
</script>
@endpush
