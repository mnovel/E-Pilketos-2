@extends('layouts.admin')

@section('title', 'Profile Saya')
@section('page-title', 'Profile Saya')
@section('page-subtitle', 'Kelola data akun Anda')

@section('breadcrumb')
    <li class="breadcrumb-item active text-main" aria-current="page">Profile</li>
@endsection

@section('content')

    @php
        $user = auth()->user();
    @endphp

    <div class="row g-4">

        {{-- ==========================================
             KARTU PROFILE (KIRI)
             ========================================== --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-4">

                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 100px; height: 100px; background: #c6f135;
                                color: #1a2e1a; font-weight: 700; font-size: 2.5rem;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>

                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted small mb-3">
                        {{ $user->email }}
                    </p>

                    <span class="badge bg-{{ $user->role->color() }} py-2 px-3">
                        <i class="bi {{ $user->role->icon() }} me-1"></i>
                        {{ $user->role->label() }}
                    </span>

                    {{-- ✅ KHUSUS VOTER — Info tambahan --}}
                    @if ($user->isVoter())
                        <div class="mt-3 pt-3 border-top text-start">
                            <div class="mb-2">
                                <small class="text-muted d-block">NIS</small>
                                <strong>{{ $user->nis ?? '-' }}</strong>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted d-block">Kelas</small>
                                <strong>{{ $user->classRoom?->name ?? '-' }}</strong>
                            </div>
                            <div>
                                <small class="text-muted d-block mb-1">Status Verifikasi</small>
                                <span class="badge bg-{{ $user->status->color() }}">
                                    <i class="bi {{ $user->status->icon() }}"></i>
                                    {{ $user->status->label() }}
                                </span>
                            </div>

                            {{-- Banner alasan reject --}}
                            @if ($user->status->isRejected() && $user->alasan_reject)
                                <div class="alert alert-danger small mt-3 mb-0 py-2">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <strong>Alasan Ditolak:</strong>
                                    <div class="mt-1">{{ $user->alasan_reject }}</div>
                                </div>
                            @endif

                            {{-- Info note --}}
                            <div class="alert alert-info small mt-3 mb-0 py-2">
                                <i class="bi bi-info-circle-fill"></i>
                                NIS, kelas, dan status <strong>tidak dapat diubah sendiri</strong>.
                                Hubungi panitia jika ada kesalahan.
                            </div>
                        </div>
                    @endif

                    @if ($user->last_login_at)
                        <div class="mt-3 pt-3 border-top">
                            <small class="text-muted">
                                <i class="bi bi-clock-history me-1"></i>
                                Login terakhir:<br>
                                {{ $user->last_login_at->translatedFormat('d M Y, H:i') }}
                            </small>
                        </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- ==========================================
             FORM (KANAN)
             ========================================== --}}
        <div class="col-md-8">

            {{-- EDIT PROFILE --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <strong>
                        <i class="bi bi-person text-success me-1"></i>
                        Data Profile
                    </strong>
                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">
                        <i class="bi bi-pencil"></i> Dapat diubah
                    </span>
                </div>
                <div class="card-body">

                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn-custom btn-custom-primary">
                                <i class="bi bi-check-lg"></i> Simpan Perubahan
                            </button>
                        </div>

                    </form>

                </div>
            </div>

            {{-- UBAH PASSWORD --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <strong>
                        <i class="bi bi-key text-warning me-1"></i>
                        Ubah Password
                    </strong>
                </div>
                <div class="card-body">

                    <form action="{{ route('profile.password') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                Password Lama <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="current_password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Password Baru <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Minimal 8 karakter, kombinasi huruf & angka.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">
                                Konfirmasi Password Baru <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn-custom btn-custom-warning">
                                <i class="bi bi-key"></i> Ubah Password
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

@endsection
