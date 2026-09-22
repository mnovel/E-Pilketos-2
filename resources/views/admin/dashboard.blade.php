@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Ringkasan aktivitas Pilketos')

@section('breadcrumb')
    <li class="breadcrumb-item active text-main" aria-current="page">Dashboard</li>
@endsection

@section('content')

    {{-- ==========================================
         WELCOME BANNER
         ========================================== --}}
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #c6f135 0%, #a8d92d 100%);">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 80px; height: 80px; background: white;
                            color: #1a2e1a; font-weight: 700; font-size: 2rem;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-grow-1 text-center text-md-start">
                    <h3 class="mb-1" style="color: #1a2e1a;">
                        Halo, {{ auth()->user()->name }}! 👋
                    </h3>
                    <p class="mb-0" style="color: #1a2e1a; opacity: 0.8;">
                        Selamat datang di panel admin Pilketos.
                        Kelola pemilihan, kandidat, dan pemilih dari sini.
                    </p>
                </div>
                <div class="text-center">
                    <div class="fw-bold" style="color: #1a2e1a; font-size: 1.1rem;">
                        {{ now()->translatedFormat('l, d M Y') }}
                    </div>
                    <div style="color: #1a2e1a; opacity: 0.7; font-size: 0.85rem;">
                        {{ now()->format('H:i') }} WIB
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==========================================
         STATISTIK LIVE (auto-refresh 10 detik)
         ========================================== --}}
    <div class="card border-0 shadow-sm mb-4" id="liveStatsCard">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong class="d-flex align-items-center gap-2">
                <i class="bi bi-broadcast text-success"></i>
                Statistik Live
                <span class="badge bg-secondary-subtle text-secondary" id="liveBadge">Memuat...</span>
            </strong>
            <small class="text-muted d-flex align-items-center gap-2">
                <i class="bi bi-arrow-clockwise"></i>
                <span>Update: <span id="liveUpdatedAt">—</span></span>
                <span class="spinner-border spinner-border-sm d-none" id="liveSpinner"></span>
            </small>
        </div>

        <div class="card-body">
            {{-- Election aktif --}}
            <div id="liveElectionBox" class="d-none">
                <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1" id="liveElectionTitle">—</h5>
                        <div class="text-muted small" id="liveElectionYear">—</div>
                    </div>
                    <a href="#" id="liveElectionLink" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-box-arrow-up-right"></i> Detail Pemilihan
                    </a>
                </div>

                <div class="row g-3 text-center">
                    <div class="col-md-2 col-6">
                        <div class="p-3 rounded-3" style="background: #cff4fc;">
                            <div class="fs-3 fw-bold text-info" id="liveCheckedIn">0</div>
                            <div class="text-muted small">Check-in</div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-3 rounded-3" style="background: #d1e7dd;">
                            <div class="fs-3 fw-bold text-success" id="liveVoted">0</div>
                            <div class="text-muted small">Sudah Memilih</div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-3 rounded-3" style="background: #f8f9fa;">
                            <div class="fs-3 fw-bold" id="liveTotalVoters">0</div>
                            <div class="text-muted small">Total Pemilih</div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-3 rounded-3" style="background: #cfe2ff;">
                            <div class="fs-3 fw-bold text-primary" id="liveParticipation">0%</div>
                            <div class="text-muted small">Partisipasi</div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-3 rounded-3" style="background: #fff3cd;">
                            <div class="fs-3 fw-bold" style="color: #cc9a06;" id="liveSessions">0</div>
                            <div class="text-muted small">Sesi Aktif</div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-3 rounded-3" style="background: #e8f5c8;">
                            <div class="fs-4 fw-bold" style="color: #7cb518;" id="liveDevices">0/0</div>
                            <div class="text-muted small">Device Online</div>
                        </div>
                    </div>
                </div>

                {{-- Progress bar --}}
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted fw-medium">Progress Partisipasi</small>
                        <small class="fw-bold" id="liveProgressLabel">0%</small>
                    </div>
                    <div class="progress" style="height: 22px; border-radius: 6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" id="liveProgress" role="progressbar" style="width: 0%;">0%</div>
                    </div>
                </div>
            </div>

            {{-- Tidak ada election aktif --}}
            <div id="liveNoElection" class="text-center py-4">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px; background: #f8f9fa;">
                    <i class="bi bi-broadcast" style="font-size: 1.8rem; opacity: 0.4;"></i>
                </div>
                <p class="mt-2 mb-1 fw-medium text-muted">Tidak ada pemilihan aktif</p>
                <small class="text-muted">
                    Statistik akan muncul otomatis saat ada pemilihan berlangsung.
                </small>
            </div>
        </div>
    </div>

    {{-- ==========================================
         STATS PEMILIH
         ========================================== --}}
    <div class="mb-2">
        <h6 class="text-muted small mb-2 text-uppercase">
            <i class="bi bi-people-fill me-1"></i> Statistik Pemilih
        </h6>
    </div>

    <div class="row g-3 mb-4">

        {{-- Total Pemilih --}}
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background: #cfe2ff;">
                        <i class="bi bi-people-fill fs-4 text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Total Pemilih</div>
                        <div class="fs-3 fw-bold">{{ $stats['total_voters'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Menunggu Verifikasi --}}
        <div class="col-md-3 col-6">
            <a href="{{ route('admin.voters.index', ['status' => 'pending']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background: #fff3cd;">
                            <i class="bi bi-hourglass-split fs-4" style="color: #cc9a06;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Menunggu</div>
                            <div class="fs-3 fw-bold" style="color: #cc9a06;">
                                {{ $stats['pending_voters'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- Terverifikasi --}}
        <div class="col-md-3 col-6">
            <a href="{{ route('admin.voters.index', ['status' => 'verified']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background: #d1e7dd;">
                            <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Terverifikasi</div>
                            <div class="fs-3 fw-bold text-success">
                                {{ $stats['verified_voters'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- Ditolak --}}
        <div class="col-md-3 col-6">
            <a href="{{ route('admin.voters.index', ['status' => 'rejected']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background: #f8d7da;">
                            <i class="bi bi-x-circle-fill fs-4 text-danger"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Ditolak</div>
                            <div class="fs-3 fw-bold text-danger">
                                {{ $stats['rejected_voters'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- ==========================================
         STATS ELECTION
         ========================================== --}}
    @if (isset($stats['total_elections']) || isset($stats['active_elections']))
        <div class="mb-2">
            <h6 class="text-muted small mb-2 text-uppercase">
                <i class="bi bi-calendar-event-fill me-1"></i> Statistik Pemilihan
            </h6>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background: #e8f5c8;">
                            <i class="bi bi-calendar-event-fill fs-4" style="color: #7cb518;"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">Total Pemilihan</div>
                            <div class="fs-3 fw-bold">{{ $stats['total_elections'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background: #d1e7dd;">
                            <i class="bi bi-broadcast fs-4 text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">Berlangsung</div>
                            <div class="fs-3 fw-bold text-success">
                                {{ $stats['active_elections'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background: #fff3cd;">
                            <i class="bi bi-stop-circle-fill fs-4" style="color: #cc9a06;"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">Ditutup</div>
                            <div class="fs-3 fw-bold" style="color: #cc9a06;">
                                {{ $stats['closed_elections'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background: #cfe2ff;">
                            <i class="bi bi-megaphone-fill fs-4 text-primary"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">Dipublikasi</div>
                            <div class="fs-3 fw-bold text-primary">
                                {{ $stats['published_elections'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ==========================================
         QUICK ACTIONS
         ========================================== --}}
    <div class="mb-2">
        <h6 class="text-muted small mb-2 text-uppercase">
            <i class="bi bi-lightning-charge-fill me-1"></i> Aksi Cepat
        </h6>
    </div>

    <div class="row g-3 mb-4">

        {{-- Verifikasi Pemilih --}}
        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.voters.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: #cfe2ff;">
                            <i class="bi bi-people-fill fs-3 text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-dark mb-1">Verifikasi Pemilih</h6>
                            <p class="text-muted small mb-0">
                                Kelola pendaftaran voter
                            </p>
                        </div>
                        <i class="bi bi-arrow-right text-muted"></i>
                    </div>
                </div>
            </a>
        </div>

        {{-- Kelola Pemilihan --}}
        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.elections.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: #e8f5c8;">
                            <i class="bi bi-calendar-event-fill fs-3" style="color: #7cb518;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-dark mb-1">Kelola Pemilihan</h6>
                            <p class="text-muted small mb-0">
                                Buat & atur pemilihan
                            </p>
                        </div>
                        <i class="bi bi-arrow-right text-muted"></i>
                    </div>
                </div>
            </a>
        </div>

        {{-- Kelola Kandidat --}}
        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.candidates.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: #d1e7dd;">
                            <i class="bi bi-person-badge fs-3 text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-dark mb-1">Kelola Kandidat</h6>
                            <p class="text-muted small mb-0">
                                Tambah & atur kandidat
                            </p>
                        </div>
                        <i class="bi bi-arrow-right text-muted"></i>
                    </div>
                </div>
            </a>
        </div>

        {{-- Sesi Voting --}}
        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.sessions.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: #cff4fc;">
                            <i class="bi bi-clock-history fs-3 text-info"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-dark mb-1">Sesi Voting</h6>
                            <p class="text-muted small mb-0">
                                Jadwal sesi per kelas
                            </p>
                        </div>
                        <i class="bi bi-arrow-right text-muted"></i>
                    </div>
                </div>
            </a>
        </div>

    </div>

    {{-- ==========================================
         DEVICE ACCESS
         ========================================== --}}
    <div class="mb-2">
        <h6 class="text-muted small mb-2 text-uppercase">
            <i class="bi bi-display me-1"></i> Device Voting
        </h6>
    </div>

    <div class="row g-3">

        {{-- Device Check-in --}}
        <div class="col-md-6">
            <a href="{{ route('device.checkin.index') }}" target="_blank" rel="noopener" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: #cff4fc;">
                            <i class="bi bi-door-open-fill fs-3 text-info"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-dark mb-1">
                                Device Check-in
                                <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.7rem;"></i>
                            </h6>
                            <p class="text-muted small mb-0">
                                Buka pintu masuk bilik suara (tab baru)
                            </p>
                        </div>
                        <i class="bi bi-arrow-right text-muted"></i>
                    </div>
                </div>
            </a>
        </div>

        {{-- Device Voting --}}
        <div class="col-md-6">
            <a href="{{ route('device.voting.index') }}" target="_blank" rel="noopener" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: #d1e7dd;">
                            <i class="bi bi-box-arrow-in-right fs-3 text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-dark mb-1">
                                Device Voting
                                <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.7rem;"></i>
                            </h6>
                            <p class="text-muted small mb-0">
                                Buka bilik suara untuk voting (tab baru)
                            </p>
                        </div>
                        <i class="bi bi-arrow-right text-muted"></i>
                    </div>
                </div>
            </a>
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        (function() {
            const url = "{{ route('admin.dashboard.live-stats') }}";
            const $ = (id) => document.getElementById(id);
            const POLL_MS = 10000; // 10 detik

            async function fetchStats() {
                const spinner = $('liveSpinner');
                if (spinner) spinner.classList.remove('d-none');

                try {
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    const data = await res.json();

                    $('liveUpdatedAt').textContent = data.updated_at;

                    const el = data.active_election;

                    if (!el) {
                        // Idle — tidak ada election aktif
                        $('liveElectionBox').classList.add('d-none');
                        $('liveNoElection').classList.remove('d-none');

                        const badge = $('liveBadge');
                        badge.className = 'badge bg-secondary-subtle text-secondary';
                        badge.textContent = 'Idle';
                    } else {
                        // Aktif — tampilkan data
                        $('liveElectionBox').classList.remove('d-none');
                        $('liveNoElection').classList.add('d-none');

                        const badge = $('liveBadge');
                        badge.className = 'badge bg-success-subtle text-success';
                        badge.textContent = 'Live';

                        $('liveElectionTitle').textContent = el.title;
                        $('liveElectionYear').textContent = el.tahun_ajaran;
                        $('liveElectionLink').href = '/admin/elections/' + el.id;

                        $('liveCheckedIn').textContent = el.checked_in;
                        $('liveVoted').textContent = el.has_voted;
                        $('liveTotalVoters').textContent = el.total_voters;
                        $('liveParticipation').textContent = el.participation_pct + '%';
                        $('liveSessions').textContent = el.active_sessions;

                        const totalOnline = el.checkin_online + el.voting_online;
                        const totalDevice = el.checkin_total + el.voting_total;
                        $('liveDevices').textContent = totalOnline + '/' + totalDevice;

                        // Progress bar
                        const pct = el.participation_pct;
                        const bar = $('liveProgress');
                        bar.style.width = pct + '%';
                        bar.textContent = pct + '%';

                        if (pct >= 75) {
                            bar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
                        } else if (pct >= 40) {
                            bar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-warning';
                        } else {
                            bar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-danger';
                        }

                        $('liveProgressLabel').textContent = pct + '%';
                    }
                } catch (e) {
                    console.error('Live stats error:', e);
                    $('liveUpdatedAt').textContent = 'error';

                    const badge = $('liveBadge');
                    badge.className = 'badge bg-danger-subtle text-danger';
                    badge.textContent = 'Error';
                } finally {
                    if (spinner) spinner.classList.add('d-none');
                }
            }

            // Load pertama + interval
            fetchStats();
            setInterval(fetchStats, POLL_MS);

            // Refresh saat tab kembali aktif
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) fetchStats();
            });
        })();
    </script>
@endpush
