@extends('layouts.admin')

@section('title', 'Detail Pemilihan')
@section('page-title', 'Detail Pemilihan')
@section('page-subtitle', $election->title)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.elections.index') }}" class="text-decoration-none text-muted-green">
            Pemilihan
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">{{ $election->title }}</li>
@endsection

@section('content')

    @php
        $runtime = $election->getRuntimeStatus();

        $totalVoters = $stats['total_voters'];
        $totalVoted = $stats['total_votes'];
        $pctVoted = $totalVoters > 0 ? round(($totalVoted / $totalVoters) * 100) : 0;

        // ============================
        // Metadata runtime (icon, warna, label)
        // ============================
        $runtimeMeta = [
            'draft' => ['icon' => 'file-earmark-fill', 'bg' => '#e9ecef', 'fg' => '#6c757d', 'badge' => 'bg-secondary', 'label' => 'Draft'],
            'ready' => ['icon' => 'hourglass-split', 'bg' => '#fff3cd', 'fg' => '#cc9a06', 'badge' => 'bg-warning text-dark', 'label' => 'Siap Aktif'],
            'active' => ['icon' => 'broadcast', 'bg' => '#d1e7dd', 'fg' => '#0f5132', 'badge' => 'bg-success', 'label' => 'Berlangsung'],
            'expired' => ['icon' => 'exclamation-triangle-fill', 'bg' => '#f8d7da', 'fg' => '#842029', 'badge' => 'bg-danger', 'label' => 'Terlewat'],
            'closed' => ['icon' => 'stop-circle-fill', 'bg' => '#fff3cd', 'fg' => '#cc9a06', 'badge' => 'bg-warning text-dark', 'label' => 'Ditutup'],
            'published' => ['icon' => 'megaphone-fill', 'bg' => '#cfe2ff', 'fg' => '#084298', 'badge' => 'bg-primary', 'label' => 'Dipublikasi'],
        ];
        $meta = $runtimeMeta[$runtime] ?? ['icon' => 'question-circle-fill', 'bg' => '#e9ecef', 'fg' => '#6c757d', 'badge' => 'bg-secondary', 'label' => ucfirst($runtime)];

        // ============================
        // Tombol aksi per runtime
        // ============================
        $actions = [];

        if ($runtime === 'draft') {
            $actions[] = ['type' => 'link', 'route' => route('admin.elections.edit', $election), 'class' => 'btn-custom-warning', 'icon' => 'pencil', 'label' => 'Edit'];
            $actions[] = ['type' => 'button', 'onclick' => 'confirmDelete()', 'class' => 'btn-custom-danger', 'icon' => 'trash', 'label' => 'Hapus'];
        }

        if ($runtime === 'ready') {
            $actions[] = ['type' => 'link', 'route' => route('admin.elections.edit', $election), 'class' => 'btn-custom-warning', 'icon' => 'pencil', 'label' => 'Edit'];
        }

        if ($runtime === 'active') {
            $actions[] = [
                'type' => 'link',
                'route' => route('admin.elections.hasil', $election),
                'class' => 'btn-custom-outline-primary',
                'icon' => 'bar-chart-fill',
                'label' => 'Lihat Hasil <small>(sementara)</small>',
                'raw' => true,
            ];
            $actions[] = ['type' => 'button', 'onclick' => 'confirmClose()', 'class' => 'btn-custom-warning', 'icon' => 'stop-circle', 'label' => 'Tutup Sekarang'];
        }

        if ($runtime === 'closed') {
            $actions[] = ['type' => 'link', 'route' => route('admin.elections.hasil', $election), 'class' => 'btn-custom-outline-primary', 'icon' => 'bar-chart-fill', 'label' => 'Lihat Hasil'];
            $actions[] = ['type' => 'button', 'onclick' => 'confirmPublish()', 'class' => 'btn-custom-primary', 'icon' => 'megaphone', 'label' => 'Publikasi Hasil'];
        }

        if ($runtime === 'published') {
            $actions[] = ['type' => 'link', 'route' => route('admin.elections.hasil', $election), 'class' => 'btn-custom-outline-primary', 'icon' => 'bar-chart-fill', 'label' => 'Lihat Hasil'];
        }
    @endphp

    {{-- ==========================================
         TOOLBAR: BACK + ACTIONS
         ========================================== --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="{{ route('admin.elections.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>

        <div class="d-flex gap-2 flex-wrap align-items-center">
            @foreach ($actions as $a)
                @if ($a['type'] === 'link')
                    <a href="{{ $a['route'] }}" class="btn-custom {{ $a['class'] }}">
                        <i class="bi bi-{{ $a['icon'] }}"></i>
                        {!! !empty($a['raw']) ? $a['label'] : e($a['label']) !!}
                    </a>
                @else
                    <button type="button" class="btn-custom {{ $a['class'] }}" onclick="{{ $a['onclick'] }}">
                        <i class="bi bi-{{ $a['icon'] }}"></i> {{ $a['label'] }}
                    </button>
                @endif
            @endforeach

            @if ($runtime === 'published')
                <span class="badge bg-primary py-2 px-3">
                    <i class="bi bi-check-circle"></i> Sudah dipublikasikan
                </span>
            @endif
        </div>
    </div>

    {{-- ==========================================
         AUTO-INFO ALERTS
         ========================================== --}}
    @if ($runtime === 'draft' && $election->hasStarted())
        <div class="alert-custom alert-custom-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>Pemilihan belum bisa diaktifkan.</strong>
                @if ($stats['total_candidates'] < 2)
                    Minimal harus ada <strong>2 kandidat</strong>.
                    Saat ini: <strong>{{ $stats['total_candidates'] }} kandidat</strong>.
                @else
                    Menunggu scheduler berikutnya (max 1 menit).
                @endif
            </div>
        </div>
    @endif

    @if ($runtime === 'draft' && !$election->hasStarted())
        <div class="alert-custom alert-custom-info mb-4">
            <i class="bi bi-clock-history alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>Pemilihan akan otomatis aktif</strong> pada
                <strong>{{ $election->start_at->translatedFormat('d M Y, H:i') }}</strong>
                (asalkan sudah ada minimal 2 kandidat).
            </div>
        </div>
    @endif

    @if ($runtime === 'ready')
        <div class="alert-custom alert-custom-warning mb-4">
            <i class="bi bi-hourglass-split alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>Menunggu aktivasi otomatis.</strong>
                Scheduler akan mengaktifkan dalam <strong>≤ 1 menit</strong>.
            </div>
        </div>
    @endif

    @if ($runtime === 'active' && !$election->hasEnded())
        <div class="alert-custom alert-custom-success mb-4">
            <i class="bi bi-broadcast alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>Voting sedang berlangsung!</strong>
                Akan otomatis ditutup pada
                <strong>{{ $election->end_at->translatedFormat('d M Y, H:i') }}</strong>.
            </div>
        </div>
    @endif

    {{-- ==========================================
         PROFILE HEADER
         ========================================== --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">

                {{-- Avatar --}}
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 100px; height: 100px; background: #c6f135;
                            color: #1a2e1a; font-weight: 700; font-size: 2.5rem;">
                    <i class="bi bi-calendar-event"></i>
                </div>

                {{-- Info --}}
                <div class="flex-grow-1 text-center text-md-start">
                    <h3 class="mb-1">{{ $election->title }}</h3>
                    <p class="text-muted mb-3">
                        {{ $election->deskripsi ?: 'Tidak ada deskripsi' }}
                    </p>

                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-book me-1"></i>{{ $election->tahun_ajaran }}
                        </span>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-calendar me-1"></i>
                            {{ $election->start_at->translatedFormat('d M Y H:i') }}
                            –
                            {{ $election->end_at->translatedFormat('d M Y H:i') }}
                        </span>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-person me-1"></i>
                            {{ $election->creator?->name ?? '-' }}
                        </span>
                    </div>
                </div>

                {{-- Runtime badge besar --}}
                <div class="text-center flex-shrink-0">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 64px; height: 64px; background: {{ $meta['bg'] }};">
                        <i class="bi bi-{{ $meta['icon'] }} fs-3" style="color: {{ $meta['fg'] }};"></i>
                    </div>
                    <span class="badge {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                </div>

            </div>
        </div>
    </div>

    {{-- ==========================================
         STATS
         ========================================== --}}
    @php
        $statCards = [
            ['label' => 'Kandidat', 'value' => $stats['total_candidates'], 'icon' => 'person-badge', 'bg' => '#d1e7dd', 'fg' => 'text-success'],
            ['label' => 'Total Pemilih', 'value' => $stats['total_voters'], 'icon' => 'people-fill', 'bg' => '#cfe2ff', 'fg' => 'text-primary'],
            ['label' => 'Sesi Voting', 'value' => $stats['total_sessions'], 'icon' => 'clock-history', 'bg' => '#cff4fc', 'fg' => 'text-info'],
            ['label' => 'Total Suara', 'value' => $stats['total_votes'], 'icon' => 'check2-square', 'bg' => '#fff3cd', 'fg' => 'text-warning'],
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach ($statCards as $s)
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: {{ $s['bg'] }};">
                            <i class="bi bi-{{ $s['icon'] }} fs-4 {{ $s['fg'] }}"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">{{ $s['label'] }}</div>
                            <div class="fs-3 fw-bold">{{ $s['value'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ==========================================
         PROGRESS PARTISIPASI
         ========================================== --}}
    @if ($totalVoters > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <i class="bi bi-graph-up-arrow text-success me-1"></i>
                        <strong>Partisipasi Pemilih</strong>
                    </div>
                    <span class="badge bg-success">{{ $pctVoted }}%</span>
                </div>

                <div class="progress" style="height: 14px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pctVoted }}%;" aria-valuenow="{{ $pctVoted }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <div class="d-flex justify-content-between mt-2 small text-muted">
                    <span>
                        <i class="bi bi-check2-square text-success"></i>
                        {{ $totalVoted }} sudah memilih
                    </span>
                    <span>
                        <i class="bi bi-people text-muted"></i>
                        {{ $totalVoters }} total pemilih
                    </span>
                </div>
            </div>
        </div>
    @endif

    {{-- ==========================================
         QUICK ACTIONS
         ========================================== --}}
    @php
        $isActive = $runtime === 'active';
        $canSeeResult = in_array($runtime, ['active', 'closed', 'published']);

        $quickCards = [
            [
                'enabled' => true,
                'url' => route('admin.candidates.index', ['election_id' => $election->id]),
                'icon' => 'person-badge',
                'bg' => '#d1e7dd',
                'fg' => 'text-success',
                'title' => 'Kelola Kandidat',
                'desc' => $stats['total_candidates'] . ' kandidat terdaftar',
            ],
            [
                'enabled' => true,
                'url' => route('admin.sessions.index', ['election_id' => $election->id]),
                'icon' => 'clock-history',
                'bg' => '#cff4fc',
                'fg' => 'text-info',
                'title' => 'Sesi Voting',
                'desc' => $stats['total_sessions'] . ' sesi terjadwal',
            ],
            [
                'enabled' => $isActive,
                'url' => $isActive ? route('device.checkin.index') : null,
                'external' => true,
                'icon' => 'door-open-fill',
                'bg' => $isActive ? '#cfe2ff' : '#e9ecef',
                'fg' => $isActive ? 'text-primary' : 'text-secondary',
                'title' => 'Device Check-in',
                'desc' => $isActive
                    ? 'Buka pintu masuk bilik suara'
                    : ($runtime === 'draft'
                        ? 'Menunggu pemilihan aktif'
                        : ($runtime === 'ready'
                            ? 'Menunggu aktivasi otomatis'
                            : 'Pemilihan tidak aktif')),
            ],
            [
                'enabled' => $isActive,
                'url' => $isActive ? route('device.voting.index') : null,
                'external' => true,
                'icon' => 'box-arrow-in-right',
                'bg' => $isActive ? '#d1e7dd' : '#e9ecef',
                'fg' => $isActive ? 'text-success' : 'text-secondary',
                'title' => 'Device Voting',
                'desc' => $isActive
                    ? 'Buka bilik suara untuk memilih'
                    : ($runtime === 'draft'
                        ? 'Menunggu pemilihan aktif'
                        : ($runtime === 'ready'
                            ? 'Menunggu aktivasi otomatis'
                            : 'Pemilihan tidak aktif')),
            ],
            [
                'enabled' => $canSeeResult,
                'url' => $canSeeResult ? route('admin.elections.hasil', $election) : null,
                'icon' => 'bar-chart-fill',
                'bg' => $isActive ? '#fff3cd' : ($canSeeResult ? '#cfe2ff' : '#e9ecef'),
                'fg' => $isActive ? 'text-warning' : ($canSeeResult ? 'text-primary' : 'text-secondary'),
                'title' => 'Hasil Pemilihan',
                'desc' => match ($runtime) {
                    'active' => 'Hasil sementara — ' . $stats['total_votes'] . ' suara',
                    'closed' => 'Siap dipublikasikan',
                    'published' => 'Hasil sudah dipublikasikan',
                    default => 'Belum tersedia',
                },
                'badge' => $isActive ? 'sementara' : null,
            ],
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach ($quickCards as $q)
            <div class="col-md-4">
                @if ($q['enabled'])
                    <a href="{{ $q['url'] }}" class="text-decoration-none" @if (!empty($q['external'])) target="_blank" rel="noopener" @endif>
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: {{ $q['bg'] }};">
                                    <i class="bi bi-{{ $q['icon'] }} fs-3 {{ $q['fg'] }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-dark mb-1">
                                        {{ $q['title'] }}
                                        @if (!empty($q['external']))
                                            <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.7rem;"></i>
                                        @endif
                                        @if (!empty($q['badge']))
                                            <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;">
                                                {{ $q['badge'] }}
                                            </span>
                                        @endif
                                    </h6>
                                    <p class="text-muted small mb-0">{{ $q['desc'] }}</p>
                                </div>
                                <i class="bi bi-arrow-right text-muted"></i>
                            </div>
                        </div>
                    </a>
                @else
                    <div class="card border-0 shadow-sm h-100" style="opacity: 0.5; cursor: not-allowed;">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: {{ $q['bg'] }};">
                                <i class="bi bi-{{ $q['icon'] }} fs-3 {{ $q['fg'] }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">{{ $q['title'] }}</h6>
                                <p class="text-muted small mb-0">{{ $q['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ==========================================
         INFO
         ========================================== --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <strong>
                <i class="bi bi-info-circle text-success me-1"></i>
                Informasi Pemilihan
            </strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-borderless mb-0 align-middle">
                <tbody>
                    <tr>
                        <td class="text-muted py-3 ps-4" style="width: 30%;">
                            <i class="bi bi-person me-1"></i> Dibuat Oleh
                        </td>
                        <td class="py-3 pe-4">{{ $election->creator?->name ?? '-' }}</td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-muted py-3 ps-4">
                            <i class="bi bi-calendar-plus me-1"></i> Dibuat Pada
                        </td>
                        <td class="py-3 pe-4">
                            {{ $election->created_at->translatedFormat('d M Y, H:i') }}
                        </td>
                    </tr>
                    @if ($election->hasil_published_at)
                        <tr class="border-top">
                            <td class="text-muted py-3 ps-4">
                                <i class="bi bi-megaphone me-1"></i> Hasil Dipublikasi
                            </td>
                            <td class="py-3 pe-4">
                                {{ $election->hasil_published_at->translatedFormat('d M Y, H:i') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==========================================
         HIDDEN FORMS
         ========================================== --}}
    <form id="closeForm" action="{{ route('admin.elections.close', $election) }}" method="POST" class="d-none">
        @csrf
    </form>
    <form id="publishForm" action="{{ route('admin.elections.publish', $election) }}" method="POST" class="d-none">
        @csrf
    </form>
    <form id="deleteForm" action="{{ route('admin.elections.destroy', $election) }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

@endsection

@push('scripts')
    <script>
        async function confirmClose() {
            const ok = await swalConfirm({
                title: 'Tutup Pemilihan Sekarang?',
                message: 'Normalnya sistem akan menutup otomatis. Ini hanya emergency override.',
                icon: 'warning',
                okText: 'Ya, Tutup',
                okColor: '#dc3545',
            });
            if (ok) document.getElementById('closeForm').submit();
        }

        async function confirmPublish() {
            const ok = await swalConfirm({
                title: 'Publikasi Hasil?',
                message: 'Hasil pemilihan akan dapat dilihat publik.',
                icon: 'question',
                okText: 'Ya, Publikasi',
                okColor: '#0d6efd',
            });
            if (ok) document.getElementById('publishForm').submit();
        }

        async function confirmDelete() {
            const ok = await swalConfirm({
                title: 'Hapus Pemilihan?',
                message: 'Data akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                okText: 'Ya, Hapus',
                okColor: '#dc3545',
            });
            if (ok) document.getElementById('deleteForm').submit();
        }
    </script>
@endpush
