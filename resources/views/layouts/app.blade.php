<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="en">

<head>
    <title>@yield('title', 'Muakey Family YouTube Premium')</title>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport" />
    <link href="{{ asset('images/logo.png') }}" rel="icon" type="image/png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet" />
    @stack('styles')
</head>

<body class="antialiased flex h-full text-base text-foreground bg-background demo1 kt-header-fixed" style="background: ghostwhite;">
    <div id="page_loading_overlay" class="page-loading-overlay hidden" aria-hidden="true">
        <div class="page-loading-overlay__content">
            <svg class="page-loading-overlay__spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="#3b82f6" stroke-width="4"></circle>
                <path class="opacity-75" fill="#3b82f6" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="page-loading-overlay__text" id="page_loading_text">Đang xử lý...</p>
        </div>
    </div>
    <script>
        const defaultThemeMode = 'light';
        let themeMode;
        if (document.documentElement) {
            if (localStorage.getItem('kt-theme')) {
                themeMode = localStorage.getItem('kt-theme');
            } else if (document.documentElement.hasAttribute('data-kt-theme-mode')) {
                themeMode = document.documentElement.getAttribute('data-kt-theme-mode');
            } else {
                themeMode = defaultThemeMode;
            }
            document.documentElement.classList.add(themeMode);
        }
    </script>

    <div class="flex grow flex-col min-h-full">
        <nav class="bg-background border-b border-border shrink-0 z-30">
            <div class="flex items-center justify-between gap-4 px-4 lg:px-6 h-14">
                <a href="/" class="flex items-center gap-2 shrink-0">
                    <img class="h-8 w-auto" src="{{ asset('images/logo.png') }}" alt="Muakey" style="max-height: 32px; max-width: 180px;" />
                </a>
                <div class="flex items-center gap-1">
                    <a href="/families" class="kt-menu-link border border-transparent items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-foreground hover:bg-accent/60 hover:text-primary">
                        <i class="ki-filled ki-element-11 text-lg"></i>
                        Danh sách family
                    </a>
                    <a href="/families/create" class="kt-menu-link border border-transparent items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-foreground hover:bg-accent/60 hover:text-primary">
                        <i class="ki-filled ki-plus text-lg"></i>
                        Thêm family
                    </a>
                    <span class="w-px h-5 bg-border mx-1" aria-hidden="true"></span>
                    <a href="/collaborators" class="kt-menu-link border border-transparent items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-foreground hover:bg-accent/60 hover:text-primary">
                        <i class="ki-filled ki-setting-2 text-lg"></i>
                        Danh sách hướng dẫn
                    </a>
                    <a href="/collaborators/create" class="kt-menu-link border border-transparent items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-foreground hover:bg-accent/60 hover:text-primary">
                        <i class="ki-filled ki-plus text-lg"></i>
                        Thêm form hướng dẫn
                    </a>
                    <span class="w-px h-5 bg-border mx-1" aria-hidden="true"></span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="kt-menu-link border border-transparent items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-foreground hover:bg-accent/60 hover:text-primary">
                            Đăng xuất
                        </button>
                    </form>
                </div>
            </div>
        </nav>
        <div class="kt-wrapper flex grow flex-col min-w-0">
            <main class="grow" id="content" role="content">
                <div class="kt-container-fixed" style="margin: 0 auto; width: 100%; max-width: unset;">
                    <div class="grid gap-5 lg:gap-7.5" style="width: 100%;">
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
    <script src="{{ asset('assets/js/core.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/layouts/demo1.js') }}"></script>
    <script src="{{ asset('assets/js/widgets/general.js') }}"></script>
    <script>
        window.checkMemberEmailDuplicate = function (textarea, excludeFamilyId) {
            var row = textarea.closest('[data-member-row]');
            var warningEl = row ? row.querySelector('[data-member-email-warning]') : null;
            if (!warningEl) {
                return;
            }
            clearTimeout(textarea._emailCheckTimer);
            textarea._emailCheckTimer = setTimeout(function () {
                var match = textarea.value.match(/[\w.+-]+@[\w-]+\.[\w.-]+/);
                if (!match) {
                    warningEl.classList.add('hidden');
                    warningEl.textContent = '';
                    return;
                }
                var email = match[0];
                var url = '/api/families/check-member-email?email=' + encodeURIComponent(email);
                if (excludeFamilyId) {
                    url += '&exclude_id=' + encodeURIComponent(excludeFamilyId);
                }
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.exists) {
                            warningEl.textContent = 'Email ' + email + ' đã tồn tại ở family: ' + (data.family && data.family.user ? data.family.user : '');
                            warningEl.classList.remove('hidden');
                        } else {
                            warningEl.classList.add('hidden');
                            warningEl.textContent = '';
                        }
                    })
                    .catch(function () {});
            }, 500);
        };
    </script>
    @stack('scripts')
</body>

</html>
