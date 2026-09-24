@extends('layouts.admin')

@section('title', 'Detail Pemilih')
@section('page-title', 'Detail Pemilih')
@section('page-subtitle', $voter->name)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.voters.index') }}" class="text-decoration-none text-muted-green">
            Pemilih
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Detail</li>
@endsection

@section('content')

    {{-- BACK BUTTON --}}
    <div class="mb-3">
        <a href="{{ route('admin.voters.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- ==========================================
         PROFILE HEADER CARD
         ========================================== --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">

                {{-- Avatar --}}
                <div class="position-relative flex-shrink-0">
                    @if ($voter->kartu_pelajar)
                        <img src="{{ asset('storage/' . $voter->kartu_pelajar) }}" alt="{{ $voter->name }}" class="rounded-circle"
                            style="width: 100px; height: 100px; object-fit: cover;
                                    border: 4px solid #c6f135;">
                    @else
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 100px; height: 100px; background: #c6f135;
                                    color: #1a2e1a; font-weight: 700; font-size: 2.5rem;
                                    border: 4px solid #e8f5c8;">
                            {{ strtoupper(substr($voter->name, 0, 1)) }}
                        </div>
                    @endif

                    {{-- Status dot indicator --}}
                    <span class="position-absolute rounded-circle border border-white"
                        style="width: 22px; height: 22px; bottom: 4px; right: 4px;
                                 background: {{ $voter->status === \App\Enums\VoterStatus::VERIFIED ? '#198754' : ($voter->status === \App\Enums\VoterStatus::PENDING ? '#ffc107' : '#dc3545') }};">
                    </span>
                </div>

                {{-- Identity --}}
                <div class="flex-grow-1 text-center text-md-start">
                    <h3 class="mb-1">{{ $voter->name }}</h3>
                    <p class="text-muted mb-3">
                        <i class="bi bi-envelope me-1"></i> {{ $voter->email }}
                    </p>

                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-hash me-1"></i>{{ $voter->nis }}
                        </span>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-mortarboard me-1"></i>{{ $voter->classRoom?->name ?? '-' }}
                        </span>

                        @if ($voter->status === \App\Enums\VoterStatus::PENDING)
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-hourglass-split me-1"></i> Menunggu Verifikasi
                            </span>
                        @elseif ($voter->status === \App\Enums\VoterStatus::VERIFIED)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle me-1"></i> Terverifikasi
                            </span>
                        @else
                            <span class="badge bg-danger">
                                <i class="bi bi-x-circle me-1"></i> Ditolak
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="d-flex flex-column gap-2 flex-shrink-0" style="min-width: 180px;">

                    {{-- Tombol Edit (conditional) --}}
                    @if ($voter->canEditVoterData())
                        <a href="{{ route('admin.voters.edit', $voter) }}" class="btn-custom btn-custom-outline-primary">
                            <i class="bi bi-pencil"></i> Edit Data
                        </a>
                    @else
                        <button type="button" class="btn-custom btn-custom-light" style="opacity: 0.6; cursor: not-allowed;" title="{{ $voter->getEditLockReason() }}" disabled>
                            <i class="bi bi-lock"></i> Terkunci
                        </button>
                    @endif

                    {{-- Approve / Reject (kalau belum verified) --}}
                    @if ($voter->status !== \App\Enums\VoterStatus::VERIFIED)
                        <button type="button" class="btn-custom btn-custom-primary" onclick="confirmApprove()">
                            <i class="bi bi-check-circle"></i> Setujui
                        </button>
                        <button type="button" class="btn-custom btn-custom-danger" onclick="confirmReject()">
                            <i class="bi bi-x-circle"></i> Tolak
                        </button>
                    @endif

                    {{-- Reset Password --}}
                    <button type="button" class="btn-custom btn-custom-warning" onclick="showResetModal()">
                        <i class="bi bi-key"></i> Reset Password
                    </button>

                    {{-- Generate Password --}}
                    <button type="button" class="btn-custom btn-custom-outline-warning" onclick="confirmGenerate()">
                        <i class="bi bi-shuffle"></i> Generate Password
                    </button>

                </div>

            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ==========================================
             STUDENT CARD
             ========================================== --}}
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong><i class="bi bi-card-image text-success me-1"></i> Kartu Pelajar</strong>
                        @if ($voter->kartu_pelajar)
                            <a href="{{ asset('storage/' . $voter->kartu_pelajar) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Buka di tab baru">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body text-center d-flex align-items-center justify-content-center" style="background: #fafafa;">

                    @if ($voter->kartu_pelajar)
                        <img src="{{ asset('storage/' . $voter->kartu_pelajar) }}" alt="Kartu Pelajar" class="img-fluid rounded shadow-sm" style="max-height: 400px; cursor: zoom-in;"
                            onclick="previewImage('{{ asset('storage/' . $voter->kartu_pelajar) }}')">
                    @else
                        <div class="text-muted py-5">
                            <i class="bi bi-image" style="font-size: 4rem; opacity: 0.3;"></i>
                            <p class="mt-2 mb-0">Tidak ada kartu pelajar</p>
                        </div>
                    @endif
                </div>
                @if ($voter->kartu_pelajar)
                    <div class="card-footer bg-white text-center">
                        <small class="text-muted">
                            <i class="bi bi-zoom-in"></i> Klik gambar untuk memperbesar
                        </small>
                    </div>
                @endif
            </div>
        </div>

        {{-- ==========================================
             INFO
             ========================================== --}}
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <strong><i class="bi bi-person-lines-fill text-success me-1"></i> Informasi Pemilih</strong>
                </div>
                <div class="card-body p-0">

                    <table class="table table-borderless mb-0 align-middle">
                        <tbody>
                            <tr>
                                <td class="text-muted py-3 ps-4" style="width: 40%;">
                                    <i class="bi bi-hash me-1"></i> NIS
                                </td>
                                <td class="py-3 pe-4">
                                    <code class="fs-6">{{ $voter->nis }}</code>
                                </td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-muted py-3 ps-4">
                                    <i class="bi bi-person me-1"></i> Nama Lengkap
                                </td>
                                <td class="py-3 pe-4 fw-medium">{{ $voter->name }}</td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-muted py-3 ps-4">
                                    <i class="bi bi-mortarboard me-1"></i> Kelas
                                </td>
                                <td class="py-3 pe-4">{{ $voter->classRoom?->name ?? '-' }}</td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-muted py-3 ps-4">
                                    <i class="bi bi-envelope me-1"></i> Email
                                </td>
                                <td class="py-3 pe-4">{{ $voter->email }}</td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-muted py-3 ps-4">
                                    <i class="bi bi-calendar-plus me-1"></i> Terdaftar
                                </td>
                                <td class="py-3 pe-4">
                                    {{ $voter->created_at->translatedFormat('d M Y, H:i') }}
                                    <small class="text-muted">
                                        ({{ $voter->created_at->diffForHumans() }})
                                    </small>
                                </td>
                            </tr>

                            @if ($voter->verified_at)
                                <tr class="border-top">
                                    <td class="text-muted py-3 ps-4">
                                        <i class="bi bi-patch-check me-1"></i> Diverifikasi
                                    </td>
                                    <td class="py-3 pe-4">
                                        {{ $voter->verified_at->translatedFormat('d M Y, H:i') }}
                                        @if ($voter->verifier)
                                            <small class="text-muted d-block">
                                                oleh {{ $voter->verifier->name }}
                                            </small>
                                        @endif
                                    </td>
                                </tr>
                            @endif

                            @if ($voter->alasan_reject)
                                <tr class="border-top">
                                    <td class="text-muted py-3 ps-4">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Alasan Ditolak
                                    </td>
                                    <td class="py-3 pe-4">
                                        <div class="alert alert-danger mb-0 py-2 px-3">
                                            {{ $voter->alasan_reject }}
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    {{-- ==========================================
         HIDDEN FORMS
         ========================================== --}}
    <form id="approveForm" action="{{ route('admin.voters.approve', $voter) }}" method="POST" class="d-none">
        @csrf
    </form>

    <form id="rejectForm" action="{{ route('admin.voters.reject', $voter) }}" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="alasan_reject" id="rejectReasonField">
    </form>

    <form id="generatePasswordForm" action="{{ route('admin.voters.generate-password', $voter) }}" method="POST" class="d-none">
        @csrf
    </form>

    {{-- ==========================================
         MODAL RESET PASSWORD
         ========================================== --}}
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.voters.reset-password', $voter) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-key"></i> Reset Password
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">

                        <div class="alert alert-warning small mb-3">
                            <i class="bi bi-exclamation-triangle"></i>
                            Reset password untuk <strong>{{ $voter->name }}</strong> (NIS: {{ $voter->nis }}).
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                Password Baru <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" id="new_password" name="password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('new_password')">
                                    <i class="bi bi-eye" id="new_password_icon"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimal 8 karakter</small>
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
                            <i class="bi bi-check-lg"></i> Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // ==========================================
        // APPROVE
        // ==========================================
        async function confirmApprove() {
            const ok = await swalConfirm({
                title: 'Setujui Pemilih?',
                message: `Verifikasi {{ $voter->name }} ({{ $voter->nis }})?`,
                icon: 'question',
                okText: 'Ya, Setujui',
                okColor: '#198754',
            });

            if (ok) {
                document.getElementById('approveForm').submit();
            }
        }

        // ==========================================
        // REJECT
        // ==========================================
        async function confirmReject() {
            const reason = await swalPrompt({
                title: 'Alasan Penolakan',
                message: 'Menolak {{ $voter->name }} ({{ $voter->nis }})',
                placeholder: 'Contoh: Foto kartu pelajar tidak jelas',
                okText: 'Lanjutkan',
            });

            if (!reason) return;

            const ok = await swalConfirm({
                title: 'Konfirmasi Penolakan',
                message: 'Yakin ingin menolak pemilih ini?',
                icon: 'warning',
                okText: 'Ya, Tolak',
                okColor: '#dc3545',
            });

            if (ok) {
                document.getElementById('rejectReasonField').value = reason;
                document.getElementById('rejectForm').submit();
            }
        }

        // ==========================================
        // RESET PASSWORD (MODAL)
        // ==========================================
        function showResetModal() {
            const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
            modal.show();
        }

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        // ==========================================
        // GENERATE PASSWORD
        // ==========================================
        async function confirmGenerate() {
            const ok = await swalConfirm({
                title: 'Generate Password Baru?',
                message: 'Sistem akan membuat password acak untuk voter ini. Password akan ditampilkan SEKALI saja.',
                icon: 'question',
                okText: 'Ya, Generate',
                okColor: '#cc9a06',
            });

            if (ok) {
                document.getElementById('generatePasswordForm').submit();
            }
        }

        // ==========================================
        // IMAGE PREVIEW
        // ==========================================
        function previewImage(url) {
            Swal.fire({
                imageUrl: url,
                imageAlt: 'Kartu Pelajar',
                width: 'auto',
                showConfirmButton: false,
                showCloseButton: true,
                padding: '1rem',
                customClass: {
                    popup: 'rounded-4',
                    image: 'rounded-3',
                },
            });
        }

        // ==========================================
        // ALERT HASIL GENERATE PASSWORD
        // ==========================================
        @if (session('generated_password'))
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Password Baru Berhasil Dibuat!',
                    html: `
                        <p class="text-muted small mb-3">
                            Password untuk <strong>{{ session('generated_for') }}</strong>
                        </p>
                        <div class="bg-light rounded p-3 mb-3">
                            <code style="font-size: 1.5rem; font-weight: 700;
                                         letter-spacing: 2px; user-select: all;">
                                {{ session('generated_password') }}
                            </code>
                        </div>
                        <div class="alert alert-warning small text-start mb-0">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Catat sekarang!</strong>
                            Password tidak akan ditampilkan lagi.
                            Berikan ke voter untuk login.
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonText: 'Saya Sudah Catat',
                    confirmButtonColor: '#198754',
                    allowOutsideClick: false,
                });
            });
        @endif
    </script>
@endpush
