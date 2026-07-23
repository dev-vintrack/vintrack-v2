@extends('layouts.app')

@section('title', 'Tipos de Rol')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Tipos de Rol</h2>
            <a href="{{ route('admin.role-types.create') }}" class="btn btn-primary">Nuevo Tipo de Rol</a>
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
                            <th>Color</th>
                            <th>Admin</th>
                            <th>Cliente</th>
                            <th>Orden</th>
                            <th>Estado</th>
                            <th style="width:160px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roleTypes as $roleType)
                            <tr>
                                <td>{{ $roleType->type_name }}</td>
                                <td>{{ $roleType->description ?? '—' }}</td>
                                <td>
                                    @if($roleType->color)
                                        <span class="badge bg-{{ $roleType->color }}">{{ $roleType->color }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $roleType->is_admin ? 'success' : 'secondary' }}">
                                        {{ $roleType->is_admin ? 'Sí' : 'No' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $roleType->is_customer ? 'success' : 'secondary' }}">
                                        {{ $roleType->is_customer ? 'Sí' : 'No' }}
                                    </span>
                                </td>
                                <td>{{ $roleType->display_order }}</td>
                                <td>
                                    <span class="badge bg-{{ $roleType->status ? 'success' : 'secondary' }}">
                                        {{ $roleType->status ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.role-types.edit', $roleType->id) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <form action="{{ route('admin.role-types.destroy', $roleType->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este tipo de rol?')">
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
