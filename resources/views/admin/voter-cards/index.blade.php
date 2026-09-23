@extends('layouts.admin')

@section('title', 'Cetak Kartu Voter')
@section('page-title', 'Cetak Kartu Voter')
@section('page-subtitle', 'Cetak kartu fisik voter dengan QR code')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.voters.index') }}" class="text-decoration-none text-muted-green">Pemilih</a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Cetak Kartu</li>
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
                        <i class="bi bi-person-badge text-primary me-1"></i>
                        Cetak Kartu Voter (PDF)
                    </strong>
                </div>
                <div class="card-body">

                    <div class="alert alert-info small mb-4">
                        <i class="bi bi-info-circle-fill"></i>
                        PDF akan berisi <strong>8 kartu per halaman A4</strong>, lengkap dengan QR code.
                        Cetak, potong, dan bagikan ke siswa.
                    </div>

                    <form action="{{ route('admin.voter-cards.download') }}" method="POST" target="_blank">
                        @csrf

                        {{-- Kelas --}}
                        <div class="mb-3">
                            <label for="class_id" class="form-label">
                                Kelas <span class="text-danger">*</span>
                            </label>
                            <select name="class_id" id="class_id" class="form-select" required>
                                <option value="">— Pilih Kelas —</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}">
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Status --}}
                        <div class="mb-3">
                            <label for="status" class="form-label">Filter Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="verified">
                                    Hanya Terverifikasi ({{ $counts['verified'] }})
                                </option>
                                <option value="all">
                                    Semua Status ({{ $counts['all'] }})
                                </option>
                            </select>
                            <small class="text-muted">
                                Disarankan hanya cetak yang sudah terverifikasi.
                            </small>
                        </div>

                        {{-- Preview --}}
                        <div class="alert alert-secondary small mb-4" id="previewBox" style="display: none;">
                            <i class="bi bi-people-fill"></i>
                            Akan mencetak <strong><span id="previewCount">0</span> kartu</strong>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.voters.index') }}" class="btn-custom btn-custom-light">
                                Batal
                            </a>
                            <button type="submit" class="btn-custom btn-custom-primary">
                                <i class="bi bi-file-earmark-pdf"></i> Generate PDF
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
            const previewBox = document.getElementById('previewBox');
            const previewCount = document.getElementById('previewCount');

            async function updatePreview() {
                if (!classSelect.value) {
                    previewBox.style.display = 'none';
                    return;
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                const response = await fetch('{{ route('admin.voter-cards.preview') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        class_id: classSelect.value,
                        status: statusSelect.value,
                    }),
                });

                if (!response.ok) return;

                const data = await response.json();
                previewCount.textContent = data.count;
                previewBox.style.display = 'block';
            }

            classSelect.addEventListener('change', updatePreview);
            statusSelect.addEventListener('change', updatePreview);
        });
    </script>
@endpush
