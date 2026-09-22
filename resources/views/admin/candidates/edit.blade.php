@extends('layouts.admin')

@section('title', 'Edit Kandidat')
@section('page-title', 'Edit Kandidat')
@section('page-subtitle', $candidate->nama)

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.elections.index') }}" class="text-decoration-none text-muted-green">
            Pemilihan
        </a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('admin.candidates.index', ['election_id' => $candidate->election_id]) }}" class="text-decoration-none text-muted-green">
            Kandidat
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Edit</li>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.candidates.index', ['election_id' => $candidate->election_id]) }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <strong><i class="bi bi-pencil text-warning me-1"></i> Edit Kandidat</strong>
                </div>
                <div class="card-body">

                    <form action="{{ route('admin.candidates.update', $candidate) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- Foto --}}
                        <div class="mb-4 text-center">
                            <div id="fotoPreview" class="rounded-circle mx-auto d-flex align-items-center justify-content-center"
                                style="width: 150px; height: 150px; background: #f0f0f0;
                                        border: 3px solid #c6f135; cursor: pointer;
                                        overflow: hidden; transition: all 0.3s;"
                                onclick="document.getElementById('fotoInput').click()">
                                <div id="fotoPlaceholder" class="text-center text-muted" style="{{ $candidate->foto ? 'display:none;' : '' }}">
                                    <i class="bi bi-camera" style="font-size: 2.5rem;"></i>
                                    <div class="small mt-1">Upload Foto</div>
                                </div>
                                <img id="fotoImg" src="{{ $candidate->foto ? asset('storage/' . $candidate->foto) : '' }}" alt="Preview"
                                    style="width: 100%; height: 100%; object-fit: cover;
                                            display: {{ $candidate->foto ? 'block' : 'none' }};">
                            </div>
                            <input type="file" id="fotoInput" name="foto" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="previewFoto(this)">
                            <small class="text-muted d-block mt-2">
                                Klik untuk ganti foto. Biarkan kosong jika tidak ingin mengubah.
                            </small>
                            @error('foto')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- No Urut + Nama --}}
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="no_urut" class="form-label">
                                    Nomor Urut <span class="text-danger">*</span>
                                </label>
                                <input type="number" id="no_urut" name="no_urut" class="form-control @error('no_urut') is-invalid @enderror" value="{{ old('no_urut', $candidate->no_urut) }}"
                                    min="1" required>
                                @error('no_urut')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8 mb-3">
                                <label for="nama" class="form-label">
                                    Nama Lengkap <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="nama" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $candidate->nama) }}" required>
                                @error('nama')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Kelas --}}
                        <div class="mb-3">
                            <label for="kelas" class="form-label">
                                Kelas <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="kelas" name="kelas" class="form-control @error('kelas') is-invalid @enderror" value="{{ old('kelas', $candidate->kelas) }}" required>
                            @error('kelas')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Visi --}}
                        <div class="mb-3">
                            <label for="visi" class="form-label">
                                Visi <span class="text-danger">*</span>
                            </label>
                            <textarea id="visi" name="visi" class="form-control @error('visi') is-invalid @enderror" rows="3" required>{{ old('visi', $candidate->visi) }}</textarea>
                            @error('visi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Misi --}}
                        <div class="mb-3">
                            <label for="misi" class="form-label">
                                Misi <span class="text-danger">*</span>
                            </label>
                            <textarea id="misi" name="misi" class="form-control @error('misi') is-invalid @enderror" rows="4" required>{{ old('misi', $candidate->misi) }}</textarea>
                            @error('misi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Program Kerja --}}
                        <div class="mb-3">
                            <label for="program_kerja" class="form-label">Program Kerja</label>
                            <textarea id="program_kerja" name="program_kerja" class="form-control @error('program_kerja') is-invalid @enderror" rows="4">{{ old('program_kerja', $candidate->program_kerja) }}</textarea>
                            @error('program_kerja')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.candidates.index', ['election_id' => $candidate->election_id]) }}" class="btn-custom btn-custom-light">
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

@push('scripts')
    <script>
        function previewFoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    document.getElementById('fotoImg').src = e.target.result;
                    document.getElementById('fotoImg').style.display = 'block';
                    document.getElementById('fotoPlaceholder').style.display = 'none';
                };

                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endpush
