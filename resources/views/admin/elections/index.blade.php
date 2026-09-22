@extends('layouts.admin')

@section('title', 'Pemilihan')
@section('page-title', 'Pemilihan')
@section('page-subtitle', 'Kelola periode pemilihan Pilketos')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted-green">Manajemen</li>
    <li class="breadcrumb-item active text-main" aria-current="page">Pemilihan</li>
@endsection

@section('content')

    <div class="table-card-custom">

        {{-- ==========================================
             HEADER CONTROLS
             ========================================== --}}
        <div class="table-header-control">

            {{-- Live search --}}
            <form method="GET" class="table-search-box" id="searchForm" onsubmit="return false;">
                <i class="bi bi-search table-search-icon"></i>
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <input type="text" name="search" id="searchInput" class="table-search-input" placeholder="Cari judul atau tahun ajaran..." value="{{ request('search') }}" autocomplete="off">
                @if (request('search'))
                    <button type="button" class="btn btn-sm position-absolute"
                        style="right: 10px; top: 50%; transform: translateY(-50%);
                               border: none; background: none; color: #999;" onclick="clearSearch()">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                @endif
            </form>

            {{-- Actions --}}
            <div class="table-filter-group">

                {{-- Filter Status --}}
                <div class="dropdown">
                    <button class="btn-table-action dropdown-toggle" type="button" id="dropdownFilterStatus" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-funnel"></i>
                        @if ($status === 'all')
                            Semua Status
                        @elseif ($status === 'draft')
                            Draft ({{ $counts['draft'] }})
                        @elseif ($status === 'active')
                            Berlangsung ({{ $counts['active'] }})
                        @elseif ($status === 'closed')
                            Ditutup ({{ $counts['closed'] }})
                        @elseif ($status === 'published')
                            Dipublikasi ({{ $counts['published'] }})
                        @endif
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownFilterStatus">
                        <li>
                            <a class="dropdown-item {{ $status === 'all' ? 'active' : '' }}"
                                href="{{ route('admin.elections.index', array_filter(['status' => 'all', 'search' => request('search'), 'per_page' => $perPage])) }}">
                                Semua Status <span class="badge bg-secondary ms-2">{{ $counts['all'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ $status === 'draft' ? 'active' : '' }}"
                                href="{{ route('admin.elections.index', array_filter(['status' => 'draft', 'search' => request('search'), 'per_page' => $perPage])) }}">
                                Draft <span class="badge bg-secondary ms-2">{{ $counts['draft'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ $status === 'active' ? 'active' : '' }}"
                                href="{{ route('admin.elections.index', array_filter(['status' => 'active', 'search' => request('search'), 'per_page' => $perPage])) }}">
                                Berlangsung <span class="badge bg-success ms-2">{{ $counts['active'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ $status === 'closed' ? 'active' : '' }}"
                                href="{{ route('admin.elections.index', array_filter(['status' => 'closed', 'search' => request('search'), 'per_page' => $perPage])) }}">
                                Ditutup <span class="badge bg-warning text-dark ms-2">{{ $counts['closed'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ $status === 'published' ? 'active' : '' }}"
                                href="{{ route('admin.elections.index', array_filter(['status' => 'published', 'search' => request('search'), 'per_page' => $perPage])) }}">
                                Dipublikasi <span class="badge bg-primary ms-2">{{ $counts['published'] }}</span>
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Create Button --}}
                <a href="{{ route('admin.elections.create') }}" class="btn-table-action btn-custom-primary text-white">
                    <i class="bi bi-plus-lg"></i> Buat Pemilihan
                </a>
            </div>
        </div>

        {{-- ==========================================
             TABLE
             ========================================== --}}
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Pemilihan</th>
                        <th>Periode</th>
                        <th class="text-center">Kandidat</th>
                        <th>Partisipasi</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($elections as $election)
                        @php
                            $runtime = $election->getRuntimeStatus();

                            // Partisipasi
                            $totalVoters = $election->voters_count;
                            $totalVoted = $election->votes_count;
                            $pctVoted = $totalVoters > 0 ? round(($totalVoted / $totalVoters) * 100) : 0;
                        @endphp

                        <tr>
                            {{-- ====================
                                 KOLOM 1: PEMILIHAN
                                 ==================== --}}
                            <td style="min-width: 220px;">
                                <div class="d-flex align-items-center gap-3">

                                    {{-- Icon avatar --}}
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: #e8f5c8;">
                                        <i class="bi bi-calendar-event-fill" style="color: #7cb518;"></i>
                                    </div>

                                    <div>
                                        <div class="fw-bold">{{ $election->title }}</div>
                                        <small class="text-muted">
                                            <i class="bi bi-book"></i> {{ $election->tahun_ajaran }}
                                        </small>
                                    </div>
                                </div>
                            </td>

                            {{-- ====================
                                 KOLOM 2: PERIODE
                                 ==================== --}}
                            <td style="min-width: 200px;">
                                <div class="small">
                                    <i class="bi bi-calendar-event text-muted"></i>
                                    {{ $election->start_at->translatedFormat('d M Y H:i') }}
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-arrow-right-short"></i>
                                    {{ $election->end_at->translatedFormat('d M Y H:i') }}
                                </div>

                                {{-- Time indicator dinamis --}}
                                @if ($runtime === 'ready')
                                    <span class="badge bg-warning text-dark mt-1">
                                        <i class="bi bi-hourglass-split"></i> Siap Aktif
                                    </span>
                                @elseif ($runtime === 'active')
                                    @if ($election->hasEnded())
                                        <span class="badge bg-danger mt-1">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Waktu Habis
                                        </span>
                                    @else
                                        @php $sisa = now()->diff($election->end_at); @endphp
                                        <span class="badge bg-success mt-1">
                                            <i class="bi bi-clock-fill"></i>
                                            Sisa
                                            @if ($sisa->days > 0)
                                                {{ $sisa->days }}h {{ $sisa->h }}j
                                            @elseif ($sisa->h > 0)
                                                {{ $sisa->h }}j {{ $sisa->i }}m
                                            @else
                                                {{ $sisa->i }}m
                                            @endif
                                        </span>
                                    @endif
                                @endif
                            </td>

                            {{-- ====================
                                 KOLOM 3: KANDIDAT
                                 ==================== --}}
                            <td class="text-center">
                                @if ($election->candidates_count > 0)
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-person-badge text-primary"></i>
                                        <span class="fw-bold">{{ $election->candidates_count }}</span>
                                    </div>
                                    <small class="text-muted d-block">kandidat</small>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger">
                                        <i class="bi bi-exclamation-triangle"></i> Kosong
                                    </span>
                                @endif
                            </td>

                            {{-- ====================
                                 KOLOM 4: PARTISIPASI
                                 ==================== --}}
                            <td style="min-width: 180px;">
                                @if ($totalVoters > 0)
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <small class="text-muted" style="min-width: 40px;">
                                            Vote
                                        </small>
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: {{ $pctVoted }}%;"></div>
                                        </div>
                                        <small class="text-muted" style="min-width: 65px;">
                                            {{ $totalVoted }}/{{ $totalVoters }}
                                        </small>
                                    </div>
                                    <small class="text-muted">
                                        {{ $pctVoted }}% sudah memilih
                                    </small>
                                @else
                                    <small class="text-muted fst-italic">
                                        Belum ada pemilih
                                    </small>
                                @endif
                            </td>

                            {{-- ====================
                                 KOLOM 5: STATUS
                                 ==================== --}}
                            <td>
                                @if ($runtime === 'draft')
                                    <span class="badge-table pending">Draft</span>
                                @elseif ($runtime === 'ready')
                                    <span class="badge-table warning">Siap Aktif</span>
                                @elseif ($runtime === 'active')
                                    <span class="badge-table success">Berlangsung</span>
                                @elseif ($runtime === 'expired')
                                    <span class="badge-table failed">Terlewat</span>
                                @elseif ($runtime === 'closed')
                                    <span class="badge-table warning">Ditutup</span>
                                @elseif ($runtime === 'published')
                                    <span class="badge bg-primary">Dipublikasi</span>
                                @else
                                    <span class="badge bg-secondary">—</span>
                                @endif
                            </td>

                            {{-- ====================
                                 KOLOM 6: AKSI
                                 ==================== --}}
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('admin.elections.show', $election) }}" class="table-btn-action" title="Detail Pemilihan">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a href="{{ route('admin.candidates.index', ['election_id' => $election->id]) }}" class="table-btn-action" title="Kelola Kandidat">
                                        <i class="bi bi-person-badge"></i>
                                    </a>

                                    <a href="{{ route('admin.sessions.index', ['election_id' => $election->id]) }}" class="table-btn-action" title="Sesi Voting">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-calendar-x" style="font-size: 3rem; opacity: 0.5;"></i>
                                    <p class="mt-2 mb-0">Belum ada pemilihan</p>
                                    <small>Klik "Buat Pemilihan" untuk memulai</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ==========================================
             PAGINATION
             ========================================== --}}
        <div class="table-footer-control d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <label for="perPageSelect" class="text-muted small mb-0">Tampilkan</label>
                    <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                        @foreach ([10, 20, 50] as $opt)
                            <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>
                                {{ $opt }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-muted small">data</span>
                </div>

                <span class="table-pagination-info mb-0">
                    Menampilkan {{ $elections->firstItem() ?? 0 }}
                    – {{ $elections->lastItem() ?? 0 }}
                    dari {{ $elections->total() }} data
                </span>
            </div>

            @if ($elections->hasPages())
                {{ $elections->links('vendor.pagination.custom') }}
            @endif
        </div>

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

        const searchInput = document.getElementById('searchInput');
        const searchForm = document.getElementById('searchForm');
        let searchTimer;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                const value = this.value.trim();

                if (value === '') {
                    searchForm.submit();
                    return;
                }

                if (value.length < 2) return;

                searchTimer = setTimeout(() => {
                    searchForm.submit();
                }, 300);
            });

            if (searchInput.value) {
                const val = searchInput.value;
                searchInput.focus();
                searchInput.setSelectionRange(val.length, val.length);
            }
        }

        function clearSearch() {
            searchInput.value = '';
            searchForm.submit();
        }
    </script>
@endpush
