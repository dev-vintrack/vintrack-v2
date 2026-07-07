@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Agregar Créditos a Usuario</h1>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.credits.purchase.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="user_id" class="form-label">Usuario</label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="provider_id" class="form-label">Proveedor</label>
                    <select name="provider_id" id="provider_id" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        @foreach ($providers as $provider)
                            <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">Cantidad de créditos</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="reason" class="form-label">Motivo</label>
                    <input type="text" name="reason" id="reason" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Agregar créditos</button>
            </form>
        </div>
    </div>
</div>
@endsection
