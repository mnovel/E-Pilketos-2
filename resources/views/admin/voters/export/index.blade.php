@extends('layouts.admin')

@section('title', 'Export Voter Credentials')
@section('page-title', 'Export Voter Credentials')
@section('page-subtitle', 'Download data akun voter untuk dibagikan')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.voters.index') }}" class="text-decoration-none text-muted-green">Pemilih</a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Export</li>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.voters.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <strong>
                        <i class="bi bi-file-earmark-excel text-success me-1"></i>
                        Export Data Akun Voter
                    </strong>
                </div>
                <div class="card-body">

                    {{-- INFO --}}
                    <div class="alert alert-info small mb-4">
                        <i class="bi bi-info-circle-fill"></i>
                        File Excel akan berisi: NIS, Nama, Kelas, Email, Password, Status.
                        Bagikan ke siswa untuk login.
                    </div>

                    <form action="{{ route('admin.voters.export.download') }}" method="POST">
                        @csrf

                        {{-- Kelas --}}
                        <div class="mb-3">
                            <label for="class_id" class="form-label">Filter Kelas</label>
                            <select name="class_id" id="class_id" class="form-select">
                                <option value="">— Semua Kelas —</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}">
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                Kosongkan untuk export semua kelas.
                            </small>
                        </div>

                        {{-- Status --}}
                        <div class="mb-3">
                            <label for="status" class="form-label">Filter Status</label>
                            <select name="status" id="status" class="form-select">
                                {{-- Count akan di-update otomatis via JS --}}
                                <option value="all" data-label="Semua Status">
                                    Semua Status ({{ $counts['all'] }})
                                </option>
                                <option value="pending" data-label="Menunggu Verifikasi">
                                    Menunggu Verifikasi ({{ $counts['pending'] }})
                                </option>
                                <option value="verified" data-label="Terverifikasi">
                                    Terverifikasi ({{ $counts['verified'] }})
                                </option>
                                <option value="rejected" data-label="Ditolak">
                                    Ditolak ({{ $counts['rejected'] }})
                                </option>
                            </select>
                            <small class="text-muted">
                                Count menyesuaikan dengan kelas yang dipilih.
                            </small>
                        </div>

                        {{-- Reset Password --}}
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" name="reset_password" id="reset_password" value="1" class="form-check-input">
                                <label for="reset_password" class="form-check-label">
                                    <strong>Reset semua password</strong>
                                </label>
                            </div>
                            <small class="text-muted d-block ms-4">
                                <i class="bi bi-exclamation-triangle text-warning"></i>
                                Kalau dicentang, password semua voter akan <strong>diganti random</strong>
                                & ditampilkan di file Excel. Password lama tidak berlaku lagi.
                            </small>
                        </div>

                        {{-- Preview Count --}}
                        <div class="alert alert-secondary small mb-4" id="previewBox" style="display: none;">
                            <i class="bi bi-people-fill"></i>
                            Akan mengexport <strong><span id="previewCount">0</span> voter</strong>
                            <span id="previewReset"></span>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.voters.index') }}" class="btn-custom btn-custom-light">
                                Batal
                            </a>
                            <button type="submit" class="btn-custom btn-custom-success">
                                <i class="bi bi-download"></i> Download Excel
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const classSelect = document.getElementById('class_id');
            const statusSelect = document.getElementById('status');
            const resetCheckbox = document.getElementById('reset_password');
            const previewBox = document.getElementById('previewBox');
            const previewCount = document.getElementById('previewCount');
            const previewReset = document.getElementById('previewReset');

            async function updatePreview() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                try {
                    const response = await fetch('{{ route('admin.voters.export.preview') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            class_id: classSelect.value || null,
                            status: statusSelect.value || 'all',
                            reset_password: resetCheckbox.checked ? 1 : 0,
                        }),
                    });

                    if (!response.ok) return;

                    const data = await response.json();

                    // ✅ Update preview count
                    previewCount.textContent = data.count;
                    previewReset.textContent = data.reset ? ' (password akan direset)' : '';
                    previewBox.style.display = 'block';

                    // ✅ Update dropdown count per status (dynamic, sesuai kelas)
                    if (data.counts) {
                        const options = statusSelect.querySelectorAll('option');
                        options.forEach(opt => {
                            const key = opt.value;
                            const baseLabel = opt.dataset.label || opt.textContent.split(' (')[0];

                            if (data.counts[key] !== undefined) {
                                opt.textContent = `${baseLabel} (${data.counts[key]})`;
                            }
                        });
                    }
                } catch (e) {
                    console.error('Preview error:', e);
                }
            }

            // Trigger saat filter berubah
            classSelect.addEventListener('change', updatePreview);
            statusSelect.addEventListener('change', updatePreview);
            resetCheckbox.addEventListener('change', updatePreview);

            // Load pertama
            updatePreview();
        });
    </script>
@endpush
