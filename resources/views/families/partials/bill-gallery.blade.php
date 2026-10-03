@php
    $billFiles = [];
    if ($json) {
        $decoded = json_decode($json, true);
        $billFiles = is_array($decoded) ? $decoded : [$json];
    }

    // Bill cho phép cả PDF và Word, render tất cả bằng <img> thì ra ảnh vỡ.
    $isImage = fn (string $file) => in_array(
        strtolower(pathinfo($file, PATHINFO_EXTENSION)),
        ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        true,
    );
@endphp
@if (count($billFiles))
    <div class="flex flex-wrap gap-2 mt-2">
        @foreach ($billFiles as $billFile)
            @if ($isImage($billFile))
                <img src="{{ asset('storage/bills/' . $billFile) }}" alt="Bill" onclick="openBillLightbox(this.src)" style="width: 90px; height: 90px; object-fit: cover; border-radius: 0.375rem; border: 1px solid #e5e7eb; cursor: pointer;" loading="lazy" />
            @else
                <a href="{{ asset('storage/bills/' . $billFile) }}" target="_blank" rel="noopener" title="{{ $billFile }}" style="width: 90px; height: 90px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; border-radius: 0.375rem; border: 1px solid #e5e7eb; background: #f9fafb; color: #4b5563; text-decoration: none; font-size: 11px; text-align: center; padding: 6px;">
                    <i class="ki-filled ki-file" style="font-size: 22px;"></i>
                    <span style="text-transform: uppercase; font-weight: 600;">{{ pathinfo($billFile, PATHINFO_EXTENSION) ?: 'file' }}</span>
                </a>
            @endif
        @endforeach
    </div>
@endif
