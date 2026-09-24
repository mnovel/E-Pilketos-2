<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @php
        $appUrl = rtrim(config('app.url', 'https://sewagati.it.com'), '/');
        $appName = config('app.name', 'Pilketos');
        $currentUrl = $appUrl . (request()->path() !== '/' ? '/' . request()->path() : '');
        $ogImage = $appUrl . '/storage/assets/images/og-image.jpg';
        $favicon = asset('storage/assets/images/favicon.ico');
        $pageTitle = $appName . ' | Pemilihan Ketua OSIS Online SMAN 1 Kota Pasuruan';

        $schemaOrg = [
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            'name' => 'SMA Negeri 1 Kota Pasuruan',
            'alternateName' => 'SMAN 1 Pasuruan',
            'url' => $appUrl,
            'logo' => $favicon,
            'description' => 'Aplikasi E-Pilketos (Pemilihan Ketua OSIS) online SMA Negeri 1 Kota Pasuruan',
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Kota Pasuruan',
                'addressRegion' => 'Jawa Timur',
                'addressCountry' => 'ID',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => -7.6375421158588255,
                'longitude' => 112.90391879447726,
            ],
            'sameAs' => [$appUrl],
        ];

        $schemaApp = [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => 'E-Pilketos SMAN 1 Kota Pasuruan',
            'url' => $appUrl,
            'applicationCategory' => 'EducationalApplication',
            'operatingSystem' => 'Web Browser',
            'description' => 'Sistem pemilihan ketua OSIS online untuk SMA Negeri 1 Kota Pasuruan',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'IDR',
            ],
        ];

        $schemaBreadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => $appUrl,
                ],
            ],
        ];
    @endphp

    <!-- ========== PRIMARY META TAGS ========== -->
    <title>@yield('title', $pageTitle)</title>
    <meta name="title" content="@yield('title', $pageTitle)">
    <meta name="description" content="@yield('description', 'E-Pilketos SMA Negeri 1 Kota Pasuruan - Sistem Pemilihan Ketua OSIS secara online, cepat, transparan, dan real-time. Voting digital untuk siswa SMAN 1 Kota Pasuruan.')">
    <meta name="keywords"
        content="e-pilketos, pilketos online, pemilihan ketua osis, SMAN 1 Kota Pasuruan, SMA Negeri 1 Pasuruan, voting online, e-voting sekolah, OSIS Pasuruan, pemilu sekolah, pilketos digital">
    <meta name="author" content="SMA Negeri 1 Kota Pasuruan">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="language" content="Indonesian">
    <meta name="revisit-after" content="7 days">
    <meta name="rating" content="general">
    <meta name="distribution" content="global">

    <!-- ========== CANONICAL URL ========== -->
    <link rel="canonical" href="{{ $currentUrl }}">

    <!-- ========== OPEN GRAPH / FACEBOOK ========== -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:title" content="@yield('title', $pageTitle)">
    <meta property="og:description" content="Sistem Pemilihan Ketua OSIS online SMAN 1 Kota Pasuruan. Voting digital yang cepat, aman, transparan, dan hasil real-time.">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="E-Pilketos SMAN 1 Kota Pasuruan">
    <meta property="og:site_name" content="E-Pilketos SMAN 1 Kota Pasuruan">
    <meta property="og:locale" content="id_ID">

    <!-- ========== TWITTER CARD ========== -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $currentUrl }}">
    <meta name="twitter:title" content="@yield('title', $pageTitle)">
    <meta name="twitter:description" content="Sistem Pemilihan Ketua OSIS online SMAN 1 Kota Pasuruan. Voting digital yang cepat, aman, dan transparan.">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="E-Pilketos SMAN 1 Kota Pasuruan">

    <!-- ========== GEO TAGS (Local SEO) ========== -->
    <meta name="geo.region" content="ID-JI">
    <meta name="geo.placename" content="Kota Pasuruan, Jawa Timur">
    <meta name="geo.position" content="-7.6375421158588255;112.90391879447726">
    <meta name="ICBM" content="-7.6375421158588255, 112.90391879447726">

    <!-- ========== THEME & MOBILE ========== -->
    <meta name="theme-color" content="#1a2e1a">
    <meta name="msapplication-TileColor" content="#1a2e1a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="E-Pilketos SMAN 1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">

    <!-- ========== FAVICON ========== -->
    <link rel="icon" type="image/x-icon" href="{{ $favicon }}">
    <link rel="apple-touch-icon" href="{{ $favicon }}">

    <!-- ========== STRUCTURED DATA (JSON-LD) ========== -->
    <script type="application/ld+json">
    {!! json_encode($schemaOrg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <script type="application/ld+json">
    {!! json_encode($schemaApp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <script type="application/ld+json">
    {!! json_encode($schemaBreadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <!-- ========== PRECONNECT ========== -->
    <link rel="preconnect" href="{{ $appUrl }}">
    <link rel="dns-prefetch" href="{{ $appUrl }}">
    <link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#1a2e1a',
                            mid: '#2d5a3d',
                            lime: '#c6f135',
                            limeHover: '#a8d92d',
                            cream: '#e8f5c8',
                            deep: '#0f1f0f',
                        },
                    },
                },
            },
        };
    </script>

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-slate-100 text-slate-800 font-sans overflow-x-hidden">

    {{-- ==========================================
         HEADER
         ========================================== --}}
    <header class="bg-brand-dark text-white py-4 sticky top-0 z-40 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
            <a href="{{ url('/') }}" class="text-brand-lime font-extrabold text-2xl flex items-center gap-2 hover:text-brand-lime">
                <i class="bi bi-asterisk"></i> Pilketos
            </a>

            <nav class="flex gap-2 items-center">
                <a href="{{ route('cek-status.index') }}"
                    class="hidden md:inline-flex items-center gap-1 text-white/80 hover:text-brand-lime hover:bg-brand-lime/10 px-4 py-2 rounded-lg text-sm font-medium transition">
                    <i class="bi bi-search"></i> Cek Status
                </a>
                <a href="{{ route('hasil.index') }}"
                    class="hidden md:inline-flex items-center gap-1 text-white/80 hover:text-brand-lime hover:bg-brand-lime/10 px-4 py-2 rounded-lg text-sm font-medium transition">
                    <i class="bi bi-trophy"></i> Hasil
                </a>

                @auth
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isOperator() ? route('operator.dashboard') : route('voter.dashboard')) }}"
                        class="bg-brand-lime hover:bg-brand-limeHover text-brand-dark px-5 py-2 rounded-lg font-bold text-sm transition hover:-translate-y-0.5 inline-flex items-center gap-1.5">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="bg-brand-lime hover:bg-brand-limeHover text-brand-dark px-5 py-2 rounded-lg font-bold text-sm transition hover:-translate-y-0.5 inline-flex items-center gap-1.5">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    {{-- ==========================================
         HERO
         ========================================== --}}
    <section class="relative overflow-hidden text-white py-16 md:py-24 bg-gradient-to-br from-brand-dark to-brand-mid">
        <div class="absolute -top-52 -right-52 w-[600px] h-[600px] bg-brand-lime rounded-full blur-[120px] opacity-15 pointer-events-none"></div>
        <div class="absolute -bottom-40 -left-40 w-[400px] h-[400px] bg-brand-limeHover rounded-full blur-[100px] opacity-10 pointer-events-none"></div>

        <div class="relative max-w-7xl mx-auto px-4 grid lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7">
                <div class="inline-block bg-brand-lime/15 text-brand-lime border border-brand-lime/30 px-4 py-2 rounded-full text-sm font-semibold mb-6">
                    <i class="bi bi-shield-check"></i> Sistem Pemilihan Digital
                </div>

                <h1 class="text-4xl md:text-5xl lg:text-[3.2rem] font-extrabold leading-tight mb-5">
                    Pemilihan Ketua OSIS <br>
                    <span class="text-brand-lime">Digital & Modern</span>
                </h1>

                <p class="text-lg opacity-85 max-w-xl mb-8 leading-relaxed">
                    Suara Anda menentukan masa depan sekolah.
                    Voting cepat, aman, dan transparan dengan teknologi QR Code.
                </p>

                <div class="flex flex-wrap gap-3">
                    @if ($activeElection && $activeElection->status === \App\Enums\ElectionStatus::ACTIVE)
                        <a href="{{ auth()->check() ? (auth()->user()->isVoter() ? route('voter.scan') : '#') : route('login') }}"
                            class="bg-brand-lime hover:bg-brand-limeHover text-brand-dark px-8 py-3.5 rounded-xl font-bold inline-flex items-center gap-2 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-lime/30">
                            <i class="bi bi-qr-code-scan"></i> Mulai Voting
                        </a>
                    @elseif ($activeElection)
                        <a href="{{ auth()->check() ? route('login') : route('register') }}"
                            class="bg-brand-lime hover:bg-brand-limeHover text-brand-dark px-8 py-3.5 rounded-xl font-bold inline-flex items-center gap-2 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-lime/30">
                            <i class="bi bi-person-plus"></i> Daftar Sekarang
                        </a>
                    @endif

                    <a href="{{ route('cek-status.index') }}"
                        class="border-2 border-white/30 hover:bg-white/10 hover:border-white/50 text-white px-8 py-3.5 rounded-xl font-bold inline-flex items-center gap-2 transition">
                        <i class="bi bi-search"></i> Cek Status
                    </a>
                </div>
            </div>

            <div class="hidden lg:flex lg:col-span-5 items-center justify-center">
                <div class="w-[280px] h-[280px] rounded-full bg-brand-lime/10 flex items-center justify-center animate-[float_4s_ease-in-out_infinite]">
                    <i class="bi bi-check2-square text-brand-lime text-[8rem]"></i>
                </div>
            </div>
        </div>
    </section>

    {{-- ==========================================
         COUNTDOWN / STATUS CARD
         ========================================== --}}
    @if ($activeElection)
        <section class="relative z-10 -mt-14">
            <div class="max-w-7xl mx-auto px-4">
                <div class="bg-white rounded-3xl shadow-2xl p-6 md:p-10 text-center">

                    @if ($activeElection->status === \App\Enums\ElectionStatus::ACTIVE)
                        <div class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-800 px-4 py-2 rounded-full text-sm font-bold mb-4">
                            <span class="w-2.5 h-2.5 bg-emerald-600 rounded-full animate-pulse"></span>
                            SEDANG BERLANGSUNG
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold text-brand-dark mb-2">{{ $activeElection->title }}</h3>
                        <p class="text-slate-500 mb-6">
                            Voting sedang berlangsung sampai
                            <strong class="text-brand-dark">{{ $activeElection->end_at->translatedFormat('d M Y, H:i') }}</strong>
                        </p>

                        <div class="text-sm font-semibold text-slate-500 uppercase tracking-widest mb-5">Berakhir dalam</div>
                        <div class="flex gap-3 justify-center flex-wrap" id="countdown" data-target="{{ $activeElection->end_at->toIso8601String() }}">
                            <div class="bg-brand-dark text-white rounded-2xl px-5 py-4 md:px-6 min-w-[80px] md:min-w-[100px] relative overflow-hidden">
                                <div class="absolute top-0 left-0 right-0 h-[3px] bg-brand-lime"></div>
                                <div class="text-3xl md:text-4xl font-extrabold text-brand-lime leading-none" id="cd-days">0</div>
                                <div class="text-[0.65rem] md:text-xs uppercase tracking-widest opacity-60 mt-2">Hari</div>
                            </div>
                            <div class="bg-brand-dark text-white rounded-2xl px-5 py-4 md:px-6 min-w-[80px] md:min-w-[100px] relative overflow-hidden">
                                <div class="absolute top-0 left-0 right-0 h-[3px] bg-brand-lime"></div>
                                <div class="text-3xl md:text-4xl font-extrabold text-brand-lime leading-none" id="cd-hours">0</div>
                                <div class="text-[0.65rem] md:text-xs uppercase tracking-widest opacity-60 mt-2">Jam</div>
                            </div>
                            <div class="bg-brand-dark text-white rounded-2xl px-5 py-4 md:px-6 min-w-[80px] md:min-w-[100px] relative overflow-hidden">
                                <div class="absolute top-0 left-0 right-0 h-[3px] bg-brand-lime"></div>
                                <div class="text-3xl md:text-4xl font-extrabold text-brand-lime leading-none" id="cd-minutes">0</div>
                                <div class="text-[0.65rem] md:text-xs uppercase tracking-widest opacity-60 mt-2">Menit</div>
                            </div>
                            <div class="bg-brand-dark text-white rounded-2xl px-5 py-4 md:px-6 min-w-[80px] md:min-w-[100px] relative overflow-hidden">
                                <div class="absolute top-0 left-0 right-0 h-[3px] bg-brand-lime"></div>
                                <div class="text-3xl md:text-4xl font-extrabold text-brand-lime leading-none" id="cd-seconds">0</div>
                                <div class="text-[0.65rem] md:text-xs uppercase tracking-widest opacity-60 mt-2">Detik</div>
                            </div>
                        </div>
                    @elseif (in_array($activeElection->status, [\App\Enums\ElectionStatus::DRAFT, \App\Enums\ElectionStatus::ACTIVE]))
                        <div class="inline-block bg-amber-100 text-amber-700 border border-amber-300 px-4 py-2 rounded-full text-sm font-semibold mb-4">
                            <i class="bi bi-clock-history"></i> Segera Dimulai
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold text-brand-dark mb-2">{{ $activeElection->title }}</h3>
                        <p class="text-slate-500 mb-6">
                            Voting akan dimulai pada
                            <strong class="text-brand-dark">{{ $activeElection->start_at->translatedFormat('d M Y, H:i') }}</strong>
                        </p>

                        <div class="text-sm font-semibold text-slate-500 uppercase tracking-widest mb-5">Dimulai dalam</div>
                        <div class="flex gap-3 justify-center flex-wrap" id="countdown" data-target="{{ $activeElection->start_at->toIso8601String() }}">
                            <div class="bg-brand-dark text-white rounded-2xl px-5 py-4 md:px-6 min-w-[80px] md:min-w-[100px] relative overflow-hidden">
                                <div class="absolute top-0 left-0 right-0 h-[3px] bg-brand-lime"></div>
                                <div class="text-3xl md:text-4xl font-extrabold text-brand-lime leading-none" id="cd-days">0</div>
                                <div class="text-[0.65rem] md:text-xs uppercase tracking-widest opacity-60 mt-2">Hari</div>
                            </div>
                            <div class="bg-brand-dark text-white rounded-2xl px-5 py-4 md:px-6 min-w-[80px] md:min-w-[100px] relative overflow-hidden">
                                <div class="absolute top-0 left-0 right-0 h-[3px] bg-brand-lime"></div>
                                <div class="text-3xl md:text-4xl font-extrabold text-brand-lime leading-none" id="cd-hours">0</div>
                                <div class="text-[0.65rem] md:text-xs uppercase tracking-widest opacity-60 mt-2">Jam</div>
                            </div>
                            <div class="bg-brand-dark text-white rounded-2xl px-5 py-4 md:px-6 min-w-[80px] md:min-w-[100px] relative overflow-hidden">
                                <div class="absolute top-0 left-0 right-0 h-[3px] bg-brand-lime"></div>
                                <div class="text-3xl md:text-4xl font-extrabold text-brand-lime leading-none" id="cd-minutes">0</div>
                                <div class="text-[0.65rem] md:text-xs uppercase tracking-widest opacity-60 mt-2">Menit</div>
                            </div>
                            <div class="bg-brand-dark text-white rounded-2xl px-5 py-4 md:px-6 min-w-[80px] md:min-w-[100px] relative overflow-hidden">
                                <div class="absolute top-0 left-0 right-0 h-[3px] bg-brand-lime"></div>
                                <div class="text-3xl md:text-4xl font-extrabold text-brand-lime leading-none" id="cd-seconds">0</div>
                                <div class="text-[0.65rem] md:text-xs uppercase tracking-widest opacity-60 mt-2">Detik</div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </section>
    @endif

    {{-- ==========================================
         FEATURES
         ========================================== --}}
    <section class="py-16 md:py-20">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-3">Kenapa Pilketos Digital?</h2>
                <p class="text-slate-500 text-lg">Pemilihan modern, cepat, dan transparan</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-3xl p-8 border-2 border-transparent shadow-md hover:shadow-xl hover:border-brand-lime hover:-translate-y-2 transition">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-100 flex items-center justify-center mb-5">
                        <i class="bi bi-lightning-charge-fill text-emerald-600 text-3xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-brand-dark mb-2">Cepat</h4>
                    <p class="text-slate-500 text-sm leading-relaxed m-0">Proses voting hanya 30 detik dengan scan QR code</p>
                </div>
                <div class="bg-white rounded-3xl p-8 border-2 border-transparent shadow-md hover:shadow-xl hover:border-brand-lime hover:-translate-y-2 transition">
                    <div class="w-16 h-16 rounded-2xl bg-blue-100 flex items-center justify-center mb-5">
                        <i class="bi bi-shield-lock-fill text-blue-600 text-3xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-brand-dark mb-2">Aman</h4>
                    <p class="text-slate-500 text-sm leading-relaxed m-0">Suara anonim dan terenkripsi. Tidak ada yang tahu pilihan Anda</p>
                </div>
                <div class="bg-white rounded-3xl p-8 border-2 border-transparent shadow-md hover:shadow-xl hover:border-brand-lime hover:-translate-y-2 transition">
                    <div class="w-16 h-16 rounded-2xl bg-amber-100 flex items-center justify-center mb-5">
                        <i class="bi bi-graph-up-arrow text-amber-600 text-3xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-brand-dark mb-2">Transparan</h4>
                    <p class="text-slate-500 text-sm leading-relaxed m-0">Hasil real-time dan dapat dilihat setelah voting selesai</p>
                </div>
                <div class="bg-white rounded-3xl p-8 border-2 border-transparent shadow-md hover:shadow-xl hover:border-brand-lime hover:-translate-y-2 transition">
                    <div class="w-16 h-16 rounded-2xl bg-rose-100 flex items-center justify-center mb-5">
                        <i class="bi bi-phone-fill text-rose-600 text-3xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-brand-dark mb-2">Modern</h4>
                    <p class="text-slate-500 text-sm leading-relaxed m-0">Cukup dengan HP dan QR code, tidak perlu kertas</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ==========================================
         CARA VOTE
         ========================================== --}}
    <section class="py-16 md:py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-3">Cara Menggunakan Hak Suara</h2>
                <p class="text-slate-500 text-lg">Hanya 4 langkah mudah, selesai dalam 30 detik</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-y-12 gap-x-5">
                <div class="relative bg-white rounded-3xl px-6 pt-10 pb-8 text-center shadow-md hover:shadow-xl hover:-translate-y-1.5 transition">
                    <div
                        class="absolute -top-5 left-1/2 -translate-x-1/2 w-12 h-12 bg-brand-lime text-brand-dark rounded-full flex items-center justify-center font-extrabold text-xl border-4 border-white shadow-lg shadow-brand-lime/40">
                        1</div>
                    <div class="w-20 h-20 mx-auto rounded-full bg-brand-dark text-brand-lime flex items-center justify-center text-3xl my-4"><i class="bi bi-box-arrow-in-right"></i></div>
                    <div class="text-lg font-bold text-brand-dark mb-2">Login Akun</div>
                    <p class="text-slate-500 text-sm leading-relaxed m-0">Login dengan NIS dan password yang diberikan panitia.</p>
                </div>
                <div class="relative bg-white rounded-3xl px-6 pt-10 pb-8 text-center shadow-md hover:shadow-xl hover:-translate-y-1.5 transition">
                    <div
                        class="absolute -top-5 left-1/2 -translate-x-1/2 w-12 h-12 bg-brand-lime text-brand-dark rounded-full flex items-center justify-center font-extrabold text-xl border-4 border-white shadow-lg shadow-brand-lime/40">
                        2</div>
                    <div class="w-20 h-20 mx-auto rounded-full bg-brand-dark text-brand-lime flex items-center justify-center text-3xl my-4"><i class="bi bi-geo-alt-fill"></i></div>
                    <div class="text-lg font-bold text-brand-dark mb-2">Datang ke Bilik</div>
                    <p class="text-slate-500 text-sm leading-relaxed m-0">Datang ke bilik suara sesuai jadwal sesi kelas Anda.</p>
                </div>
                <div class="relative bg-white rounded-3xl px-6 pt-10 pb-8 text-center shadow-md hover:shadow-xl hover:-translate-y-1.5 transition">
                    <div
                        class="absolute -top-5 left-1/2 -translate-x-1/2 w-12 h-12 bg-brand-lime text-brand-dark rounded-full flex items-center justify-center font-extrabold text-xl border-4 border-white shadow-lg shadow-brand-lime/40">
                        3</div>
                    <div class="w-20 h-20 mx-auto rounded-full bg-brand-dark text-brand-lime flex items-center justify-center text-3xl my-4"><i class="bi bi-qr-code-scan"></i></div>
                    <div class="text-lg font-bold text-brand-dark mb-2">Scan QR Code</div>
                    <p class="text-slate-500 text-sm leading-relaxed m-0">Scan QR di layar device untuk memulai voting.</p>
                </div>
                <div class="relative bg-white rounded-3xl px-6 pt-10 pb-8 text-center shadow-md hover:shadow-xl hover:-translate-y-1.5 transition">
                    <div
                        class="absolute -top-5 left-1/2 -translate-x-1/2 w-12 h-12 bg-brand-lime text-brand-dark rounded-full flex items-center justify-center font-extrabold text-xl border-4 border-white shadow-lg shadow-brand-lime/40">
                        4</div>
                    <div class="w-20 h-20 mx-auto rounded-full bg-brand-dark text-brand-lime flex items-center justify-center text-3xl my-4"><i class="bi bi-check2-square"></i></div>
                    <div class="text-lg font-bold text-brand-dark mb-2">Pilih Kandidat</div>
                    <p class="text-slate-500 text-sm leading-relaxed m-0">Pilih kandidat favorit Anda di layar device. Selesai!</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ==========================================
         CANDIDATES SECTION
         ========================================== --}}
    @if ($activeElection && $activeElection->candidates->count() > 0)
        <section class="py-16 md:py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-brand-dark mb-3">Kenalan dengan Kandidat</h2>
                    <p class="text-slate-500 text-lg">{{ $activeElection->candidates->count() }} kandidat siap memimpin OSIS</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 justify-center">
                    @foreach ($activeElection->candidates as $c)
                        <div x-data="{ open: false }" class="contents">
                            {{-- Card Kandidat --}}
                            <div class="bg-white rounded-3xl overflow-hidden border-2 border-slate-100 shadow-lg hover:shadow-2xl hover:border-brand-lime hover:-translate-y-2 transition">
                                <div class="relative h-[240px] md:h-[260px] overflow-hidden bg-gradient-to-br from-brand-cream to-emerald-100 group">
                                    @if ($c->foto)
                                        <img src="{{ asset('storage/' . $c->foto) }}" alt="{{ $c->nama }}" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-8xl font-extrabold text-brand-dark opacity-30">
                                            {{ strtoupper(substr($c->nama, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="absolute top-4 left-4 bg-brand-dark text-brand-lime px-4 py-2 rounded-full text-sm font-extrabold shadow-lg">
                                        No. {{ $c->no_urut }}
                                    </div>
                                </div>

                                <div class="p-6">
                                    <div class="text-2xl font-extrabold text-brand-dark mb-1.5">{{ $c->nama }}</div>
                                    <div class="text-slate-500 text-sm mb-4">
                                        <i class="bi bi-mortarboard-fill mr-1"></i>
                                        {{ $c->classRoom?->name ?? '-' }}
                                    </div>

                                    <div class="bg-slate-50 border-l-4 border-brand-lime rounded-lg px-4 py-3 mb-5">
                                        <div class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wide mb-1">
                                            <i class="bi bi-bullseye"></i> Visi
                                        </div>
                                        <p class="text-brand-dark text-sm italic leading-snug m-0 line-clamp-3">"{{ $c->visi }}"</p>
                                    </div>

                                    <button type="button" @click="$dispatch('open-candidate-{{ $c->id }}')"
                                        class="w-full bg-brand-dark hover:bg-brand-mid text-brand-lime border-0 rounded-lg px-5 py-3 font-bold text-sm cursor-pointer inline-flex items-center justify-center gap-1.5 transition hover:-translate-y-0.5">
                                        <i class="bi bi-info-circle"></i> Lihat Detail
                                    </button>
                                </div>
                            </div>

                            {{-- MODAL DETAIL KANDIDAT --}}
                            <div x-data="{ open: false }" @open-candidate-{{ $c->id }}.window="open = true" x-effect="document.body.style.overflow = open ? 'hidden' : ''" x-show="open"
                                x-cloak @keydown.escape.window="open = false" class="fixed inset-0 z-50 flex items-center justify-center p-2 md:p-4">

                                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="open = false"
                                    class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

                                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="relative bg-white rounded-3xl max-w-3xl w-full max-h-[calc(100vh-1rem)] flex flex-col overflow-hidden shadow-2xl">

                                    <div class="bg-gradient-to-br from-brand-dark to-brand-mid px-6 py-4 flex items-center justify-between shrink-0">
                                        <div>
                                            <div class="text-[0.68rem] uppercase tracking-widest text-white/70">Kandidat</div>
                                            <h5 class="mb-0 text-lg font-bold text-brand-lime">
                                                No. {{ $c->no_urut }} — {{ $c->nama }}
                                            </h5>
                                        </div>
                                        <button type="button" @click="open = false"
                                            class="text-white/80 hover:text-white text-2xl leading-none w-9 h-9 flex items-center justify-center rounded-lg hover:bg-white/10 transition">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-4 md:gap-6 p-4 md:p-6 flex-1 min-h-0 overflow-y-auto">
                                        <div class="w-full aspect-[16/9] md:aspect-[3/4] rounded-2xl overflow-hidden bg-gradient-to-br from-brand-cream to-emerald-100">
                                            @if ($c->foto)
                                                <img src="{{ asset('storage/' . $c->foto) }}" alt="{{ $c->nama }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-6xl md:text-7xl font-extrabold text-brand-dark opacity-30">
                                                    {{ strtoupper(substr($c->nama, 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="min-h-0">
                                            <div class="grid grid-cols-3 gap-2 p-3 bg-slate-50 rounded-2xl mb-5">
                                                <div class="text-center">
                                                    <div class="text-[0.65rem] uppercase tracking-wider font-extrabold text-slate-500 mb-1">No. Urut</div>
                                                    <div class="text-base font-extrabold text-brand-dark">{{ $c->no_urut }}</div>
                                                </div>
                                                <div class="text-center border-l border-dashed border-slate-300">
                                                    <div class="text-[0.65rem] uppercase tracking-wider font-extrabold text-slate-500 mb-1">Kelas</div>
                                                    <div class="text-base font-extrabold text-brand-dark">{{ $c->classRoom?->name ?? '-' }}</div>
                                                </div>
                                                <div class="text-center border-l border-dashed border-slate-300">
                                                    <div class="text-[0.65rem] uppercase tracking-wider font-extrabold text-slate-500 mb-1">Nama</div>
                                                    <div class="text-base font-extrabold text-brand-dark break-words">{{ $c->nama }}</div>
                                                </div>
                                            </div>

                                            <div class="mb-5 last:mb-0">
                                                <span
                                                    class="inline-flex items-center gap-1.5 text-[0.68rem] font-extrabold uppercase tracking-widest text-brand-dark bg-brand-cream px-3 py-1 rounded-full mb-2">
                                                    <i class="bi bi-bullseye"></i> Visi
                                                </span>
                                                <p class="text-slate-600 text-sm leading-relaxed whitespace-pre-line break-words m-0">
                                                    {{ $c->visi }}
                                                </p>
                                            </div>

                                            @if ($c->misi)
                                                <div class="mb-5 last:mb-0">
                                                    <span
                                                        class="inline-flex items-center gap-1.5 text-[0.68rem] font-extrabold uppercase tracking-widest text-brand-dark bg-brand-cream px-3 py-1 rounded-full mb-2">
                                                        <i class="bi bi-list-check"></i> Misi
                                                    </span>
                                                    <p class="text-slate-600 text-sm leading-relaxed whitespace-pre-line break-words m-0">
                                                        {{ $c->misi }}
                                                    </p>
                                                </div>
                                            @endif

                                            @if ($c->program_kerja)
                                                <div class="mb-5 last:mb-0">
                                                    <span
                                                        class="inline-flex items-center gap-1.5 text-[0.68rem] font-extrabold uppercase tracking-widest text-brand-dark bg-brand-cream px-3 py-1 rounded-full mb-2">
                                                        <i class="bi bi-clipboard-check"></i> Program Kerja
                                                    </span>
                                                    <p class="text-slate-600 text-sm leading-relaxed whitespace-pre-line break-words m-0">
                                                        {{ $c->program_kerja }}
                                                    </p>
                                                </div>
                                            @endif

                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ==========================================
         CTA SECTION
         ========================================== --}}
    <section class="relative overflow-hidden text-white py-20 text-center bg-gradient-to-br from-brand-dark to-brand-mid">
        <div class="absolute -top-24 -right-24 w-[400px] h-[400px] bg-brand-lime rounded-full blur-[120px] opacity-15 pointer-events-none"></div>
        <div class="relative max-w-7xl mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-extrabold mb-4">Siap Menggunakan Hak Suara Anda?</h2>
            <p class="opacity-85 text-lg mb-8">Bergabunglah dalam pemilihan digital yang modern dan transparan</p>

            <div class="flex gap-3 justify-center flex-wrap">
                @guest
                    <a href="{{ route('register') }}"
                        class="bg-brand-lime hover:bg-brand-limeHover text-brand-dark px-8 py-3.5 rounded-xl font-bold inline-flex items-center gap-2 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-lime/30">
                        <i class="bi bi-person-plus"></i> Daftar Sekarang
                    </a>
                    <a href="{{ route('login') }}"
                        class="border-2 border-white/30 hover:bg-white/10 hover:border-white/50 text-white px-8 py-3.5 rounded-xl font-bold inline-flex items-center gap-2 transition">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                @else
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isOperator() ? route('operator.dashboard') : route('voter.dashboard')) }}"
                        class="bg-brand-lime hover:bg-brand-limeHover text-brand-dark px-8 py-3.5 rounded-xl font-bold inline-flex items-center gap-2 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-lime/30">
                        <i class="bi bi-speedometer2"></i> Ke Dashboard
                    </a>
                @endguest
            </div>
        </div>
    </section>

    {{-- ==========================================
         FOOTER
         ========================================== --}}
    <footer class="bg-brand-deep text-white/60 py-8 text-sm">
        <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-3">
            <div>
                <i class="bi bi-asterisk text-brand-lime"></i>
                <strong class="text-white/90">Pilketos</strong> &copy; {{ date('Y') }} {{ config('app.name', 'Pilketos') }}
            </div>
            <div class="flex gap-6">
                <a href="{{ route('cek-status.index') }}" class="hover:text-brand-lime transition">Cek Status</a>
                <a href="{{ route('hasil.index') }}" class="hover:text-brand-lime transition">Hasil</a>
                <a href="{{ route('login') }}" class="hover:text-brand-lime transition">Login</a>
            </div>
        </div>
    </footer>

    {{-- ==========================================
         COUNTDOWN SCRIPT
         ========================================== --}}
    @if ($activeElection)
        <script>
            (function() {
                const countdownEl = document.getElementById('countdown');
                if (!countdownEl) return;

                const target = new Date(countdownEl.dataset.target).getTime();

                const daysEl = document.getElementById('cd-days');
                const hoursEl = document.getElementById('cd-hours');
                const minutesEl = document.getElementById('cd-minutes');
                const secondsEl = document.getElementById('cd-seconds');

                function updateCountdown() {
                    const now = new Date().getTime();
                    const diff = target - now;

                    if (diff <= 0) {
                        daysEl.textContent = '0';
                        hoursEl.textContent = '0';
                        minutesEl.textContent = '0';
                        secondsEl.textContent = '0';
                        setTimeout(() => window.location.reload(), 5000);
                        return;
                    }

                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                    daysEl.textContent = days;
                    hoursEl.textContent = String(hours).padStart(2, '0');
                    minutesEl.textContent = String(minutes).padStart(2, '0');
                    secondsEl.textContent = String(seconds).padStart(2, '0');
                }

                updateCountdown();
                setInterval(updateCountdown, 1000);
            })();
        </script>
    @endif

</body>

</html>
