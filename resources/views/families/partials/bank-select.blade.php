@php($currentBank = $currentBank ?? '')

<select name="name_bank" id="name_bank" class="kt-select" data-current="{{ $currentBank }}">
    <option value="">-- Chọn ngân hàng --</option>
</select>

<script>
    (function() {
        var CACHE_KEY = 'vietqr_banks_cache';
        var CACHE_TTL_MS = 24 * 60 * 60 * 1000;

        function populate(select, banks) {
            var current = select.getAttribute('data-current') || '';
            var sorted = banks.slice().sort(function(a, b) {
                return a.shortName.localeCompare(b.shortName);
            });

            var matched = false;
            sorted.forEach(function(bank) {
                var option = document.createElement('option');
                option.value = bank.shortName;
                option.textContent = bank.shortName + ' - ' + bank.name;
                if (current && bank.shortName === current) {
                    option.selected = true;
                    matched = true;
                }
                select.appendChild(option);
            });

            if (current && !matched) {
                var legacyOption = document.createElement('option');
                legacyOption.value = current;
                legacyOption.textContent = current + ' (giá trị cũ)';
                legacyOption.selected = true;
                select.appendChild(legacyOption);
            }
        }

        function markUnavailable(select) {
            var option = document.createElement('option');
            option.disabled = true;
            option.textContent = 'Không tải được danh sách ngân hàng, thử tải lại trang';
            select.appendChild(option);
        }

        function loadBanks() {
            try {
                var cached = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null');
                if (cached && cached.fetchedAt && (Date.now() - cached.fetchedAt) < CACHE_TTL_MS && Array.isArray(cached.banks)) {
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
                        localStorage.setItem(CACHE_KEY, JSON.stringify({
                            fetchedAt: Date.now(),
                            banks: banks
                        }));
                    } catch (e) {}
                    return banks;
                });
        }

        document.querySelectorAll('#name_bank[data-current]').forEach(function(select) {
            loadBanks()
                .then(function(banks) {
                    populate(select, banks);
                })
                .catch(function() {
                    markUnavailable(select);
                });
        });
    })();
</script>