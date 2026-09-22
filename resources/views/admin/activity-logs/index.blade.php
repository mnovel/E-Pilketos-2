@extends('layouts.admin')

@section('title', 'Activity Log')
@section('page-title', 'Activity Log')
@section('page-subtitle', 'Riwayat aktivitas sistem')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted-green">Manajemen</li>
    <li class="breadcrumb-item active text-main" aria-current="page">Activity Log</li>
@endsection

@section('content')

    {{-- STATS --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #cfe2ff;">
                        <i class="bi bi-journal-text fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Total Log</div>
                        <div class="fs-3 fw-bold">{{ number_format($stats['total']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #d1e7dd;">
                        <i class="bi bi-calendar-check fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Hari Ini</div>
                        <div class="fs-3 fw-bold text-success">{{ number_format($stats['today']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #fff3cd;">
                        <i class="bi bi-calendar-week fs-4" style="color: #cc9a06;"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Minggu Ini</div>
                        <div class="fs-3 fw-bold" style="color: #cc9a06;">
                            {{ number_format($stats['this_week']) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #cff4fc;">
                        <i class="bi bi-people-fill fs-4 text-info"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">User Unik</div>
                        <div class="fs-3 fw-bold text-info">{{ $stats['unique_user'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="table-card-custom">

        {{-- HEADER --}}
        <div class="table-header-control">

            {{-- Search --}}
            <form method="GET" class="table-search-box" onsubmit="return false;">
                <i class="bi bi-search table-search-icon"></i>
                <input type="hidden" name="role" value="{{ request('role') }}">
                <input type="hidden" name="action" value="{{ request('action') }}">
                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <input type="text" name="search" id="searchInput" class="table-search-input" placeholder="Cari user, email, atau action..." value="{{ request('search') }}" autocomplete="off">
            </form>

            {{-- Filters --}}
            <div class="table-filter-group">

                {{-- ✅ Filter Role --}}
                <div class="dropdown">
                    <button class="btn-table-action dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        @php
                            $roleMeta = [
                                'admin' => ['label' => 'Administrator', 'icon' => 'bi-shield-check', 'color' => 'primary'],
                                'operator' => ['label' => 'Operator', 'icon' => 'bi-person-badge', 'color' => 'info'],
                                'voter' => ['label' => 'Pemilih', 'icon' => 'bi-person', 'color' => 'success'],
                                'system' => ['label' => 'System', 'icon' => 'bi-gear', 'color' => 'secondary'],
                            ];
                            $currentRole = $roleMeta[request('role')] ?? null;
                        @endphp

                        @if ($currentRole)
                            <i class="bi {{ $currentRole['icon'] }} text-{{ $currentRole['color'] }}"></i>
                            {{ $currentRole['label'] }}
                        @else
                            <i class="bi bi-person-badge"></i>
                            Semua Role
                        @endif
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item {{ !request('role') ? 'active' : '' }}" href="{{ route('admin.activity-logs.index', request()->except('role', 'page')) }}">
                                <i class="bi bi-people"></i> Semua Role
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('role') === 'admin' ? 'active' : '' }}"
                                href="{{ route('admin.activity-logs.index', array_merge(request()->except('page'), ['role' => 'admin'])) }}">
                                <i class="bi bi-shield-check text-primary"></i> Administrator
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('role') === 'operator' ? 'active' : '' }}"
                                href="{{ route('admin.activity-logs.index', array_merge(request()->except('page'), ['role' => 'operator'])) }}">
                                <i class="bi bi-person-badge text-info"></i> Operator
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('role') === 'voter' ? 'active' : '' }}"
                                href="{{ route('admin.activity-logs.index', array_merge(request()->except('page'), ['role' => 'voter'])) }}">
                                <i class="bi bi-person text-success"></i> Pemilih
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('role') === 'system' ? 'active' : '' }}"
                                href="{{ route('admin.activity-logs.index', array_merge(request()->except('page'), ['role' => 'system'])) }}">
                                <i class="bi bi-gear text-secondary"></i> System
                                <small class="text-muted d-block ms-4" style="font-size: 0.7rem;">
                                    aksi otomatis & vote anonim
                                </small>
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Filter Action --}}
                <div class="dropdown">
                    <button class="btn-table-action dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-funnel"></i>
                        @if (request('action'))
                            {{ Str::limit(request('action'), 20) }}
                        @else
                            Semua Aksi
                        @endif
                    </button>
                    <ul class="dropdown-menu" style="max-height: 400px; overflow-y: auto;">
                        <li>
                            <a class="dropdown-item {{ !request('action') ? 'active' : '' }}" href="{{ route('admin.activity-logs.index', request()->except('action', 'page')) }}">
                                Semua Aksi
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        @foreach ($actions as $action)
                            <li>
                                <a class="dropdown-item {{ request('action') === $action ? 'active' : '' }}"
                                    href="{{ route('admin.activity-logs.index', array_merge(request()->except('page'), ['action' => $action])) }}">
                                    <code class="small">{{ $action }}</code>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Reset --}}
                @if (request()->anyFilled(['role', 'action', 'date_from', 'date_to', 'search']))
                    <a href="{{ route('admin.activity-logs.index') }}" class="btn-table-action">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                @endif

            </div>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 180px;">Waktu</th>
                        <th>User</th>
                        <th>Aksi</th>
                        <th>IP Address</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $color = $log->getActionColor();
                            $icon = $log->getActionIcon();
                        @endphp
                        <tr>
                            <td>
                                <div class="small">
                                    <div class="fw-medium">
                                        {{ $log->created_at->translatedFormat('d M Y') }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $log->created_at->format('H:i:s') }}
                                        · {{ $log->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                @if ($log->user)
                                    <div class="table-user-cell">
                                        <div class="table-user-avatar"
                                            style="background: #c6f135; color: #1a2e1a;
                                                    display: flex; align-items: center;
                                                    justify-content: center;
                                                    font-weight: 700; font-size: 0.85rem;">
                                            {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="table-user-name">{{ $log->user->name }}</div>
                                            <div class="table-user-sub">{{ $log->user->email }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        <i class="bi bi-gear"></i> System
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                        style="width: 32px; height: 32px;
                                                background: var(--bs-{{ $color }}-bg-subtle, #e9ecef);">
                                        <i class="bi {{ $icon }} text-{{ $color }}" style="font-size: 0.9rem;"></i>
                                    </div>
                                    <div>
                                        <div class="small fw-medium">{{ $log->getActionLabel() }}</div>
                                        <code class="small text-muted" style="font-size: 0.7rem;">
                                            {{ $log->action }}
                                        </code>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <small class="text-muted"><code>{{ $log->ip_address ?? '—' }}</code></small>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.activity-logs.show', $log) }}" class="table-btn-action" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: #f8f9fa;">
                                        <i class="bi bi-journal-x" style="font-size: 2rem; opacity: 0.5;"></i>
                                    </div>
                                    <p class="mt-2 mb-1 fw-medium">Belum ada log</p>
                                    <small>
                                        @if (request()->anyFilled(['role', 'action', 'search']))
                                            Tidak ada log yang cocok dengan filter
                                        @else
                                            Aktivitas akan muncul di sini
                                        @endif
                                    </small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if ($logs->hasPages() || $logs->total() > 0)
            <div class="table-footer-control d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <label class="text-muted small mb-0">Tampilkan</label>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                            @foreach ([20, 50, 100, 200] as $opt)
                                <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>
                                    {{ $opt }}
                                </option>
                            @endforeach
                        </select>
                        <span class="text-muted small">data</span>
                    </div>

                    <span class="table-pagination-info mb-0">
                        Menampilkan {{ $logs->firstItem() ?? 0 }}
                        – {{ $logs->lastItem() ?? 0 }}
                        dari {{ $logs->total() }} data
                    </span>
                </div>

                @if ($logs->hasPages())
                    {{ $logs->links('vendor.pagination.custom') }}
                @endif
            </div>
        @endif

    </div>

@endsection

@push('scripts')
    <script>
        function changePerPage(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        // Live search
        const searchInput = document.getElementById('searchInput');
        let searchTimer;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                const value = this.value.trim();

                if (value !== '' && value.length < 2) return;

                searchTimer = setTimeout(() => {
                    const url = new URL(window.location.href);
                    if (value === '') {
                        url.searchParams.delete('search');
                    } else {
                        url.searchParams.set('search', value);
                    }
                    url.searchParams.delete('page');
                    window.location.href = url.toString();
                }, 400);
            });
        }
    </script>
@endpush
