@php($row = $row ?? ['id' => null, 'text' => '', 'order_code' => null, 'product_name' => null, 'email' => null, 'region' => null, 'purchase_date' => null])

<tr data-member-row>
    <td data-member-index style="color: #9ca3af;"></td>
    <td>
        <input type="hidden" name="member_ids[]" value="{{ $row['id'] }}" />
        <input type="hidden" name="member_texts[]" data-member-text value="{{ $row['text'] }}" />
        <span data-member-cell="order_code" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; background: #f3f4f6; padding: 2px 6px; border-radius: 4px;">{{ $row['order_code'] ?: '—' }}</span>
    </td>
    <td data-member-cell="product_name" style="color: #4b5563;">{{ $row['product_name'] ?: '—' }}</td>
    <td data-member-cell="email" style="color: #4b5563; white-space: nowrap;">{{ $row['email'] ?: '—' }}</td>
    <td data-member-cell="region" style="color: #4b5563; white-space: nowrap;">{{ $row['region'] ?: '—' }}</td>
    <td data-member-cell="purchase_date" style="text-align: right; color: #6b7280; white-space: nowrap;">{{ $row['purchase_date'] ?: '—' }}</td>
    <td style="text-align: right; white-space: nowrap;">
        <button type="button" class="kt-btn kt-btn-sm" onclick="openMemberEditor(this.closest('[data-member-row]'))">Sửa</button>
        <button type="button" class="kt-btn kt-btn-sm kt-btn-destructive" onclick="removeMemberRow(this)">Xóa</button>
    </td>
</tr>
