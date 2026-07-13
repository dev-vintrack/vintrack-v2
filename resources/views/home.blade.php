@extends('layouts.app')

@section('title', 'Dashboard - VINTRACK')

@section('content')
<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Dashboard</h1>

        @include('home.partials.dashboard_content')
    </div>
</div>
@endsection
