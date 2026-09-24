@extends('layouts.admin')

@section('title', 'Edit Pemilih')
@section('page-title', 'Edit Pemilih')
@section('page-subtitle', $voter->name)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.voters.index') }}" class="text-decoration-none text-muted-green">
            Pemilih
        </a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('admin.voters.show', $voter) }}" class="text-decoration-none text-muted-green">
            Detail
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Edit</li>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.voters.show', $voter) }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- ==========================================
         WARNING: KELAS LOCKED
         ========================================== --}}
    @php
        $classLocked = $voterRecord && ($voterRecord->checked_in || $voterRecord->has_voted);
    @endphp

    @if ($classLocked)
        <div class="alert alert-warning d-flex align-items-start gap-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            <div>
                <strong>Kelas tidak bisa diubah.</strong>
                <p class="mb-0 small">
                    Voter ini sudah
                    @if ($voterRecord->has_voted)
                        <strong>melakukan voting</strong>
                    @else
                        <strong>check-in</strong>
                    @endif
                    di sesi sebelumnya.
                    @if ($voterRecord->session)
                        (Sesi: <strong>{{ $voterRecord->session->classRoom?->name ?? '-' }}</strong>)
                    @endif
                    Field kelas akan diabaikan saat submit.
                </p>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.voters.update', $voter) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">

            {{-- ==========================================
                 DATA PEMILIH
                 ========================================== --}}
            <div class="col-md-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <strong><i class="bi bi-person-gear text-success me-1"></i> Data Pemilih</strong>
                    </div>
                    <div class="card-body">

                        {{-- NIS --}}
                        <div class="mb-3">
                            <label for="nis" class="form-label">
                                NIS <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="nis" name="nis" class="form-control @error('nis') is-invalid @enderror" value="{{ old('nis', $voter->nis) }}" required>
                            @error('nis')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Nama --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $voter->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Kelas --}}
                        <div class="mb-3">
                            <label for="class_id" class="form-label">
                                Kelas <span class="text-danger">*</span>
                                @if ($classLocked)
                                    <span class="badge bg-warning text-dark ms-1">
                                        <i class="bi bi-lock-fill"></i> Terkunci
                                    </span>
                                @endif
                            </label>
                            <select id="class_id" name="class_id" class="form-select @error('class_id') is-invalid @enderror" {{ $classLocked ? 'disabled' : '' }} required>
                                <option value="">— Pilih Kelas —</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" {{ old('class_id', $voter->class_id) == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- Disabled select tidak ter-submit → kirim hidden input --}}
                            @if ($classLocked)
                                <input type="hidden" name="class_id" value="{{ $voter->class_id }}">
                            @endif

                            @error('class_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            @if ($classLocked)
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i>
                                    Kelas tidak bisa diubah karena voter sudah check-in / voting.
                                </small>
                            @else
                                <small class="text-muted">Hanya kelas aktif yang ditampilkan.</small>
                            @endif
                        </div>

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $voter->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Info read-only --}}
                        <div class="alert alert-light border small mb-0">
                            <i class="bi bi-info-circle text-muted"></i>
                            <strong>Status</strong> dan <strong>Password</strong> tidak bisa diubah dari sini.
                            Gunakan aksi <em>Setujui</em>, <em>Tolak</em>, <em>Reset Password</em>, atau
                            <em>Generate Password</em> di halaman detail.
                        </div>

                    </div>
                </div>
            </div>

            {{-- ==========================================
                 KARTU PELAJAR
                 ========================================== --}}
            <div class="col-md-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <strong><i class="bi bi-card-image text-success me-1"></i> Kartu Pelajar</strong>
                    </div>
                    <div class="card-body">

                        {{-- Preview existing --}}
                        <div id="existingKartuBox" class="{{ $voter->kartu_pelajar ? '' : 'd-none' }} mb-3 text-center">
                            <img id="existingKartuImg" src="{{ $voter->kartu_pelajar ? asset('storage/' . $voter->kartu_pelajar) : '' }}" alt="Kartu Pelajar" class="img-fluid rounded shadow-sm"
                                style="max-height: 300px;">
                            <div class="form-check d-flex justify-content-center align-items-center gap-2 mt-2">
                                <input type="checkbox" name="hapus_kartu" id="hapus_kartu" value="1" class="form-check-input" {{ old('hapus_kartu') ? 'checked' : '' }}>
                                <label for="hapus_kartu" class="form-check-label text-danger small">
                                    <i class="bi bi-trash"></i> Hapus kartu ini
                                </label>
                            </div>
                        </div>

                        {{-- Empty state --}}
                        <div id="kartuEmptyBox" class="{{ $voter->kartu_pelajar ? 'd-none' : '' }} text-center text-muted py-4">
                            <i class="bi bi-image" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="mt-2 mb-0 small">Belum ada gambar</p>
                        </div>

                        {{-- Preview gambar baru --}}
                        <div id="kartuPreviewBox" class="d-none mb-3 text-center">
                            <img id="kartuPreviewImg" src="" alt="Preview Baru" class="img-fluid rounded shadow-sm" style="max-height: 300px;">
                            <p class="small text-success mt-1 mb-0">
                                <i class="bi bi-check-circle"></i> Gambar baru siap disimpan
                            </p>
                        </div>

                        {{-- Upload input --}}
                        <div class="mb-0">
                            <label for="kartu_pelajar" class="form-label small">Ganti Foto Kartu</label>
                            <input type="file" id="kartu_pelajar" name="kartu_pelajar" class="form-control @error('kartu_pelajar') is-invalid @enderror"
                                accept="image/jpg,image/jpeg,image/png,image/webp" onchange="previewKartu(this)">
                            @error('kartu_pelajar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">JPG, PNG, WEBP · Maks 2 MB · Opsional</small>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        {{-- ACTIONS --}}
        <div class="d-flex justify-content-end gap-2 mt-4 mb-4">
            <a href="{{ route('admin.voters.show', $voter) }}" class="btn-custom btn-custom-light">
                Batal
            </a>
            <button type="submit" class="btn-custom btn-custom-primary">
                <i class="bi bi-check-lg"></i> Simpan Perubahan
            </button>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        function previewKartu(input) {
            const previewBox = document.getElementById('kartuPreviewBox');
            const previewImg = document.getElementById('kartuPreviewImg');
            const existingBox = document.getElementById('existingKartuBox');
            const emptyBox = document.getElementById('kartuEmptyBox');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewBox.classList.remove('d-none');
                    existingBox.classList.add('d-none');
                    emptyBox.classList.add('d-none');
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                previewBox.classList.add('d-none');
                // Kembalikan ke state existing
                if (existingBox.querySelector('img')?.src) {
                    existingBox.classList.remove('d-none');
                } else {
                    emptyBox.classList.remove('d-none');
                }
            }
        }
    </script>
@endpush
