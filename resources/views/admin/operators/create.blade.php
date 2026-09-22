@extends('layouts.admin')

@section('title', 'Tambah Operator')
@section('page-title', 'Tambah Operator')
@section('page-subtitle', 'Buat akun petugas device voting')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.operators.index') }}" class="text-decoration-none text-muted-green">
            Operator
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Tambah</li>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.operators.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <strong>
                        <i class="bi bi-person-plus text-success me-1"></i> Form Operator Baru
                    </strong>
                </div>
                <div class="card-body">

                    <form action="{{ route('admin.operators.store') }}" method="POST">
                        @csrf

                        {{-- Nama --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Contoh: Pak Budi" required
                                autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="operator@sekolah.sch.id"
                                required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Email ini yang dipakai untuk login</small>
                        </div>

                        {{-- Password --}}
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Konfirmasi Password --}}
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">
                                Konfirmasi Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                        </div>

                        {{-- Info --}}
                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle"></i>
                            Operator tidak perlu verifikasi. Bisa langsung login setelah akun dibuat.
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.operators.index') }}" class="btn-custom btn-custom-light">
                                Batal
                            </a>
                            <button type="submit" class="btn-custom btn-custom-primary">
                                <i class="bi bi-check-lg"></i> Simpan Operator
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection
