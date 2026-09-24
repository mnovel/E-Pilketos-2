<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Cek Status - {{ config('app.name', 'Pilketos') }}</title>
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

                <a href="{{ route('cek-status.index') }}" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-arrow-left"></i> Cek Lagi
                </a>
            </div>
        </div>
    </header>

    {{-- CONTENT --}}
    <div class="container py-5">

        <div class="row justify-content-center">
            <div class="col-md-7">

                @if ($voter)
                    @php
                        $status = $voter->status;
                    @endphp

                    {{-- CARD HASIL --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-5">

                            {{-- Header dengan icon --}}
                            <div class="text-center mb-4">
                                @if ($status === \App\Enums\VoterStatus::VERIFIED)
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px; background: #d1e7dd;">
                                        <i class="bi bi-check-circle-fill" style="font-size: 3rem; color: #198754;"></i>
                                    </div>
                                    <h3 class="fw-bold text-success mb-2">Terverifikasi</h3>
                                    <p class="text-muted mb-0">
                                        Akun Anda sudah diverifikasi panitia. Anda bisa login untuk voting.
                                    </p>
                                @elseif ($status === \App\Enums\VoterStatus::PENDING)
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px; background: #fff3cd;">
                                        <i class="bi bi-hourglass-split" style="font-size: 3rem; color: #cc9a06;"></i>
                                    </div>
                                    <h3 class="fw-bold mb-2" style="color: #cc9a06;">
                                        Menunggu Verifikasi
                                    </h3>
                                    <p class="text-muted mb-0">
                                        Akun Anda sedang menunggu verifikasi panitia.
                                    </p>
                                @elseif ($status === \App\Enums\VoterStatus::REJECTED)
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px; background: #f8d7da;">
                                        <i class="bi bi-x-circle-fill" style="font-size: 3rem; color: #dc3545;"></i>
                                    </div>
                                    <h3 class="fw-bold text-danger mb-2">Ditolak</h3>
                                    <p class="text-muted mb-0">
                                        Pendaftaran Anda ditolak oleh panitia.
                                    </p>
                                @endif
                            </div>

                            {{-- Info Data --}}
                            <div class="bg-light rounded-3 p-4 mb-3">
                                <table class="table table-borderless mb-0 align-middle">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted ps-0" style="width: 40%;">
                                                <i class="bi bi-hash me-1"></i> NIS
                                            </td>
                                            <td class="pe-0">
                                                <code class="fs-6">{{ $voter->nis }}</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted ps-0">
                                                <i class="bi bi-person me-1"></i> Nama
                                            </td>
                                            <td class="pe-0 fw-medium">{{ $voter->name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted ps-0">
                                                <i class="bi bi-mortarboard me-1"></i> Kelas
                                            </td>
                                            <td class="pe-0">{{ $voter->classRoom?->name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted ps-0">
                                                <i class="bi bi-calendar-plus me-1"></i> Terdaftar
                                            </td>
                                            <td class="pe-0">
                                                {{ $voter->created_at->translatedFormat('d M Y, H:i') }}
                                            </td>
                                        </tr>

                                        @if ($voter->verified_at)
                                            <tr>
                                                <td class="text-muted ps-0">
                                                    <i class="bi bi-patch-check me-1"></i> Diverifikasi
                                                </td>
                                                <td class="pe-0">
                                                    {{ $voter->verified_at->translatedFormat('d M Y, H:i') }}
                                                    @if ($voter->verifier)
                                                        <small class="text-muted d-block">
                                                            oleh {{ $voter->verifier->name }}
                                                        </small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            {{-- Alasan Reject --}}
                            @if ($status === \App\Enums\VoterStatus::REJECTED && $voter->alasan_reject)
                                <div class="alert alert-danger mb-3">
                                    <strong>
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        Alasan Penolakan:
                                    </strong>
                                    <p class="mb-0 mt-2">{{ $voter->alasan_reject }}</p>
                                </div>
                            @endif

                            {{-- Action --}}
                            @if ($status === \App\Enums\VoterStatus::VERIFIED)
                                <a href="{{ route('login') }}" class="btn w-100"
                                    style="background: #c6f135; color: #1a2e1a;
                                          font-weight: 700; padding: 12px;">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                    Login untuk Voting
                                </a>
                            @elseif ($status === \App\Enums\VoterStatus::PENDING)
                                <div class="alert alert-warning mb-0">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Mohon tunggu.</strong>
                                    Akun Anda akan diverifikasi panitia. Cek lagi nanti.
                                </div>
                            @elseif ($status === \App\Enums\VoterStatus::REJECTED)
                                <div class="alert alert-danger mb-3">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Ingin mendaftar ulang?</strong>
                                    Hubungi panitia Pilketos untuk informasi lebih lanjut.
                                </div>
                                <a href="{{ route('register') }}" class="btn w-100 btn-outline-danger">
                                    <i class="bi bi-arrow-counterclockwise"></i> Daftar Ulang
                                </a>
                            @endif

                        </div>
                    </div>
                @else
                    {{-- NIS TIDAK DITEMUKAN --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-5 text-center">

                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px; background: #f8d7da;">
                                <i class="bi bi-person-x" style="font-size: 3rem; color: #dc3545;"></i>
                            </div>

                            <h3 class="fw-bold mb-2">NIS Tidak Ditemukan</h3>
                            <p class="text-muted mb-4">
                                NIS <code>{{ $nis }}</code> tidak terdaftar sebagai pemilih Pilketos.
                            </p>

                            <div class="alert alert-info text-start small">
                                <strong>Kemungkinan:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>NIS salah ketik → cek lagi</li>
                                    <li>Belum daftar → <a href="{{ route('register') }}">daftar di sini</a></li>
                                    <li>Lupa NIS → hubungi panitia</li>
                                </ul>
                            </div>

                            <a href="{{ route('cek-status.index') }}" class="btn w-100" style="background: #c6f135; color: #1a2e1a; font-weight: 700; padding: 12px;">
                                <i class="bi bi-arrow-left"></i> Coba Lagi
                            </a>

                        </div>
                    </div>
                @endif

            </div>
        </div>

    </div>

    {{-- FOOTER --}}
    <footer class="text-center py-4 text-muted small">
        &copy; {{ date('Y') }} {{ config('app.name', 'Pilketos') }}
    </footer>

</body>

</html>
