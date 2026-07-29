<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="vi">

<head>
    <title>Hướng dẫn Cộng tác viên YouTube Premium</title>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport" />
    <link href="https://muakey.com/favicon.ico" rel="shortcut icon" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet" />
    <style>
        .guide-content {
            line-height: 1.8;
        }

        .guide-content h1,
        .guide-content h2,
        .guide-content h3 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .guide-content h1 {
            font-size: 2rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }

        .guide-content h2 {
            font-size: 1.5rem;
        }

        .guide-content h3 {
            font-size: 1.25rem;
        }

        .guide-content p {
            margin-bottom: 1rem;
        }

        .guide-content ul,
        .guide-content ol {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .guide-content li {
            margin-bottom: 0.5rem;
        }

        .guide-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }

        .guide-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        .guide-content table th,
        .guide-content table td {
            border: 1px solid #e5e7eb;
            padding: 0.75rem;
            text-align: left;
        }

        .guide-content table th {
            background-color: #f9fafb;
            font-weight: 600;
        }

        .guide-content blockquote {
            border-left: 4px solid #3b82f6;
            padding-left: 1rem;
            margin: 1rem 0;
            color: #6b7280;
            font-style: italic;
        }

        .guide-content a {
            color: #3b82f6;
            text-decoration: underline;
        }

        .guide-content a:hover {
            color: #2563eb;
        }
    </style>
</head>

<body class="antialiased flex h-full text-base text-foreground bg-background" style="display: flex; flex-direction: column; height: 100vh;">
    <nav class="bg-background border-b border-border shrink-0 z-30">
        <div class="flex items-center justify-between gap-4 px-4 lg:px-6 h-14">
            <a href="{{ url('/') }}" class="flex items-center gap-2 shrink-0">
                <img class="h-8 w-auto" src="{{ asset('images/logo.png') }}" alt="Muakey" style="max-height: 32px; max-width: 180px;" />
            </a>
            <div class="flex items-center gap-1">
                <a href="{{ route('families.index') }}" class="kt-menu-link border border-transparent items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-foreground hover:bg-accent/60 hover:text-primary">Danh sách family</a>
                <a href="{{ route('families.create') }}" class="kt-menu-link border border-transparent items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-foreground hover:bg-accent/60 hover:text-primary">Thêm family</a>
                <span class="w-px h-5 bg-border mx-1" aria-hidden="true"></span>
                <a href="{{ route('collaborators.index') }}" class="kt-menu-link border border-transparent items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-foreground hover:bg-accent/60 hover:text-primary">Danh sách hướng dẫn</a>
                <a href="{{ route('collaborators.create') }}" class="kt-menu-link border border-transparent items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-foreground hover:bg-accent/60 hover:text-primary">Thêm form hướng dẫn</a>
            </div>
        </div>
    </nav>

    <main class="flex-1">
        <div style="height: 242px; width: 100%; position: relative;">
            <img style="width:100%; height:100%; object-fit: cover;" src="https://muakey.com/images/cover/product-cover-960.png" alt="">
            <img src="{{ asset('images/ytb-pre-logo.webp') }}" id="ytb-pre-logo" style="position: absolute; top: 50%; left: 18%; border-radius: 24px;" alt="">
            <script>
                window.addEventListener('resize', function() {
                    if (window.innerWidth <= 1719) {
                        document.getElementById('ytb-pre-logo').style.top = 0;
                    } else {
                        document.getElementById('ytb-pre-logo').style.top = '50%';
                    }
                });
                if (window.innerWidth <= 1719) {
                    document.getElementById('ytb-pre-logo').style.top = 0;
                } else {
                    document.getElementById('ytb-pre-logo').style.top = '50%';
                }
            </script>
        </div>
        <div class="">
            <div class="text-center" style="padding: 0 10px;">
                <h1 class="text-3xl lg:text-4xl font-bold text-foreground mb-4" style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <img src="https://muakey.com/favicon.ico" alt="Muakey" class="h-8 w-8" /> Hướng dẫn Cộng tác viên YouTube Premium <img src="{{ asset('images/youtube-premium-logo.png') }}" alt="Muakey" class="h-20 w-20" />
                </h1>
                <p class="text-lg text-secondary-foreground max-w-2xl mx-auto">
                    Tài liệu hướng dẫn chi tiết cho các cộng tác viên của <a href="https://muakey.com" target="_blank" style="color: #2563eb; text-decoration: underline;">Muakey</a> về cách sử dụng và quản lý YouTube Premium
                </p>
            </div>

            <div class="max-w-4xl mx-auto kt-container-fixed py-8 lg:py-12" style="margin-top: 30px;">
                @forelse ($activeCollaborators as $collaborator)
                    <div class="kt-card mb-6">
                        <div class="kt-card-body p-6 lg:p-8">
                            <div class="guide-content" style="padding : 0 24px">
                                {!! $collaborator->content !!}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="kt-card p-8 text-center">
                        <i class="ki-filled ki-information text-muted-foreground text-5xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-foreground mb-2">Chưa có nội dung hướng dẫn</h3>
                        <p class="text-secondary-foreground">Nội dung hướng dẫn sẽ được cập nhật sớm nhất.</p>
                    </div>
                @endforelse
            </div>

            <div class="kt-container-fixed">
                <div class="max-w-4xl mx-auto mt-12" style="margin: 24px 0; ">
                    <div class="kt-card bg-accent/60" style="padding: 24px">
                        <div class="kt-card-body p-6">
                            <div class="flex items-start gap-4">
                                <i class="ki-filled ki-information text-primary text-2xl mt-1"></i>
                                <div>
                                    <h3 class="text-lg font-semibold text-foreground mb-2">Cần hỗ trợ?</h3>
                                    <p class="text-sm text-secondary-foreground mb-3">
                                        Nếu bạn có bất kỳ câu hỏi nào về hướng dẫn này, vui lòng liên hệ với đội ngũ hỗ trợ của Muakey.
                                    </p>
                                    <div class="flex flex-wrap gap-4 text-sm">
                                        <a href="https://muakey.com" target="_blank" class="text-primary hover:underline">
                                            <i class="ki-filled ki-global"></i> Website: muakey.com
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="{{ asset('assets/js/core.bundle.js') }}"></script>
    <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
</body>

</html>
