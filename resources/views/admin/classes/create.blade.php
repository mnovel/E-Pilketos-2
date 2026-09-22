@extends('layouts.admin')

@section('title', 'Tambah Kelas')
@section('page-title', 'Tambah Kelas')
@section('page-subtitle', 'Tambahkan kelas baru ke master data')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.classes.index') }}" class="text-decoration-none text-muted-green">
            Kelas
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Tambah</li>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.classes.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <strong>
                        <i class="bi bi-plus-circle text-success me-1"></i> Form Kelas Baru
                    </strong>
                </div>
                <div class="card-body">

                    <form action="{{ route('admin.classes.store') }}" method="POST">
                        @csrf

                        {{-- Nama Kelas --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Nama Kelas <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Contoh: X-IPA-1" required
                                autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Format: TINGKAT-JURUSAN-ROMBEL (uppercase)</small>
                        </div>

                        <div class="row">
                            {{-- Tingkat --}}
                            <div class="col-md-6 mb-3">
                                <label for="tingkat" class="form-label">
                                    Tingkat <span class="text-danger">*</span>
                                </label>
                                <select id="tingkat" name="tingkat" class="form-select @error('tingkat') is-invalid @enderror" required>
                                    <option value="">— Pilih Tingkat —</option>
                                    <option value="X" {{ old('tingkat') == 'X' ? 'selected' : '' }}>X</option>
                                    <option value="XI" {{ old('tingkat') == 'XI' ? 'selected' : '' }}>XI</option>
                                    <option value="XII" {{ old('tingkat') == 'XII' ? 'selected' : '' }}>XII</option>
                                    <option value="GURU" {{ old('tingkat') == 'GURU' ? 'selected' : '' }}>GURU</option>
                                </select>
                                @error('tingkat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Jurusan --}}
                            <div class="col-md-6 mb-3">
                                <label for="jurusan" class="form-label">Jurusan</label>
                                <input type="text" id="jurusan" name="jurusan" class="form-control @error('jurusan') is-invalid @enderror" value="{{ old('jurusan') }}"
                                    placeholder="Contoh: IPA, IPS (opsional)">
                                @error('jurusan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Rombel --}}
                        <div class="mb-3">
                            <label for="rombel" class="form-label">Rombel</label>
                            <input type="text" id="rombel" name="rombel" class="form-control @error('rombel') is-invalid @enderror" value="{{ old('rombel') }}"
                                placeholder="Contoh: 1, 2, 3 (opsional)">
                            @error('rombel')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Is Active --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label for="is_active" class="form-check-label">
                                    Kelas Aktif
                                </label>
                            </div>
                            <small class="text-muted">
                                Kelas nonaktif tidak akan muncul di pilihan saat register pemilih.
                            </small>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.classes.index') }}" class="btn-custom btn-custom-light">
                                Batal
                            </a>
                            <button type="submit" class="btn-custom btn-custom-primary">
                                <i class="bi bi-check-lg"></i> Simpan Kelas
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection
