<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil - {{ config('app.name', 'Pilketos') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('storage/assets/images/favicon.ico') }}">

    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/css/main.css') }}">
</head>

<body>

    <div class="login-wrapper">
        <div class="login-bg-shape login-bg-shape-1"></div>
        <div class="login-bg-shape login-bg-shape-2"></div>

        <div class="login-card text-center">

            <a href="/" class="login-brand text-decoration-none">
                <i class="bi bi-asterisk"></i>
                <span>Pilketos</span>
            </a>

            <div class="my-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            </div>

            <h4 class="mb-2">Pendaftaran Berhasil!</h4>

            <p class="text-muted mb-4">
                Akun Anda sedang menunggu verifikasi panitia Pilketos.
            </p>

            <div class="alert alert-info text-start" style="font-size: 0.875rem;">
                <div class="mb-1">
                    <strong>NIS:</strong> {{ session('registered_nis') }}
                </div>
                <div class="mb-1">
                    <strong>Email:</strong> {{ session('registered_email') }}
                </div>
                <div>
                    <strong>Status:</strong>
                    <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                </div>
            </div>

            <p class="text-muted mb-4" style="font-size: 0.85rem;">
                Anda dapat login setelah akun diverifikasi oleh panitia.
            </p>

            <div class="d-flex gap-2">
                <a href="{{ route('cek-status.index') }}" class="btn w-100 btn-outline-primary">
                    <i class="bi bi-search"></i> Cek Status
                </a>
                <a href="{{ route('login') }}" class="btn w-100" style="background: #c6f135; color: #1a2e1a; font-weight: 700;">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
            </div>

            <a href="{{ route('login') }}" class="btn-login text-decoration-none d-inline-flex justify-content-center align-items-center w-100">
                <span>Ke Halaman Login</span>
                <i class="bi bi-arrow-right"></i>
            </a>

        </div>
    </div>

    <script src="{{ asset('storage/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

</body>

</html>
