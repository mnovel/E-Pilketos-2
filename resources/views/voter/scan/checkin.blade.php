<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in - Pilketos</title>
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #f8f9fa;
        }

        .status-card {
            max-width: 420px;
            width: 100%;
            border-radius: 20px;
            padding: 32px 24px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            background: white;
        }

        .status-icon {
            font-size: 5rem;
            line-height: 1;
        }

        .progress-thin {
            height: 4px;
        }
    </style>
</head>

<body>

    <div class="status-card">
        <div class="status-icon mb-3">
            @if ($status === 'success')
                <i class="bi bi-check-circle-fill text-success"></i>
            @elseif ($status === 'error')
                <i class="bi bi-x-circle-fill text-danger"></i>
            @else
                <i class="bi bi-info-circle-fill text-info"></i>
            @endif
        </div>

        <h3 class="mb-2">{{ $title }}</h3>
        <p class="text-muted mb-4">{{ $message }}</p>

        @if (isset($voter))
            <div class="bg-light rounded p-3 text-start mb-4">
                <div class="mb-1">
                    <small class="text-muted">Nama</small>
                    <div class="fw-medium">{{ $voter->user?->name ?? auth()->user()->name }}</div>
                </div>
                <div>
                    <small class="text-muted">Kelas</small>
                    <div class="fw-medium">{{ $voter->classRoom?->name ?? '-' }}</div>
                </div>
            </div>
        @endif

        {{-- ==========================================
             SUCCESS — Auto-redirect + tombol
             ========================================== --}}
        @if ($status === 'success')
            <div class="alert alert-success small mb-3">
                <i class="bi bi-arrow-right"></i>
                Silakan menuju bilik suara
            </div>

            {{-- Progress bar countdown --}}
            <div class="progress progress-thin mb-2">
                <div id="progressBar" class="progress-bar bg-success" role="progressbar" style="width: 100%;"></div>
            </div>
            <p class="text-muted small mb-3">
                Otomatis ke halaman scan dalam <strong id="countdown">5</strong> detik...
            </p>

            {{-- Tombol Aksi --}}
            <div class="d-grid gap-2">
                <a href="{{ route('voter.scan') }}" class="btn btn-success">
                    <i class="bi bi-qr-code-scan"></i> Scan QR Voting Sekarang
                </a>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="stopCountdown()">
                    <i class="bi bi-x-circle"></i> Batalkan Auto-Redirect
                </button>
            </div>
        @endif

        {{-- ==========================================
             ERROR — Tombol retry
             ========================================== --}}
        @if ($status === 'error')
            <div class="d-grid gap-2">
                <a href="{{ route('voter.scan') }}" class="btn-custom btn-custom-primary">
                    <i class="bi bi-arrow-clockwise"></i> Coba Scan Lagi
                </a>
                <a href="{{ route('voter.dashboard') }}" class="btn-custom btn-custom-secondary btn-sm">
                    <i class="bi bi-house"></i> Kembali ke Dashboard
                </a>
            </div>
        @endif

        {{-- ==========================================
             INFO (sudah check-in) — Tombol ke voting
             ========================================== --}}
        @if ($status === 'info')
            <div class="d-grid gap-2">
                <a href="{{ route('voter.scan') }}" class="btn btn-info text-white">
                    <i class="bi bi-qr-code-scan"></i> Scan QR Voting
                </a>
                <a href="{{ route('voter.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-house"></i> Dashboard
                </a>
            </div>
        @endif
    </div>

    @if ($status === 'success')
        <script>
            let countdown = 5;
            let stopped = false;

            const countdownEl = document.getElementById('countdown');
            const progressEl = document.getElementById('progressBar');
            const totalTime = 5000; // ms
            const startTime = Date.now();

            const interval = setInterval(() => {
                if (stopped) return;

                const elapsed = Date.now() - startTime;
                const remaining = Math.max(0, totalTime - elapsed);

                // Update progress bar
                progressEl.style.width = (remaining / totalTime * 100) + '%';

                // Update text countdown
                const sec = Math.ceil(remaining / 1000);
                if (countdownEl) countdownEl.textContent = sec;

                // Redirect saat habis
                if (remaining <= 0) {
                    clearInterval(interval);
                    window.location.href = "{{ route('voter.scan') }}";
                }
            }, 100);

            function stopCountdown() {
                stopped = true;
                clearInterval(interval);
                progressEl.style.width = '100%';
                progressEl.classList.remove('bg-success');
                progressEl.classList.add('bg-secondary');
                if (countdownEl) countdownEl.parentElement.innerHTML =
                    '<i class="bi bi-pause-circle"></i> Auto-redirect dibatalkan. Klik tombol untuk lanjut.';
            }

            // Support: kalau user klik back browser → stop countdown
            window.addEventListener('beforeunload', () => {
                stopped = true;
            });
        </script>
    @endif

</body>

</html>
