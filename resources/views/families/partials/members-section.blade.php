@php($rows = $rows ?? [])
@php($excludeFamilyId = $excludeFamilyId ?? null)

<div class="mb-5">
    <div class="flex items-center justify-between mb-3" style="gap: 12px;">
        <h4 class="kt-card-title" style="margin: 0;">Thành viên</h4>
        <span id="member-count" style="flex-shrink: 0; background: #eff6ff; color: #2563eb; font-weight: 700; font-size: 12px; padding: 3px 10px; border-radius: 9999px;">0 / 5</span>
    </div>

    <div class="kt-table-wrapper" style="border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow-x: auto;">
        <table class="kt-table kt-table-highlight">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Mã đơn hàng</th>
                    <th>Tên sản phẩm</th>
                    <th>Email</th>
                    <th>Khu vực bạn sống</th>
                    <th style="text-align: right;">Ngày mua</th>
                    <th style="width: 140px; text-align: right;">Thao tác</th>
                </tr>
            </thead>
            <tbody id="member-rows">
                @foreach ($rows as $row)
                    @include('families.partials.member-row', ['row' => $row])
                @endforeach
            </tbody>
        </table>
    </div>

    <div id="member-empty" style="display: none; text-align: center; padding: 24px 16px; color: #9ca3af;">
        <i class="ki-filled ki-people" style="font-size: 24px; display: block; margin-bottom: 6px;"></i>
        <p style="margin: 0;">Chưa có thành viên nào. Bấm "Thêm thành viên" rồi dán nội dung đơn hàng.</p>
    </div>

    <template id="member-row-template">
        @include('families.partials.member-row')
    </template>

    <button type="button" class="kt-btn mt-3" onclick="openMemberEditor(null)">Thêm thành viên</button>
</div>

