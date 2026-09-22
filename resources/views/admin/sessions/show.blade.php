@extends('layouts.admin')

@section('title', 'Detail Sesi')
@section('page-title', 'Detail Sesi')
@section('page-subtitle', ($session->classRoom?->name ?? '-') . ' — ' . $session->election->title)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.sessions.index', ['election_id' => $session->election_id]) }}" class="text-decoration-none text-muted-green">
            Sesi Voting
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">
        {{ $session->classRoom?->name ?? '-' }}
    </li>
@endsection

@section('content')

    @php $runtime = $session->getRuntimeStatus(); @endphp

    {{-- ==========================================
         BACK + ACTIONS
         ========================================== --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="{{ route('admin.sessions.index', ['election_id' => $session->election_id]) }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>

        <div class="d-flex gap-2 flex-wrap">

            @if ($runtime === 'scheduled')
                <a href="{{ route('admin.sessions.edit', $session) }}" class="btn-custom btn-custom-warning">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <button type="button" class="btn-custom btn-custom-light" onclick="confirmAssign()">
                    <i class="bi bi-people"></i> Assign Pemilih
                </button>
                <button type="button" class="btn-custom btn-custom-danger" onclick="confirmDelete()">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            @endif

            @if ($runtime === 'ready')
                <a href="{{ route('admin.sessions.edit', $session) }}" class="btn-custom btn-custom-warning">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <button type="button" class="btn-custom btn-custom-light" onclick="confirmAssign()">
                    <i class="bi bi-people"></i> Assign Pemilih
                </button>
            @endif

            @if ($runtime === 'active')
                <button type="button" class="btn-custom btn-custom-warning" onclick="confirmClose()">
                    <i class="bi bi-stop-circle"></i> Tutup Sekarang
                </button>
            @endif

            @if ($runtime === 'expired')
                <span class="badge bg-danger py-2 px-3">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Sesi terlewat
                </span>
            @endif

            @if ($runtime === 'closed')
                <span class="badge bg-dark py-2 px-3">
                    <i class="bi bi-stop-circle-fill"></i>
                    Sesi sudah selesai
                </span>
            @endif

        </div>
    </div>

    {{-- ==========================================
         AUTO-INFO ALERTS
         ========================================== --}}
    @if ($runtime === 'ready')
        <div class="alert-custom alert-custom-warning mb-4">
            <i class="bi bi-hourglass-split alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>Menunggu aktivasi otomatis.</strong>
                Sistem akan mengaktifkan sesi ini pada
                <strong>{{ $session->startDateTime()->translatedFormat('d M Y, H:i') }}</strong>
                (scheduler cek tiap menit, asalkan election sudah aktif).
            </div>
        </div>
    @endif

    @if ($runtime === 'active')
        @php $sisa = now()->diff($session->endDateTime()); @endphp
        <div class="alert-custom alert-custom-success mb-4">
            <i class="bi bi-broadcast alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>Sesi sedang berlangsung!</strong>
                Sisa waktu:
                @if ($sisa->h > 0)
                    <strong>{{ $sisa->h }} jam {{ $sisa->i }} menit</strong>
                @else
                    <strong>{{ $sisa->i }} menit</strong>
                @endif
                — Akan otomatis ditutup pada
                {{ $session->endDateTime()->translatedFormat('d M Y, H:i') }}.
            </div>
        </div>
    @endif

    @if ($runtime === 'expired')
        <div class="alert-custom alert-custom-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>Sesi terlewat.</strong>
                Sesi ini tidak pernah diaktifkan sampai waktu selesai
                ({{ $session->endDateTime()->translatedFormat('d M Y, H:i') }}).
                Pemilih di kelas ini tidak bisa vote di sesi ini.
            </div>
        </div>
    @endif

    {{-- ==========================================
         HEADER PROFILE
         ========================================== --}}
    @php
        $runtimeIcon = match ($runtime) {
            'scheduled' => 'bi-clock',
            'ready' => 'bi-hourglass-split',
            'active' => 'bi-broadcast',
            'expired' => 'bi-exclamation-triangle-fill',
            'closed' => 'bi-stop-circle-fill',
            default => 'bi-question-circle',
        };
        $runtimeColor = match ($runtime) {
            'scheduled' => ['#e9ecef', '#6c757d'],
            'ready' => ['#fff3cd', '#cc9a06'],
            'active' => ['#d1e7dd', '#0f5132'],
            'expired' => ['#f8d7da', '#842029'],
            'closed' => ['#e9ecef', '#6c757d'],
            default => ['#e9ecef', '#6c757d'],
        };
    @endphp

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">

                {{-- Avatar Kelas --}}
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 100px; height: 100px; background: #c6f135;
                            color: #1a2e1a; font-weight: 700; font-size: 1.3rem;">
                    {{ $session->classRoom?->name ?? '-' }}
                </div>

                <div class="flex-grow-1 text-center text-md-start">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-1">
                        <h3 class="mb-0">Sesi {{ $session->classRoom?->name ?? '-' }}</h3>
                    </div>
                    <p class="text-muted mb-3">
                        {{ $session->election->title }} — {{ $session->election->tahun_ajaran }}
                    </p>

                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-calendar-event me-1"></i>
                            {{ $session->tanggal->translatedFormat('d M Y') }}
                        </span>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-clock me-1"></i>
                            {{ \Carbon\Carbon::parse($session->waktu_mulai)->format('H:i') }}
                            –
                            {{ \Carbon\Carbon::parse($session->waktu_selesai)->format('H:i') }}
                        </span>

                        @if ($session->operator)
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-person me-1"></i>
                                {{ $session->operator->name }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Runtime Badge Besar --}}
                <div class="text-center flex-shrink-0">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 64px; height: 64px; background: {{ $runtimeColor[0] }};">
                        <i class="bi {{ $runtimeIcon }} fs-3" style="color: {{ $runtimeColor[1] }};"></i>
                    </div>
                    @if ($runtime === 'scheduled')
                        <span class="badge bg-secondary">Terjadwal</span>
                    @elseif ($runtime === 'ready')
                        <span class="badge bg-warning text-dark">Siap Aktif</span>
                    @elseif ($runtime === 'active')
                        <span class="badge bg-success">Berlangsung</span>
                    @elseif ($runtime === 'expired')
                        <span class="badge bg-danger">Terlewat</span>
                    @elseif ($runtime === 'closed')
                        <span class="badge bg-dark">Selesai</span>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- ==========================================
         STATS
         ========================================== --}}
    <div class="row g-3 mb-4">

        {{-- Total Pemilih --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: #cfe2ff;">
                        <i class="bi bi-people-fill fs-4 text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Total Pemilih</div>
                        <div class="fs-3 fw-bold">{{ $stats['total_voters'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sudah Check-in --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: #cff4fc;">
                        <i class="bi bi-door-open-fill fs-4 text-info"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Sudah Check-in</div>
                        <div class="fs-3 fw-bold text-info">{{ $stats['total_checked_in'] }}</div>
                        @if ($stats['total_voters'] > 0)
                            <small class="text-muted">
                                {{ round(($stats['total_checked_in'] / $stats['total_voters']) * 100) }}%
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Sudah Memilih --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: #d1e7dd;">
                        <i class="bi bi-check2-square fs-4 text-success"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Sudah Memilih</div>
                        <div class="fs-3 fw-bold text-success">{{ $stats['total_voted'] }}</div>
                        @if ($stats['total_voters'] > 0)
                            <small class="text-muted">
                                {{ round(($stats['total_voted'] / $stats['total_voters']) * 100) }}%
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ==========================================
         PROGRESS OVERVIEW
         ========================================== --}}
    @if ($stats['total_voters'] > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <strong>
                    <i class="bi bi-graph-up-arrow text-success me-1"></i>
                    Progress
                </strong>
            </div>
            <div class="card-body">
                @php
                    $pctCheckin = round(($stats['total_checked_in'] / $stats['total_voters']) * 100);
                    $pctVoted = round(($stats['total_voted'] / $stats['total_voters']) * 100);
                @endphp

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">
                            <i class="bi bi-door-open text-info"></i> Check-in
                        </span>
                        <span class="small fw-bold">
                            {{ $stats['total_checked_in'] }} / {{ $stats['total_voters'] }}
                            ({{ $pctCheckin }}%)
                        </span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-info" style="width: {{ $pctCheckin }}%;" role="progressbar"></div>
                    </div>
                </div>

                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">
                            <i class="bi bi-check2-square text-success"></i> Memilih
                        </span>
                        <span class="small fw-bold">
                            {{ $stats['total_voted'] }} / {{ $stats['total_voters'] }}
                            ({{ $pctVoted }}%)
                        </span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-success" style="width: {{ $pctVoted }}%;" role="progressbar"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ==========================================
         VOTER LIST
         ========================================== --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <strong>
                <i class="bi bi-people text-success me-1"></i>
                Daftar Pemilih
            </strong>
            <span class="badge bg-secondary">{{ $voters->count() }} orang</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th class="text-center">Check-in</th>
                            <th class="text-center">Memilih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($voters as $i => $voter)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td><code>{{ $voter->user?->nis ?? '-' }}</code></td>
                                <td>{{ $voter->user?->name ?? '-' }}</td>
                                <td>{{ $voter->classRoom?->name ?? '-' }}</td>
                                <td class="text-center">
                                    @if ($voter->checked_in)
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                        <small class="text-muted d-block">
                                            {{ $voter->checked_in_at?->format('H:i') }}
                                        </small>
                                    @else
                                        <i class="bi bi-dash-circle text-muted"></i>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($voter->has_voted)
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                        <small class="text-muted d-block">
                                            {{ $voter->voted_at?->format('H:i') }}
                                        </small>
                                    @else
                                        <i class="bi bi-dash-circle text-muted"></i>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.5;"></i>
                                    <p class="mt-2 mb-0">Belum ada pemilih di sesi ini</p>
                                    @if ($runtime === 'scheduled' || $runtime === 'ready')
                                        <small>
                                            Klik "Assign Pemilih" untuk menambahkan otomatis
                                        </small>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ==========================================
         HIDDEN FORMS
         ========================================== --}}
    <form id="assignForm" action="{{ route('admin.sessions.assign-voters', $session) }}" method="POST" class="d-none">
        @csrf
    </form>
    <form id="closeForm" action="{{ route('admin.sessions.close', $session) }}" method="POST" class="d-none">
        @csrf
    </form>
    <form id="deleteForm" action="{{ route('admin.sessions.destroy', $session) }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

@endsection

@push('scripts')
    <script>
        async function confirmAssign() {
            const ok = await swalConfirm({
                title: 'Assign Pemilih?',
                message: 'Sistem akan mencari pemilih terverifikasi dari kelas {{ $session->classRoom?->name ?? '-' }} dan menambahkannya ke sesi ini.',
                icon: 'question',
                okText: 'Ya, Assign',
                okColor: '#198754',
            });
            if (ok) document.getElementById('assignForm').submit();
        }

        async function confirmClose() {
            const ok = await swalConfirm({
                title: 'Tutup Sesi Sekarang?',
                message: 'Normalnya sistem akan menutup otomatis. Ini hanya emergency override.',
                icon: 'warning',
                okText: 'Ya, Tutup',
                okColor: '#dc3545',
            });
            if (ok) document.getElementById('closeForm').submit();
        }

        async function confirmDelete() {
            const ok = await swalConfirm({
                title: 'Hapus Sesi?',
                message: 'Sesi kelas {{ $session->classRoom?->name ?? '-' }} akan dihapus. Pemilih akan dilepas dari sesi ini.',
                icon: 'warning',
                okText: 'Ya, Hapus',
                okColor: '#dc3545',
            });
            if (ok) document.getElementById('deleteForm').submit();
        }
    </script>
@endpush
