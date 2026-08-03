@extends('layouts.app')

@section('title', 'Danh sách family')

@section('content')
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
                <button type="button" class="kt-btn kt-btn-outline" onclick="document.getElementById('history-search-modal').style.display = 'flex'; document.getElementById('history-search-input').focus();">
                    <i class="ki-filled ki-time"></i> Tìm lịch sử
                </button>
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
                            <h3 title="{{ $family->user }}" style="font-size: 1.05rem; font-weight: 700; min-width: 0; flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $family->user }}</h3>
                            <div class="flex items-center gap-3" style="flex-shrink: 0;">
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
                            <span>Thành viên: {{ $family->member_count }}</span>
                            <div class="flex items-center gap-3">
                                <button type="button" onclick="showFamilyHistory({{ $family->id }})" style="background: none; border: none; padding: 0; color: #3b82f6; cursor: pointer;">Lịch sử</button>
                                <button type="button" onclick="document.getElementById('members-modal-{{ $family->id }}').style.display = 'flex'" style="background: none; border: none; padding: 0; color: #3b82f6; cursor: pointer;">Xem thêm</button>
                            </div>
                        </div>

                        <div id="quickpay-modal-{{ $family->id }}" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1050;" onclick="if (event.target === this) this.style.display = 'none';">
                            <div class="kt-card" style="width: 100%; max-width: 420px; background: #fff; padding: 1.25rem; border-radius: 0.5rem;">
                                <h4 class="kt-card-title mb-3">Thanh toán nhanh - {{ $family->user }}</h4>

                                @if ($family->number_bank && $family->name_bank)
                                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 12px; margin-bottom: 12px; display: flex; gap: 12px; align-items: flex-start;">
                                    <img data-momo-qr data-bank="{{ $family->name_bank }}" data-account="{{ $family->number_bank }}" data-account-name="{{ $family->user }}" src="https://img.vietqr.io/image/{{ rawurlencode($family->name_bank) }}-{{ rawurlencode($family->number_bank) }}-compact2.png?accountName={{ rawurlencode($family->user) }}&addInfo={{ rawurlencode('Thanh toan ' . $family->user) }}" alt="QR chuyển khoản" style="width: 130px; height: auto; border-radius: 0.375rem; flex-shrink: 0;" loading="lazy" />
                                    <div style="font-size: 13px; line-height: 1.8;">
                                        <div><span style="color: #6b7280;">Ngân hàng:</span> <strong>{{ $family->name_bank }}</strong></div>
                                        <div><span style="color: #6b7280;">Số tài khoản:</span> <strong>{{ $family->number_bank }}</strong></div>
                                        <div><span style="color: #6b7280;">Chủ tài khoản:</span> <strong>{{ $family->user }}</strong></div>
                                    </div>
                                </div>
                                @endif

                                <form method="POST" action="{{ route('families.quick-pay', $family) }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="grid gap-3 mb-3">
                                        <label>Số tháng thanh toán
                                            <input type="number" name="monthly_payment" min="0" value="1" class="kt-input w-full" />
                                        </label>
                                        <label>Bill thanh toán (tuỳ chọn)
                                            <input type="file" name="bill_payment[]" multiple class="kt-input w-full" />
                                        </label>
                                        <label>Hoặc dán ảnh bill (Ctrl+V) - copy ảnh từ Zalo hoặc bất kỳ đâu rồi dán vào đây
                                            <div
                                                contenteditable="true"
                                                data-paste-box
                                                data-target="bill-payment-paste-{{ $family->id }}"
                                                data-preview="bill-payment-preview-{{ $family->id }}"
                                                style="min-height: 44px; border: 1px dashed #d1d5db; border-radius: 0.375rem; padding: 8px 10px; font-size: 13px; color: #9ca3af; background: #fafafa; outline: none;">Dán ảnh vào đây...</div>
                                            <input type="hidden" name="bill_payment_paste" id="bill-payment-paste-{{ $family->id }}" />
                                        </label>
                                        <img id="bill-payment-preview-{{ $family->id }}" style="display: none; max-width: 160px; border-radius: 0.375rem; border: 1px solid #e5e7eb;" alt="Ảnh bill đã dán" />
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
                            <div class="kt-card" style="width: 100%; max-width: 1200px; max-height: 85vh; overflow-y: auto; background: #fff; padding: 1.25rem; border-radius: 0.5rem;">
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
                                                <th>Số tháng</th>
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
                                            $memberMonths = null;
                                            if ($member->product_name && preg_match('/(\d+)\s*Tháng/ui', $member->product_name, $memberMonthMatch)) {
                                            $memberMonths = (int) $memberMonthMatch[1];
                                            }
                                            $memberExpireDate = $member->purchase_date
                                            ? $member->purchase_date->copy()->addMonths($memberMonths ?? (str_contains((string) $member->product_name, '6') ? 6 : 12))
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
                                                <td>{{ $memberMonths !== null ? $memberMonths . ' tháng' : '—' }}</td>
                                                <td style="color: #4b5563; white-space: nowrap;">{{ $member->email ?: '—' }}</td>
                                                <td style="color: #4b5563; white-space: nowrap;">{{ $member->region ?: '—' }}</td>
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

<div id="history-search-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1050; padding: 16px;" onclick="if (event.target === this) this.style.display = 'none';">
    <div class="kt-card" style="width: 100%; max-width: 640px; max-height: 80vh; overflow-y: auto; background: #fff; padding: 1.25rem; border-radius: 0.5rem;">
        <h4 class="kt-card-title mb-3">Tìm kiếm lịch sử thêm / sửa / xoá</h4>
        <form onsubmit="searchHistory(event)" class="flex items-center gap-2 mb-3">
            <input type="text" id="history-search-input" placeholder="Mã đơn hàng, email, tên sản phẩm hoặc tên chủ family" class="kt-input w-full" />
            <button type="submit" class="kt-btn kt-btn-primary">Tìm</button>
        </form>
        <div id="history-search-results" class="grid gap-2 mb-3">
            <p style="color: #9ca3af;">Nhập từ khoá để tìm kiếm.</p>
        </div>
        <div class="flex justify-end">
            <button type="button" class="kt-btn" onclick="document.getElementById('history-search-modal').style.display = 'none'">Đóng</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    var vietQrBanksPromise = null;

    function loadVietQrBanks() {
        if (vietQrBanksPromise) return vietQrBanksPromise;

        vietQrBanksPromise = (function() {
            try {
                var cached = JSON.parse(localStorage.getItem('vietqr_banks_cache') || 'null');
                if (cached && cached.fetchedAt && (Date.now() - cached.fetchedAt) < 24 * 60 * 60 * 1000 && Array.isArray(cached.banks)) {
                    return Promise.resolve(cached.banks);
                }
            } catch (e) {}

            return fetch('https://api.vietqr.io/v2/banks')
                .then(function(res) {
                    return res.json();
                })
                .then(function(json) {
                    var banks = json && json.data ? json.data : [];
                    try {
                        localStorage.setItem('vietqr_banks_cache', JSON.stringify({
                            fetchedAt: Date.now(),
                            banks: banks
                        }));
                    } catch (e) {}
                    return banks;
                });
        })();

        return vietQrBanksPromise;
    }

    function fixQuickPayQrCodes() {
        var images = document.querySelectorAll('img[data-momo-qr]');
        if (!images.length) return;

        loadVietQrBanks().then(function(banks) {
            images.forEach(function(img) {
                var bankName = (img.getAttribute('data-bank') || '').trim().toLowerCase();
                var match = banks.find(function(b) {
                    return (b.shortName || '').trim().toLowerCase() === bankName || (b.code || '').trim().toLowerCase() === bankName;
                });
                if (!match) return;

                var account = img.getAttribute('data-account') || '';
                var accountName = img.getAttribute('data-account-name') || '';
                img.src = 'https://img.vietqr.io/image/' + encodeURIComponent(match.bin) + '-' + encodeURIComponent(account) + '-compact2.png' +
                    '?accountName=' + encodeURIComponent(accountName) +
                    '&addInfo=' + encodeURIComponent('Thanh toan ' + accountName);
            });
        }).catch(function() {});
    }

    fixQuickPayQrCodes();

    document.querySelectorAll('[data-paste-box]').forEach(function(box) {
        box.addEventListener('paste', function(event) {
            var items = (event.clipboardData || window.clipboardData).items || [];
            var imageItem = null;
            for (var i = 0; i < items.length; i++) {
                if (items[i].type && items[i].type.indexOf('image/') === 0) {
                    imageItem = items[i];
                    break;
                }
            }
            if (!imageItem) return;

            event.preventDefault();
            var file = imageItem.getAsFile();
            var reader = new FileReader();
            reader.onload = function() {
                var hidden = document.getElementById(box.getAttribute('data-target'));
                var preview = document.getElementById(box.getAttribute('data-preview'));
                if (hidden) hidden.value = reader.result;
                if (preview) {
                    preview.src = reader.result;
                    preview.style.display = 'block';
                }
                box.textContent = 'Đã dán ảnh ✓ (dán lại để đổi ảnh khác)';
            };
            reader.readAsDataURL(file);
        });
    });

    window.toggleFamilyHistoryOldValue = function(id) {
        var preview = document.getElementById(id + '-preview');
        var full = document.getElementById(id + '-full');
        if (preview) preview.style.display = 'none';
        if (full) full.style.display = 'block';
    };

    var HISTORY_STATUS_META = {
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

    function historyPad(n) {
        return n < 10 ? '0' + n : '' + n;
    }

    function historyFormatDateTime(value) {
        var d = value ? new Date(value) : null;
        if (!d || isNaN(d.getTime())) return '';
        return historyPad(d.getDate()) + '/' + historyPad(d.getMonth() + 1) + '/' + d.getFullYear() +
            ' ' + historyPad(d.getHours()) + ':' + historyPad(d.getMinutes()) + ':' + historyPad(d.getSeconds());
    }

    function historyFormatDateOnly(value) {
        var d = value ? new Date(value) : null;
        if (!d || isNaN(d.getTime())) return '';
        return historyPad(d.getDate()) + '/' + historyPad(d.getMonth() + 1) + '/' + d.getFullYear();
    }

    function historyEscapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function historyFieldLines(fields) {
        if (!fields) return '';
        return '' +
            '<div>Mã đơn hàng: ' + historyEscapeHtml(fields.order_code) + '</div>' +
            '<div>Tên sản phẩm: ' + historyEscapeHtml(fields.product_name) + '</div>' +
            '<div>Email: ' + historyEscapeHtml(fields.email) + '</div>' +
            '<div>Khu vực bạn sống: ' + historyEscapeHtml(fields.region) + '</div>' +
            '<div>Ngày mua: ' + historyEscapeHtml(historyFormatDateOnly(fields.purchase_date)) + '</div>';
    }

    function renderHistoryItemHtml(item) {
        var meta = HISTORY_STATUS_META[item.status] || HISTORY_STATUS_META.change;
        var oldFields = item.old_value ? JSON.parse(item.old_value) : null;
        var newFields = item.new_value ? JSON.parse(item.new_value) : null;
        var isDelete = item.status === 'delete';

        var html = '<div style="position: relative; margin-bottom: 16px;">' +
            '<span style="position: absolute; left: -20px; top: 6px; width: 10px; height: 10px; border-radius: 50%; background: #fff; border: 2px solid ' + meta.dot + '; z-index: 1;"></span>' +
            '<div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; background: ' + (isDelete ? '#eef1f5' : '#fff') + ';">' +
            '<div style="margin-bottom: 8px; display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">' +
            '<span style="display: inline-block; padding: 2px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; background: ' + meta.badgeBg + '; color: ' + meta.badgeColor + ';">' + meta.label + '</span>' +
            (item.family_user ? '<a href="/families/' + item.family_id + '/edit" style="display: inline-block; padding: 2px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; background: #eff6ff; color: #2563eb; text-decoration: none;">' + historyEscapeHtml(item.family_user) + '</a>' : '') +
            '<span style="color: #6b7280; font-size: 13px;">' + historyEscapeHtml(historyFormatDateTime(item.created_at)) + '</span>' +
            '</div>' +
            '<div style="line-height: 1.6;' + (isDelete ? ' font-style: italic; color: #6b7280;' : '') + '">' +
            historyFieldLines(isDelete ? oldFields : newFields) +
            '</div>';

        if (item.status === 'change' && oldFields) {
            var toggleId = 'history-old-' + item.id;
            html += '<hr style="margin: 8px 0; border: none; border-top: 1px solid #e5e7eb;">' +
                '<div id="' + toggleId + '-preview" style="color: #9ca3af; font-size: 13px;">' +
                '<div style="line-height: 1.6; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden;">' +
                historyFieldLines(oldFields) +
                '</div>' +
                '<button type="button" onclick="toggleFamilyHistoryOldValue(\'' + toggleId + '\')" style="background: none; border: none; padding: 0; margin-top: 2px; color: #9ca3af; font-size: 13px; cursor: pointer; text-decoration: underline;">...</button>' +
                '</div>' +
                '<div id="' + toggleId + '-full" style="display: none; margin-top: 8px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; color: #9ca3af; font-size: 13px; line-height: 1.6;">' +
                historyFieldLines(oldFields) +
                '</div>';
        }

        html += '</div></div>';
        return html;
    }

    function renderHistoryTimelineHtml(items) {
        var html = '<div style="position: relative; padding-left: 20px;">' +
            '<div style="position: absolute; left: 4px; top: 6px; bottom: 6px; width: 2px; background: #e5e7eb;"></div>';
        items.forEach(function(item) {
            html += renderHistoryItemHtml(item);
        });
        html += '</div>';
        return html;
    }

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

                list.innerHTML = renderHistoryTimelineHtml(items);
            })
            .catch(function() {
                list.innerHTML = '<p>Không tải được lịch sử.</p>';
            });
    }

    function searchHistory(event) {
        if (event) event.preventDefault();

        var input = document.getElementById('history-search-input');
        var results = document.getElementById('history-search-results');
        if (!input || !results) return;

        var q = input.value.trim();
        if (q === '') {
            results.innerHTML = '<p style="color: #9ca3af;">Nhập từ khoá để tìm kiếm.</p>';
            return;
        }

        results.innerHTML = '<p>Đang tìm...</p>';

        fetch('/api/history/search?q=' + encodeURIComponent(q))
            .then(function(response) {
                return response.json();
            })
            .then(function(items) {
                if (items.length === 0) {
                    results.innerHTML = '<p>Không tìm thấy kết quả nào.</p>';
                    return;
                }

                results.innerHTML = renderHistoryTimelineHtml(items);
            })
            .catch(function() {
                results.innerHTML = '<p>Không tải được kết quả tìm kiếm.</p>';
            });
    }
</script>
@endpush
@endsection