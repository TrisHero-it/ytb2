@extends('layouts.app')

@section('title', 'Thêm family')

@section('content')
<div class="lg:col-span-3">
    <div class="kt-card kt-card-grid h-full min-w-full">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Thêm family</h3>
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

            <form method="POST" action="{{ route('families.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="grid gap-4 mb-5">
                    <label>Email
                        <input type="text" name="email" value="{{ old('email') }}" placeholder="Email" class="kt-input" />
                    </label>
                    <label>Họ và tên
                        <input type="text" name="user" value="{{ old('user') }}" placeholder="Họ và tên" class="kt-input" />
                    </label>
                    <label>Số điện thoại
                        <input type="text" name="number_phone" value="{{ old('number_phone') }}" placeholder="Số điện thoại" class="kt-input" />
                    </label>
                    <label>Affiliate by
                        <input type="text" name="afiilicate_by" value="{{ old('afiilicate_by') }}" placeholder="Affiliate By" class="kt-input" />
                    </label>
                    <label>Số tài khoản
                        <input type="text" name="number_bank" value="{{ old('number_bank') }}" placeholder="Số tài khoản" class="kt-input" />
                    </label>
                    <label>Ngân hàng
                        @include('families.partials.bank-select', ['currentBank' => old('name_bank')])
                    </label>
                    <label>Ngày fam tự thanh toán hàng tháng
                        <input type="number" min=1 max="31" name="auto_payment_day" value="{{ old('auto_payment_day') }}" class="kt-input" placeholder="Ngày fam tự thanh toán hàng tháng" />
                    </label>
                    <label>Bill gốc
                        <input type="file" name="bill_of_master[]" multiple class="kt-input" />
                    </label>
                </div>

                <div id="member-rows">
                    @include('families.partials.member-row')
                </div>

                <template id="member-row-template">
                    @include('families.partials.member-row')
                </template>

                <button type="button" class="kt-btn mb-5" onclick="
                        const tpl = document.getElementById('member-row-template');
                        const clone = tpl.content.cloneNode(true);
                        document.getElementById('member-rows').appendChild(clone);
                    ">Thêm thành viên</button>

                <button type="submit" class="kt-btn kt-btn-primary">Lưu family</button>
            </form>
        </div>
    </div>
</div>
@endsection