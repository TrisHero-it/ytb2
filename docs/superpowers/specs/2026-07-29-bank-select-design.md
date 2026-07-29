# Bank select for families create/edit forms

## Problem
`name_bank` in `families/create.blade.php` and `families/edit.blade.php` is a free-text
input. Users mistype bank names, which breaks any later matching/display logic. Replace
it with a `<select>` populated from a live bank list.

## Approach
Client-side only, no backend changes.

- New shared partial: `resources/views/families/partials/bank-select.blade.php`.
  Renders `<select name="name_bank" class="kt-select" id="name_bank" data-current="...">`
  plus a `<script>` block that:
  1. Reads `localStorage['vietqr_banks_cache']` (`{ fetchedAt, banks }`); reuses it if
     younger than 24h.
  2. Otherwise `fetch('https://api.vietqr.io/v2/banks')`, expects
     `{ code: "00", data: [{ shortName, name, ... }] }`, caches the `data` array with a
     timestamp.
  3. Populates `<option value="{shortName}">{shortName} - {name}</option>`, sorted by
     `shortName`.
  4. If `data-current` is non-empty: selects the matching option; if no option's value
     matches, appends and selects an extra option
     `<option value="{current}">{current} (giá trị cũ)</option>` so existing data isn't
     silently dropped.
  5. On fetch failure: leaves the current-value option (if any) in place and appends a
     disabled option `"Không tải được danh sách ngân hàng, thử tải lại trang"`.
- `create.blade.php` includes the partial with no current value.
- `edit.blade.php` includes the partial with
  `'currentBank' => old('name_bank', $family->name_bank)`.
- Stored value is the bank's `shortName` (e.g. "Vietcombank") — matches existing
  `name_bank => required|string` validation in `StoreFamilyRequest` /
  `UpdateFamilyRequest`; no validation/migration changes needed.

## Out of scope
- No backend proxy/cache endpoint (VietQR API supports CORS directly).
- No changes to `number_bank` or other fields.
- No changes to `member-row.blade.php` (no bank field there).

## Testing
Manual: open `/families/create` and `/families/edit/{id}` in a browser, confirm the
select populates with banks, confirm selecting + submitting saves the `shortName`, and
confirm an existing family with a legacy free-text `name_bank` still shows/selects that
value.
