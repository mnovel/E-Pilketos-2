@extends('layouts.admin')

@section('title', 'Buat Pemilihan')
@section('page-title', 'Buat Pemilihan')
@section('page-subtitle', 'Buat periode pemilihan baru')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.elections.index') }}" class="text-decoration-none text-muted-green">
            Pemilihan
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Buat</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/flatpickr/flatpickr.min.css') }}">
@endpush

@section('content')

    {{-- BACK BUTTON --}}
    <div class="mb-3">
        <a href="{{ route('admin.elections.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <strong><i class="bi bi-plus-circle text-success me-1"></i> Form Pemilihan Baru</strong>
                </div>
                <div class="card-body">

                    <form action="{{ route('admin.elections.store') }}" method="POST">
                        @csrf

                        {{-- Title --}}
                        <div class="mb-3">
                            <label for="title" class="form-label">
                                Judul Pemilihan <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" placeholder="Contoh: Pemilihan Ketua OSIS 2026"
                                value="{{ old('title') }}" required autofocus>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tahun Ajaran --}}
                        <div class="mb-3">
                            <label for="tahun_ajaran" class="form-label">
                                Tahun Ajaran <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="tahun_ajaran" name="tahun_ajaran" class="form-control @error('tahun_ajaran') is-invalid @enderror" placeholder="Contoh: 2025/2026"
                                value="{{ old('tahun_ajaran') }}" required>
                            @error('tahun_ajaran')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Deskripsi --}}
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea id="deskripsi" name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror" rows="3" placeholder="Deskripsi singkat (opsional)">{{ old('deskripsi') }}</textarea>
                            @error('deskripsi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            {{-- Start At --}}
                            <div class="col-md-6 mb-3">
                                <label for="start_at" class="form-label">
                                    Tanggal Mulai <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="start_at" name="start_at" class="form-control @error('start_at') is-invalid @enderror" placeholder="Pilih tanggal & waktu"
                                    value="{{ old('start_at') }}" required>
                                @error('start_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- End At --}}
                            <div class="col-md-6 mb-3">
                                <label for="end_at" class="form-label">
                                    Tanggal Selesai <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="end_at" name="end_at" class="form-control @error('end_at') is-invalid @enderror" placeholder="Pilih tanggal & waktu"
                                    value="{{ old('end_at') }}" required>
                                @error('end_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- ACTIONS --}}
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="{{ route('admin.elections.index') }}" class="btn-custom btn-custom-light">
                                Batal
                            </a>
                            <button type="submit" class="btn-custom btn-custom-primary">
                                <i class="bi bi-check-lg"></i> Simpan
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('storage/assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script>
        flatpickr('#start_at', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            minDate: 'today',
            time_24hr: true,
        });

        flatpickr('#end_at', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            minDate: 'today',
            time_24hr: true,
        });
    </script>
@endpush
