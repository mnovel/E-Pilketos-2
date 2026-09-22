@extends('layouts.admin')

@section('title', 'Dashboard Operator')
@section('page-title', 'Dashboard Operator')
@section('page-subtitle', 'Selamat datang, ' . auth()->user()->name)

@section('breadcrumb')
    <li class="breadcrumb-item active text-main" aria-current="page">Dashboard</li>
@endsection

@section('content')

    {{-- ==========================================
         ALERT: SESI AKTIF
         ========================================== --}}
    @if ($activeSessions->count() > 0)
        <div class="alert-custom alert-custom-success mb-4">
            <i class="bi bi-broadcast alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>{{ $activeSessions->count() }} sesi sedang aktif.</strong>
                Buka device check-in atau voting untuk memulai.
            </div>
        </div>
    @endif

    {{-- ==========================================
         STATS
         ========================================== --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Sesi Hari Ini</div>
                            <div class="fs-2 fw-bold">{{ $stats['total_sessions_today'] }}</div>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-calendar-event fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Sesi Aktif</div>
                            <div class="fs-2 fw-bold text-success">{{ $stats['total_active'] }}</div>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-broadcast fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Check-in Hari Ini</div>
                            <div class="fs-2 fw-bold text-info">{{ $stats['total_checkin_today'] }}</div>
                        </div>
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="bi bi-door-open-fill fs-4 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1">Sudah Memilih</div>
                            <div class="fs-2 fw-bold text-warning">{{ $stats['total_voted_today'] }}</div>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="bi bi-check2-square fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==========================================
         QUICK ACTIONS
         ========================================== --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <a href="{{ route('device.checkin.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                <i class="bi bi-door-open-fill fs-2 text-info"></i>
                            </div>
                            <div>
                                <h5 class="text-dark mb-1">Device Check-in</h5>
                                <p class="text-muted small mb-0">
                                    Buka device pintu untuk absensi siswa
                                </p>
                            </div>
                            <i class="bi bi-arrow-right fs-4 text-muted ms-auto"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6">
            <a href="{{ route('device.voting.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="bi bi-box-arrow-in-right fs-2 text-success"></i>
                            </div>
                            <div>
                                <h5 class="text-dark mb-1">Device Voting</h5>
                                <p class="text-muted small mb-0">
                                    Buka bilik suara untuk voting
                                </p>
                            </div>
                            <i class="bi bi-arrow-right fs-4 text-muted ms-auto"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- ==========================================
         SESI AKTIF SEKARANG
         ========================================== --}}
    @if ($activeSessions->count() > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <strong>
                    <i class="bi bi-broadcast text-success me-1"></i>
                    Sesi Sedang Berlangsung
                </strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Kelas</th>
                                <th>Pemilihan</th>
                                <th>Waktu</th>
                                <th class="text-center">Progress</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activeSessions as $session)
                                @php
                                    $total = $session->voters()->count();
                                    $checkedIn = $session->voters()->where('checked_in', true)->count();
                                    $voted = $session->voters()->where('has_voted', true)->count();
                                    $pct = $total > 0 ? round(($voted / $total) * 100) : 0;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold fs-5">
                                            {{ $session->classRoom?->name ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $session->election->title }}
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($session->waktu_mulai)->format('H:i') }}
                                            –
                                            {{ \Carbon\Carbon::parse($session->waktu_selesai)->format('H:i') }}
                                        </small>
                                    </td>
                                    <td style="min-width: 180px;">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <small class="text-muted" style="min-width: 60px;">
                                                Vote
                                            </small>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-success" style="width: {{ $pct }}%;"></div>
                                            </div>
                                            <small class="text-muted" style="min-width: 45px;">
                                                {{ $voted }}/{{ $total }}
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <small class="text-muted" style="min-width: 60px;">
                                                Check-in
                                            </small>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-info" style="width: {{ $total > 0 ? round(($checkedIn / $total) * 100) : 0 }}%;"></div>
                                            </div>
                                            <small class="text-muted" style="min-width: 45px;">
                                                {{ $checkedIn }}/{{ $total }}
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="{{ route('device.checkin.index') }}" class="btn-custom btn-custom-sm btn-custom-outline-primary" title="Check-in">
                                                <i class="bi bi-door-open"></i>
                                            </a>
                                            <a href="{{ route('device.voting.index') }}" class="btn-custom btn-custom-sm btn-custom-outline-secondary" title="Voting">
                                                <i class="bi bi-box-arrow-in-right"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ==========================================
         JADWAL HARI INI
         ========================================== --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <strong>
                <i class="bi bi-clock-history text-primary me-1"></i>
                Jadwal Sesi Hari Ini
            </strong>
            <span class="badge bg-secondary">{{ $todaySessions->count() }} sesi</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kelas</th>
                            <th>Waktu</th>
                            <th>Operator</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($todaySessions as $session)
                            <tr>
                                <td>
                                    <div class="fw-medium">
                                        {{ $session->classRoom?->name ?? '-' }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $session->election->title }}
                                    </small>
                                </td>
                                <td>
                                    <i class="bi bi-clock text-muted"></i>
                                    {{ \Carbon\Carbon::parse($session->waktu_mulai)->format('H:i') }}
                                    –
                                    {{ \Carbon\Carbon::parse($session->waktu_selesai)->format('H:i') }}
                                </td>
                                <td>
                                    @if ($session->operator)
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-person"></i>
                                            {{ $session->operator->name }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($session->status->value === 'active')
                                        <span class="badge-table success">Aktif</span>
                                    @elseif ($session->status->value === 'closed')
                                        <span class="badge-table failed">Selesai</span>
                                    @else
                                        <span class="badge-table pending">Terjadwal</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x" style="font-size: 3rem; opacity: 0.5;"></i>
                                    <p class="mt-2 mb-0">Tidak ada sesi hari ini</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
