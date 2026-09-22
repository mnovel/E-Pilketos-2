<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting - Pilketos</title>
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
             SUCCESS — Device sudah siap, tidak auto-redirect
             ========================================== --}}
        @if ($status === 'success')
            <div class="alert alert-success small mb-3">
                <i class="bi bi-arrow-left-right"></i>
                Silakan lihat layar device untuk memilih
            </div>

            <div class="alert alert-info small mb-3">
                <i class="bi bi-info-circle"></i>
                Setelah selesai memilih, ikuti instruksi di layar device.
            </div>

            <div class="d-grid gap-2">
                <a href="{{ route('voter.dashboard') }}" class="btn btn-success">
                    <i class="bi bi-house-check"></i> Selesai — Kembali ke Dashboard
                </a>
            </div>
        @endif

        {{-- ==========================================
             ERROR — Tombol retry
             ========================================== --}}
        @if ($status === 'error')
            <div class="d-grid gap-2">
                <a href="{{ route('voter.scan') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-clockwise"></i> Coba Scan Lagi
                </a>
                <a href="{{ route('voter.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-house"></i> Kembali ke Dashboard
                </a>
            </div>
        @endif

        {{-- ==========================================
             INFO (sudah vote) — Tombol dashboard
             ========================================== --}}
        @if ($status === 'info')
            <div class="d-grid gap-2">
                <a href="{{ route('voter.dashboard') }}" class="btn btn-info text-white">
                    <i class="bi bi-house"></i> Kembali ke Dashboard
                </a>
            </div>
        @endif
    </div>

</body>

</html>
