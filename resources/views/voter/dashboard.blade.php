@extends('layouts.admin')

@section('title', 'Dashboard Pemilih')
@section('page-title', 'Dashboard Pemilih')
@section('page-subtitle', 'Selamat datang, ' . auth()->user()->name)

@section('breadcrumb')
    <li class="breadcrumb-item active text-main" aria-current="page">Dashboard</li>
@endsection

@section('content')

    {{-- ==========================================
         PROFILE HEADER
         ========================================== --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">

                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 90px; height: 90px; background: #c6f135;
                            color: #1a2e1a; font-weight: 700; font-size: 2rem;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>

                <div class="flex-grow-1 text-center text-md-start">
                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <p class="text-muted mb-2">
                        NIS: <strong>{{ $user->nis ?? '-' }}</strong> ·
                        Kelas: <strong>{{ $user->classRoom?->name ?? '-' }}</strong>
                    </p>

                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                        @if ($isVerified)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle me-1"></i> Terverifikasi
                            </span>
                        @else
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-hourglass-split me-1"></i> Menunggu Verifikasi
                            </span>
                        @endif

                        @if ($voter && $voter->checked_in)
                            <span class="badge bg-info text-white">
                                <i class="bi bi-door-open me-1"></i> Sudah Check-in
                            </span>
                        @endif

                        @if ($voter && $voter->has_voted)
                            <span class="badge bg-primary">
                                <i class="bi bi-check2-square me-1"></i> Sudah Memilih
                            </span>
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-column gap-2">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="btn-custom btn-custom-outline-secondary w-100">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ==========================================
         ALERT: BELUM VERIFIED
         ========================================== --}}
    @if (!$isVerified)
        <div class="alert-custom alert-custom-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>Akun Anda belum diverifikasi panitia.</strong>
                Anda belum bisa memilih sampai akun diverifikasi. Hubungi panitia Pilketos.
            </div>
        </div>
    @endif

    {{-- ==========================================
         ALERT: SUDAH VOTE
         ========================================== --}}
    @if ($voter && $voter->has_voted)
        <div class="alert-custom alert-custom-success mb-4">
            <i class="bi bi-check-circle-fill alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>Terima kasih!</strong>
                Suara Anda sudah tercatat
                @if ($voter->voted_at)
                    pada {{ $voter->voted_at->translatedFormat('d M Y, H:i') }}
                @endif.
                Anda tidak bisa memilih lagi.
            </div>
        </div>
    @endif

    {{-- ==========================================
         ALERT: CARA VOTING
         ========================================== --}}
    @if ($voter && $isVerified && !$voter->has_voted)
        <div class="alert-custom alert-custom-info mb-4">
            <i class="bi bi-info-circle-fill alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>Cara memilih:</strong>
                Datang ke bilik suara sesuai jadwal kelas Anda.
                Scan QR yang tampil di device menggunakan HP ini, lalu pilih kandidat di layar device.
            </div>
        </div>
    @endif

    {{-- ==========================================
         STATUS PEMILIH
         ========================================== --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <strong>
                <i class="bi bi-clipboard-check text-success me-1"></i>
                Status Pemilih
            </strong>
        </div>
        <div class="card-body">

            @if ($voter)
                {{-- Info Pemilihan --}}
                <div class="mb-4">
                    <div class="text-muted small mb-1">Pemilihan</div>
                    <div class="fw-medium fs-5">{{ $voter->election->title }}</div>
                    <small class="text-muted">
                        {{ $voter->election->tahun_ajaran }} ·
                        {{ $voter->election->start_at->translatedFormat('d M Y') }}
                        –
                        {{ $voter->election->end_at->translatedFormat('d M Y') }}
                    </small>
                </div>

                {{-- Progress Steps --}}
                <div class="row g-3">

                    {{-- Step 1: Terdaftar --}}
                    <div class="col-md-4">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background: #d1e7dd;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: #198754; color: white;">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <div>
                                <div class="fw-medium small">Terdaftar</div>
                                <small class="text-muted">
                                    Kelas {{ $voter->classRoom?->name ?? '-' }}
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- Step 2: Check-in --}}
                    <div class="col-md-4">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background: {{ $voter->checked_in ? '#d1e7dd' : '#f8f9fa' }};">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 40px; height: 40px;
                                        background: {{ $voter->checked_in ? '#198754' : '#e9ecef' }};
                                        color: {{ $voter->checked_in ? 'white' : '#adb5bd' }};">
                                <i class="bi {{ $voter->checked_in ? 'bi-check-lg' : 'bi-hourglass' }}"></i>
                            </div>
                            <div>
                                <div class="fw-medium small">Check-in</div>
                                <small class="text-muted">
                                    @if ($voter->checked_in)
                                        {{ $voter->checked_in_at->translatedFormat('d M Y, H:i') }}
                                    @else
                                        Belum check-in
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- Step 3: Vote --}}
                    <div class="col-md-4">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background: {{ $voter->has_voted ? '#d1e7dd' : '#f8f9fa' }};">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 40px; height: 40px;
                                        background: {{ $voter->has_voted ? '#198754' : '#e9ecef' }};
                                        color: {{ $voter->has_voted ? 'white' : '#adb5bd' }};">
                                <i class="bi {{ $voter->has_voted ? 'bi-check-lg' : 'bi-hourglass' }}"></i>
                            </div>
                            <div>
                                <div class="fw-medium small">Memilih</div>
                                <small class="text-muted">
                                    @if ($voter->has_voted)
                                        {{ $voter->voted_at->translatedFormat('d M Y, H:i') }}
                                    @else
                                        Belum memilih
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>

                </div>
            @else
                <div class="text-center text-muted py-5">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: #f8f9fa;">
                        <i class="bi bi-clipboard-x" style="font-size: 2rem; opacity: 0.5;"></i>
                    </div>
                    <p class="mt-2 mb-1 fw-medium">Belum ada data pemilihan</p>
                    <small>Anda belum di-assign ke pemilihan oleh panitia</small>
                </div>
            @endif

        </div>
    </div>

    {{-- ==========================================
         JADWAL SESI KELAS
         ========================================== --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <strong>
                <i class="bi bi-calendar-event text-success me-1"></i>
                Jadwal Sesi Kelas {{ $user->classRoom?->name ?? '-' }}
            </strong>
            <span class="badge bg-secondary">{{ $sessions->count() }} sesi</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Pemilihan</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sessions as $session)
                            @php
                                $runtime = $session->getRuntimeStatus();
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-medium">
                                        {{ $session->election->title }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $session->election->tahun_ajaran }}
                                    </small>
                                </td>
                                <td>
                                    <i class="bi bi-calendar text-muted"></i>
                                    {{ $session->tanggal->translatedFormat('d M Y') }}
                                </td>
                                <td>
                                    <i class="bi bi-clock text-muted"></i>
                                    {{ \Carbon\Carbon::parse($session->waktu_mulai)->format('H:i') }}
                                    –
                                    {{ \Carbon\Carbon::parse($session->waktu_selesai)->format('H:i') }}
                                </td>
                                <td class="text-center">
                                    @if ($runtime === 'scheduled')
                                        <span class="badge-table pending">
                                            <i class="bi bi-clock"></i> Terjadwal
                                        </span>
                                    @elseif ($runtime === 'ready')
                                        <span class="badge-table warning">
                                            <i class="bi bi-hourglass-split"></i> Siap Aktif
                                        </span>
                                    @elseif ($runtime === 'active')
                                        <span class="badge-table success">
                                            <i class="bi bi-broadcast"></i> Sedang Berlangsung
                                        </span>
                                    @elseif ($runtime === 'expired')
                                        <span class="badge-table failed">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Terlewat
                                        </span>
                                    @else
                                        <span class="badge-table failed">
                                            <i class="bi bi-stop-circle-fill"></i> Selesai
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: #f8f9fa;">
                                        <i class="bi bi-calendar-x" style="font-size: 2rem; opacity: 0.5;"></i>
                                    </div>
                                    <p class="mt-2 mb-1 fw-medium">Belum ada jadwal sesi</p>
                                    <small>Hubungi panitia kalau sesi kelasmu belum dibuat</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
