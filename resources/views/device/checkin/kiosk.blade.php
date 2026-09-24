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
        body {
            background: #1a2e1a;
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, sans-serif;
            margin: 0;
        }

        /* WAITING */
        .waiting-card {
            background: white;
            color: #1a2e1a;
            border-radius: 24px;
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .waiting-icon {
            font-size: 5rem;
            color: #c6f135;
            animation: pulse 2s ease-in-out infinite;
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

        /* KIOSK */
        .kiosk-card {
            background: white;
            color: #1a2e1a;
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .qr-box {
            background: #fff;
            padding: 20px;
            border-radius: 16px;
            display: inline-block;
            border: 4px solid #c6f135;
            margin: 20px 0;
        }

        #qrcode img,
        #qrcode canvas {
            display: block;
            width: 280px !important;
            height: 280px !important;
        }

        .progress-thin {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-thin .progress-bar {
            background: #c6f135;
            transition: width 1s linear;
        }

        /* FLASH */
        .scan-flash {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(198, 241, 53, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .scan-flash .icon {
            font-size: 8rem;
            color: #1a2e1a;
        }

        .scan-flash .name {
            font-size: 2rem;
            color: #1a2e1a;
            font-weight: 700;
            margin-top: 20px;
        }

        .scan-flash .kelas {
            font-size: 1.2rem;
            color: #1a2e1a;
            opacity: 0.7;
        }
    </style>
</head>

<body>

    @if (!$election || !$device)
        {{-- WAITING SCREEN --}}
        <div class="waiting-card">
            <i class="bi bi-hourglass-split waiting-icon"></i>
            <h2 class="mt-4 mb-2">Menunggu Pemilihan...</h2>
            <p class="text-muted mb-4">
                Belum ada pemilihan yang sedang berlangsung.
            </p>
            <div class="alert alert-info small mb-3">
                <i class="bi bi-info-circle"></i>
                Layar ini akan otomatis menampilkan QR saat pemilihan dimulai.
            </div>
            <div class="text-muted small">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh otomatis dalam <span id="waitCounter">5</span>s
            </div>
        </div>

        <script>
            let counter = 5;
            const counterEl = document.getElementById('waitCounter');
            setInterval(() => {
                counter--;
                if (counterEl) counterEl.textContent = counter;
                if (counter <= 0) window.location.reload();
            }, 1000);
        </script>
    @else
        {{-- KIOSK SCREEN --}}
        <div class="kiosk-card">
            <h2 class="mb-1">{{ $election->title }}</h2>
            <p class="text-muted mb-0">
                Check-in Pemilih — {{ $election->tahun_ajaran }}
            </p>

            <div class="qr-box" id="qrcode"></div>

            <p class="mb-3">
                <i class="bi bi-phone"></i>
                Scan QR ini dari HP kamu untuk check-in
            </p>

            <div class="progress-thin mb-2">
                <div class="progress-bar" id="timer-bar" style="width: 100%"></div>
            </div>
            <small class="text-muted">
                Refresh dalam <span id="timer-text">30</span>s
            </small>

            <div class="mt-4 pt-3 border-top">
                <div class="d-flex justify-content-around">
                    <div>
                        <div class="fs-3 fw-bold" id="count-checked">0</div>
                        <small class="text-muted">Sudah Check-in</small>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold" id="count-total">0</div>
                        <small class="text-muted">Total Pemilih</small>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2 justify-content-center">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshQR()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh QR
                </button>
                <form action="{{ route('device.checkin.close') }}" method="POST" class="d-inline" onsubmit="return confirm('Tutup device check-in?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-x-circle"></i> Tutup Device
                    </button>
                </form>
            </div>
        </div>

        {{-- SCAN FLASH --}}
        <div id="scanFlash" class="scan-flash d-none">
            <i class="bi bi-check-circle-fill icon"></i>
            <div class="name" id="flashName">-</div>
            <div class="kelas" id="flashKelas">-</div>
            <div class="mt-3" style="color: #1a2e1a; opacity: 0.6;">
                Silakan masuk ke bilik suara
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
        <script>
            const SCAN_URL = '{{ url('/scan/checkin') }}';
            const STATUS_URL = '{{ route('device.checkin.status') }}';

            let currentToken = '{{ $device->device_token }}';
            let currentExpires = {{ max(0, now()->diffInSeconds($device->token_expired_at, false)) }};
            let qrCode = null;
            let pollTimer = null;
            let countdownTimer = null;

            function renderQR(token) {
                const container = document.getElementById('qrcode');
                container.innerHTML = '';

                qrCode = new QRCode(container, {
                    text: `${SCAN_URL}/${token}`,
                    width: 280,
                    height: 280,
                    colorDark: '#1a2e1a',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M,
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
                document.getElementById('timer-text').textContent = seconds;
                const pct = Math.max(0, Math.min(100, (seconds / 30) * 100));
                document.getElementById('timer-bar').style.width = pct + '%';
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
                            if (data.message) alert(data.message);
                            window.location.reload();
                            return;
                        }

                        if (data.total_checked !== undefined) {
                            document.getElementById('count-checked').textContent = data.total_checked;
                        }
                        if (data.total_voters !== undefined) {
                            document.getElementById('count-total').textContent = data.total_voters;
                        }

                        if (data.token && data.token !== currentToken) {
                            currentToken = data.token;
                            renderQR(currentToken);
                            startCountdown(30);
                        }

                        if (data.status === 'scanned' && data.voter) {
                            showScanFlash(data.voter);
                        }
                    })
                    .catch(err => console.error('Poll error:', err));
            }

            function showScanFlash(voter) {
                const flash = document.getElementById('scanFlash');
                document.getElementById('flashName').textContent = voter.nama;
                document.getElementById('flashKelas').textContent = voter.kelas;
                flash.classList.remove('d-none');
                setTimeout(() => flash.classList.add('d-none'), 3000);
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
                    .catch(err => console.error('Refresh error:', err));
            }

            renderQR(currentToken);
            startCountdown(currentExpires);

            pollTimer = setInterval(pollStatus, 2000);
            pollStatus();
        </script>
    @endif

</body>

</html>
