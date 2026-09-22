<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilketos Digital — Pemilihan Ketua OSIS</title>
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f5f7fa;
            overflow-x: hidden;
        }

        /* ==========================================
           HEADER
           ========================================== */
        .lp-header {
            background: #1a2e1a;
            color: white;
            padding: 18px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .lp-brand {
            color: #c6f135;
            font-weight: 800;
            font-size: 1.5rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .lp-brand:hover {
            color: #c6f135;
        }

        .lp-nav {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .lp-nav-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .lp-nav-link:hover {
            color: #c6f135;
            background: rgba(198, 241, 53, 0.1);
        }

        .lp-nav-btn {
            background: #c6f135;
            color: #1a2e1a;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .lp-nav-btn:hover {
            background: #a8d92d;
            color: #1a2e1a;
            transform: translateY(-2px);
        }

        /* ==========================================
           HERO
           ========================================== */
        .lp-hero {
            background: linear-gradient(135deg, #1a2e1a 0%, #2d5a3d 100%);
            color: white;
            padding: 80px 0 100px;
            position: relative;
            overflow: hidden;
        }

        .lp-hero::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: #c6f135;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.15;
            top: -200px;
            right: -200px;
        }

        .lp-hero::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: #a8d92d;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.1;
            bottom: -150px;
            left: -150px;
        }

        .lp-hero-content {
            position: relative;
            z-index: 1;
        }

        .lp-hero-badge {
            display: inline-block;
            background: rgba(198, 241, 53, 0.15);
            color: #c6f135;
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 24px;
            border: 1px solid rgba(198, 241, 53, 0.3);
        }

        .lp-hero h1 {
            font-size: 3.2rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
        }

        .lp-hero h1 span {
            color: #c6f135;
        }

        .lp-hero p {
            font-size: 1.1rem;
            opacity: 0.85;
            max-width: 500px;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .lp-hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .lp-btn {
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .lp-btn-primary {
            background: #c6f135;
            color: #1a2e1a;
        }

        .lp-btn-primary:hover {
            background: #a8d92d;
            color: #1a2e1a;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(198, 241, 53, 0.3);
        }

        .lp-btn-outline {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .lp-btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* Hero Illustration */
        .lp-hero-illustration {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lp-hero-icon {
            width: 280px;
            height: 280px;
            background: rgba(198, 241, 53, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            animation: float 4s ease-in-out infinite;
        }

        .lp-hero-icon i {
            font-size: 8rem;
            color: #c6f135;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        /* ==========================================
           COUNTDOWN CARD
           ========================================== */
        .countdown-section {
            margin-top: -60px;
            position: relative;
            z-index: 2;
        }

        .countdown-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .countdown-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .countdown-grid {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .countdown-box {
            background: #1a2e1a;
            color: white;
            border-radius: 16px;
            padding: 20px 24px;
            min-width: 100px;
            position: relative;
            overflow: hidden;
        }

        .countdown-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #c6f135;
        }

        .countdown-value {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
            color: #c6f135;
        }

        .countdown-unit {
            font-size: 0.75rem;
            opacity: 0.6;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 8px;
        }

        /* ==========================================
           LIVE STATUS
           ========================================== */
        .status-live {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #d1e7dd;
            color: #0f5132;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .status-dot-live {
            width: 10px;
            height: 10px;
            background: #198754;
            border-radius: 50%;
            animation: blink 1.5s ease-in-out infinite;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }
        }

        /* ==========================================
           FEATURES SECTION
           ========================================== */
        .features-section {
            padding: 80px 0;
        }

        .features-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .features-title h2 {
            font-size: 2.2rem;
            font-weight: 800;
            color: #1a2e1a;
            margin-bottom: 12px;
        }

        .features-title p {
            color: #6c757d;
            font-size: 1.05rem;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            height: 100%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
            border-color: #c6f135;
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .feature-icon i {
            font-size: 1.8rem;
        }

        .feature-card h4 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a2e1a;
            margin-bottom: 10px;
        }

        .feature-card p {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
            line-height: 1.6;
        }

        /* ==========================================
           CANDIDATES SECTION
           ========================================== */
        .candidates-section {
            padding: 80px 0;
            background: white;
        }

        .candidate-card {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 32px 24px;
            text-align: center;
            transition: all 0.3s;
            height: 100%;
            border: 2px solid transparent;
        }

        .candidate-card:hover {
            transform: translateY(-8px);
            border-color: #c6f135;
            background: white;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        }

        .candidate-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 16px;
            border: 4px solid #c6f135;
            display: block;
        }

        .candidate-photo-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #c6f135;
            color: #1a2e1a;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-weight: 700;
            font-size: 3rem;
            border: 4px solid #e8f5c8;
        }

        .candidate-no {
            display: inline-block;
            background: #1a2e1a;
            color: #c6f135;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .candidate-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1a2e1a;
            margin-bottom: 4px;
        }

        .candidate-class {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 16px;
        }

        .candidate-visi {
            color: #495057;
            font-size: 0.85rem;
            font-style: italic;
            line-height: 1.5;
            max-height: 60px;
            overflow: hidden;
        }

        /* ==========================================
           CTA SECTION
           ========================================== */
        .cta-section {
            background: linear-gradient(135deg, #1a2e1a 0%, #2d5a3d 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: #c6f135;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.15;
            top: -100px;
            right: -100px;
        }

        .cta-content {
            position: relative;
            z-index: 1;
        }

        .cta-section h2 {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .cta-section p {
            opacity: 0.85;
            margin-bottom: 32px;
            font-size: 1.05rem;
        }

        /* ==========================================
           FOOTER
           ========================================== */
        .lp-footer {
            background: #0f1f0f;
            color: rgba(255, 255, 255, 0.6);
            padding: 32px 0;
            text-align: center;
            font-size: 0.85rem;
        }

        .lp-footer a {
            color: #c6f135;
            text-decoration: none;
        }

        /* ==========================================
           RESPONSIVE
           ========================================== */
        @media (max-width: 768px) {
            .lp-hero h1 {
                font-size: 2.2rem;
            }

            .lp-hero {
                padding: 50px 0 80px;
            }

            .lp-hero-icon {
                width: 200px;
                height: 200px;
            }

            .lp-hero-icon i {
                font-size: 5rem;
            }

            .countdown-box {
                padding: 16px 18px;
                min-width: 80px;
            }

            .countdown-value {
                font-size: 1.8rem;
            }

            .features-title h2 {
                font-size: 1.8rem;
            }

            .cta-section h2 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>

    {{-- ==========================================
         HEADER
         ========================================== --}}
    <header class="lp-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ url('/') }}" class="lp-brand">
                    <i class="bi bi-asterisk"></i>
                    Pilketos
                </a>

                <nav class="lp-nav">
                    <a href="{{ route('cek-status.index') }}" class="lp-nav-link d-none d-md-inline-flex">
                        <i class="bi bi-search"></i> Cek Status
                    </a>
                    <a href="{{ route('hasil.index') }}" class="lp-nav-link d-none d-md-inline-flex">
                        <i class="bi bi-trophy"></i> Hasil
                    </a>

                    @auth
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isOperator() ? route('operator.dashboard') : route('voter.dashboard')) }}"
                            class="lp-nav-btn">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="lp-nav-btn">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    @endauth
                </nav>
            </div>
        </div>
    </header>

    {{-- ==========================================
         HERO
         ========================================== --}}
    <section class="lp-hero">
        <div class="container">
            <div class="row align-items-center">

                <div class="col-lg-7 lp-hero-content">
                    <div class="lp-hero-badge">
                        <i class="bi bi-shield-check"></i>
                        Sistem Pemilihan Digital
                    </div>

                    <h1>
                        Pemilihan Ketua OSIS <br>
                        <span>Digital & Modern</span>
                    </h1>

                    <p>
                        Suara Anda menentukan masa depan sekolah.
                        Voting cepat, aman, dan transparan dengan teknologi QR Code.
                    </p>

                    <div class="lp-hero-actions">
                        @if ($activeElection && $activeElection->status === \App\Enums\ElectionStatus::ACTIVE)
                            <a href="{{ auth()->check() ? (auth()->user()->isVoter() ? route('voter.scan') : '#') : route('login') }}" class="lp-btn lp-btn-primary">
                                <i class="bi bi-qr-code-scan"></i>
                                Mulai Voting
                            </a>
                        @elseif ($activeElection)
                            <a href="{{ auth()->check() ? route('login') : route('register') }}" class="lp-btn lp-btn-primary">
                                <i class="bi bi-person-plus"></i>
                                Daftar Sekarang
                            </a>
                        @endif

                        <a href="{{ route('cek-status.index') }}" class="lp-btn lp-btn-outline">
                            <i class="bi bi-search"></i>
                            Cek Status
                        </a>
                    </div>
                </div>

                <div class="col-lg-5 lp-hero-illustration d-none d-lg-flex">
                    <div class="lp-hero-icon">
                        <i class="bi bi-check2-square"></i>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ==========================================
         COUNTDOWN / STATUS CARD
         ========================================== --}}
    @if ($activeElection)
        <section class="countdown-section">
            <div class="container">
                <div class="countdown-card">

                    @if ($activeElection->status === \App\Enums\ElectionStatus::ACTIVE)
                        {{-- LIVE STATUS --}}
                        <div class="status-live">
                            <span class="status-dot-live"></span>
                            SEDANG BERLANGSUNG
                        </div>
                        <h3 class="mb-2">{{ $activeElection->title }}</h3>
                        <p class="text-muted mb-4">
                            Voting sedang berlangsung sampai
                            <strong>{{ $activeElection->end_at->translatedFormat('d M Y, H:i') }}</strong>
                        </p>

                        <div class="countdown-label">Berakhir dalam</div>
                        <div class="countdown-grid" id="countdown" data-target="{{ $activeElection->end_at->toIso8601String() }}">
                            <div class="countdown-box">
                                <div class="countdown-value" id="cd-days">0</div>
                                <div class="countdown-unit">Hari</div>
                            </div>
                            <div class="countdown-box">
                                <div class="countdown-value" id="cd-hours">0</div>
                                <div class="countdown-unit">Jam</div>
                            </div>
                            <div class="countdown-box">
                                <div class="countdown-value" id="cd-minutes">0</div>
                                <div class="countdown-unit">Menit</div>
                            </div>
                            <div class="countdown-box">
                                <div class="countdown-value" id="cd-seconds">0</div>
                                <div class="countdown-unit">Detik</div>
                            </div>
                        </div>
                    @elseif (in_array($activeElection->status, [\App\Enums\ElectionStatus::DRAFT, \App\Enums\ElectionStatus::ACTIVE]))
                        {{-- COUNTDOWN TO START --}}
                        <div class="lp-hero-badge"
                            style="background: rgba(255,193,7,0.15);
                                                           color: #cc9a06;
                                                           border-color: rgba(255,193,7,0.3);">
                            <i class="bi bi-clock-history"></i>
                            Segera Dimulai
                        </div>
                        <h3 class="mb-2">{{ $activeElection->title }}</h3>
                        <p class="text-muted mb-4">
                            Voting akan dimulai pada
                            <strong>{{ $activeElection->start_at->translatedFormat('d M Y, H:i') }}</strong>
                        </p>

                        <div class="countdown-label">Dimulai dalam</div>
                        <div class="countdown-grid" id="countdown" data-target="{{ $activeElection->start_at->toIso8601String() }}">
                            <div class="countdown-box">
                                <div class="countdown-value" id="cd-days">0</div>
                                <div class="countdown-unit">Hari</div>
                            </div>
                            <div class="countdown-box">
                                <div class="countdown-value" id="cd-hours">0</div>
                                <div class="countdown-unit">Jam</div>
                            </div>
                            <div class="countdown-box">
                                <div class="countdown-value" id="cd-minutes">0</div>
                                <div class="countdown-unit">Menit</div>
                            </div>
                            <div class="countdown-box">
                                <div class="countdown-value" id="cd-seconds">0</div>
                                <div class="countdown-unit">Detik</div>
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
    <section class="features-section">
        <div class="container">
            <div class="features-title">
                <h2>Kenapa Pilketos Digital?</h2>
                <p>Pemilihan modern, cepat, dan transparan</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: #d1e7dd;">
                            <i class="bi bi-lightning-charge-fill text-success"></i>
                        </div>
                        <h4>Cepat</h4>
                        <p>Proses voting hanya 30 detik dengan scan QR code</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: #cfe2ff;">
                            <i class="bi bi-shield-lock-fill text-primary"></i>
                        </div>
                        <h4>Aman</h4>
                        <p>Suara anonim dan terenkripsi. Tidak ada yang tahu pilihan Anda</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: #fff3cd;">
                            <i class="bi bi-graph-up-arrow" style="color: #cc9a06;"></i>
                        </div>
                        <h4>Transparan</h4>
                        <p>Hasil real-time dan dapat dilihat setelah voting selesai</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: #f8d7da;">
                            <i class="bi bi-phone-fill text-danger"></i>
                        </div>
                        <h4>Modern</h4>
                        <p>Cukup dengan HP dan QR code, tidak perlu kertas</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==========================================
         CANDIDATES PREVIEW
         ========================================== --}}
    @if ($activeElection && $activeElection->candidates->count() > 0)
        <section class="candidates-section">
            <div class="container">
                <div class="features-title">
                    <h2>Kenalan dengan Kandidat</h2>
                    <p>{{ $activeElection->candidates->count() }} kandidat siap memimpin</p>
                </div>

                <div class="row g-4 justify-content-center">
                    @foreach ($activeElection->candidates as $c)
                        <div class="col-md-6 col-lg-4">
                            <div class="candidate-card">
                                @if ($c->foto)
                                    <img src="{{ asset('storage/' . $c->foto) }}" alt="{{ $c->nama }}" class="candidate-photo">
                                @else
                                    <div class="candidate-photo-placeholder">
                                        {{ strtoupper(substr($c->nama, 0, 1)) }}
                                    </div>
                                @endif

                                <div class="candidate-no">No. {{ $c->no_urut }}</div>
                                <div class="candidate-name">{{ $c->nama }}</div>
                                <div class="candidate-class">
                                    <i class="bi bi-mortarboard"></i> {{ $c->classRoom?->name ?? '-' }}
                                </div>
                                <div class="candidate-visi">"{{ Str::limit($c->visi, 100) }}"</div>
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
    <section class="cta-section">
        <div class="container cta-content">
            <h2>Siap Menggunakan Hak Suara Anda?</h2>
            <p>Bergabunglah dalam pemilihan digital yang modern dan transparan</p>

            <div class="d-flex gap-3 justify-content-center flex-wrap">
                @guest
                    <a href="{{ route('register') }}" class="lp-btn lp-btn-primary">
                        <i class="bi bi-person-plus"></i>
                        Daftar Sekarang
                    </a>
                    <a href="{{ route('login') }}" class="lp-btn lp-btn-outline">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Login
                    </a>
                @else
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isOperator() ? route('operator.dashboard') : route('voter.dashboard')) }}"
                        class="lp-btn lp-btn-primary">
                        <i class="bi bi-speedometer2"></i>
                        Ke Dashboard
                    </a>
                @endguest
            </div>
        </div>
    </section>

    {{-- ==========================================
         FOOTER
         ========================================== --}}
    <footer class="lp-footer">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <i class="bi bi-asterisk" style="color: #c6f135;"></i>
                    <strong>Pilketos Digital</strong> &copy; {{ date('Y') }}
                </div>
                <div class="d-flex gap-3">
                    <a href="{{ route('cek-status.index') }}">Cek Status</a>
                    <a href="{{ route('hasil.index') }}">Hasil</a>
                    <a href="{{ route('login') }}">Login</a>
                </div>
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

                        // Refresh halaman setelah countdown habis
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
