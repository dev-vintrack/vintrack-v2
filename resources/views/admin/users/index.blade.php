@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endpush

@section('title', 'Administración de Usuarios')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Usuarios</h2>
        </div>

        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <h4 class="mb-3">Resumen</h4>
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card shadow border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Total de Usuarios</h6>
                                <h2 id="kpi-total">{{ number_format($kpis['total'], 0) }}</h2>
                            </div>
                            <i class="bi bi-people fs-1 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card shadow border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Usuarios Activos</h6>
                                <h2 id="kpi-active">{{ number_format($kpis['active'], 0) }}</h2>
                            </div>
                            <i class="bi bi-person-check fs-1 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card shadow border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Usuarios Pendientes</h6>
                                <h2 id="kpi-pending">{{ number_format($kpis['pending'], 0) }}</h2>
                            </div>
                            <i class="bi bi-person-clock fs-1 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card shadow border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted">Administradores</h6>
                                <h2 id="kpi-admins">{{ number_format($kpis['admins'], 0) }}</h2>
                            </div>
                            <i class="bi bi-person-gear fs-1 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tablaUsuarios" class="table table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Activo</th>
                                <th style="min-width:180px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->telefono ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ \App\Presentation\Support\RoleHelper::isAdmin($user) ? 'danger' : 'info' }}">
                                        {{ $roles[$user->rol] ?? $user->rol }}
                                    </span>
                                </td>
                                <td>
                                    @if($user->status === 'pending')
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @else
                                        <span class="badge bg-success">Activo</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $user->activo ? 'success' : 'secondary' }}">
                                        {{ $user->activo ? 'Sí' : 'No' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <a href="{{ route('admin.consultations.index', ['user_id' => $user->id]) }}" class="btn btn-sm btn-outline-info">Consultas</a>
                                    <a href="{{ route('admin.wallets.movements', ['user_id' => $user->id]) }}" class="btn btn-sm btn-outline-secondary">Movimientos</a>
                                    <a href="{{ route('admin.wallets.index', ['user_id' => $user->id]) }}" class="btn btn-sm btn-outline-success">Créditos</a>
                                    @if($user->status === 'pending' && \App\Presentation\Support\RoleHelper::requiresApproval($user->rol))
                                        <form action="{{ route('admin.users.approve', $user->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button class="btn btn-sm btn-success">Aprobar</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('¿Eliminar este usuario?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#tablaUsuarios').DataTable({
                responsive: true,
                pageLength: 10,
                ordering: true,
                searching: true,
                lengthMenu: [10, 25, 50, 100],
                order: [[0, 'desc']],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                dom: 'Blfrtip',
                buttons: [
                    { extend: 'excelHtml5', text: 'Excel' },
                    { extend: 'pdfHtml5', text: 'PDF', orientation: 'landscape', pageSize: 'A3' },
                    { extend: 'copyHtml5', text: 'Copiar' },
                    { extend: 'print', text: 'Imprimir' }
                ],
                columnDefs: [
                    { targets: -1, orderable: false, searchable: false }
                ]
            });
        });
    </script>
@endpush
