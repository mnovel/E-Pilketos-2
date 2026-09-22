@extends('layouts.admin')

@section('title', 'Edit Kelas')
@section('page-title', 'Edit Kelas')
@section('page-subtitle', $class->name)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.classes.index') }}" class="text-decoration-none text-muted-green">
            Kelas
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Edit</li>
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
                        <i class="bi bi-pencil text-warning me-1"></i> Edit Kelas
                    </strong>
                </div>
                <div class="card-body">

                    <form action="{{ route('admin.classes.update', $class) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Nama Kelas --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Nama Kelas <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $class->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tingkat" class="form-label">
                                    Tingkat <span class="text-danger">*</span>
                                </label>
                                <select id="tingkat" name="tingkat" class="form-select" required>
                                    @foreach (['X', 'XI', 'XII', 'GURU'] as $t)
                                        <option value="{{ $t }}" {{ old('tingkat', $class->tingkat) == $t ? 'selected' : '' }}>
                                            {{ $t }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="jurusan" class="form-label">Jurusan</label>
                                <input type="text" id="jurusan" name="jurusan" class="form-control" value="{{ old('jurusan', $class->jurusan) }}" placeholder="IPA, IPS, dll (opsional)">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="rombel" class="form-label">Rombel</label>
                            <input type="text" id="rombel" name="rombel" class="form-control" value="{{ old('rombel', $class->rombel) }}" placeholder="1, 2, 3 (opsional)">
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input" {{ old('is_active', $class->is_active) ? 'checked' : '' }}>
                                <label for="is_active" class="form-check-label">
                                    Kelas Aktif
                                </label>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.classes.index') }}" class="btn-custom btn-custom-light">
                                Batal
                            </a>
                            <button type="submit" class="btn-custom btn-custom-primary">
                                <i class="bi bi-check-lg"></i> Simpan Perubahan
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection
