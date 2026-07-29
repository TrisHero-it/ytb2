@extends('layouts.app')

@section('title', 'Danh sách Collaborators')

@section('content')
    <div class="lg:col-span-3">
        <div class="kt-card kt-card-grid h-full min-w-full">
            <div class="kt-card-header flex items-center justify-between gap-4">
                <h3 class="kt-card-title">Danh sách Collaborators</h3>
                <a href="{{ route('collaborators.create') }}" class="kt-btn kt-btn-primary">Thêm form hướng dẫn</a>
            </div>
            <div class="p-5">
                <table class="kt-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Thao tác</th>
                            <th>Nội dung</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($collaborators as $collaborator)
                            <tr>
                                <td>#{{ $collaborator->id }}</td>
                                <td>
                                    <a href="{{ route('collaborators.edit', $collaborator) }}" class="kt-btn kt-btn-sm">Sửa</a>
                                    <form method="POST" action="{{ route('collaborators.destroy', $collaborator) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-destructive" onclick="return confirm('Xóa collaborator này?')">Xóa</button>
                                    </form>
                                </td>
                                <td class="line-clamp-2">{!! $collaborator->content !!}</td>
                                <td>{{ $collaborator->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Chưa có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
