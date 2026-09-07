@extends('layouts.app')

@section('title', 'Sửa family')

@section('content')
<div class="lg:col-span-3">
    <div class="kt-card kt-card-grid h-full min-w-full">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Sửa family</h3>
        </div>
        <div class="p-5">
            @if ($errors->any())
            <div class="mb-4 text-destructive">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('families.update', $family) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid gap-4 mb-5 sm:grid-cols-2">
                    <label>Email chủ family
                        <input type="text" name="email" value="{{ old('email', $family->email) }}" placeholder="Email chủ family" class="kt-input" />
                    </label>
                    <label>Họ và tên
                        <input type="text" name="user" value="{{ old('user', $family->user) }}" placeholder="Họ và tên" class="kt-input" />
                    </label>
                    <label>Số điện thoại
                        <input type="text" name="number_phone" value="{{ old('number_phone', $family->number_phone) }}" placeholder="Số điện thoại" class="kt-input" />
                    </label>
                    <label>Số tài khoản
                        <input type="text" name="number_bank" value="{{ old('number_bank', $family->number_bank) }}" placeholder="Số tài khoản" class="kt-input" />
                    </label>
                    <label>Ngân hàng
                        @include('families.partials.bank-select', ['currentBank' => old('name_bank', $family->name_bank)])
                    </label>
                    <label>Ngày đến hạn thanh toán
                        <input type="date" name="next_payment_at" value="{{ old('next_payment_at', $family->next_payment_at?->format('Y-m-d')) }}" class="kt-input" />
                    </label>
                    <label>Affiliate bởi
                        <input type="text" name="afiilicate_by" value="{{ old('afiilicate_by', $family->afiilicate_by) }}" placeholder="Affiliate bởi" class="kt-input" />
                    </label>
                    <label class="sm:col-span-2">Ghi chú
                        <textarea name="note" placeholder="Ghi chú" class="kt-input">{{ old('note', $family->note) }}</textarea>
                    </label>
                    <label class="sm:col-span-2">Bill gốc
                        <input type="file" name="bill_of_master[]" multiple class="kt-input" />
                        @include('families.partials.bill-gallery', ['json' => $family->bill_of_master])
                    </label>
                    <div class="sm:col-span-2">
                        <div style="font-size: 13px; font-weight: 500; margin-bottom: 4px;">Bill thanh toán</div>
                        @include('families.partials.bill-gallery', ['json' => $family->bill_payment])
                        @if (! $family->bill_payment)
                            <p style="color: #9ca3af; font-size: 13px;">Chưa có bill thanh toán.</p>
                        @endif
                    </div>
                </div>

                <div id="member-rows">
                    @foreach ($family->members as $member)
                    @include('families.partials.member-row', ['member' => $member, 'excludeFamilyId' => $family->id])
                    @endforeach
                </div>

                <template id="member-row-template">
                    @include('families.partials.member-row', ['excludeFamilyId' => $family->id])
                </template>

                <button type="button" class="kt-btn mb-5" onclick="
                        const tpl = document.getElementById('member-row-template');
                        const clone = tpl.content.cloneNode(true);
                        document.getElementById('member-rows').appendChild(clone);
                    ">Thêm thành viên</button>

                <button type="submit" class="kt-btn kt-btn-primary">Cập nhật family</button>
            </form>
        </div>
    </div>
</div>

<div id="bill-lightbox" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); align-items: center; justify-content: center; z-index: 2000; padding: 24px;" onclick="if (event.target === this) closeBillLightbox();">
    <img id="bill-lightbox-image" src="" alt="Bill" style="max-width: 100%; max-height: 100%; border-radius: 0.5rem;" />
    <button type="button" onclick="closeBillLightbox()" style="position: fixed; top: 16px; right: 24px; background: none; border: none; color: #fff; font-size: 32px; line-height: 1; cursor: pointer;">&times;</button>
</div>

@push('scripts')
<script>
    function openBillLightbox(src) {
        var lightbox = document.getElementById('bill-lightbox');
        var image = document.getElementById('bill-lightbox-image');
        if (!lightbox || !image) return;
        image.src = src;
        lightbox.style.display = 'flex';
    }

    function closeBillLightbox() {
        var lightbox = document.getElementById('bill-lightbox');
        if (lightbox) lightbox.style.display = 'none';
    }
</script>
@endpush
@endsection