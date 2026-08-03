@php
    $billFiles = [];
    if ($json) {
        $decoded = json_decode($json, true);
        $billFiles = is_array($decoded) ? $decoded : [$json];
    }
@endphp
@if (count($billFiles))
    <div class="flex flex-wrap gap-2 mt-2">
        @foreach ($billFiles as $billFile)
            <img src="{{ asset('storage/bills/' . $billFile) }}" alt="Bill" onclick="openBillLightbox(this.src)" style="width: 90px; height: 90px; object-fit: cover; border-radius: 0.375rem; border: 1px solid #e5e7eb; cursor: pointer;" loading="lazy" />
        @endforeach
    </div>
@endif
