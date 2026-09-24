<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status - {{ config('app.name', 'Pilketos') }}</title>
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/css/main.css') }}">

    <style>
        body {
            background: #f5f7fa;
            min-height: 100vh;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <header style="background: #1a2e1a; color: white; padding: 20px 0;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <a href="{{ url('/') }}" class="text-decoration-none">
                    <h3 class="mb-0" style="color: #c6f135;">
                        <i class="bi bi-asterisk"></i> Pilketos
                    </h3>
                </a>

                <div class="d-flex gap-2">
                    @auth
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isOperator() ? route('operator.dashboard') : route('voter.dashboard')) }}" class="btn btn-sm"
                            style="background: #c6f135; color: #1a2e1a; font-weight: 600;">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-sm" style="background: #c6f135; color: #1a2e1a; font-weight: 600;">
                            <i class="bi bi-person-plus"></i> Daftar
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    {{-- CONTENT --}}
    <div class="container py-5">

        <div class="row justify-content-center">
            <div class="col-md-6">

                {{-- CARD --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5 text-center">

                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: #e8f5c8;">
                            <i class="bi bi-search" style="font-size: 2rem; color: #7cb518;"></i>
                        </div>

                        <h3 class="fw-bold mb-2">Cek Status Pendaftaran</h3>
                        <p class="text-muted mb-4">
                            Masukkan NIS untuk melihat status pendaftaran Anda
                        </p>

                        <form action="{{ route('cek-status.check') }}" method="POST">
                            @csrf

                            <div class="mb-4">
                                <input type="text" name="nis" class="form-control form-control-lg text-center @error('nis') is-invalid @enderror" value="{{ old('nis') }}"
                                    placeholder="Masukkan NIS" style="font-size: 1.2rem; font-weight: 600;
                                              letter-spacing: 2px;" autofocus required>

                                @error('nis')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn w-100"
                                style="background: #c6f135; color: #1a2e1a;
                                           font-weight: 700; padding: 12px;
                                           font-size: 1rem;">
                                <i class="bi bi-search"></i> Cek Status
                            </button>

                        </form>

                        <div class="mt-4 pt-4 border-top">
                            <small class="text-muted">
                                Belum daftar? <a href="{{ route('register') }}" class="fw-medium">
                                    Daftar sekarang
                                </a>
                            </small>
                        </div>

                    </div>
                </div>

                {{-- Info --}}
                <div class="alert alert-info mt-4 small">
                    <i class="bi bi-info-circle"></i>
                    <strong>Info:</strong> Status akan <strong>Menunggu</strong> sampai
                    panitia memverifikasi akun Anda. Biasanya 1×24 jam kerja.
                </div>

            </div>
        </div>

    </div>

    {{-- FOOTER --}}
    <footer class="text-center py-4 text-muted small">
        &copy; {{ date('Y') }} {{ config('app.name', 'Pilketos') }}
    </footer>

</body>

</html>
