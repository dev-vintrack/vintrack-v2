@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Notificaciones</h1>
    <span class="badge text-bg-primary">{{ $notifications->total() }}</span>
</div>

<div class="list-group shadow-sm">
@forelse($notifications as $notification)
    <form method="POST" action="{{ route('customer.notifications.read', $notification) }}">
        @csrf
        <button class="list-group-item list-group-item-action text-start {{ $notification->read_at ? '' : 'list-group-item-info' }}" type="submit">
            <span class="d-flex justify-content-between gap-3">
                <strong>{{ $notification->title }}</strong>
                <small>{{ $notification->created_at->format('d/m/Y H:i') }}</small>
            </span>
            <span class="d-block">{{ $notification->body }}</span>
            @if(!$notification->read_at)<small class="fw-semibold">No leída</small>@endif
        </button>
    </form>
@empty
    <div class="list-group-item text-muted">No tienes notificaciones.</div>
@endforelse
</div>

<div class="mt-3">{{ $notifications->links() }}</div>
@endsection
