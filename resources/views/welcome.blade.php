<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilketos Digital</title>
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <style>
        body {
            background: linear-gradient(135deg, #1a2e1a 0%, #2d5a3d 100%);
            min-height: 100vh;
            color: white;
            display: flex;
            align-items: center;
            font-family: system-ui, sans-serif;
        }
    </style>
</head>

<body>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 text-center">

                <div class="mb-4">
                    <i class="bi bi-asterisk" style="font-size: 5rem; color: #c6f135;"></i>
                </div>

                <h1 class="fw-bold mb-3" style="font-size: 3rem;">
                    Pilketos <span style="color: #c6f135;">Digital</span>
                </h1>

                <p class="mb-5" style="opacity: 0.8; font-size: 1.1rem;">
                    Sistem Pemilihan Ketua OSIS Digital — Cepat, Aman, Transparan
                </p>

                <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                    <a href="{{ route('login') }}" class="btn btn-lg" style="background: #c6f135; color: #1a2e1a;
                              font-weight: 700; padding: 14px 40px;">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>

                    <a href="{{ route('cek-status.index') }}" class="btn btn-lg btn-outline-light" style="padding: 14px 40px; font-weight: 600;">
                        <i class="bi bi-search"></i> Cek Status
                    </a>

                    <a href="{{ route('hasil.index') }}" class="btn btn-lg btn-outline-light" style="padding: 14px 40px; font-weight: 600;">
                        <i class="bi bi-trophy"></i> Hasil
                    </a>
                </div>

                <div class="mt-5 pt-5 border-top" style="border-color: rgba(255,255,255,0.1) !important;">
                    <small style="opacity: 0.6;">
                        &copy; {{ date('Y') }} Pilketos Digital —
                        SMK Negeri Contoh
                    </small>
                </div>

            </div>
        </div>
    </div>

</body>

</html>
