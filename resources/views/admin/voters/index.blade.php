@extends('layouts.admin')

@section('title', 'Verifikasi Pemilih')
@section('page-title', 'Verifikasi Pemilih')
@section('page-subtitle', 'Kelola pendaftaran pemilih Pilketos')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted-green">Manajemen</li>
    <li class="breadcrumb-item active text-main" aria-current="page">Pemilih</li>
@endsection

@section('content')

    {{-- ==========================================
         STATS CARDS
         ========================================== --}}
    <div class="row g-3 mb-4">

        {{-- Total --}}
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #cfe2ff;">
                        <i class="bi bi-people-fill fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Total Pemilih</div>
                        <div class="fs-3 fw-bold">{{ $counts['all'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Menunggu --}}
        <div class="col-md-3 col-6">
            <a href="{{ route('admin.voters.index', ['status' => 'pending']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #fff3cd;">
                            <i class="bi bi-hourglass-split fs-4" style="color: #cc9a06;"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">Menunggu</div>
                            <div class="fs-3 fw-bold" style="color: #cc9a06;">{{ $counts['pending'] }}</div>
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
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #d1e7dd;">
                            <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">Terverifikasi</div>
                            <div class="fs-3 fw-bold text-success">{{ $counts['verified'] }}</div>
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
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #f8d7da;">
                            <i class="bi bi-x-circle-fill fs-4 text-danger"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">Ditolak</div>
                            <div class="fs-3 fw-bold text-danger">{{ $counts['rejected'] }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

    </div>

    {{-- ==========================================
         FORM BULK
         ========================================== --}}
    <form id="bulkForm" method="POST" action="">
        @csrf
        <input type="hidden" name="alasan_reject" id="bulkAlasan" value="">
    </form>

    {{-- ==========================================
         TABLE CARD
         ========================================== --}}
    <div class="table-card-custom">

        {{-- BULK ACTION BAR --}}
        <div id="bulkBar" class="d-none align-items-center justify-content-between p-3" style="background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
            <div class="d-flex align-items-center gap-3">
                <span class="fw-medium">
                    <i class="bi bi-check-square-fill text-success"></i>
                    <span id="bulkCount">0</span> pemilih dipilih
                </span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn-custom btn-custom-light" onclick="clearSelection()">
                    Batal
                </button>
                <button type="button" class="btn-custom btn-custom-primary" onclick="submitBulk('approve')">
                    <i class="bi bi-check-circle"></i> Setujui
                </button>
                <button type="button" class="btn-custom btn-custom-danger" onclick="submitBulk('reject')">
                    <i class="bi bi-x-circle"></i> Tolak
                </button>
            </div>
        </div>

        {{-- HEADER CONTROLS --}}
        <div class="table-header-control">

            {{-- Live search --}}
            <form method="GET" class="table-search-box" id="searchForm" onsubmit="return false;">
                <i class="bi bi-search table-search-icon"></i>
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <input type="text" name="search" id="searchInput" class="table-search-input" placeholder="Ketik untuk mencari nama, NIS, atau kelas..." value="{{ request('search') }}"
                    autocomplete="off">
                @if (request('search'))
                    <button type="button" class="btn btn-sm position-absolute"
                        style="right: 10px; top: 50%; transform: translateY(-50%);
                                   border: none; background: none; color: #999;" onclick="clearSearch()">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                @endif
            </form>

            {{-- Status Filter --}}
            <div class="table-filter-group">
                <div class="dropdown">
                    <button class="btn-table-action dropdown-toggle" type="button" id="dropdownFilterStatus" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-funnel"></i>
                        @if ($status === 'all')
                            Semua Status
                        @elseif ($status === 'pending')
                            Menunggu ({{ $counts['pending'] }})
                        @elseif ($status === 'verified')
                            Terverifikasi ({{ $counts['verified'] }})
                        @elseif ($status === 'rejected')
                            Ditolak ({{ $counts['rejected'] }})
                        @endif
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownFilterStatus">
                        <li>
                            <a class="dropdown-item {{ $status === 'all' ? 'active' : '' }}"
                                href="{{ route('admin.voters.index', array_filter(['status' => 'all', 'search' => request('search'), 'per_page' => $perPage])) }}">
                                Semua Status <span class="badge bg-secondary ms-2">{{ $counts['all'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ $status === 'pending' ? 'active' : '' }}"
                                href="{{ route('admin.voters.index', array_filter(['status' => 'pending', 'search' => request('search'), 'per_page' => $perPage])) }}">
                                Menunggu <span class="badge bg-warning text-dark ms-2">{{ $counts['pending'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ $status === 'verified' ? 'active' : '' }}"
                                href="{{ route('admin.voters.index', array_filter(['status' => 'verified', 'search' => request('search'), 'per_page' => $perPage])) }}">
                                Terverifikasi <span class="badge bg-success ms-2">{{ $counts['verified'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ $status === 'rejected' ? 'active' : '' }}"
                                href="{{ route('admin.voters.index', array_filter(['status' => 'rejected', 'search' => request('search'), 'per_page' => $perPage])) }}">
                                Ditolak <span class="badge bg-danger ms-2">{{ $counts['rejected'] }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
                {{-- Di table-filter-group, sebelum tombol atau setelahnya --}}
                <a href="{{ route('admin.voters.import.index') }}" class="btn-table-action btn-custom-primary text-white">
                    <i class="bi bi-cloud-upload"></i> Import
                </a>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="checkAll" class="form-check-input" title="Pilih semua">
                        </th>
                        <th>Pemilih</th>
                        <th>NIS</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th>Terdaftar</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($voters as $voter)
                        <tr>
                            {{-- Checkbox --}}
                            <td>
                                @if ($voter->status !== \App\Enums\VoterStatus::VERIFIED)
                                    <input type="checkbox" name="ids[]" value="{{ $voter->id }}" form="bulkForm" class="form-check-input row-check">
                                @endif
                            </td>

                            {{-- User Cell --}}
                            <td>
                                <div class="table-user-cell">
                                    @if ($voter->kartu_pelajar)
                                        <img src="{{ asset('storage/' . $voter->kartu_pelajar) }}" alt="{{ $voter->name }}" class="table-user-avatar" style="object-fit: cover;">
                                    @else
                                        <div class="table-user-avatar"
                                            style="background: #c6f135; color: #1a2e1a;
                                                    display: flex; align-items: center;
                                                    justify-content: center;
                                                    font-weight: 700;">
                                            {{ strtoupper(substr($voter->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="table-user-name">{{ $voter->name }}</div>
                                        <div class="table-user-sub">{{ $voter->email }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- NIS --}}
                            <td class="table-order-id">{{ $voter->nis }}</td>

                            {{-- Kelas --}}
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $voter->classRoom?->name ?? '-' }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td>
                                @if ($voter->status === \App\Enums\VoterStatus::PENDING)
                                    <span class="badge-table pending">
                                        <i class="bi bi-hourglass-split"></i> Menunggu
                                    </span>
                                @elseif ($voter->status === \App\Enums\VoterStatus::VERIFIED)
                                    <span class="badge-table success">
                                        <i class="bi bi-check-circle"></i> Terverifikasi
                                    </span>
                                @else
                                    <span class="badge-table failed">
                                        <i class="bi bi-x-circle"></i> Ditolak
                                    </span>
                                @endif
                            </td>

                            {{-- Terdaftar --}}
                            <td>
                                <span class="text-muted small">
                                    {{ $voter->created_at->translatedFormat('d M Y') }}
                                </span>
                            </td>

                            {{-- Aksi --}}
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('admin.voters.show', $voter) }}" class="table-btn-action" title="Lihat detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: #e8f5c8;">
                                        <i class="bi bi-people" style="font-size: 2rem; color: #7cb518;"></i>
                                    </div>
                                    <p class="mt-2 mb-1 fw-medium">Tidak ada data pemilih</p>
                                    <small>
                                        @if (request('search'))
                                            Tidak ditemukan hasil untuk "{{ request('search') }}"
                                        @elseif ($status === 'pending')
                                            Semua pemilih sudah diverifikasi 🎉
                                        @else
                                            Belum ada pemilih dengan status ini
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
        <div class="table-footer-control d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <label for="perPageSelect" class="text-muted small mb-0">Tampilkan</label>
                    <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                        @foreach ([10, 20, 50, 100] as $opt)
                            <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>
                                {{ $opt }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-muted small">data</span>
                </div>

                <span class="table-pagination-info mb-0">
                    Menampilkan {{ $voters->firstItem() ?? 0 }}
                    – {{ $voters->lastItem() ?? 0 }}
                    dari {{ $voters->total() }} data
                </span>
            </div>

            @if ($voters->hasPages())
                {{ $voters->links('vendor.pagination.custom') }}
            @endif
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        // ==========================================
        // CHECKBOX LOGIC
        // ==========================================
        const checkAll = document.getElementById('checkAll');
        const bulkBar = document.getElementById('bulkBar');
        const bulkCount = document.getElementById('bulkCount');

        function rowChecks() {
            return document.querySelectorAll('.row-check');
        }

        function updateBulkBar() {
            const checked = document.querySelectorAll('.row-check:checked');
            const total = rowChecks().length;

            if (checked.length > 0) {
                bulkBar.classList.remove('d-none');
                bulkBar.classList.add('d-flex');
                bulkCount.textContent = checked.length;
            } else {
                bulkBar.classList.add('d-none');
                bulkBar.classList.remove('d-flex');
            }

            if (checkAll) {
                checkAll.checked = checked.length > 0 && checked.length === total;
                checkAll.indeterminate = checked.length > 0 && checked.length < total;
            }
        }

        checkAll?.addEventListener('change', function() {
            rowChecks().forEach(cb => {
                cb.checked = this.checked;
            });
            updateBulkBar();
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('row-check')) {
                updateBulkBar();
            }
        });

        // ==========================================
        // BULK SUBMIT
        // ==========================================
        async function submitBulk(action) {
            const count = document.querySelectorAll('.row-check:checked').length;

            if (count === 0) {
                swalToast('Pilih minimal 1 pemilih dulu.', 'warning');
                return;
            }

            if (action === 'approve') {
                const ok = await swalConfirm({
                    title: 'Setujui Pemilih?',
                    message: `Yakin ingin memverifikasi ${count} pemilih terpilih?`,
                    icon: 'question',
                    okText: 'Ya, Setujui',
                    okColor: '#198754',
                });

                if (ok) {
                    const form = document.getElementById('bulkForm');
                    form.action = '{{ route('admin.voters.bulk-approve') }}';
                    form.submit();
                }
                return;
            }

            const reason = await swalPrompt({
                title: 'Alasan Penolakan',
                message: `Menolak ${count} pemilih terpilih`,
                placeholder: 'Contoh: Foto kartu pelajar tidak jelas',
                okText: 'Lanjutkan',
            });

            if (!reason) return;

            const ok = await swalConfirm({
                title: 'Konfirmasi Penolakan',
                message: `Yakin ingin menolak ${count} pemilih?`,
                icon: 'warning',
                okText: 'Ya, Tolak',
                okColor: '#dc3545',
            });

            if (ok) {
                document.getElementById('bulkAlasan').value = reason;

                const form = document.getElementById('bulkForm');
                form.action = '{{ route('admin.voters.bulk-reject') }}';
                form.submit();
            }
        }

        function clearSelection() {
            rowChecks().forEach(cb => cb.checked = false);
            if (checkAll) {
                checkAll.checked = false;
                checkAll.indeterminate = false;
            }
            updateBulkBar();
        }

        // ==========================================
        // CHANGE PER PAGE
        // ==========================================
        function changePerPage(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        // ==========================================
        // LIVE SEARCH
        // ==========================================
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
                }, 400);
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
