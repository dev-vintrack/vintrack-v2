@extends('layouts.app')

@section('title', 'Historial Global de Vehículos Consultados')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/vintrack/datatables-1.13.6.bootstrap5.min.css') }}">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="mb-1">Historial Global de Vehículos Consultados</h2><p class="text-muted mb-0">Universo autorizado de consultas y su proceso documental aplicable.</p></div></div>
<div class="card border-0 shadow-sm mb-4"><div class="card-body"><div class="row g-3 align-items-end">
    <div class="col-md-3"><label class="form-label" for="user_id">Cliente</label><select id="user_id" class="form-select history-filter"><option value="">Todos</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label" for="date_from">Desde</label><input id="date_from" type="date" class="form-control history-filter"></div>
    <div class="col-md-2"><label class="form-label" for="date_to">Hasta</label><input id="date_to" type="date" class="form-control history-filter"></div>
    <div class="col-md-2"><label class="form-label" for="vin">VIN</label><input id="vin" maxlength="32" class="form-control history-filter"></div>
    <div class="col-md-2"><label class="form-label" for="plate">Placas</label><input id="plate" maxlength="32" class="form-control history-filter"></div>
    <div class="col-md-2"><label class="form-label" for="theft_status">Status robo</label><select id="theft_status" class="form-select history-filter"><option value="">Todos</option><option>POSITIVO</option><option>NEGATIVO</option></select></div>
    <div class="col-md-3"><label class="form-label" for="case_status">Estado del proceso</label><select id="case_status" class="form-select history-filter"><option value="">Todos</option><option value="NO_CASE">NO APLICA</option>@foreach(\App\Domain\NotificationCases\Enums\NotificationCaseStatus::cases() as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></div>
</div></div></div>
<div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive"><table id="globalHistory" class="table table-hover align-middle w-100"><thead><tr>
<th>Fecha</th><th>Cliente</th><th>VIN</th><th>Placas</th><th>Marca</th><th>Modelo</th><th>Año</th><th>Servicio</th><th>Status robo</th><th>Notificado</th><th>Validado</th><th>Fecha límite</th><th>Estado general</th><th>Acciones</th>
</tr></thead></table></div></div></div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/vintrack/jquery-3.7.1.min.js') }}"></script><script src="{{ asset('vendor/vintrack/datatables-1.13.6.min.js') }}"></script><script src="{{ asset('vendor/vintrack/datatables-1.13.6.bootstrap5.min.js') }}"></script>
<script>
$(function(){const esc=v=>$('<div>').text(v??'—').html();const table=$('#globalHistory').DataTable({processing:true,serverSide:true,pageLength:10,lengthMenu:[10,25,50,100],searchDelay:350,order:[[0,'desc']],ajax:{url:@json(route('admin.consultations.data')),data:d=>{$('.history-filter').each(function(){d[this.id]=this.value;});}},columns:[
{data:'consulted_at'},{data:'user',render:u=>u?`${esc(u.name)}<small class="d-block text-muted">${esc(u.email)}</small>`:'—'},{data:'vin'},{data:'license_plate'},{data:'make'},{data:'model'},{data:'model_year'},{data:'service'},{data:'theft_status'},{data:'notified_status'},{data:'validated_status'},{data:'notification_deadline',defaultContent:'—'},{data:'general_status'},{data:'action',orderable:false,searchable:false,render:a=>a?`<a class="btn btn-sm btn-outline-primary" href="${esc(a.url)}">${esc(a.label)}</a>`:'—'}],language:{url:'{{ asset('vendor/vintrack/datatables-es-ES-1.13.6.json') }}'}});$('.history-filter').on('change input',()=>table.ajax.reload());});
</script>
@endpush
