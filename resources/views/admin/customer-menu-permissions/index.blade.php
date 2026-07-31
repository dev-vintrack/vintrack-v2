@extends('layouts.app')

@section('title', 'Permisos de Menú Cliente')

@section('content')
<div class="row">
    <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Permisos de Menú Cliente</h2>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.customer-menu-permissions.update') }}" method="POST">
        @csrf
        @method('PUT')

        @foreach($permissions as $role => $items)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">{{ $roles[$role] ?? ucfirst($role) }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Etiqueta</th>
                                    <th>Ruta</th>
                                    <th>Icono</th>
                                    <th style="width:110px;">Orden</th>
                                    <th style="width:120px;">Habilitado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td>{{ $item->label }}</td>
                                        <td><code>{{ $item->route_name }}</code></td>
                                        <td>{{ $item->icon ?? '—' }}</td>
                                        <td>
                                            <input type="number" name="permissions[{{ $item->id }}][display_order]"
                                                   class="form-control form-control-sm"
                                                   value="{{ $item->display_order }}" min="0" required>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input type="hidden" name="permissions[{{ $item->id }}][enabled]" value="0">
                                                <input type="checkbox" name="permissions[{{ $item->id }}][enabled]"
                                                       class="form-check-input" value="1"
                                                       {{ $item->enabled ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
    </form>
</div>
</div>
@endsection
