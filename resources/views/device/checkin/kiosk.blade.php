<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Check-in - {{ config('app.name', 'Pilketos') }}</title>

    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">

    <style>
        :root {
            --green-dark: #1a2e1a;
            --lime: #c6f135;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
        }

        body {
            background: var(--green-dark);
            color: white;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow-x: hidden;
        }

        /* =========================================================
           WAITING
           ========================================================= */

        .waiting-card {
            background: white;
            color: var(--green-dark);
            border-radius: clamp(16px, 3vw, 24px);
            padding: clamp(28px, 6vw, 60px) clamp(20px, 5vw, 40px);
            text-align: center;
            width: min(100%, 500px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .waiting-icon {
            font-size: clamp(3.5rem, 12vw, 5rem);
            color: var(--lime);
            animation: pulse 2s ease-in-out infinite;
        }

        .waiting-card h2 {
            font-size: clamp(1.35rem, 5vw, 2rem);
        }

        .waiting-card p {
            font-size: clamp(0.9rem, 3vw, 1rem);
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.6;
                transform: scale(0.95);
            }
        }

        /* =========================================================
           KIOSK
           ========================================================= */

        .kiosk-card {
            background: white;
            color: var(--green-dark);
            border-radius: clamp(16px, 3vw, 24px);
            padding: clamp(20px, 5vw, 40px);
            text-align: center;
            width: min(100%, 600px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .kiosk-card h2 {
            font-size: clamp(1.25rem, 5vw, 2rem);
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .kiosk-card>p {
            font-size: clamp(0.85rem, 3vw, 1rem);
        }

        /* =========================================================
           QR CODE
           ========================================================= */

        .qr-wrapper {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .qr-box {
            background: #fff;
            padding: clamp(8px, 2.5vw, 20px);
            border-radius: clamp(10px, 2.5vw, 16px);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: clamp(2px, 0.8vw, 4px) solid var(--lime);
            margin: clamp(12px, 3vw, 20px) 0;
            max-width: 100%;
        }

        #qrcode {
            width: min(280px, 58vw, 42vh);
            aspect-ratio: 1 / 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #qrcode img,
        #qrcode canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
            max-width: 100%;
            max-height: 100%;
        }

        /* =========================================================
           INSTRUCTION
           ========================================================= */

        .scan-instruction {
            font-size: clamp(0.85rem, 3vw, 1rem);
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        /* =========================================================
           PROGRESS
           ========================================================= */

        .progress-thin {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            width: 100%;
        }

        .progress-thin .progress-bar {
            background: var(--lime);
            transition: width 1s linear;
        }

        /* =========================================================
           STATISTICS
           ========================================================= */

        .stats {
            display: flex;
            justify-content: center;
            align-items: stretch;
            gap: clamp(20px, 8vw, 60px);
            flex-wrap: wrap;
        }

        .stat-item {
            flex: 1 1 120px;
            min-width: 100px;
        }

        .stat-number {
            font-size: clamp(1.5rem, 6vw, 2rem);
            line-height: 1.1;
        }

        .stat-label {
            font-size: clamp(0.7rem, 2.5vw, 0.875rem);
        }

        /* =========================================================
           ACTION BUTTONS
           ========================================================= */

        .action-buttons {
            display: flex;
            justify-content: center;
            align-items: stretch;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            min-height: 38px;
            white-space: nowrap;
        }

        .action-buttons form {
            display: inline-flex;
        }

        /* =========================================================
           FLASH
           ========================================================= */

        .scan-flash {
            position: fixed;
            inset: 0;
            padding: 24px;
            background: rgba(198, 241, 53, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }

        .scan-flash .icon {
            font-size: clamp(4.5rem, 20vw, 8rem);
            color: var(--green-dark);
            line-height: 1;
        }

        .scan-flash .name {
            font-size: clamp(1.4rem, 6vw, 2rem);
            color: var(--green-dark);
            font-weight: 700;
            margin-top: clamp(12px, 4vw, 20px);
            max-width: 100%;
            overflow-wrap: anywhere;
        }

        .scan-flash .kelas {
            font-size: clamp(1rem, 4vw, 1.2rem);
            color: var(--green-dark);
            opacity: 0.7;
            margin-top: 4px;
        }

        .scan-flash .flash-message {
            color: var(--green-dark);
            opacity: 0.6;
            font-size: clamp(0.8rem, 3vw, 1rem);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        /* =========================================================
           SMALL MOBILE
           ========================================================= */

        @media (max-width: 575.98px) {
            body {
                padding: 10px;
            }

            .kiosk-card,
            .waiting-card {
                border-radius: 16px;
            }

            .kiosk-card {
                padding: 18px 14px;
            }

            .qr-box {
                margin: 12px 0;
            }

            .stats {
                gap: 10px;
            }

            .stat-item {
                min-width: 90px;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
            }

            .action-buttons .btn,
            .action-buttons form,
            .action-buttons form .btn {
                width: 100%;
            }

            .waiting-card .alert {
                font-size: 0.8rem;
            }
        }

        /* =========================================================
           VERY SMALL SCREEN
           ========================================================= */

        @media (max-width: 360px) {
            body {
                padding: 6px;
            }

            .kiosk-card {
                padding: 14px 10px;
            }

            #qrcode {
                width: min(240px, 62vw, 38vh);
            }

            .stats {
                gap: 4px;
            }

            .stat-item {
                min-width: 80px;
            }
        }

        /* =========================================================
           LANDSCAPE MOBILE
           ========================================================= */

        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding: 8px;
                align-items: flex-start;
            }

            .kiosk-card {
                margin: 8px auto;
                padding: 14px 20px;
            }

            .qr-box {
                margin: 8px 0;
            }

            #qrcode {
                width: min(190px, 34vh);
            }

            .kiosk-card h2 {
                margin-bottom: 2px !important;
            }

            .scan-instruction {
                margin-bottom: 8px;
            }

            .kiosk-card .mt-4 {
                margin-top: 12px !important;
            }

            .kiosk-card .pt-3 {
                padding-top: 10px !important;
            }
        }

        /* =========================================================
           TABLET / DESKTOP
           ========================================================= */

        @media (min-width: 768px) {
            body {
                padding: 24px;
            }
        }

        /* =========================================================
           ACCESSIBILITY
           ========================================================= */

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>

    @if (!$election || !$device)
        {{-- WAITING SCREEN --}}
        <div class="waiting-card">

            <i class="bi bi-hourglass-split waiting-icon"></i>

            <h2 class="mt-4 mb-2">
                Menunggu Pemilihan...
            </h2>

            <p class="text-muted mb-4">
                Belum ada pemilihan yang sedang berlangsung.
            </p>

            <div class="alert alert-info small mb-3">
                <i class="bi bi-info-circle"></i>
                Layar ini akan otomatis menampilkan QR saat pemilihan dimulai.
            </div>

            <div class="text-muted small">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh otomatis dalam
                <span id="waitCounter">5</span>s
            </div>

        </div>

        <script>
            let counter = 5;
            const counterEl = document.getElementById('waitCounter');

            setInterval(() => {
                counter--;

                if (counterEl) {
                    counterEl.textContent = counter;
                }

                if (counter <= 0) {
                    window.location.reload();
                }
            }, 1000);
        </script>
    @else
        {{-- KIOSK SCREEN --}}
        <div class="kiosk-card">

            <h2 class="mb-1">
                {{ $election->title }}
            </h2>

            <p class="text-muted mb-0">
                Check-in Pemilih — {{ $election->tahun_ajaran }}
            </p>

            {{-- QR --}}
            <div class="qr-wrapper">
                <div class="qr-box">
                    <div id="qrcode"></div>
                </div>
            </div>

            <p class="scan-instruction">
                <i class="bi bi-phone"></i>
                Scan QR ini dari HP kamu untuk check-in
            </p>

            {{-- TIMER --}}
            <div class="progress-thin mb-2">
                <div class="progress-bar" id="timer-bar" style="width: 100%">
                </div>
            </div>

            <small class="text-muted">
                Refresh dalam
                <span id="timer-text">30</span>s
            </small>

            {{-- STATISTICS --}}
            <div class="mt-4 pt-3 border-top">

                <div class="stats">

                    <div class="stat-item">
                        <div class="stat-number fw-bold" id="count-checked">
                            0
                        </div>

                        <small class="text-muted stat-label">
                            Sudah Check-in
                        </small>
                    </div>

                    <div class="stat-item">
                        <div class="stat-number fw-bold" id="count-total">
                            0
                        </div>

                        <small class="text-muted stat-label">
                            Total Pemilih
                        </small>
                    </div>

                </div>

            </div>

            {{-- ACTIONS --}}
            <div class="mt-4 action-buttons">

                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshQR()">

                    <i class="bi bi-arrow-clockwise"></i>
                    Refresh QR

                </button>

                <form action="{{ route('device.checkin.close') }}" method="POST" onsubmit="return confirm('Tutup device check-in?')">

                    @csrf

                    <button type="submit" class="btn btn-sm btn-outline-danger">

                        <i class="bi bi-x-circle"></i>
                        Tutup Device

                    </button>

                </form>

            </div>

        </div>

        {{-- SCAN FLASH --}}
        <div id="scanFlash" class="scan-flash d-none">

            <i class="bi bi-check-circle-fill icon"></i>

            <div class="name" id="flashName">
                -
            </div>

            <div class="kelas" id="flashKelas">
                -
            </div>

            <div class="mt-3 flash-message">
                Silakan masuk ke bilik suara
            </div>

        </div>

        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

        <script>
            const SCAN_URL = '{{ url('/scan/checkin') }}';
            const STATUS_URL = '{{ route('device.checkin.status') }}';

            let currentToken = '{{ $device->device_token }}';

            let currentExpires =
                {{ max(0, now()->diffInSeconds($device->token_expired_at, false)) }};

            let qrCode = null;
            let pollTimer = null;
            let countdownTimer = null;

            function renderQR(token) {

                const container = document.getElementById('qrcode');

                if (!container) {
                    return;
                }

                container.innerHTML = '';

                qrCode = new QRCode(container, {
                    text: `${SCAN_URL}/${token}`,
                    width: 280,
                    height: 280,
                    colorDark: '#1a2e1a',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            }

            function startCountdown(seconds) {

                clearInterval(countdownTimer);

                let remaining = seconds;

                updateCountdown(remaining);

                countdownTimer = setInterval(() => {

                    remaining--;

                    if (remaining <= 0) {

                        clearInterval(countdownTimer);

                        refreshQR();

                    } else {

                        updateCountdown(remaining);

                    }

                }, 1000);
            }

            function updateCountdown(seconds) {

                const timerText =
                    document.getElementById('timer-text');

                const timerBar =
                    document.getElementById('timer-bar');

                if (timerText) {
                    timerText.textContent = seconds;
                }

                if (timerBar) {

                    const pct = Math.max(
                        0,
                        Math.min(100, (seconds / 30) * 100)
                    );

                    timerBar.style.width = pct + '%';
                }
            }

            function pollStatus() {

                fetch(STATUS_URL, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })

                    .then(r => r.json())

                    .then(data => {

                        if (data.action === 'reload') {

                            clearInterval(pollTimer);
                            clearInterval(countdownTimer);

                            if (data.message) {
                                alert(data.message);
                            }

                            window.location.reload();

                            return;
                        }

                        if (data.total_checked !== undefined) {

                            const checked =
                                document.getElementById('count-checked');

                            if (checked) {
                                checked.textContent = data.total_checked;
                            }
                        }

                        if (data.total_voters !== undefined) {

                            const total =
                                document.getElementById('count-total');

                            if (total) {
                                total.textContent = data.total_voters;
                            }
                        }

                        if (
                            data.token &&
                            data.token !== currentToken
                        ) {

                            currentToken = data.token;

                            renderQR(currentToken);

                            startCountdown(30);
                        }

                        if (
                            data.status === 'scanned' &&
                            data.voter
                        ) {

                            showScanFlash(data.voter);
                        }

                    })

                    .catch(err => {
                        console.error('Poll error:', err);
                    });
            }

            function showScanFlash(voter) {

                const flash =
                    document.getElementById('scanFlash');

                const name =
                    document.getElementById('flashName');

                const kelas =
                    document.getElementById('flashKelas');

                if (!flash) {
                    return;
                }

                if (name) {
                    name.textContent = voter.nama;
                }

                if (kelas) {
                    kelas.textContent = voter.kelas;
                }

                flash.classList.remove('d-none');

                setTimeout(() => {
                    flash.classList.add('d-none');
                }, 3000);
            }

            function refreshQR() {

                fetch(STATUS_URL + '?refresh=1', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })

                    .then(r => r.json())

                    .then(data => {

                        if (data.token) {

                            currentToken = data.token;

                            renderQR(currentToken);

                            startCountdown(30);
                        }

                    })

                    .catch(err => {
                        console.error('Refresh error:', err);
                    });
            }

            renderQR(currentToken);

            startCountdown(currentExpires);

            pollTimer = setInterval(
                pollStatus,
                2000
            );

            pollStatus();
        </script>
    @endif

</body>

</html>
