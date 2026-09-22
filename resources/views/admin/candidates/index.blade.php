@extends('layouts.admin')

@section('title', 'Kandidat')
@section('page-title', 'Kandidat')
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
    <li class="breadcrumb-item active text-main" aria-current="page">Kandidat</li>
@endsection

@section('content')

    @php
        $runtime = $election->getRuntimeStatus();
        $isDraft = $election->status === \App\Enums\ElectionStatus::DRAFT;
        $totalCandidates = $candidates->count();
    @endphp

    {{-- ==========================================
         BACK + CREATE
         ========================================== --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="{{ route('admin.elections.show', $election) }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>

        @if ($isDraft)
            <a href="{{ route('admin.candidates.create', ['election_id' => $election->id]) }}" class="btn-custom btn-custom-primary">
                <i class="bi bi-plus-lg"></i> Tambah Kandidat
            </a>
        @endif
    </div>

    {{-- ==========================================
         ALERT: BUKAN DRAFT
         ========================================== --}}
    @if (!$isDraft)
        <div class="alert-custom alert-custom-info mb-4">
            <i class="bi bi-info-circle-fill alert-custom-icon"></i>
            <div class="alert-custom-content">
                Pemilihan sudah <strong>{{ $election->status->label() }}</strong>.
                Kandidat hanya bisa diubah saat pemilihan berstatus <strong>Draft</strong>.
            </div>
        </div>
    @endif

    {{-- ==========================================
         ALERT: KANDIDAT KURANG DARI 2
         ========================================== --}}
    @if ($isDraft && $totalCandidates < 2)
        <div class="alert-custom alert-custom-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill alert-custom-icon"></i>
            <div class="alert-custom-content">
                <strong>Minimal 2 kandidat dibutuhkan.</strong>
                Saat ini baru ada <strong>{{ $totalCandidates }} kandidat</strong>.
                Pemilihan tidak akan otomatis aktif sampai jumlah kandidat cukup.
            </div>
        </div>
    @endif

    {{-- ==========================================
         INFO CARD: RINGKASAN ELECTION
         ========================================== --}}
    <div class="row g-3 mb-4">

        {{-- Periode --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #e8f5c8;">
                        <i class="bi bi-calendar-event-fill fs-5" style="color: #7cb518;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Periode Pemilihan</div>
                        <div class="fw-bold small">
                            {{ $election->start_at->translatedFormat('d M Y H:i') }}
                        </div>
                        <small class="text-muted">
                            s/d {{ $election->end_at->translatedFormat('d M Y H:i') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Kandidat --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #d1e7dd;">
                        <i class="bi bi-person-badge fs-5 text-success"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Total Kandidat</div>
                        <div class="fw-bold fs-4">{{ $totalCandidates }}</div>
                        <small class="text-muted">
                            @if ($totalCandidates >= 2)
                                <span class="text-success">
                                    <i class="bi bi-check-circle"></i> Cukup
                                </span>
                            @else
                                <span class="text-danger">
                                    <i class="bi bi-exclamation-circle"></i> Kurang
                                </span>
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    @php
                        $statusIcon = match ($runtime) {
                            'draft' => 'bi-file-earmark-fill',
                            'ready' => 'bi-hourglass-split',
                            'active' => 'bi-broadcast',
                            'expired' => 'bi-exclamation-triangle-fill',
                            'closed' => 'bi-stop-circle-fill',
                            'published' => 'bi-megaphone-fill',
                            default => 'bi-question-circle-fill',
                        };
                        $statusColor = match ($runtime) {
                            'draft' => ['#e9ecef', '#6c757d'],
                            'ready' => ['#fff3cd', '#cc9a06'],
                            'active' => ['#d1e7dd', '#0f5132'],
                            'expired' => ['#f8d7da', '#842029'],
                            'closed' => ['#fff3cd', '#cc9a06'],
                            'published' => ['#cfe2ff', '#084298'],
                            default => ['#e9ecef', '#6c757d'],
                        };
                    @endphp
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: {{ $statusColor[0] }};">
                        <i class="bi {{ $statusIcon }} fs-5" style="color: {{ $statusColor[1] }};"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Status Pemilihan</div>
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
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ==========================================
         CANDIDATE GRID
         ========================================== --}}
    @if ($totalCandidates > 0)
        <div class="row g-4">
            @foreach ($candidates as $candidate)
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 overflow-hidden">

                        {{-- Photo Header --}}
                        <div
                            style="background: linear-gradient(135deg, #c6f135 0%, #a8d92d 100%);
                                    height: 220px;
                                    display: flex; align-items: center; justify-content: center;
                                    position: relative;">

                            {{-- Photo --}}
                            @if ($candidate->foto)
                                <img src="{{ asset('storage/' . $candidate->foto) }}" alt="{{ $candidate->nama }}" class="rounded-circle"
                                    style="width: 140px; height: 140px; object-fit: cover;
                                            border: 5px solid white;
                                            box-shadow: 0 8px 24px rgba(0,0,0,0.15);">
                            @else
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 140px; height: 140px; background: white;
                                            color: #1a2e1a; font-weight: 700; font-size: 3rem;
                                            border: 5px solid white;
                                            box-shadow: 0 8px 24px rgba(0,0,0,0.15);">
                                    {{ strtoupper(substr($candidate->nama, 0, 1)) }}
                                </div>
                            @endif

                            {{-- No Urut Badge --}}
                            <span class="position-absolute"
                                style="top: 14px; left: 14px;
                                         background: #1a2e1a; color: #c6f135;
                                         width: 44px; height: 44px;
                                         border-radius: 50%;
                                         display: flex; align-items: center; justify-content: center;
                                         font-weight: 700; font-size: 1.15rem;
                                         box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                                {{ $candidate->no_urut }}
                            </span>
                        </div>

                        {{-- Info --}}
                        <div class="card-body text-center">
                            <h5 class="mb-1">{{ $candidate->nama }}</h5>
                            <p class="text-muted small mb-3">
                                <i class="bi bi-mortarboard"></i>
                                {{ $candidate->classRoom?->name ?? '-' }}
                            </p>

                            {{-- Visi preview --}}
                            @if ($candidate->visi)
                                <p class="text-muted small mb-3 fst-italic" style="max-height: 40px; overflow: hidden;">
                                    "{{ Str::limit($candidate->visi, 60) }}"
                                </p>
                            @endif

                            {{-- Actions --}}
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('admin.candidates.show', $candidate) }}" class="btn-custom btn-custom-sm btn-custom-outline-primary">
                                    <i class="bi bi-eye"></i> Detail
                                </a>

                                @if ($isDraft)
                                    <a href="{{ route('admin.candidates.edit', $candidate) }}" class="btn-custom btn-custom-sm btn-custom-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <button type="button" class="btn-custom btn-custom-sm btn-custom-outline-danger" onclick="confirmDelete({{ $candidate->id }}, @js($candidate->nama))"
                                        title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- ==========================================
             EMPTY STATE
             ========================================== --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px; background: #e8f5c8;">
                    <i class="bi bi-person-plus" style="font-size: 3rem; color: #7cb518;"></i>
                </div>
                <h4 class="mt-3 mb-2">Belum ada kandidat</h4>
                <p class="text-muted mb-4">
                    @if ($isDraft)
                        Tambahkan minimal <strong>2 kandidat</strong> sebelum pemilihan dapat diaktifkan secara otomatis.
                    @else
                        Pemilihan ini tidak memiliki kandidat.
                    @endif
                </p>

                @if ($isDraft)
                    <a href="{{ route('admin.candidates.create', ['election_id' => $election->id]) }}" class="btn-custom btn-custom-primary">
                        <i class="bi bi-plus-lg"></i> Tambah Kandidat Pertama
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- ==========================================
         HIDDEN DELETE FORMS
         ========================================== --}}
    @foreach ($candidates as $candidate)
        <form id="deleteForm-{{ $candidate->id }}" action="{{ route('admin.candidates.destroy', $candidate) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

@endsection

@push('scripts')
    <script>
        async function confirmDelete(id, nama) {
            const ok = await swalConfirm({
                title: 'Hapus Kandidat?',
                message: `Kandidat "${nama}" akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`,
                icon: 'warning',
                okText: 'Ya, Hapus',
                okColor: '#dc3545',
            });

            if (ok) {
                document.getElementById(`deleteForm-${id}`).submit();
            }
        }
    </script>
@endpush
