@php($member = $member ?? null)
@php($excludeFamilyId = $excludeFamilyId ?? null)

<div class="kt-card p-4 mb-3" data-member-row>
    <input type="hidden" name="member_ids[]" value="{{ $member?->id }}" />
    <textarea name="member_texts[]" rows="4" class="kt-textarea w-full" placeholder="Dán nội dung đơn hàng" oninput="checkMemberEmailDuplicate(this, {{ $excludeFamilyId ?? 'null' }})">{{ $member?->raw_text ?? ($member ? json_encode([
        'order_code' => $member->order_code,
        'product_name' => $member->product_name,
        'email' => $member->email,
        'region' => $member->region,
        'purchase_date' => $member->purchase_date?->format('d/m/Y'),
    ], JSON_UNESCAPED_UNICODE) : '') }}</textarea>
    <p class="text-sm text-destructive mt-1 hidden" data-member-email-warning></p>
    <button type="button" onclick="this.closest('[data-member-row]').remove()" class="kt-btn kt-btn-sm kt-btn-destructive mt-2">Xóa thành viên</button>
</div>