<div id="member-editor-modal" data-member-editor style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1050; padding: 16px;" onclick="if (event.target === this) closeMemberEditor();">
    <div class="kt-card" style="width: 100%; max-width: 560px; background: #fff; padding: 1.25rem; border-radius: 0.5rem;">
        <h4 class="kt-card-title mb-3" id="member-editor-title">Thêm thành viên</h4>

        <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px;">Dán nội dung đơn hàng</label>
        <textarea id="member-editor-text" rows="7" class="kt-textarea w-full" placeholder="Dán nội dung đơn hàng" oninput="onMemberEditorInput(this); checkMemberEmailDuplicate(this, {{ $excludeFamilyId ?? 'null' }})"></textarea>
        <p class="text-sm text-destructive mt-1 hidden" data-member-email-warning></p>
        <p id="member-editor-format-warning" class="text-sm mt-1 hidden" style="color: #b45309;">Chưa đọc được định dạng đơn hàng. Kiểm tra lại các dòng "Mã đơn hàng", "Tên sản phẩm", "Email", "Ngày mua".</p>

        <div style="font-size: 13px; font-weight: 500; margin: 12px 0 4px;">Xem trước</div>
        <div id="member-editor-preview" style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 12px 16px; font-size: 13px; line-height: 1.8; color: #4b5563; background: #fafafa;"></div>

        <div class="flex justify-end gap-2 mt-4">
            <button type="button" class="kt-btn" onclick="closeMemberEditor()">Hủy</button>
            <button type="button" class="kt-btn kt-btn-primary" onclick="saveMemberEditor()">Lưu thành viên</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    var MEMBER_PATTERN = /(?:Mã đơn hàng|Mã ĐH):\s*(.*?)\s*(?:Tên sản phẩm|Sản phẩm):\s*(.*?)\s*Email:\s*(.*?)\s*(?:Khu vực bạn sống:\s*(.*?)\s*)?Ngày mua:\s*(.*)$/iu;

    var MEMBER_COLUMNS = [
        { key: 'order_code', label: 'Mã đơn hàng' },
        { key: 'product_name', label: 'Tên sản phẩm' },
        { key: 'email', label: 'Email' },
        { key: 'region', label: 'Khu vực bạn sống' },
        { key: 'purchase_date', label: 'Ngày mua' }
    ];

    // Dòng đang được sửa; null nghĩa là đang thêm mới.
    var memberEditorTarget = null;

    function memberEmptyFields() {
        return { order_code: null, product_name: null, email: null, region: null, purchase_date: null };
    }

    function memberTrimOrNull(value) {
        var trimmed = (value === null || value === undefined) ? '' : String(value).trim();
        return trimmed === '' ? null : trimmed;
    }

    // Chỉ dùng để xem trước. Khi lưu, server vẫn phân tích lại bằng MemberTextParser.
    function parseMemberText(input) {
        input = (input === null || input === undefined) ? '' : String(input).trim();
        if (input === '') return memberEmptyFields();

        if (input.charAt(0) === '{') {
            try {
                var decoded = JSON.parse(input);
                return {
                    order_code: memberTrimOrNull(decoded.order_code),
                    product_name: memberTrimOrNull(decoded.product_name),
                    email: decoded.email ? String(decoded.email).trim().toLowerCase() : null,
                    region: memberTrimOrNull(decoded.region),
                    purchase_date: memberDisplayDate(decoded.purchase_date)
                };
            } catch (e) {}
        }

        var matches = input.match(MEMBER_PATTERN);
        if (!matches) return memberEmptyFields();

        return {
            order_code: memberTrimOrNull(matches[1]),
            product_name: memberTrimOrNull(matches[2]),
            email: matches[3] ? matches[3].trim().toLowerCase() : null,
            region: memberTrimOrNull(matches[4]),
            purchase_date: memberDisplayDate(matches[5])
        };
    }

    function memberDisplayDate(value) {
        if (!value) return null;
        var text = String(value);

        var dmy = text.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);
        if (dmy) {
            return ('0' + dmy[1]).slice(-2) + '/' + ('0' + dmy[2]).slice(-2) + '/' + dmy[3];
        }

        var ymd = text.match(/(\d{4})-(\d{2})-(\d{2})/);
        if (ymd) return ymd[3] + '/' + ymd[2] + '/' + ymd[1];

        return null;
    }

    function memberHasAnyField(fields) {
        return MEMBER_COLUMNS.some(function(column) {
            return fields[column.key] !== null;
        });
    }

    function renderMemberRow(row, fields, rawText) {
        row.querySelector('[data-member-text]').value = rawText;

        MEMBER_COLUMNS.forEach(function(column) {
            var cell = row.querySelector('[data-member-cell="' + column.key + '"]');
            if (cell) cell.textContent = fields[column.key] || '—';
        });
    }

    function renumberMemberRows() {
        var rows = document.querySelectorAll('#member-rows [data-member-row]');
        rows.forEach(function(row, index) {
            var cell = row.querySelector('[data-member-index]');
            if (cell) cell.textContent = index + 1;
        });

        var counter = document.getElementById('member-count');
        if (counter) {
            counter.textContent = rows.length + ' / 5';
            counter.style.color = rows.length >= 5 ? '#dc2626' : '#2563eb';
        }

        var empty = document.getElementById('member-empty');
        var wrapper = document.getElementById('member-rows').closest('.kt-table-wrapper');
        if (empty) empty.style.display = rows.length === 0 ? 'block' : 'none';
        if (wrapper) wrapper.style.display = rows.length === 0 ? 'none' : 'block';
    }

    function removeMemberRow(button) {
        button.closest('[data-member-row]').remove();
        renumberMemberRows();
    }

    function openMemberEditor(row) {
        memberEditorTarget = row;

        var modal = document.getElementById('member-editor-modal');
        var textarea = document.getElementById('member-editor-text');

        document.getElementById('member-editor-title').textContent = row ? 'Sửa thành viên' : 'Thêm thành viên';
        textarea.value = row ? row.querySelector('[data-member-text]').value : '';

        onMemberEditorInput(textarea);
        modal.querySelector('[data-member-email-warning]').classList.add('hidden');

        modal.style.display = 'flex';
        textarea.focus();
    }

    function closeMemberEditor() {
        document.getElementById('member-editor-modal').style.display = 'none';
        memberEditorTarget = null;
    }

    function onMemberEditorInput(textarea) {
        var text = textarea.value;
        var fields = parseMemberText(text);
        var preview = document.getElementById('member-editor-preview');
        var warning = document.getElementById('member-editor-format-warning');

        preview.innerHTML = MEMBER_COLUMNS.map(function(column) {
            var value = fields[column.key];
            var color = value ? '#4b5563' : '#9ca3af';
            return '<div><span style="color: #6b7280;">' + column.label + ':</span> ' +
                '<span style="color: ' + color + ';">' + memberEscapeHtml(value || '—') + '</span></div>';
        }).join('');

        warning.classList.toggle('hidden', text.trim() === '' || memberHasAnyField(fields));
    }

    function memberEscapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function saveMemberEditor() {
        var textarea = document.getElementById('member-editor-text');
        var text = textarea.value.trim();

        if (text === '') {
            textarea.focus();
            return;
        }

        var fields = parseMemberText(text);
        var row = memberEditorTarget;

        if (!row) {
            var template = document.getElementById('member-row-template');
            row = template.content.cloneNode(true).querySelector('[data-member-row]');
            document.getElementById('member-rows').appendChild(row);
        }

        renderMemberRow(row, fields, text);
        renumberMemberRows();
        closeMemberEditor();
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && document.getElementById('member-editor-modal').style.display === 'flex') {
            closeMemberEditor();
        }
    });

    renumberMemberRows();
</script>
@endpush
