@extends('layouts.app')

@section('title', 'Thêm form hướng dẫn')

@section('content')
    <div class="lg:col-span-3">
        <div class="kt-card kt-card-grid h-full min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Thêm form hướng dẫn</h3>
            </div>
            <div class="kt-card-body">
                @if ($errors->any())
                    <div class="mb-4 text-destructive">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('collaborators.store') }}" id="add_collaborator_form" class="grid gap-5">
                    @csrf

                    <div class="flex flex-col gap-2">
                        <label class="kt-form-label text-mono font-semibold text-sm">
                            Nội dung <span class="text-destructive">*</span>
                        </label>
                        <textarea name="content" id="content_editor" style="visibility: hidden; position: absolute;">{{ old('content') }}</textarea>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="kt-form-label text-mono font-semibold text-sm">
                            Trạng thái <span class="text-destructive">*</span>
                        </label>
                        <select name="status" class="kt-select">
                            <option value="active" @selected(old('status', 'active') === 'active')>Hoạt động</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Không hoạt động</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2.5 justify-end">
                        <a href="{{ route('collaborators.index') }}" class="kt-btn kt-btn-outline">Hủy</a>
                        <button type="submit" class="kt-btn kt-btn-primary">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#content_editor'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'blockQuote', 'insertTable', '|', 'undo', 'redo']
            })
            .then(function (editor) {
                window.editor = editor;
                document.getElementById('add_collaborator_form').addEventListener('submit', function () {
                    document.getElementById('content_editor').value = editor.getData();
                });
            })
            .catch(function (error) {
                console.error('Error initializing CKEditor:', error);
            });
    </script>
@endpush
