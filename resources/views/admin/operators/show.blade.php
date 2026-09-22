@extends('layouts.admin')

@section('title', 'Detail Operator')
@section('page-title', 'Detail Operator')
@section('page-subtitle', $operator->name)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.operators.index') }}" class="text-decoration-none text-muted-green">
            Operator
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">{{ $operator->name }}</li>
@endsection

@section('content')

    {{-- BACK + ACTIONS --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="{{ route('admin.operators.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.operators.edit', $operator) }}" class="btn-custom btn-custom-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div>
    </div>

    {{-- PROFILE --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">

                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 90px; height: 90px; background: #c6f135;
                            color: #1a2e1a; font-weight: 700; font-size: 2rem;">
                    {{ strtoupper(substr($operator->name, 0, 1)) }}
                </div>

                <div class="flex-grow-1 text-center text-md-start">
                    <h3 class="mb-1">{{ $operator->name }}</h3>
                    <p class="text-muted mb-3">
                        <i class="bi bi-envelope me-1"></i> {{ $operator->email }}
                    </p>

                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                        <span class="badge bg-primary">
                            <i class="bi bi-person-badge me-1"></i> Operator
                        </span>
                        @if ($operator->last_login_at)
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-clock me-1"></i>
                                Login terakhir {{ $operator->last_login_at->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- STATS --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #cff4fc;">
                        <i class="bi bi-clock-history fs-4 text-info"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Total Sesi</div>
                        <div class="fs-3 fw-bold">{{ $stats['total_sessions'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #d1e7dd;">
                        <i class="bi bi-broadcast fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Sesi Aktif</div>
                        <div class="fs-3 fw-bold text-success">{{ $stats['active_sessions'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #e9ecef;">
                        <i class="bi bi-stop-circle fs-4 text-secondary"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Sesi Selesai</div>
                        <div class="fs-3 fw-bold text-secondary">{{ $stats['closed_sessions'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SESSION LIST --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <strong>
                <i class="bi bi-clock-history text-success me-1"></i>
                Sesi yang Dikerjakan
            </strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kelas</th>
                            <th>Pemilihan</th>
                            <th>Jadwal</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($operator->operatedSessions as $session)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $session->classRoom?->name ?? '-' }}</div>
                                </td>
                                <td>
                                    <small>{{ $session->election?->title ?? '-' }}</small>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $session->tanggal->translatedFormat('d M Y') }}
                                        ·
                                        {{ \Carbon\Carbon::parse($session->waktu_mulai)->format('H:i') }}
                                        –
                                        {{ \Carbon\Carbon::parse($session->waktu_selesai)->format('H:i') }}
                                    </small>
                                </td>
                                <td class="text-center">
                                    @php $runtime = $session->getRuntimeStatus(); @endphp
                                    @if ($runtime === 'scheduled')
                                        <span class="badge-table pending">Terjadwal</span>
                                    @elseif ($runtime === 'ready')
                                        <span class="badge-table warning">Siap</span>
                                    @elseif ($runtime === 'active')
                                        <span class="badge-table success">Berlangsung</span>
                                    @else
                                        <span class="badge-table failed">Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.5;"></i>
                                    <p class="mt-2 mb-0">Operator belum di-assign ke sesi apapun</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
