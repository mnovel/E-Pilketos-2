@extends('layouts.admin')

@section('title', 'Scan QR')
@section('page-title', 'Scan QR')
@section('page-subtitle', 'Arahkan kamera ke QR di device')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('voter.dashboard') }}" class="text-decoration-none text-muted-green">
            Dashboard
        </a>
    </li>
    <li class="breadcrumb-item active text-main" aria-current="page">Scan QR</li>
@endsection

@section('content')

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">

            {{-- INFO CARD --}}
            <div class="alert-custom alert-custom-info mb-3">
                <i class="bi bi-info-circle-fill alert-custom-icon"></i>
                <div class="alert-custom-content">
                    Arahkan kamera ke <strong>QR yang tampil di device</strong>
                    (pintu masuk / bilik suara).
                </div>
            </div>

            {{-- SCANNER CARD --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <strong>
                        <i class="bi bi-camera-video text-success me-1"></i>
                        Kamera
                    </strong>
                    <button type="button" class="btn btn-sm btn-outline-secondary float-end" id="btnSwitchCamera" style="display: none;" title="Ganti kamera">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                </div>
                <div class="card-body p-2">

                    {{-- Container kamera --}}
                    <div id="reader" style="width: 100%; border-radius: 12px; overflow: hidden;"></div>

                    {{-- Loading --}}
                    <div id="loading" class="text-center py-5">
                        <div class="spinner-border text-success" role="status"></div>
                        <p class="mt-3 mb-0 text-muted">Menyiapkan kamera...</p>
                        <small class="text-muted">
                            Izinkan akses kamera jika diminta
                        </small>
                    </div>

                    {{-- Error --}}
                    <div id="errorBox" class="alert alert-danger d-none mt-3">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span id="errorMessage"></span>
                    </div>

                </div>
                <div class="card-footer bg-white text-center">
                    <small class="text-muted">
                        <i class="bi bi-shield-lock"></i>
                        Kamera hanya aktif saat halaman ini terbuka
                    </small>
                </div>
            </div>

            {{-- MANUAL INPUT (fallback) --}}
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <details>
                        <summary class="text-muted small">
                            <i class="bi bi-keyboard"></i>
                            Kamera tidak bekerja? Input manual
                        </summary>
                        <div class="mt-3">
                            <label for="manualToken" class="form-label small">
                                Kode Token:
                            </label>
                            <div class="input-group">
                                <input type="text" id="manualToken" class="form-control" placeholder="Contoh: PLK-XXXXXX" autocomplete="off">
                                <button type="button" class="btn-custom btn-custom-primary" onclick="submitManual()">
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1">
                                Kode token tampil di device saat QR di-scan.
                            </small>
                        </div>
                    </details>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        // ==========================================
        // QR SCANNER
        // ==========================================
        let html5QrCode = null;
        let cameras = [];
        let currentCameraIndex = 0;
        let isProcessing = false;

        const loadingEl = document.getElementById('loading');
        const errorBox = document.getElementById('errorBox');
        const errorMsg = document.getElementById('errorMessage');
        const switchBtn = document.getElementById('btnSwitchCamera');

        // ==========================================
        // START SCANNER
        // ==========================================
        function startScanner(cameraId = null) {
            if (html5QrCode) {
                html5QrCode.clear();
            }

            html5QrCode = new Html5Qrcode('reader');

            const config = {
                fps: 10,
                qrbox: {
                    width: 240,
                    height: 240
                },
                aspectRatio: 1.0,
            };

            const cameraConfig = cameraId ?
                {
                    deviceId: {
                        exact: cameraId
                    }
                } :
                {
                    facingMode: 'environment'
                };

            html5QrCode.start(
                    cameraConfig,
                    config,
                    onScanSuccess,
                    onScanError
                )
                .then(() => {
                    loadingEl.style.display = 'none';

                    // Tampil tombol switch kalau ada > 1 kamera
                    if (cameras.length > 1) {
                        switchBtn.style.display = 'inline-block';
                    }
                })
                .catch(err => {
                    loadingEl.style.display = 'none';
                    showError('Tidak bisa mengakses kamera: ' + err);
                });
        }

        // ==========================================
        // HANDLE SCAN
        // ==========================================
        function onScanSuccess(decodedText) {
            if (isProcessing) return;
            isProcessing = true;

            // Feedback
            if (navigator.vibrate) navigator.vibrate(100);

            // Extract path dari URL yang di-scan
            // QR berisi: http://host/scan/checkin/ABC123 atau /scan/voting/XYZ789
            try {
                const url = new URL(decodedText);
                const path = url.pathname;
                const search = url.search;

                // Validasi: harus mengandung /scan/
                if (!path.includes('/scan/')) {
                    showError('QR tidak dikenali. Pastikan QR dari device Pilketos.');
                    isProcessing = false;
                    return;
                }

                // Stop scanner
                html5QrCode.stop().then(() => {
                    // Redirect ke path yang di-scan
                    window.location.href = path + search;
                }).catch(() => {
                    window.location.href = path + search;
                });

            } catch (e) {
                // Kalau bukan URL valid, coba cek apakah token langsung
                if (decodedText.startsWith('PLK-') || decodedText.length === 12) {
                    window.location.href = '/scan/checkin/' + decodedText;
                } else {
                    showError('QR tidak valid: ' + decodedText);
                    isProcessing = false;
                }
            }
        }

        function onScanError(error) {
            // Abaikan error per-frame (normal saat belum ada QR)
        }

        function showError(message) {
            errorBox.classList.remove('d-none');
            errorMsg.textContent = message;

            setTimeout(() => {
                errorBox.classList.add('d-none');
                isProcessing = false;
            }, 3000);
        }

        // ==========================================
        // SWITCH CAMERA
        // ==========================================
        switchBtn?.addEventListener('click', function() {
            currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
            const nextCamera = cameras[currentCameraIndex];
            startScanner(nextCamera.id);
        });

        // ==========================================
        // MANUAL INPUT
        // ==========================================
        function submitManual() {
            const token = document.getElementById('manualToken').value.trim();

            if (!token) return;

            // Coba deteksi tipe — default ke checkin
            window.location.href = '/scan/checkin/' + token;
        }

        document.getElementById('manualToken')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitManual();
            }
        });

        // ==========================================
        // INIT
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            // Cek izin kamera dulu
            Html5Qrcode.getCameras()
                .then(devices => {
                    if (devices && devices.length > 0) {
                        cameras = devices;
                        // Prefer kamera belakang
                        const backCamera = devices.find(d =>
                            d.label.toLowerCase().includes('back') ||
                            d.label.toLowerCase().includes('rear') ||
                            d.label.toLowerCase().includes('environment')
                        );

                        startScanner(backCamera ? backCamera.id : devices[0].id);
                    } else {
                        showError('Tidak ada kamera terdeteksi di device ini.');
                        loadingEl.style.display = 'none';
                    }
                })
                .catch(err => {
                    loadingEl.style.display = 'none';
                    showError('Akses kamera ditolak. Aktifkan izin kamera di browser.');
                });
        });
    </script>
@endpush
