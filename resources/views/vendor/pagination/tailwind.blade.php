@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-center flex-wrap gap-2" style="border-top: 1px solid #e5e7eb; padding-top: 1rem; margin-top: 1rem;">
        {{-- First Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="kt-btn kt-btn-outline kt-btn-sm" aria-disabled="true" style="opacity: .5; cursor: not-allowed;">&laquo; Đầu</span>
        @else
            <a href="{{ $paginator->url(1) }}" class="kt-btn kt-btn-outline kt-btn-sm">&laquo; Đầu</a>
        @endif

        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="kt-btn kt-btn-outline kt-btn-sm" aria-disabled="true" style="opacity: .5; cursor: not-allowed;">Trang trước</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="kt-btn kt-btn-outline kt-btn-sm">Trang trước</a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="kt-btn kt-btn-outline kt-btn-sm" aria-disabled="true" style="cursor: default;">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="kt-btn kt-btn-primary kt-btn-sm">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="kt-btn kt-btn-outline kt-btn-sm" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        <span style="font-size: 13px; padding: 0 4px;">Trang {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="kt-btn kt-btn-outline kt-btn-sm">Trang sau</a>
        @else
            <span class="kt-btn kt-btn-outline kt-btn-sm" aria-disabled="true" style="opacity: .5; cursor: not-allowed;">Trang sau</span>
        @endif

        {{-- Last Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->url($paginator->lastPage()) }}" class="kt-btn kt-btn-outline kt-btn-sm">Cuối &raquo;</a>
        @else
            <span class="kt-btn kt-btn-outline kt-btn-sm" aria-disabled="true" style="opacity: .5; cursor: not-allowed;">Cuối &raquo;</span>
        @endif
    </nav>
@endif
