@extends('layouts.app')

@section('title', 'Historial de Vehículos Consultados - VINTRACK')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Historial de Vehículos Consultados</h2><p class="text-muted mb-0">Tus consultas y el estado documental aplicable.</p></div>
    <span class="badge text-bg-light border px-3 py-2">Información privada</span>
</div>
<div class="card border-0 shadow-sm mb-4"><div class="card-body"><div class="row g-3 align-items-end">
    <div class="col-md-3"><label for="date_from" class="form-label">Desde</label><input id="date_from" type="date" class="form-control history-filter"></div>
    <div class="col-md-3"><label for="date_to" class="form-label">Hasta</label><input id="date_to" type="date" class="form-control history-filter"></div>
    <div class="col-md-3"><label for="theft_status" class="form-label">Status robo</label><select id="theft_status" class="form-select history-filter"><option value="">Todos</option><option>POSITIVO</option><option>NEGATIVO</option></select></div>
    <div class="col-md-3"><label for="case_status" class="form-label">Estado del proceso</label><select id="case_status" class="form-select history-filter"><option value="">Todos</option><option value="NO_CASE">NO APLICA</option>@foreach(\App\Domain\NotificationCases\Enums\NotificationCaseStatus::cases() as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></div>
</div></div></div>
<div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive">
<table id="consultationHistory" class="table table-hover align-middle w-100"><thead><tr>
    <th>Fecha</th><th>VIN</th><th>Placas</th><th>Marca</th><th>Modelo</th><th>Año</th><th>Servicio</th><th>Status robo</th><th>Notificado</th><th>Validado</th><th>Fecha límite</th><th>Estado general</th><th>Acciones</th>
</tr></thead></table></div></div></div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    const esc = value => $('<div>').text(value ?? '—').html();
    const table = $('#consultationHistory').DataTable({processing:true,serverSide:true,pageLength:10,lengthMenu:[10,25,50,100],searchDelay:350,order:[[0,'desc']],ajax:{url:@json(route('customer.consultations.data')),data:d=>{$('.history-filter').each(function(){d[this.id]=this.value;});}},columns:[
        {data:'consulted_at'},{data:'vin'},{data:'license_plate'},{data:'make'},{data:'model'},{data:'model_year'},{data:'service'},
        {data:'theft_status',render:v=>`<span class="badge ${v==='POSITIVO'?'text-bg-danger':'text-bg-success'}">${esc(v)}</span>`},
        {data:'notified_status'},{data:'validated_status'},{data:'notification_deadline',defaultContent:'—'},
        {data:'general_status',render:(v,t,row)=>row.case_relation==='OTHER_USER_CASE'?`<span class="badge text-bg-info">${esc(row.case_message)}</span>`:`<span class="badge text-bg-secondary">${esc(v)}</span>`},
        {data:'action',orderable:false,searchable:false,render:a=>a?`<a class="btn btn-sm btn-outline-primary" href="${esc(a.url)}">${esc(a.label)}</a>`:'—'}
    ],createdRow:(row,data)=>{if(data.case_relation==='OWN_CASE'&&data.general_status==='PENDING')row.classList.add('table-danger');else if(data.case_relation==='OTHER_USER_CASE')row.classList.add('table-info');},language:{url:'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'}});
    $('.history-filter').on('change',()=>table.ajax.reload());
});
</script>
@endpush
