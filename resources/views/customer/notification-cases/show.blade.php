@extends('layouts.app')
@section('title', 'Expediente '.$case->case_number)

@section('content')
@php
$statusClass = match($case->status->value) { 'PENDING','REJECTED' => 'warning', 'VALIDATED' => 'success', 'CLOSED_NO_FOLLOW_UP' => 'dark', default => 'info' };
$fields = [
 ['recovery_place','Lugar de recuperación',true], ['country','País',true], ['state','Estado',true], ['municipality','Alcaldía/Municipio',true],
 ['neighborhood','Colonia',false], ['postal_code','Código Postal',false], ['street','Calle',false], ['street_number','Número',false],
 ['license_plate','Placas',true], ['make','Marca',true], ['model','Modelo',false], ['model_year','Año',true], ['engine_number','Número de motor',false],
 ['color','Color',false], ['origin','Procedencia',true], ['authority','Autoridad',true], ['iph','IPH',false], ['nuc','NUC',false],
 ['investigation_file','Carpeta de Investigación',true], ['safekeeping','Resguardo',true], ['inventory','Inventario',false]
];
@endphp
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><div><a href="{{ route('customer.notification-cases.index') }}" class="text-decoration-none">← Volver</a><h1 class="h3 mt-2 mb-0">{{ $case->case_number }}</h1></div><span class="badge text-bg-{{ $statusClass }} align-self-center fs-6">{{ $case->status->label() }}</span></div>
<div class="alert alert-{{ $statusClass }}" role="status"><strong>Fecha límite:</strong> {{ $case->notification_deadline_at->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}. {{ $editable ? 'Puede guardar cambios y enviar el expediente.' : 'El expediente está en modo solo lectura.' }}</div>
@if($case->status->value === 'REJECTED' && $latestRejection)
<div class="alert alert-danger" role="alert"><h2 class="h5">Corrección requerida</h2><p class="mb-1"><strong>Motivo:</strong> {{ $latestRejection->reason }}</p><small>Rechazado el {{ $latestRejection->occurred_at->format('d/m/Y H:i:s') }}. Corrija los datos o evidencias indicados y vuelva a enviar el mismo expediente.</small></div>
@endif

<form method="POST" action="{{ route('customer.notification-cases.update', $case) }}" class="card shadow-sm mb-4">
@csrf @method('PUT')
<input type="hidden" name="lock_version" value="{{ $case->lock_version }}"><input type="hidden" name="request_key" value="{{ (string) Str::uuid() }}">
<div class="card-body"><h2 class="h5">Identificación y captura</h2><div class="row g-3">
<div class="col-md-6"><label class="form-label" for="case_number">Folio</label><input id="case_number" class="form-control" value="{{ $case->case_number }}" readonly></div>
<div class="col-md-6"><label class="form-label" for="vin">VIN</label><input id="vin" class="form-control" value="{{ $case->vin }}" readonly></div>
<div class="col-md-6"><label class="form-label" for="recovered_at">Fecha y hora de recuperación <span aria-hidden="true">*</span></label><input type="datetime-local" id="recovered_at" name="recovered_at" class="form-control" value="{{ old('recovered_at', $case->recovered_at?->format('Y-m-d\\TH:i')) }}" max="{{ now(config('app.timezone'))->format('Y-m-d\\TH:i') }}" {{ $editable ? '' : 'disabled' }}></div>
@foreach($fields as [$name,$label,$required])
<div class="col-md-6"><label class="form-label" for="{{ $name }}">{{ $label }} @if($required)<span aria-hidden="true">*</span>@endif</label><input id="{{ $name }}" name="{{ $name }}" class="form-control" value="{{ old($name, $case->{$name}) }}" {{ $editable ? '' : 'disabled' }}></div>
@endforeach
<div class="col-12"><p class="form-text mb-1">Capture al menos IPH o NUC.</p><label class="form-label" for="notes">Notas</label><textarea id="notes" name="notes" class="form-control" rows="3" {{ $editable ? '' : 'disabled' }}>{{ old('notes', $case->notes) }}</textarea></div>
</div></div>
@if($editable)<div class="card-footer text-end"><button class="btn btn-outline-primary">Guardar borrador</button></div>@endif
</form>

<div class="card shadow-sm mb-4"><div class="card-body"><h2 class="h5">Evidencias</h2><p>Formatos permitidos: PDF, JPG, JPEG y PNG. Tamaño máximo: 3 MB por archivo. Máximo: 8 archivos activos por expediente.</p><p><strong>{{ count($documents) }} de 8 archivos</strong></p>
@if($editable && count($documents) < 8)<form id="evidence-upload" enctype="multipart/form-data" class="mb-3"><label for="document" class="form-label">Archivo</label><div class="input-group"><input type="file" id="document" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required><button class="btn btn-primary">Cargar</button></div></form>@endif
<ul class="list-group" id="evidence-list">@forelse($documents as $document)<li class="list-group-item d-flex justify-content-between align-items-center"><span>{{ $document['original_name'] }} ({{ number_format($document['size_bytes']/1024, 1) }} KB)</span><span><a class="btn btn-sm btn-outline-primary" href="{{ route('notification-cases.documents.show', [$case, $document['id']]) }}">Descargar</a>@if($editable)<button type="button" class="btn btn-sm btn-outline-danger remove-document" data-id="{{ $document['id'] }}">Remover</button>@endif</span></li>@empty<li class="list-group-item text-muted">Sin evidencias.</li>@endforelse</ul>
</div></div>
@if($canSubmit)<form method="POST" action="{{ route('customer.notification-cases.submit', $case) }}" onsubmit="return confirm('Después del envío el expediente dejará de ser editable. ¿Desea continuar?')" class="text-end">@csrf<input type="hidden" name="lock_version" value="{{ $case->lock_version }}"><input type="hidden" name="request_key" value="{{ (string) Str::uuid() }}"><input type="hidden" name="confirm_submission" value="1"><button class="btn btn-success">Enviar expediente definitivamente</button></form>@endif
@endsection

@push('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const upload = document.getElementById('evidence-upload');
if (upload) upload.addEventListener('submit', async event => { event.preventDefault(); const response = await fetch(@json(route('notification-cases.documents.store', $case)), {method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Idempotency-Key':crypto.randomUUID(),'Accept':'application/json'}, body:new FormData(upload)}); const body=await response.json(); if(!response.ok){alert(body.message || 'No fue posible cargar la evidencia.'); return;} location.reload(); });
document.querySelectorAll('.remove-document').forEach(button => button.addEventListener('click', async () => { if(!confirm('¿Remover esta evidencia?')) return; const url=@json(route('notification-cases.documents.index', $case))+'/'+button.dataset.id; const response=await fetch(url,{method:'DELETE',headers:{'X-CSRF-TOKEN':csrf,'Idempotency-Key':crypto.randomUUID(),'Accept':'application/json'}}); const body=await response.json(); if(!response.ok){alert(body.message || 'No fue posible remover la evidencia.'); return;} location.reload(); }));
</script>
@endpush
