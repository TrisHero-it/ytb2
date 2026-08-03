@extends('layouts.app')

@section('title', 'Danh sách family')

@section('content')
@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif
@if (session('error'))
<div class="alert alert-error">
    {{ session('error') }}
</div>
@endif
<div class="lg:col-span-3">
    <div class="kt-card kt-card-grid h-full min-w-full">
        <div class="kt-card-header flex items-center justify-between gap-4">
            <h3 class="kt-card-title">Danh sách family</h3>
            <a href="{{ route('families.create') }}" class="kt-btn kt-btn-primary">Thêm family</a>
        </div>
        <div class="p-5">
            <form method="GET" action="{{ route('families.index') }}" class="flex items-center gap-2 mb-5">
                <input type="text" name="search" value="{{ $search }}" placeholder="Tìm theo mã đơn hàng, email hoặc tên chủ family" class="kt-input" />
                <select name="sort" class="kt-input">
                    <option value="next_payment" @selected($sort==='next_payment' || $sort==='' )>Thanh toán tiếp theo</option>
                    <option value="family_empty" @selected($sort==='family_empty' )>Sắp trống</option>
                    <option value="members_desc" @selected($sort==='members_desc' )>Nhiều thành viên nhất</option>
                    <option value="members_asc" @selected($sort==='members_asc' )>Ít thành viên nhất</option>
                </select>
                <button type="submit" class="kt-btn">Tìm kiếm</button>
            </form>

            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
                @forelse ($families as $family)
                @php
                $dayColor = function (?int $days) {
                if ($days === null) {
                return '#111827';
                }

                if ($days < 0) {
                    return '#dc2626' ;
                    }

                    return $days <=7 ? '#f59e0b' : '#16a34a' ;
                    };

                    $nextPaymentDate=$family->next_payment_date !== '9999-12-31'
                    ? \Illuminate\Support\Carbon::parse($family->next_payment_date)->format('d/m/Y')
                    : null;
                    $nextPaymentDays = $family->next_payment_date !== '9999-12-31'
                    ? now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($family->next_payment_date)->startOfDay(), false)
                    : null;
                    $familyEmptyDate = $family->family_empty_date !== '9999-12-31'
                    ? \Illuminate\Support\Carbon::parse($family->family_empty_date)->format('d/m/Y')
                    : null;
                    $familyEmptyDays = $family->family_empty_date !== '9999-12-31'
                    ? now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($family->family_empty_date)->startOfDay(), false)
                    : null;

                    $paymentAt = $family->payment_at?->format('d/m/Y');
                    @endphp
                    <div class="kt-card" style="padding: 1.25rem; border-radius: 0.75rem; background: aliceblue">
                        <div class="flex items-center justify-between mb-3">
                            <h3 style="font-size: 1.05rem; font-weight: 700;">{{ $family->user }}</h3>
                            <div class="flex items-center gap-3">
                                <button type="button" title="Thanh toán nhanh" onclick="document.getElementById('quickpay-modal-{{ $family->id }}').style.display = 'flex'" style="display: flex; align-items: center; gap: 4px; background: none; border: none; padding: 0; color: #2563eb; font-size: 13px; cursor: pointer;">
                                    <i class="ki-filled ki-bill"></i> Thanh toán
                                </button>
                                <a href="{{ route('families.edit', $family) }}" title="Sửa" style="color: #3b82f6; font-size: 16px;">
                                    <i class="ki-filled ki-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('families.destroy', $family) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Xóa" onclick="return confirm('Xóa family này?')" style="background: none; border: none; padding: 0; color: #ef4444; font-size: 16px; cursor: pointer;">
                                        <i class="ki-filled ki-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <hr style="border-color: #e5e7eb; margin-bottom: 12px;">

                        <div class="grid gap-3" style="font-size: 13px;">
                            <div>
                                <div style="color: #6b7280;">Email</div>
                                <div style="font-weight: 500;">{{ $family->email ?: '—' }}</div>
                            </div>
                            <div>
                                <div style="color: #6b7280;">Số điện thoại</div>
                                <div style="font-weight: 500;">{{ $family->number_phone ?: '—' }}</div>
                            </div>
                            <div>
                                <div style="color: #6b7280;">Ngày thanh toán gần nhất</div>
                                <div style="font-weight: 500;">{{ $paymentAt ?? '—' }}</div>
                            </div>
                            <div>
                                <div style="color: #6b7280;">Affiliate by</div>
                                <div style="font-weight: 500;">{{ $family->afiilicate_by ?: '—' }}</div>
                            </div>
                        </div>

                        <div style="background: #eff6ff; border-radius: 0.5rem; padding: 12px; margin-top: 12px; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between; gap: 12px;">
                                <div>
                                    <div style="color: #6b7280;">Hạn thanh toán youtube</div>
                                    <div style="font-weight: 600; color: {{ $dayColor($nextPaymentDays) }};">{{ $nextPaymentDate ?? 'Chưa đặt ngày tự thanh toán' }}</div>
                                </div>
                                @if ($nextPaymentDays !== null)
                                <div style="text-align: right;">
                                    <div style="color: #6b7280;">{{ $nextPaymentDays < 0 ? 'Đã quá hạn' : 'Còn lại' }}</div>
                                    <div style="font-weight: 700; color: {{ $dayColor($nextPaymentDays) }};">{{ abs($nextPaymentDays) }} ngày</div>
                                </div>
                                @endif
                            </div>
                            <hr style="border-color: #dbeafe; margin: 10px 0;">
                            <div style="display: flex; justify-content: space-between; gap: 12px;">
                                <div>
                                    <div style="color: #6b7280;">Ngày family trống</div>
                                    <div style="font-weight: 600;">{{ $familyEmptyDate ?? 'Chưa có thành viên' }}</div>
                                </div>
                                @if ($familyEmptyDays !== null)
                                <div style="text-align: right;">
                                    <div style="color: #6b7280;">{{ $familyEmptyDays < 0 ? 'Đã trống' : 'Còn lại' }}</div>
                                    <div style="font-weight: 700; color: {{ $dayColor($familyEmptyDays) }};">{{ abs($familyEmptyDays) }} ngày</div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <hr style="border-color: #e5e7eb; margin: 12px 0;">
                        <div class="flex items-center justify-between" style="font-size: 13px;">
                            <span >Thành viên: {{ $family->member_count }}</span>
                            <div class="flex items-center gap-3">
                                <button type="button" onclick="showFamilyHistory({{ $family->id }})" style="background: none; border: none; padding: 0; color: #3b82f6; cursor: pointer;">Lịch sử</button>
                                <button type="button" onclick="document.getElementById('members-modal-{{ $family->id }}').style.display = 'flex'" style="background: none; border: none; padding: 0; color: #3b82f6; cursor: pointer;">Xem thêm</button>
                            </div>
                        </div>

                        <div id="quickpay-modal-{{ $family->id }}" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1050;" onclick="if (event.target === this) this.style.display = 'none';">
                            <div class="kt-card" style="width: 100%; max-width: 420px; background: #fff; padding: 1.25rem; border-radius: 0.5rem;">
                                <h4 class="kt-card-title mb-3">Thanh toán nhanh - {{ $family->user }}</h4>
                                <form method="POST" action="{{ route('families.quick-pay', $family) }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="grid gap-3 mb-3">
                                        <label>Số tháng thanh toán
                                            <input type="number" name="monthly_payment" min="0" class="kt-input w-full" />
                                        </label>
                                        <label>Bill thanh toán (tuỳ chọn)
                                            <input type="file" name="bill_payment[]" multiple class="kt-input w-full" />
                                        </label>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="kt-btn" onclick="document.getElementById('quickpay-modal-{{ $family->id }}').style.display = 'none'">Hủy</button>
                                        <button type="submit" class="kt-btn kt-btn-primary">Xác nhận thanh toán</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div id="history-modal-{{ $family->id }}" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1050;" onclick="if (event.target === this) this.style.display = 'none';">
                            <div class="kt-card" style="width: 100%; max-width: 560px; max-height: 80vh; overflow-y: auto; background: #fff; padding: 1.25rem; border-radius: 0.5rem;">
                                <h4 class="kt-card-title mb-3">Lịch sử gia đình - {{ $family->user }}</h4>
                                <div id="history-list-{{ $family->id }}" class="grid gap-2 mb-3"></div>
                                <div class="flex justify-end">
                                    <button type="button" class="kt-btn" onclick="document.getElementById('history-modal-{{ $family->id }}').style.display = 'none'">Đóng</button>
                                </div>
                            </div>
                        </div>

                        <div id="members-modal-{{ $family->id }}" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1050; padding: 16px;" onclick="if (event.target === this) this.style.display = 'none';">
                            <div class="kt-card" style="width: 100%; max-width: 760px; max-height: 80vh; overflow-y: auto; background: #fff; padding: 1.25rem; border-radius: 0.5rem;">
                                <div class="flex items-center justify-between mb-3" style="gap: 12px;">
                                    <h4 class="kt-card-title" style="margin: 0;">Thành viên - {{ $family->user }}</h4>
                                    <span style="flex-shrink: 0; background: #eff6ff; color: {{ $family->member_count >= 5 ? '#dc2626' : '#2563eb' }}; font-weight: 700; font-size: 12px; padding: 3px 10px; border-radius: 9999px;">{{ $family->member_count }} / 5</span>
                                </div>
                                @if ($family->members->isEmpty())
                                <div style="text-align: center; padding: 32px 16px; color: #9ca3af;">
                                    <i class="ki-filled ki-people" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
                                    <p style="margin: 0;">Family chưa có thành viên nào.</p>
                                </div>
                                @else
                                <div class="kt-table-wrapper" style="border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                                    <table class="kt-table kt-table-highlight">
                                        <thead>
                                            <tr>
                                                <th style="width: 40px;">#</th>
                                                <th>Mã đơn hàng</th>
                                                <th>Tên sản phẩm</th>
                                                <th>Email</th>
                                                <th>Khu vực bạn sống</th>
                                                <th style="text-align: right;">Ngày mua</th>
                                                <th style="text-align: right;">Ngày hết hạn</th>
                                                <th style="text-align: right;">Còn lại</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($family->members as $index => $member)
                                            @php
                                                $memberExpireDate = $member->purchase_date
                                                    ? $member->purchase_date->copy()->addMonths(str_contains((string) $member->product_name, '6') ? 6 : 12)
                                                    : null;
                                                $memberExpireDays = $memberExpireDate
                                                    ? now()->startOfDay()->diffInDays($memberExpireDate->copy()->startOfDay(), false)
                                                    : null;
                                            @endphp
                                            <tr>
                                                <td style="color: #9ca3af;">{{ $index + 1 }}</td>
                                                <td>
                                                    <span style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; background: #f3f4f6; padding: 2px 6px; border-radius: 4px;">{{ $member->order_code ?: '—' }}</span>
                                                </td>
                                                <td>{{ $member->product_name ?: '—' }}</td>
                                                <td style="color: #4b5563;">{{ $member->email ?: '—' }}</td>
                                                <td style="color: #4b5563;">{{ $member->region ?: '—' }}</td>
                                                <td style="text-align: right; color: #6b7280; white-space: nowrap;">{{ $member->purchase_date?->format('d/m/Y') ?? '—' }}</td>
                                                <td style="text-align: right; white-space: nowrap; color: {{ $dayColor($memberExpireDays) }};">{{ $memberExpireDate?->format('d/m/Y') ?? '—' }}</td>
                                                <td style="text-align: right; white-space: nowrap; font-weight: 600; color: {{ $dayColor($memberExpireDays) }};">
                                                    @if ($memberExpireDays === null)
                                                        —
                                                    @else
                                                        {{ $memberExpireDays < 0 ? 'Đã quá hạn' : abs($memberExpireDays) . ' ngày' }}
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @endif
                                <div class="flex justify-end mt-3">
                                    <button type="button" class="kt-btn" onclick="document.getElementById('members-modal-{{ $family->id }}').style.display = 'none'">Đóng</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p>Không có family nào.</p>
                    @endforelse
            </div>

            {{ $families->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.toggleFamilyHistoryOldValue = function(id) {
        var preview = document.getElementById(id + '-preview');
        var full = document.getElementById(id + '-full');
        if (preview) preview.style.display = 'none';
        if (full) full.style.display = 'block';
    };

    function showFamilyHistory(familyId) {
        var modal = document.getElementById('history-modal-' + familyId);
        var list = document.getElementById('history-list-' + familyId);
        if (!modal || !list) return;

        list.innerHTML = '<p>Đang tải...</p>';
        modal.style.display = 'flex';

        fetch('/api/families/' + familyId + '/history')
            .then(function(response) {
                return response.json();
            })
            .then(function(items) {
                if (items.length === 0) {
                    list.innerHTML = '<p>Chưa có lịch sử thay đổi.</p>';
                    return;
                }

                var STATUS_META = {
                    add: {
                        label: 'Thêm',
                        dot: '#3b82f6',
                        badgeBg: '#dbeafe',
                        badgeColor: '#1d4ed8'
                    },
                    change: {
                        label: 'Sửa',
                        dot: '#f59e0b',
                        badgeBg: '#fef3c7',
                        badgeColor: '#b45309'
                    },
                    delete: {
                        label: 'Xoá',
                        dot: '#ef4444',
                        badgeBg: '#fee2e2',
                        badgeColor: '#b91c1c'
                    },
                };

                function pad(n) {
                    return n < 10 ? '0' + n : '' + n;
                }

                function formatDateTime(value) {
                    var d = value ? new Date(value) : null;
                    if (!d || isNaN(d.getTime())) return '';
                    return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() +
                        ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
                }

                function formatDateOnly(value) {
                    var d = value ? new Date(value) : null;
                    if (!d || isNaN(d.getTime())) return '';
                    return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear();
                }

                function escapeHtml(value) {
                    return String(value === null || value === undefined ? '' : value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                }

                function fieldLines(fields) {
                    if (!fields) return '';
                    return '' +
                        '<div>Mã đơn hàng: ' + escapeHtml(fields.order_code) + '</div>' +
                        '<div>Tên sản phẩm: ' + escapeHtml(fields.product_name) + '</div>' +
                        '<div>Email: ' + escapeHtml(fields.email) + '</div>' +
                        '<div>Khu vực bạn sống: ' + escapeHtml(fields.region) + '</div>' +
                        '<div>Ngày mua: ' + escapeHtml(formatDateOnly(fields.purchase_date)) + '</div>';
                }

                var html = '<div style="position: relative; padding-left: 20px;">' +
                    '<div style="position: absolute; left: 4px; top: 6px; bottom: 6px; width: 2px; background: #e5e7eb;"></div>';

                items.forEach(function(item) {
                    var meta = STATUS_META[item.status] || STATUS_META.change;
                    var oldFields = item.old_value ? JSON.parse(item.old_value) : null;
                    var newFields = item.new_value ? JSON.parse(item.new_value) : null;
                    var isDelete = item.status === 'delete';

                    html += '<div style="position: relative; margin-bottom: 16px;">' +
                        '<span style="position: absolute; left: -20px; top: 6px; width: 10px; height: 10px; border-radius: 50%; background: #fff; border: 2px solid ' + meta.dot + '; z-index: 1;"></span>' +
                        '<div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; background: ' + (isDelete ? '#eef1f5' : '#fff') + ';">' +
                        '<div style="margin-bottom: 8px;">' +
                        '<span style="display: inline-block; padding: 2px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; background: ' + meta.badgeBg + '; color: ' + meta.badgeColor + ';">' + meta.label + '</span>' +
                        '<span style="color: #6b7280; font-size: 13px; margin-left: 8px;">' + escapeHtml(formatDateTime(item.created_at)) + '</span>' +
                        '</div>' +
                        '<div style="line-height: 1.6;' + (isDelete ? ' font-style: italic; color: #6b7280;' : '') + '">' +
                        fieldLines(isDelete ? oldFields : newFields) +
                        '</div>';

                    if (item.status === 'change' && oldFields) {
                        var toggleId = 'history-old-' + item.id;
                        html += '<hr style="margin: 8px 0; border: none; border-top: 1px solid #e5e7eb;">' +
                            '<div id="' + toggleId + '-preview" style="color: #9ca3af; font-size: 13px;">' +
                            '<div style="line-height: 1.6; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden;">' +
                            fieldLines(oldFields) +
                            '</div>' +
                            '<button type="button" onclick="toggleFamilyHistoryOldValue(\'' + toggleId + '\')" style="background: none; border: none; padding: 0; margin-top: 2px; color: #9ca3af; font-size: 13px; cursor: pointer; text-decoration: underline;">...</button>' +
                            '</div>' +
                            '<div id="' + toggleId + '-full" style="display: none; margin-top: 8px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; color: #9ca3af; font-size: 13px; line-height: 1.6;">' +
                            fieldLines(oldFields) +
                            '</div>';
                    }

                    html += '</div></div>';
                });

                html += '</div>';
                list.innerHTML = html;
            })
            .catch(function() {
                list.innerHTML = '<p>Không tải được lịch sử.</p>';
            });
    }
</script>
@endpush
@endsection