<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pemilihan - {{ config('app.name', 'Pilketos') }}</title>
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/css/main.css') }}">
</head>

<body style="background: #f5f7fa; min-height: 100vh;">

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
                        <a href="{{ route('login') }}" class="btn btn-sm" style="background: #c6f135; color: #1a2e1a; font-weight: 600;">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    {{-- CONTENT --}}
    <div class="container py-5">

        {{-- Title --}}
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-2">🏆 Hasil Pemilihan</h1>
            <p class="text-muted">Daftar pemilihan yang hasilnya sudah dipublikasikan</p>
        </div>

        {{-- Election List --}}
        @if ($elections->count() > 0)
            <div class="row g-4 justify-content-center">
                @foreach ($elections as $election)
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ route('hasil.show', $election) }}" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100">
                                <div
                                    style="background: linear-gradient(135deg, #c6f135 0%, #a8d92d 100%);
                                            height: 120px;
                                            display: flex; align-items: center; justify-content: center;
                                            border-radius: 0.5rem 0.5rem 0 0;">
                                    <i class="bi bi-trophy-fill" style="font-size: 3rem; color: #1a2e1a;"></i>
                                </div>
                                <div class="card-body">
                                    <h5 class="mb-1">{{ $election->title }}</h5>
                                    <p class="text-muted small mb-3">
                                        {{ $election->tahun_ajaran }}
                                    </p>

                                    <div class="d-flex justify-content-between small text-muted mb-3">
                                        <span>
                                            <i class="bi bi-people-fill"></i>
                                            {{ $election->votes_count }} suara
                                        </span>
                                        <span>
                                            <i class="bi bi-person-badge"></i>
                                            {{ $election->candidates_count }} kandidat
                                        </span>
                                    </div>

                                    <div class="text-primary fw-medium small">
                                        Lihat hasil <i class="bi bi-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-hourglass-split" style="font-size: 4rem; color: #ccc;"></i>
                    <h4 class="mt-3 mb-2">Belum Ada Hasil</h4>
                    <p class="text-muted mb-0">
                        Hasil pemilihan belum dipublikasikan.
                        Cek kembali nanti.
                    </p>
                </div>
            </div>
        @endif

    </div>

    {{-- FOOTER --}}
    <footer class="text-center py-4 text-muted small">
        &copy; {{ date('Y') }} {{ config('app.name', 'Pilketos') }}
    </footer>

</body>

</html>
