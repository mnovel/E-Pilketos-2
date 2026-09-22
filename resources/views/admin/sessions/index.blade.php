@extends('layouts.admin')

@section('title', 'Sesi Voting')
@section('page-title', 'Sesi Voting')
@section('page-subtitle', $election->title)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.elections.index') }}" class="text-decoration-none text-muted-green">
            Pemilihan
        </a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('admin.elections.show', $election) }}" class="text-decoration-none text-muted-green">
            {{ $election->title }}
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Sesi Voting</li>
@endsection

@section('content')

    @php $electionRuntime = $election->getRuntimeStatus(); @endphp

    {{-- ==========================================
         BACK + CREATE
         ========================================== --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="{{ route('admin.elections.show', $election) }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>

        @if (in_array($electionRuntime, ['draft', 'ready', 'active']))
            <a href="{{ route('admin.sessions.create', ['election_id' => $election->id]) }}" class="btn-custom btn-custom-primary">
                <i class="bi bi-plus-lg"></i> Buat Sesi
            </a>
        @endif
    </div>

    {{-- ==========================================
     INFO: RINGKASAN ELECTION
     ========================================== --}}
    <div class="row g-3 mb-4">

        {{-- Periode Pemilihan --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: #e8f5c8;">
                        <i class="bi bi-calendar-event-fill fs-4" style="color: #7cb518;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Periode Pemilihan</div>
                        <div class="fw-bold">
                            {{ $election->start_at->translatedFormat('d M Y H:i') }}
                        </div>
                        <small class="text-muted">
                            s/d {{ $election->end_at->translatedFormat('d M Y H:i') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Pemilihan --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    @php
                        $statusIcon = match ($electionRuntime) {
                            'draft' => 'bi-file-earmark-fill',
                            'ready' => 'bi-hourglass-split',
                            'active' => 'bi-broadcast',
                            'expired' => 'bi-exclamation-triangle-fill',
                            'closed' => 'bi-stop-circle-fill',
                            'published' => 'bi-megaphone-fill',
                            default => 'bi-question-circle-fill',
                        };
                        $statusColor = match ($electionRuntime) {
                            'draft' => ['#e9ecef', '#6c757d'],
                            'ready' => ['#fff3cd', '#cc9a06'],
                            'active' => ['#d1e7dd', '#0f5132'],
                            'expired' => ['#f8d7da', '#842029'],
                            'closed' => ['#fff3cd', '#cc9a06'],
                            'published' => ['#cfe2ff', '#084298'],
                            default => ['#e9ecef', '#6c757d'],
                        };
                    @endphp

                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: {{ $statusColor[0] }};">
                        <i class="bi {{ $statusIcon }} fs-4" style="color: {{ $statusColor[1] }};"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Status Pemilihan</div>
                        @if ($electionRuntime === 'draft')
                            <span class="badge-table pending">Draft</span>
                        @elseif ($electionRuntime === 'ready')
                            <span class="badge-table warning">Siap Aktif</span>
                        @elseif ($electionRuntime === 'active')
                            <span class="badge-table success">Berlangsung</span>
                        @elseif ($electionRuntime === 'expired')
                            <span class="badge-table failed">Terlewat</span>
                        @elseif ($electionRuntime === 'closed')
                            <span class="badge-table warning">Ditutup</span>
                        @elseif ($electionRuntime === 'published')
                            <span class="badge bg-primary">Dipublikasi</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Sesi + Progress --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px; background: #cfe2ff;">
                        <i class="bi bi-clock-history fs-4 text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Total Sesi</div>
                        <div class="fw-bold fs-4">{{ $sessions->count() }} sesi</div>
                        <small class="text-muted">
                            @php
                                $activeCount = $sessions->filter(fn($s) => $s->getRuntimeStatus() === 'active')->count();
                                $doneCount = $sessions->filter(fn($s) => in_array($s->getRuntimeStatus(), ['closed', 'expired']))->count();
                            @endphp
                            @if ($activeCount > 0)
                                <span class="text-success">
                                    <i class="bi bi-broadcast"></i> {{ $activeCount }} aktif
                                </span>
                            @elseif ($doneCount === $sessions->count() && $sessions->count() > 0)
                                <span class="text-muted">
                                    <i class="bi bi-check-circle"></i> Semua selesai
                                </span>
                            @else
                                <span class="text-muted">
                                    {{ $doneCount }} selesai
                                </span>
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ==========================================
         TABLE
         ========================================== --}}
    <div class="table-card-custom">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>Jadwal</th>
                        <th>Operator</th>
                        <th>Pemilih</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $session)
                        @php $sessionRuntime = $session->getRuntimeStatus(); @endphp

                        <tr>
                            {{-- KELAS --}}
                            <td>
                                <div class="fw-bold fs-5">
                                    {{ $session->classRoom?->name ?? '-' }}
                                </div>
                            </td>

                            {{-- JADWAL + TIME INDICATOR --}}
                            <td style="min-width: 200px;">
                                <div>
                                    <i class="bi bi-calendar-event text-muted"></i>
                                    {{ $session->tanggal->translatedFormat('d M Y') }}
                                </div>
                                <small class="text-muted d-block">
                                    <i class="bi bi-clock"></i>
                                    {{ \Carbon\Carbon::parse($session->waktu_mulai)->format('H:i') }}
                                    –
                                    {{ \Carbon\Carbon::parse($session->waktu_selesai)->format('H:i') }}
                                </small>

                                {{-- Time indicator --}}
                                @if ($sessionRuntime === 'ready')
                                    <span class="badge bg-warning text-dark mt-1">
                                        <i class="bi bi-hourglass-split"></i> Siap Aktif
                                    </span>
                                @elseif ($sessionRuntime === 'active')
                                    @php $sisa = now()->diff($session->endDateTime()); @endphp
                                    <span class="badge bg-success mt-1">
                                        <i class="bi bi-broadcast"></i>
                                        Sisa
                                        @if ($sisa->h > 0)
                                            {{ $sisa->h }}j {{ $sisa->i }}m
                                        @else
                                            {{ $sisa->i }}m
                                        @endif
                                    </span>
                                @endif
                            </td>

                            {{-- OPERATOR --}}
                            <td>
                                @if ($session->operator)
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-person"></i>
                                        {{ $session->operator->name }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            {{-- PEMILIH --}}
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $session->voters_count }} pemilih
                                </span>
                            </td>

                            {{-- PROGRESS (pakai withCount) --}}
                            <td style="min-width: 160px;">
                                @php
                                    $total = $session->voters_count;
                                    $checkedIn = $session->checked_in_count;
                                    $voted = $session->voted_count;
                                    $pctVoted = $total > 0 ? round(($voted / $total) * 100) : 0;
                                    $pctCheckin = $total > 0 ? round(($checkedIn / $total) * 100) : 0;
                                @endphp

                                {{-- Progress Vote --}}
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <small class="text-muted" style="min-width: 45px;">Vote</small>
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: {{ $pctVoted }}%;"></div>
                                    </div>
                                    <small class="text-muted" style="min-width: 45px;">
                                        {{ $voted }}/{{ $total }}
                                    </small>
                                </div>

                                {{-- Progress Check-in --}}
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted" style="min-width: 45px;">Check</small>
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar bg-info" style="width: {{ $pctCheckin }}%;"></div>
                                    </div>
                                    <small class="text-muted" style="min-width: 45px;">
                                        {{ $checkedIn }}/{{ $total }}
                                    </small>
                                </div>
                            </td>

                            {{-- STATUS --}}
                            <td>
                                @if ($sessionRuntime === 'scheduled')
                                    <span class="badge-table pending">
                                        <i class="bi bi-clock"></i> Terjadwal
                                    </span>
                                @elseif ($sessionRuntime === 'ready')
                                    <span class="badge-table warning">
                                        <i class="bi bi-hourglass-split"></i> Siap Aktif
                                    </span>
                                @elseif ($sessionRuntime === 'active')
                                    <span class="badge-table success">
                                        <i class="bi bi-broadcast"></i> Berlangsung
                                    </span>
                                @elseif ($sessionRuntime === 'expired')
                                    <span class="badge-table failed">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Terlewat
                                    </span>
                                @elseif ($sessionRuntime === 'closed')
                                    <span class="badge-table failed">
                                        <i class="bi bi-stop-circle-fill"></i> Selesai
                                    </span>
                                @else
                                    <span class="badge bg-secondary">—</span>
                                @endif
                            </td>

                            {{-- AKSI --}}
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('admin.sessions.show', $session) }}" class="table-btn-action" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if (in_array($sessionRuntime, ['scheduled', 'ready']))
                                        <a href="{{ route('admin.sessions.edit', $session) }}" class="table-btn-action" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-clock-history" style="font-size: 3rem; opacity: 0.5;"></i>
                                    <p class="mt-2 mb-0">Belum ada sesi voting</p>
                                    <small>
                                        @if (in_array($electionRuntime, ['draft', 'ready', 'active']))
                                            Klik "Buat Sesi" untuk memulai
                                        @else
                                            Pemilihan sudah {{ $election->status->label() }}
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

@endsection
