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
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
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

        /* ==========================================
           CUSTOM BUTTON STYLE (mirip Spark Admin)
           ========================================== */
        .btn-spark {
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 20px;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-spark:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .btn-spark:active {
            transform: translateY(0);
        }

        .btn-spark-success {
            background: #198754;
            color: #fff;
        }

        .btn-spark-success:hover {
            background: #157347;
            color: #fff;
        }

        .btn-spark-primary {
            background: #0d6efd;
            color: #fff;
        }

        .btn-spark-primary:hover {
            background: #0b5ed7;
            color: #fff;
        }

        .btn-spark-info {
            background: #0dcaf0;
            color: #fff;
        }

        .btn-spark-info:hover {
            background: #31d2f2;
            color: #fff;
        }

        .btn-spark-outline {
            background: transparent;
            border: 1.5px solid #dee2e6;
            color: #6c757d;
        }

        .btn-spark-outline:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
            color: #495057;
        }

        .btn-spark i {
            margin-right: 6px;
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
            <div class="bg-light rounded-3 p-3 text-start mb-4">
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

        {{-- SUCCESS --}}
        @if ($status === 'success')
            <div class="alert alert-success small mb-3 rounded-3">
                <i class="bi bi-arrow-left-right"></i>
                Silakan lihat layar device untuk memilih
            </div>

            <div class="alert alert-info small mb-3 rounded-3">
                <i class="bi bi-info-circle"></i>
                Setelah selesai memilih, ikuti instruksi di layar device.
            </div>

            <div class="d-grid gap-2">
                <a href="{{ route('voter.dashboard') }}" class="btn-spark btn-spark-success">
                    <i class="bi bi-house-check"></i> Selesai — Kembali ke Dashboard
                </a>
            </div>
        @endif

        {{-- ERROR --}}
        @if ($status === 'error')
            <div class="d-grid gap-2">
                <a href="{{ route('voter.scan') }}" class="btn-spark btn-spark-primary">
                    <i class="bi bi-arrow-clockwise"></i> Coba Scan Lagi
                </a>
                <a href="{{ route('voter.dashboard') }}" class="btn-spark btn-spark-outline btn-sm">
                    <i class="bi bi-house"></i> Kembali ke Dashboard
                </a>
            </div>
        @endif

        {{-- INFO --}}
        @if ($status === 'info')
            <div class="d-grid gap-2">
                <a href="{{ route('voter.dashboard') }}" class="btn-spark btn-spark-info">
                    <i class="bi bi-house"></i> Kembali ke Dashboard
                </a>
            </div>
        @endif
    </div>

</body>

</html>
