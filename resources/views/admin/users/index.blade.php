@extends('layouts.app')

@section('title', 'Administración de Usuarios')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Usuarios</h2>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
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
@endsection
