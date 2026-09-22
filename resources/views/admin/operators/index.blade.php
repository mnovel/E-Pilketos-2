@extends('layouts.admin')

@section('title', 'Kelola Operator')
@section('page-title', 'Kelola Operator')
@section('page-subtitle', 'Kelola akun petugas device voting')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted-green">Manajemen</li>
    <li class="breadcrumb-item active text-main" aria-current="page">Operator</li>
@endsection

@section('content')

    {{-- STATS --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #cfe2ff;">
                        <i class="bi bi-person-badge-fill fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Total Operator</div>
                        <div class="fs-3 fw-bold">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #d1e7dd;">
                        <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Pernah Bertugas</div>
                        <div class="fs-3 fw-bold text-success">{{ $stats['with_session'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #cff4fc;">
                        <i class="bi bi-clock-history fs-4 text-info"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Sesi Dikerjakan</div>
                        <div class="fs-3 fw-bold text-info">{{ $stats['total_sessions'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="table-card-custom">

        {{-- HEADER CONTROLS --}}
        <div class="table-header-control">
            <form method="GET" class="table-search-box" onsubmit="return false;">
                <i class="bi bi-search table-search-icon"></i>
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <input type="text" name="search" id="searchInput" class="table-search-input" placeholder="Cari nama atau email operator..." value="{{ request('search') }}" autocomplete="off">
            </form>

            <div class="table-filter-group">
                <a href="{{ route('admin.operators.create') }}" class="btn-table-action btn-custom-primary text-white">
                    <i class="bi bi-plus-lg"></i> Tambah Operator
                </a>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Operator</th>
                        <th>Email</th>
                        <th class="text-center">Sesi</th>
                        <th>Login Terakhir</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($operators as $operator)
                        <tr>
                            <td>
                                <div class="table-user-cell">
                                    <div class="table-user-avatar"
                                        style="background: #c6f135; color: #1a2e1a;
                                                display: flex; align-items: center;
                                                justify-content: center;
                                                font-weight: 700;">
                                        {{ strtoupper(substr($operator->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="table-user-name">{{ $operator->name }}</div>
                                        <div class="table-user-sub">ID: #{{ $operator->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="small">{{ $operator->email }}</span>
                            </td>
                            <td class="text-center">
                                @if ($operator->operated_sessions_count > 0)
                                    <span class="badge bg-info bg-opacity-10 text-info">
                                        <i class="bi bi-clock-history"></i> {{ $operator->operated_sessions_count }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($operator->last_login_at)
                                    <small class="text-muted">
                                        {{ $operator->last_login_at->diffForHumans() }}
                                    </small>
                                @else
                                    <small class="text-muted fst-italic">Belum pernah</small>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('admin.operators.show', $operator) }}" class="table-btn-action" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.operators.edit', $operator) }}" class="table-btn-action" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="table-btn-action" title="Reset Password" onclick="showResetModal({{ $operator->id }}, '{{ addslashes($operator->name) }}')">
                                        <i class="bi bi-key text-warning"></i>
                                    </button>
                                    <button type="button" class="table-btn-action" title="Hapus" onclick="confirmDelete({{ $operator->id }}, '{{ addslashes($operator->name) }}')">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: #cfe2ff;">
                                        <i class="bi bi-person-badge" style="font-size: 2rem; color: #0d6efd;"></i>
                                    </div>
                                    <p class="mt-2 mb-1 fw-medium">Belum ada operator</p>
                                    <small>Klik "Tambah Operator" untuk menambahkan</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if ($operators->hasPages() || $operators->total() > 0)
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
                        Menampilkan {{ $operators->firstItem() ?? 0 }}
                        – {{ $operators->lastItem() ?? 0 }}
                        dari {{ $operators->total() }} data
                    </span>
                </div>

                @if ($operators->hasPages())
                    {{ $operators->links('vendor.pagination.custom') }}
                @endif
            </div>
        @endif

    </div>

    {{-- MODAL RESET PASSWORD --}}
    <div class="modal fade" id="resetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="resetForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Reset Password — <span id="resetName"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                Password Baru <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="new_password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label">
                                Konfirmasi Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="new_password_confirmation" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key"></i> Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- HIDDEN FORMS --}}
    @foreach ($operators as $operator)
        <form id="deleteForm-{{ $operator->id }}" action="{{ route('admin.operators.destroy', $operator) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

@endsection

@push('scripts')
    <script>
        function showResetModal(id, name) {
            document.getElementById('resetName').textContent = name;
            document.getElementById('resetForm').action = `/admin/operators/${id}/reset-password`;
            document.getElementById('new_password').value = '';
            document.getElementById('new_password_confirmation').value = '';

            const modal = new bootstrap.Modal(document.getElementById('resetModal'));
            modal.show();
        }

        async function confirmDelete(id, name) {
            const ok = await swalConfirm({
                title: 'Hapus Operator?',
                message: `Operator "${name}" akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`,
                icon: 'warning',
                okText: 'Ya, Hapus',
                okColor: '#dc3545',
            });

            if (ok) {
                document.getElementById(`deleteForm-${id}`).submit();
            }
        }

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

                if (value.length < 2 && value !== '') return;

                searchTimer = setTimeout(() => {
                    const url = new URL(window.location.href);
                    if (value === '') {
                        url.searchParams.delete('search');
                    } else {
                        url.searchParams.set('search', value);
                    }
                    window.location.href = url.toString();
                }, 400);
            });
        }
    </script>
@endpush
