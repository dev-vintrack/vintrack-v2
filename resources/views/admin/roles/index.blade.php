@extends('layouts.app')

@section('title', 'Roles')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Roles</h2>
            <div>
                <a href="{{ route('admin.role-types.index') }}" class="btn btn-outline-secondary me-2">Tipos de Rol</a>
                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">Nuevo Rol</a>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Tipo de Rol</th>
                            <th>Home Route</th>
                            <th>Aprobación</th>
                            <th>Orden</th>
                            <th>Estado</th>
                            <th style="width:160px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            <tr>
                                <td>{{ $role->nombre }}</td>
                                <td>{{ $role->descripcion ?? '—' }}</td>
                                <td>
                                    @if($role->roleType)
                                        <span class="badge bg-{{ $role->roleType->color ?? 'secondary' }}">
                                            {{ $role->roleType->type_name }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><code>{{ $role->home_route ?? '—' }}</code></td>
                                <td>
                                    <span class="badge bg-{{ $role->requires_approval ? 'warning text-dark' : 'success' }}">
                                        {{ $role->requires_approval ? 'Sí' : 'No' }}
                                    </span>
                                </td>
                                <td>{{ $role->display_order }}</td>
                                <td>
                                    <span class="badge bg-{{ $role->status ? 'success' : 'secondary' }}">
                                        {{ $role->status ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.roles.edit', $role->id_rol) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <form action="{{ route('admin.roles.destroy', $role->id_rol) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este rol? Los usuarios asignados perderán el rol.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
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
