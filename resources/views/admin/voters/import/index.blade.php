@extends('layouts.admin')

@section('title', 'Import Pemilih')
@section('page-title', 'Import Pemilih')
@section('page-subtitle', 'Import data voter massal dari Excel/CSV')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.voters.index') }}" class="text-decoration-none text-muted-green">
            Pemilih
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Import</li>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('admin.voters.index') }}" class="btn-custom btn-custom-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">

            {{-- LANGKAH --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-center flex-grow-1">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                style="width: 40px; height: 40px; background: #c6f135; color: #1a2e1a;
                                        font-weight: 700;">1</div>
                            <div class="small fw-medium">Upload File</div>
                        </div>
                        <i class="bi bi-arrow-right text-muted"></i>
                        <div class="text-center flex-grow-1">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                style="width: 40px; height: 40px; background: #e9ecef; color: #6c757d;
                                        font-weight: 700;">2</div>
                            <div class="small text-muted">Preview</div>
                        </div>
                        <i class="bi bi-arrow-right text-muted"></i>
                        <div class="text-center flex-grow-1">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                style="width: 40px; height: 40px; background: #e9ecef; color: #6c757d;
                                        font-weight: 700;">3</div>
                            <div class="small text-muted">Import</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FORM UPLOAD --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <strong>
                        <i class="bi bi-cloud-upload text-success me-1"></i> Upload File
                    </strong>
                </div>
                <div class="card-body">

                    @if (session('error'))
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('admin.voters.import.preview') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- Drop Zone --}}
                        <div id="dropZone" class="border-2 border-dashed rounded-3 text-center p-5 mb-3"
                            style="border: 2px dashed #c6f135; cursor: pointer;
                                    background: #fafcf5; transition: all 0.2s;">
                            <i class="bi bi-cloud-arrow-up" style="font-size: 4rem; color: #7cb518;"></i>
                            <h5 class="mt-3 mb-2">Drag & drop file di sini</h5>
                            <p class="text-muted mb-3">atau klik untuk pilih file</p>

                            <input type="file" id="fileInput" name="file" accept=".csv,.xlsx,.xls" class="d-none" required>

                            <div id="fileName" class="mt-3 small text-success fw-medium d-none">
                                <i class="bi bi-file-earmark-check"></i>
                                <span></span>
                            </div>

                            <button type="button" class="btn-custom btn-custom-outline-primary" onclick="document.getElementById('fileInput').click()">
                                <i class="bi bi-folder2-open"></i> Pilih File
                            </button>
                        </div>

                        @error('file')
                            <div class="alert alert-danger small">{{ $message }}</div>
                        @enderror

                        <div class="d-grid">
                            <button type="submit" class="btn-custom btn-custom-primary" id="submitBtn" disabled>
                                <i class="bi bi-eye"></i> Preview Data
                            </button>
                        </div>

                    </form>

                </div>
            </div>

            {{-- TEMPLATE --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #cfe2ff;">
                            <i class="bi bi-download text-primary fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Belum punya template?</h6>
                            <p class="text-muted small mb-2">
                                Download template CSV, isi data sesuai kolom, lalu upload kembali.
                            </p>
                            <a href="{{ route('admin.voters.import.template') }}" class="btn-custom btn-custom-outline-primary btn-sm">
                                <i class="bi bi-download"></i> Download Template CSV
                            </a>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="small text-muted">
                        <strong><i class="bi bi-info-circle"></i> Kolom template:</strong>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark border me-1">nis</span> (wajib, unik)
                            <span class="badge bg-light text-dark border me-1 ms-2">nama</span> (wajib)
                            <span class="badge bg-light text-dark border me-1 ms-2">kelas</span> (wajib, harus sesuai master)
                            <span class="badge bg-light text-dark border ms-2">email</span> (opsional)
                        </div>
                        <div class="mt-2">
                            <i class="bi bi-exclamation-triangle text-warning"></i>
                            Password default semua voter: <code>password</code>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileName = document.getElementById('fileName');
        const submitBtn = document.getElementById('submitBtn');

        dropZone.addEventListener('click', (e) => {
            if (e.target.tagName !== 'BUTTON') {
                fileInput.click();
            }
        });

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.background = '#e8f5c8';
            dropZone.style.borderColor = '#7cb518';
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.style.background = '#fafcf5';
            dropZone.style.borderColor = '#c6f135';
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.background = '#fafcf5';
            dropZone.style.borderColor = '#c6f135';

            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                handleFile();
            }
        });

        fileInput.addEventListener('change', handleFile);

        function handleFile() {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];

                fileName.querySelector('span').textContent = file.name;
                fileName.classList.remove('d-none');
                submitBtn.disabled = false;
            }
        }
    </script>
@endpush
