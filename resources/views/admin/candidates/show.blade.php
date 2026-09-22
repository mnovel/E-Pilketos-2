@extends('layouts.admin')

@section('title', 'Detail Kandidat')
@section('page-title', 'Detail Kandidat')
@section('page-subtitle', $candidate->nama)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.elections.index') }}" class="text-decoration-none text-muted-green">
            Pemilihan
        </a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('admin.candidates.index', ['election_id' => $candidate->election_id]) }}" class="text-decoration-none text-muted-green">
            Kandidat
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">{{ $candidate->nama }}</li>
@endsection

@section('content')

    @php
        $isDraft = $candidate->election->status === \App\Enums\ElectionStatus::DRAFT;
    @endphp

    {{-- ==========================================
         BACK + ACTIONS
         ========================================== --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="{{ route('admin.candidates.index', ['election_id' => $candidate->election_id]) }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>

        @if ($isDraft)
            <div class="d-flex gap-2">
                <a href="{{ route('admin.candidates.edit', $candidate) }}" class="btn-custom btn-custom-warning">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <button type="button" class="btn-custom btn-custom-danger" onclick="confirmDelete()">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            </div>
        @endif
    </div>

    {{-- ==========================================
         ALERT: BUKAN DRAFT
         ========================================== --}}
    @if (!$isDraft)
        <div class="alert-custom alert-custom-info mb-4">
            <i class="bi bi-info-circle-fill alert-custom-icon"></i>
            <div class="alert-custom-content">
                Pemilihan sudah <strong>{{ $candidate->election->status->label() }}</strong>.
                Data kandidat tidak dapat diubah lagi.
            </div>
        </div>
    @endif

    {{-- ==========================================
         PROFILE HEADER
         ========================================== --}}
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #c6f135 0%, #a8d92d 100%);">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">

                {{-- Photo --}}
                @if ($candidate->foto)
                    <img src="{{ asset('storage/' . $candidate->foto) }}" alt="{{ $candidate->nama }}" class="rounded-circle flex-shrink-0"
                        style="width: 140px; height: 140px; object-fit: cover;
                                border: 5px solid white;
                                box-shadow: 0 8px 24px rgba(0,0,0,0.15);">
                @else
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width: 140px; height: 140px; background: white;
                                color: #1a2e1a; font-weight: 700; font-size: 3.5rem;
                                border: 5px solid white;
                                box-shadow: 0 8px 24px rgba(0,0,0,0.15);">
                        {{ strtoupper(substr($candidate->nama, 0, 1)) }}
                    </div>
                @endif

                {{-- Info --}}
                <div class="flex-grow-1 text-center text-md-start">
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start gap-2 mb-2">
                        <span class="badge" style="background: #1a2e1a; color: #c6f135;
                                     font-size: 0.95rem; padding: 8px 14px;">
                            <i class="bi bi-hash"></i> No. Urut {{ $candidate->no_urut }}
                        </span>
                        <span class="badge bg-white text-dark">
                            <i class="bi bi-book"></i> {{ $candidate->election->tahun_ajaran }}
                        </span>
                    </div>

                    <h3 class="mb-1" style="color: #1a2e1a;">{{ $candidate->nama }}</h3>

                    <p class="mb-0" style="color: #1a2e1a; opacity: 0.85;">
                        <i class="bi bi-mortarboard"></i>
                        {{ $candidate->classRoom?->name ?? '-' }}
                    </p>

                    <p class="mb-0 mt-1 small" style="color: #1a2e1a; opacity: 0.7;">
                        <i class="bi bi-calendar-event"></i>
                        {{ $candidate->election->title }}
                    </p>
                </div>

            </div>
        </div>
    </div>

    {{-- ==========================================
         VISI & MISI
         ========================================== --}}
    <div class="row g-4 mb-4">

        {{-- VISI --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <strong>
                        <i class="bi bi-eye-fill text-success me-1"></i> Visi
                    </strong>
                </div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-line; line-height: 1.7;">
                        {{ $candidate->visi ?: '—' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- MISI --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <strong>
                        <i class="bi bi-list-check text-success me-1"></i> Misi
                    </strong>
                </div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-line; line-height: 1.7;">
                        {{ $candidate->misi ?: '—' }}
                    </p>
                </div>
            </div>
        </div>

    </div>

    {{-- ==========================================
         PROGRAM KERJA
         ========================================== --}}
    @if ($candidate->program_kerja)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <strong>
                    <i class="bi bi-clipboard-check text-success me-1"></i> Program Kerja
                </strong>
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space: pre-line; line-height: 1.7;">
                    {{ $candidate->program_kerja }}
                </p>
            </div>
        </div>
    @endif

    {{-- ==========================================
         INFO CANDIDATE
         ========================================== --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <strong>
                <i class="bi bi-info-circle text-success me-1"></i> Informasi Kandidat
            </strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-borderless mb-0 align-middle">
                <tbody>
                    <tr>
                        <td class="text-muted py-3 ps-4" style="width: 30%;">
                            <i class="bi bi-hash me-1"></i> Nomor Urut
                        </td>
                        <td class="py-3 pe-4 fw-medium">{{ $candidate->no_urut }}</td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-muted py-3 ps-4">
                            <i class="bi bi-person me-1"></i> Nama
                        </td>
                        <td class="py-3 pe-4 fw-medium">{{ $candidate->nama }}</td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-muted py-3 ps-4">
                            <i class="bi bi-mortarboard me-1"></i> Kelas
                        </td>
                        <td class="py-3 pe-4">
                            {{ $candidate->classRoom?->name ?? '-' }}
                        </td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-muted py-3 ps-4">
                            <i class="bi bi-calendar-plus me-1"></i> Terdaftar
                        </td>
                        <td class="py-3 pe-4">
                            {{ $candidate->created_at->translatedFormat('d M Y, H:i') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==========================================
         HIDDEN DELETE FORM
         ========================================== --}}
    <form id="deleteForm" action="{{ route('admin.candidates.destroy', $candidate) }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

@endsection

@push('scripts')
    <script>
        async function confirmDelete() {
            const ok = await swalConfirm({
                title: 'Hapus Kandidat?',
                message: 'Kandidat "{{ $candidate->nama }}" akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                okText: 'Ya, Hapus',
                okColor: '#dc3545',
            });

            if (ok) {
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
@endpush
