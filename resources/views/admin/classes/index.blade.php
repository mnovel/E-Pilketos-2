@extends('layouts.admin')

@section('title', 'Kelola Kelas')
@section('page-title', 'Kelola Kelas')
@section('page-subtitle', 'Master data kelas sekolah')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted-green">Manajemen</li>
    <li class="breadcrumb-item active text-main" aria-current="page">Kelas</li>
@endsection

@section('content')

    {{-- ==========================================
         STATS
         ========================================== --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #cfe2ff;">
                        <i class="bi bi-mortarboard-fill fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Total Kelas</div>
                        <div class="fs-3 fw-bold">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #d1e7dd;">
                        <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Aktif</div>
                        <div class="fs-3 fw-bold text-success">{{ $stats['active'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #e9ecef;">
                        <i class="bi bi-slash-circle fs-4 text-secondary"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Nonaktif</div>
                        <div class="fs-3 fw-bold text-secondary">{{ $stats['inactive'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #fff3cd;">
                        <i class="bi bi-people-fill fs-4" style="color: #cc9a06;"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Ada Siswa</div>
                        <div class="fs-3 fw-bold" style="color: #cc9a06;">{{ $stats['with_user'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==========================================
         TABLE
         ========================================== --}}
    <div class="table-card-custom">

        {{-- HEADER --}}
        <div class="table-header-control">
            <form method="GET" class="table-search-box" onsubmit="return false;">
                <i class="bi bi-search table-search-icon"></i>
                <input type="hidden" name="tingkat" value="{{ $tingkat }}">
                <input type="text" name="search" id="searchInput" class="table-search-input" placeholder="Cari nama kelas..." value="{{ request('search') }}" autocomplete="off">
            </form>

            <div class="table-filter-group">
                <div class="dropdown">
                    <button class="btn-table-action dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-funnel"></i>
                        @if ($tingkat === 'all')
                            Semua Tingkat
                        @else
                            Tingkat {{ $tingkat }}
                        @endif
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item {{ $tingkat === 'all' ? 'active' : '' }}" href="{{ route('admin.classes.index', array_filter(['tingkat' => 'all', 'search' => request('search')])) }}">
                                Semua Tingkat
                            </a>
                        </li>
                        @foreach ($tingkatList as $t)
                            <li>
                                <a class="dropdown-item {{ $tingkat === $t ? 'active' : '' }}" href="{{ route('admin.classes.index', array_filter(['tingkat' => $t, 'search' => request('search')])) }}">
                                    Tingkat {{ $t }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <a href="{{ route('admin.classes.create') }}" class="btn-table-action btn-custom-primary text-white">
                    <i class="bi bi-plus-lg"></i> Tambah Kelas
                </a>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>Tingkat</th>
                        <th>Jurusan</th>
                        <th class="text-center">Siswa</th>
                        <th class="text-center">Sesi</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $class)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $class->name }}</div>
                                <small class="text-muted">
                                    Rombel {{ $class->rombel ?? '-' }}
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $class->tingkat }}
                                </span>
                            </td>
                            <td>{{ $class->jurusan ?? '—' }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary">
                                    <i class="bi bi-people-fill"></i> {{ $class->users_count }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info bg-opacity-10 text-info">
                                    {{ $class->sessions_count }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if ($class->is_active)
                                    <span class="badge-table success">
                                        <i class="bi bi-check-circle"></i> Aktif
                                    </span>
                                @else
                                    <span class="badge-table failed">
                                        <i class="bi bi-slash-circle"></i> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">

                                    {{-- Detail --}}
                                    <a href="{{ route('admin.classes.show', $class) }}" class="table-btn-action" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    {{-- Edit --}}
                                    <a href="{{ route('admin.classes.edit', $class) }}" class="table-btn-action" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    {{-- Toggle Active --}}
                                    <button type="button" class="table-btn-action" title="{{ $class->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                        onclick="toggleActive({{ $class->id }}, '{{ $class->name }}', {{ $class->is_active ? 'true' : 'false' }})">
                                        <i class="bi {{ $class->is_active ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' }} fs-5"></i>
                                    </button>

                                    {{-- Delete --}}
                                    <button type="button" class="table-btn-action" title="Hapus" onclick="confirmDelete({{ $class->id }}, '{{ $class->name }}')">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: #e8f5c8;">
                                        <i class="bi bi-mortarboard" style="font-size: 2rem; color: #7cb518;"></i>
                                    </div>
                                    <p class="mt-2 mb-1 fw-medium">Belum ada kelas</p>
                                    <small>
                                        @if (request('search'))
                                            Tidak ditemukan hasil untuk "{{ request('search') }}"
                                        @else
                                            Klik "Tambah Kelas" untuk memulai
                                        @endif
                                    </small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    {{-- Hidden forms --}}
    @foreach ($classes as $class)
        <form id="deleteForm-{{ $class->id }}" action="{{ route('admin.classes.destroy', $class) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>

        <form id="toggleForm-{{ $class->id }}" action="{{ route('admin.classes.toggle-active', $class) }}" method="POST" class="d-none">
            @csrf
        </form>
    @endforeach

@endsection

@push('scripts')
    <script>
        async function toggleActive(id, name, isActive) {
            const action = isActive ? 'nonaktifkan' : 'aktifkan';

            const ok = await swalConfirm({
                title: `${isActive ? 'Nonaktifkan' : 'Aktifkan'} Kelas?`,
                message: `Yakin ingin ${action} kelas "${name}"?`,
                icon: 'question',
                okText: `Ya, ${isActive ? 'Nonaktifkan' : 'Aktifkan'}`,
                okColor: isActive ? '#dc3545' : '#198754',
            });

            if (ok) {
                document.getElementById(`toggleForm-${id}`).submit();
            }
        }

        async function confirmDelete(id, name) {
            const ok = await swalConfirm({
                title: 'Hapus Kelas?',
                message: `Kelas "${name}" akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`,
                icon: 'warning',
                okText: 'Ya, Hapus',
                okColor: '#dc3545',
            });

            if (ok) {
                document.getElementById(`deleteForm-${id}`).submit();
            }
        }

        // Live search
        const searchInput = document.getElementById('searchInput');
        let searchTimer;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                const value = this.value.trim();
                const url = new URL(window.location.href);

                if (value === '') {
                    url.searchParams.delete('search');
                } else {
                    url.searchParams.set('search', value);
                }

                if (value.length >= 2 || value === '') {
                    searchTimer = setTimeout(() => {
                        window.location.href = url.toString();
                    }, 400);
                }
            });
        }
    </script>
@endpush
