@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="lg:col-span-3">
        <div class="kt-card kt-card-grid h-full min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Foundation OK</h3>
            </div>
            <div class="p-5">
                <p>Xin chào, {{ auth()->user()->name }}.</p>
                <p>Laravel foundation (routing, auth, database, layout) đã sẵn sàng cho các phase tiếp theo.</p>
            </div>
        </div>
    </div>
@endsection
