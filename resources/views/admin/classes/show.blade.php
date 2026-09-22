@extends('layouts.admin')

@section('title', 'Detail Kelas')
@section('page-title', 'Detail Kelas')
@section('page-subtitle', $class->name)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.classes.index') }}" class="text-decoration-none text-muted-green">
            Kelas
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">{{ $class->name }}</li>
@endsection

@section('content')

    {{-- BACK --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="{{ route('admin.classes.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.classes.edit', $class) }}" class="btn-custom btn-custom-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div>
    </div>

    {{-- HEADER --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">

                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 90px; height: 90px; background: #c6f135;
                            color: #1a2e1a; font-weight: 700; font-size: 1.3rem;">
                    {{ $class->tingkat }}
                </div>

                <div class="flex-grow-1 text-center text-md-start">
                    <h3 class="mb-1">{{ $class->name }}</h3>
                    <p class="text-muted mb-3">
                        Tingkat {{ $class->tingkat }}
                        @if ($class->jurusan)
                            · Jurusan {{ $class->jurusan }}
                        @endif
                        @if ($class->rombel)
                            · Rombel {{ $class->rombel }}
                        @endif
                    </p>

                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                        @if ($class->is_active)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle me-1"></i> Aktif
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                <i class="bi bi-slash-circle me-1"></i> Nonaktif
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- STATS --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #cfe2ff;">
                        <i class="bi bi-people-fill fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Total Siswa</div>
                        <div class="fs-3 fw-bold">{{ $class->users_count }}</div>
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
                        <div class="text-muted small mb-1">Sesi Voting</div>
                        <div class="fs-3 fw-bold">{{ $class->sessions_count }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #fff3cd;">
                        <i class="bi bi-check2-square fs-4" style="color: #cc9a06;"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Total Voter</div>
                        <div class="fs-3 fw-bold">{{ $class->voters_count }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- USER LIST --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <strong>
                <i class="bi bi-people text-success me-1"></i>
                Daftar Siswa
            </strong>
            <span class="badge bg-secondary">{{ $users->total() }} orang</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $i => $user)
                            <tr>
                                <td class="text-muted">{{ $users->firstItem() + $i }}</td>
                                <td><code>{{ $user->nis }}</code></td>
                                <td>{{ $user->name }}</td>
                                <td class="text-muted small">{{ $user->email }}</td>
                                <td class="text-center">
                                    @if ($user->status === \App\Enums\VoterStatus::PENDING)
                                        <span class="badge-table pending">Menunggu</span>
                                    @elseif ($user->status === \App\Enums\VoterStatus::VERIFIED)
                                        <span class="badge-table success">Terverifikasi</span>
                                    @else
                                        <span class="badge-table failed">Ditolak</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.5;"></i>
                                    <p class="mt-2 mb-0">Belum ada siswa di kelas ini</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($users->hasPages())
            <div class="card-footer bg-white">
                {{ $users->links('vendor.pagination.custom') }}
            </div>
        @endif
    </div>

@endsection
