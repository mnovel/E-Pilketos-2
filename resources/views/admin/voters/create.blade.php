@extends('layouts.admin')

@section('title', 'Tambah Pemilih')
@section('page-title', 'Tambah Pemilih')
@section('page-subtitle', 'Tambah data pemilih baru')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.voters.index') }}" class="text-decoration-none text-muted-green">
            Pemilih
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Tambah</li>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.voters.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.voters.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">

            {{-- ==========================================
                 DATA PEMILIH
                 ========================================== --}}
            <div class="col-md-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <strong><i class="bi bi-person-plus text-success me-1"></i> Data Pemilih</strong>
                    </div>
                    <div class="card-body">

                        {{-- NIS --}}
                        <div class="mb-3">
                            <label for="nis" class="form-label">
                                NIS <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="nis" name="nis" class="form-control @error('nis') is-invalid @enderror" value="{{ old('nis') }}" placeholder="Contoh: 12345678" required>
                            @error('nis')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Nama --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Contoh: Ahmad Fauzi"
                                required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Kelas --}}
                        <div class="mb-3">
                            <label for="class_id" class="form-label">
                                Kelas <span class="text-danger">*</span>
                            </label>
                            <select id="class_id" name="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
                                <option value="">— Pilih Kelas —</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Hanya kelas aktif yang ditampilkan.</small>
                        </div>

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}"
                                placeholder="Kosongkan untuk auto-generate">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                Kalau dikosongkan, email akan otomatis dibuat dari NIS.
                            </small>
                        </div>

                        {{-- Password --}}
                        <div class="mb-0">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Kosongkan untuk default: password">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                    <i class="bi bi-eye" id="password_icon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                Default: <code>password</code> · Minimal 8 karakter.
                            </small>
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

                        <div class="mb-3">
                            <label for="kartu_pelajar" class="form-label">Upload Foto Kartu</label>
                            <input type="file" id="kartu_pelajar" name="kartu_pelajar" class="form-control @error('kartu_pelajar') is-invalid @enderror"
                                accept="image/jpg,image/jpeg,image/png,image/webp" onchange="previewKartu(this)">
                            @error('kartu_pelajar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Format: JPG, PNG, WEBP · Maks 2 MB · Opsional
                            </small>
                        </div>

                        {{-- Empty state --}}
                        <div id="kartuEmptyBox" class="text-center text-muted py-4">
                            <i class="bi bi-image" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="mt-2 mb-0 small">Belum ada gambar</p>
                        </div>

                        {{-- Preview --}}
                        <div id="kartuPreviewBox" class="text-center d-none">
                            <img id="kartuPreviewImg" src="" alt="Preview" class="img-fluid rounded shadow-sm" style="max-height: 300px;">
                        </div>

                    </div>
                </div>
            </div>

        </div>

        {{-- ACTIONS --}}
        <div class="d-flex justify-content-end gap-2 mt-4 mb-4">
            <a href="{{ route('admin.voters.index') }}" class="btn-custom btn-custom-light">
                Batal
            </a>
            <button type="submit" class="btn-custom btn-custom-primary">
                <i class="bi bi-check-lg"></i> Simpan Pemilih
            </button>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
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

        function previewKartu(input) {
            const emptyBox = document.getElementById('kartuEmptyBox');
            const previewBox = document.getElementById('kartuPreviewBox');
            const previewImg = document.getElementById('kartuPreviewImg');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewBox.classList.remove('d-none');
                    emptyBox.classList.add('d-none');
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                previewBox.classList.add('d-none');
                emptyBox.classList.remove('d-none');
            }
        }
    </script>
@endpush
