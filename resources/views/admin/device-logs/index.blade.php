@extends('layouts.admin')

@section('title', 'Log Device')
@section('page-title', 'Log Device')
@section('page-subtitle', 'Monitor device check-in & voting yang aktif')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted-green">Manajemen</li>
    <li class="breadcrumb-item active text-main" aria-current="page">Log Device</li>
@endsection

@section('content')

    {{-- ============ STATS ============ --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #cfe2ff;">
                        <i class="bi bi-door-open fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Check-in Online</div>
                        <div class="fs-3 fw-bold">
                            {{ $stats['checkin_online'] }}
                            <span class="text-muted fs-6">/ {{ $stats['checkin_total'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #d1e7dd;">
                        <i class="bi bi-check2-square fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Voting Online</div>
                        <div class="fs-3 fw-bold text-success">
                            {{ $stats['voting_online'] }}
                            <span class="text-muted fs-6">/ {{ $stats['voting_total'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #fff3cd;">
                        <i class="bi bi-exclamation-triangle fs-4" style="color: #cc9a06;"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Stale (&gt;10 menit)</div>
                        <div class="fs-3 fw-bold" style="color: #cc9a06;">
                            {{ $stats['stale_count'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #cff4fc;">
                        <i class="bi bi-hdd-network fs-4 text-info"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Total Device</div>
                        <div class="fs-3 fw-bold text-info">
                            {{ $stats['checkin_total'] + $stats['voting_total'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ TABLE CARD ============ --}}
    <div class="table-card-custom">

        {{-- HEADER --}}
        <div class="table-header-control">

            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small fw-medium">Filter:</span>
            </div>

            {{-- Filters --}}
            <div class="table-filter-group">

                {{-- Filter Tipe --}}
                <div class="dropdown">
                    <button class="btn-table-action dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-diagram-3"></i>
                        @if ($type === 'checkin')
                            Check-in
                        @elseif($type === 'voting')
                            Voting
                        @else
                            Semua Tipe
                        @endif
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item {{ $type === 'all' ? 'active' : '' }}" href="{{ route('admin.device-logs.index', request()->except('type', 'page')) }}">
                                Semua Tipe
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item {{ $type === 'checkin' ? 'active' : '' }}" href="{{ route('admin.device-logs.index', array_merge(request()->except('page'), ['type' => 'checkin'])) }}">
                                🚪 Check-in
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ $type === 'voting' ? 'active' : '' }}" href="{{ route('admin.device-logs.index', array_merge(request()->except('page'), ['type' => 'voting'])) }}">
                                🗳️ Voting
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Filter Status --}}
                <div class="dropdown">
                    <button class="btn-table-action dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-broadcast"></i>
                        @if ($status === 'online')
                            Online
                        @elseif($status === 'offline')
                            Offline
                        @else
                            Semua Status
                        @endif
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item {{ $status === 'all' ? 'active' : '' }}" href="{{ route('admin.device-logs.index', request()->except('status', 'page')) }}">
                                Semua Status
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item {{ $status === 'online' ? 'active' : '' }}"
                                href="{{ route('admin.device-logs.index', array_merge(request()->except('page'), ['status' => 'online'])) }}">
                                🟢 Online
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ $status === 'offline' ? 'active' : '' }}"
                                href="{{ route('admin.device-logs.index', array_merge(request()->except('page'), ['status' => 'offline'])) }}">
                                ⚪ Offline
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Filter Election --}}
                @if ($elections->count())
                    <div class="dropdown">
                        <button class="btn-table-action dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-calendar-event"></i>
                            @php $selectedElection = $elections->firstWhere('id', $electionId); @endphp
                            @if ($selectedElection)
                                {{ Str::limit($selectedElection->title, 22) }}
                            @else
                                Semua Pemilihan
                            @endif
                        </button>
                        <ul class="dropdown-menu" style="max-height: 400px; overflow-y: auto;">
                            <li>
                                <a class="dropdown-item {{ !$electionId ? 'active' : '' }}" href="{{ route('admin.device-logs.index', request()->except('election_id', 'page')) }}">
                                    Semua Pemilihan
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            @foreach ($elections as $e)
                                <li>
                                    <a class="dropdown-item {{ $electionId == $e->id ? 'active' : '' }}"
                                        href="{{ route('admin.device-logs.index', array_merge(request()->except('page'), ['election_id' => $e->id])) }}">
                                        {{ Str::limit($e->title, 40) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Reset --}}
                @if (request()->anyFilled(['type', 'status', 'election_id']))
                    <a href="{{ route('admin.device-logs.index') }}" class="btn-table-action">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                @endif

                {{-- Purge Button --}}
                @if ($stats['stale_count'] > 0)
                    <form method="POST" action="{{ route('admin.device-logs.purge') }}" class="ms-auto"
                        onsubmit="return confirm('Hapus semua device yang offline > 10 menit? Tindakan ini tidak bisa dibatalkan.');">
                        @csrf
                        <button type="submit" class="btn-table-action text-danger">
                            <i class="bi bi-trash"></i> Purge Offline ({{ $stats['stale_count'] }})
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @php
            $onlineThreshold = now()->subMinutes(2);
        @endphp

        {{-- ============ CHECK-IN DEVICES ============ --}}
        @if (in_array($type, ['all', 'checkin']))
            <div class="px-3 pt-3 pb-1">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-door-open text-primary"></i>
                    <strong>Check-in Devices</strong>
                    <span class="badge bg-primary-subtle text-primary">{{ $checkinDevices->count() }}</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 180px;">Device</th>
                            <th>Pemilihan</th>
                            <th>Sesi</th>
                            <th>Token</th>
                            <th>Last Ping</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($checkinDevices as $d)
                            @php
                                $isOnline = $d->last_ping_at && $d->last_ping_at->gte($onlineThreshold);
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="table-user-avatar"
                                            style="background: #cfe2ff; color: #084298;
                                                    display: flex; align-items: center; justify-content: center;
                                                    font-weight: 700; font-size: 0.9rem;">
                                            <i class="bi bi-door-open"></i>
                                        </div>
                                        <div>
                                            <div class="table-user-name">{{ $d->device_label }}</div>
                                            <div class="table-user-sub">#{{ $d->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        {{ Str::limit($d->election?->title, 35) ?? '—' }}
                                    </div>
                                </td>
                                <td>
                                    @if ($d->session)
                                        <div class="small fw-medium">
                                            {{ $d->session->classRoom?->name ?? '-' }}
                                        </div>
                                    @else
                                        <span class="text-muted small fst-italic">—</span>
                                    @endif
                                </td>
                                <td>
                                    <code class="small">{{ $d->device_token }}</code>
                                </td>
                                <td>
                                    @if ($d->last_ping_at)
                                        <div class="small">
                                            <div class="fw-medium">
                                                {{ $d->last_ping_at->format('H:i:s') }}
                                            </div>
                                            <small class="text-muted">
                                                {{ $d->last_ping_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    @else
                                        <small class="text-muted fst-italic">Belum pernah ping</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($isOnline)
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Online
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <i class="bi bi-circle" style="font-size: 0.5rem;"></i> Offline
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('admin.device-logs.destroy-checkin', $d) }}" class="d-inline"
                                        onsubmit="return confirm('Hapus device {{ $d->device_label }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="table-btn-action text-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: #f8f9fa;">
                                            <i class="bi bi-door-closed" style="font-size: 2rem; opacity: 0.5;"></i>
                                        </div>
                                        <p class="mt-2 mb-1 fw-medium">Tidak ada check-in device</p>
                                        <small>Buka kiosk check-in untuk memulai</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ============ VOTING DEVICES ============ --}}
        @if (in_array($type, ['all', 'voting']))
            <div class="px-3 pt-4 pb-1 {{ $type === 'all' && $checkinDevices->count() ? 'border-top' : '' }}">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-check2-square text-success"></i>
                    <strong>Voting Devices</strong>
                    <span class="badge bg-success-subtle text-success">{{ $votingDevices->count() }}</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 180px;">Device</th>
                            <th>Pemilihan</th>
                            <th class="text-center">Status</th>
                            <th>Voter Aktif</th>
                            <th>Last Ping</th>
                            <th class="text-center">Koneksi</th>
                            <th class="text-center" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($votingDevices as $d)
                            @php
                                $isOnline = $d->last_ping_at && $d->last_ping_at->gte($onlineThreshold);
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="table-user-avatar"
                                            style="background: #d1e7dd; color: #0f5132;
                                                    display: flex; align-items: center; justify-content: center;
                                                    font-weight: 700; font-size: 0.9rem;">
                                            <i class="bi bi-check2-square"></i>
                                        </div>
                                        <div>
                                            <div class="table-user-name">{{ $d->device_label }}</div>
                                            <div class="table-user-sub">#{{ $d->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        {{ Str::limit($d->election?->title, 35) ?? '—' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $d->status->color() }}">
                                        {{ $d->status->label() }}
                                    </span>
                                </td>
                                <td>
                                    @if ($d->assignedVoter)
                                        <div class="small fw-medium">
                                            {{ $d->assignedVoter->user?->name ?? '-' }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $d->assignedVoter->classRoom?->name ?? '-' }}
                                        </small>
                                    @else
                                        <span class="text-muted small fst-italic">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($d->last_ping_at)
                                        <div class="small">
                                            <div class="fw-medium">
                                                {{ $d->last_ping_at->format('H:i:s') }}
                                            </div>
                                            <small class="text-muted">
                                                {{ $d->last_ping_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    @else
                                        <small class="text-muted fst-italic">Belum pernah ping</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($isOnline)
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Online
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <i class="bi bi-circle" style="font-size: 0.5rem;"></i> Offline
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('admin.device-logs.destroy-voting', $d) }}" class="d-inline"
                                        onsubmit="return confirm('Hapus device {{ $d->device_label }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="table-btn-action text-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: #f8f9fa;">
                                            <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.5;"></i>
                                        </div>
                                        <p class="mt-2 mb-1 fw-medium">Tidak ada voting device</p>
                                        <small>Buka kiosk voting untuk memulai</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

    </div>

    {{-- Info footer --}}
    <div class="mt-3">
        <small class="text-muted">
            <i class="bi bi-info-circle"></i>
            Device dianggap <strong>online</strong> jika ping &lt; 2 menit.
            Gunakan <strong>Purge Offline</strong> untuk membersihkan device yang tidak aktif &gt; 10 menit.
        </small>
    </div>

@endsection
