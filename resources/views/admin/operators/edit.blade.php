@extends('layouts.admin')

@section('title', 'Edit Operator')
@section('page-title', 'Edit Operator')
@section('page-subtitle', $operator->name)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.operators.index') }}" class="text-decoration-none text-muted-green">
            Operator
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Edit</li>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.operators.show', $operator) }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <strong>
                        <i class="bi bi-pencil text-warning me-1"></i> Edit Operator
                    </strong>
                </div>
                <div class="card-body">

                    <form action="{{ route('admin.operators.update', $operator) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $operator->name) }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $operator->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-warning small">
                            <i class="bi bi-key"></i>
                            Untuk reset password, gunakan tombol <strong>"Reset Password"</strong> di halaman list operator.
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.operators.show', $operator) }}" class="btn-custom btn-custom-light">
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
