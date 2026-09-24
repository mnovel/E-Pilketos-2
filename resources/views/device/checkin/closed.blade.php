<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Device Ditutup - {{ config('app.name', 'Pilketos') }}</title>
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

        .closed-card {
            background: white;
            color: #1a2e1a;
            border-radius: 24px;
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .closed-icon {
            font-size: 5rem;
            color: #dc3545;
        }
    </style>
</head>

<body>

    <div class="closed-card">
        <i class="bi bi-power closed-icon"></i>
        <h2 class="mt-4 mb-2">Device Ditutup</h2>
        <p class="text-muted mb-4">
            Device check-in sudah ditutup. Siswa tidak bisa check-in sampai device dibuka kembali.
        </p>

        <div class="alert alert-warning small mb-4">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Pastikan ini disengaja.</strong>
            Klik tombol di bawah kalau mau buka lagi.
        </div>

        <form action="{{ route('device.checkin.reopen') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-lg w-100" style="background: #c6f135; color: #1a2e1a; font-weight: 700; padding: 16px;">
                <i class="bi bi-power"></i> Buka Device Lagi
            </button>
        </form>

        <p class="text-muted small mt-4 mb-0">
            Untuk keluar dari mode kiosk, tutup tab browser ini.
        </p>
    </div>

</body>

</html>
