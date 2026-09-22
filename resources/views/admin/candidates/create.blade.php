@extends('layouts.admin')

@section('title', 'Tambah Kandidat')
@section('page-title', 'Tambah Kandidat')
@section('page-subtitle', $election->title)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.elections.index') }}" class="text-decoration-none text-muted-green">
            Pemilihan
        </a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('admin.candidates.index', ['election_id' => $election->id]) }}" class="text-decoration-none text-muted-green">
            Kandidat
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Tambah</li>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.candidates.index', ['election_id' => $election->id]) }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <strong><i class="bi bi-person-plus text-success me-1"></i> Form Kandidat Baru</strong>
                </div>
                <div class="card-body">

                    <form action="{{ route('admin.candidates.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="election_id" value="{{ $election->id }}">

                        {{-- Foto --}}
                        <div class="mb-4 text-center">
                            <div id="fotoPreview" class="rounded-circle mx-auto d-flex align-items-center justify-content-center"
                                style="width: 150px; height: 150px; background: #f0f0f0;
                                        border: 3px dashed #ccc; cursor: pointer;
                                        overflow: hidden; transition: all 0.3s;"
                                onclick="document.getElementById('fotoInput').click()">
                                <div id="fotoPlaceholder" class="text-center text-muted">
                                    <i class="bi bi-camera" style="font-size: 2.5rem;"></i>
                                    <div class="small mt-1">Upload Foto</div>
                                </div>
                                <img id="fotoImg" src="" alt="Preview" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                            </div>
                            <input type="file" id="fotoInput" name="foto" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="previewFoto(this)">
                            <small class="text-muted d-block mt-2">
                                Klik untuk upload. JPG/PNG, maks 2MB.
                            </small>
                            @error('foto')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- No Urut + Kelas --}}
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="no_urut" class="form-label">
                                    Nomor Urut <span class="text-danger">*</span>
                                </label>
                                <input type="number" id="no_urut" name="no_urut" class="form-control @error('no_urut') is-invalid @enderror" value="{{ old('no_urut', $nextNo) }}" min="1"
                                    required>
                                @error('no_urut')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8 mb-3">
                                <label for="nama" class="form-label">
                                    Nama Lengkap <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="nama" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}" placeholder="Nama lengkap kandidat"
                                    required>
                                @error('nama')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
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
                        </div>

                        {{-- Visi --}}
                        <div class="mb-3">
                            <label for="visi" class="form-label">
                                Visi <span class="text-danger">*</span>
                            </label>
                            <textarea id="visi" name="visi" class="form-control @error('visi') is-invalid @enderror" rows="3" placeholder="Visi kandidat" required>{{ old('visi') }}</textarea>
                            @error('visi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Misi --}}
                        <div class="mb-3">
                            <label for="misi" class="form-label">
                                Misi <span class="text-danger">*</span>
                            </label>
                            <textarea id="misi" name="misi" class="form-control @error('misi') is-invalid @enderror" rows="4" placeholder="Misi kandidat (boleh multi-baris)" required>{{ old('misi') }}</textarea>
                            @error('misi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Program Kerja --}}
                        <div class="mb-3">
                            <label for="program_kerja" class="form-label">Program Kerja</label>
                            <textarea id="program_kerja" name="program_kerja" class="form-control @error('program_kerja') is-invalid @enderror" rows="4" placeholder="Program kerja (opsional)">{{ old('program_kerja') }}</textarea>
                            @error('program_kerja')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.candidates.index', ['election_id' => $election->id]) }}" class="btn-custom btn-custom-light">
                                Batal
                            </a>
                            <button type="submit" class="btn-custom btn-custom-primary">
                                <i class="bi bi-check-lg"></i> Simpan Kandidat
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
        function previewFoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    document.getElementById('fotoImg').src = e.target.result;
                    document.getElementById('fotoImg').style.display = 'block';
                    document.getElementById('fotoPlaceholder').style.display = 'none';
                    document.getElementById('fotoPreview').style.border = '3px solid #c6f135';
                };

                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endpush
