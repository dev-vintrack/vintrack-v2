@extends('layouts.app')

@section('title', 'Historial de Vehículos Consultados - VINTRACK')
@include('partials.datatables-export-assets')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Historial de Vehículos Consultados</h2><p class="text-muted mb-0">Tus consultas y el estado documental aplicable.</p></div>
    <span class="badge text-bg-light border px-3 py-2">Información privada</span>
</div>
<div class="row g-3 mb-4"><div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Resultados</small><h3 id="history-total" class="mb-0">0</h3></div></div></div><div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">En esta página</small><h3 id="history-page" class="mb-0">0</h3></div></div></div><div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Robo positivo</small><h3 id="history-theft" class="mb-0 text-danger">0</h3></div></div></div><div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted">Validados</small><h3 id="history-validated" class="mb-0 text-success">0</h3></div></div></div></div>
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
<script>
$(function () {
    const esc = value => $('<div>').text(value ?? '—').html();
    const badge = value => `<span class="badge text-bg-${value==='VALIDATED'?'success':(value==='PENDING'?'danger':'warning')}">${esc(value)}</span>`;
    const table = $('#consultationHistory').DataTable({processing:true,serverSide:true,pageLength:10,lengthMenu:[10,25,50,100],searchDelay:350,order:[[0,'desc']],dom:"<'vintrack-table-toolbar row g-3 align-items-center px-3 pt-3'<'col-lg-6'B><'col-lg-6'f>>rt<'row px-3 py-3'<'col-md-5'i><'col-md-7 d-flex justify-content-md-end'p>>",buttons:[{extend:'excelHtml5',text:'<i class="bi bi-file-earmark-excel me-1"></i> Excel',className:'buttons-excel',title:'Historial de Vehículos Consultados',filename:'historial-vehiculos',exportOptions:{columns:[0,1,2,3,4,5,6,7,8,9,10,11]}},{extend:'pdfHtml5',text:'<i class="bi bi-file-earmark-pdf me-1"></i> PDF',className:'buttons-pdf',title:'Historial de Vehículos Consultados',filename:'historial-vehiculos',orientation:'landscape',exportOptions:{columns:[0,1,2,3,4,5,6,7,8,9,10,11]}}],ajax:{url:@json(route('customer.consultations.data')),data:d=>{$('.history-filter').each(function(){d[this.id]=this.value;});}},columns:[
        {data:'consulted_at'},{data:'vin'},{data:'license_plate'},{data:'make'},{data:'model'},{data:'model_year'},{data:'service'},
        {data:'theft_status',render:v=>`<span class="badge ${v==='POSITIVO'?'text-bg-danger':'text-bg-success'}">${esc(v)}</span>`},
        {data:'notified_status'},{data:'validated_status'},{data:'notification_deadline',defaultContent:'—'},
        {data:'general_status',render:(v,t,row)=>badge(row.case_relation==='OTHER_USER_CASE'?row.case_message:v)},
        {data:'actions',orderable:false,searchable:false,render:actions=>actions?.length?actions.map(action=>`<a class="btn btn-sm btn-outline-primary me-1" href="${esc(action.url)}">${action.type==='report'?'&#128196; ':''}${esc(action.label)}</a>`).join(''):'—'}
    ],createdRow:(row,data)=>{if(data.case_relation==='OWN_CASE'&&data.general_status==='PENDING')row.classList.add('table-danger');else if(data.case_relation==='OTHER_USER_CASE')row.classList.add('table-info');},language:{url:'{{ asset('vendor/vintrack/datatables-es-ES-1.13.6.json') }}',search:'Búsqueda rápida:'}});
    table.on('xhr',(_,__,json)=>{const rows=json.data||[];$('#history-total').text(json.recordsFiltered||0);$('#history-page').text(rows.length);$('#history-theft').text(rows.filter(row=>row.theft_status==='POSITIVO').length);$('#history-validated').text(rows.filter(row=>row.general_status==='VALIDATED').length);});
    $('.history-filter').on('change',()=>table.ajax.reload());
});
</script>
@endpush
