<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Voting - Pilketos</title>
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <style>
        body {
            background: #1a2e1a;
            color: white;
            min-height: 100vh;
            margin: 0;
            font-family: system-ui, sans-serif;
            overflow-x: hidden;
        }

        /* WAITING */
        .waiting-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .waiting-card {
            background: white;
            color: #1a2e1a;
            border-radius: 24px;
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .waiting-icon {
            font-size: 5rem;
            color: #c6f135;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.6;
                transform: scale(0.95);
            }
        }

        /* IDLE */
        .idle-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .kiosk-card {
            background: white;
            color: #1a2e1a;
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .qr-box {
            background: #fff;
            padding: 20px;
            border-radius: 16px;
            display: inline-block;
            border: 4px solid #c6f135;
            margin: 20px 0;
        }

        #qrcode img,
        #qrcode canvas {
            display: block;
            width: 280px !important;
            height: 280px !important;
        }

        .progress-thin {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-thin .progress-bar {
            background: #c6f135;
            transition: width 1s linear;
        }

        /* BALLOT */
        .ballot-wrapper {
            min-height: 100vh;
            padding: 30px 20px;
            display: none;
        }

        .ballot-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .ballot-header .voter-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #c6f135;
        }

        .ballot-header .voter-info {
            opacity: 0.7;
            font-size: 0.95rem;
        }

        .candidate-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .candidate-card {
            background: white;
            color: #1a2e1a;
            border-radius: 20px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 4px solid transparent;
            position: relative;
        }

        .candidate-card:hover {
            transform: translateY(-4px);
            border-color: #c6f135;
            box-shadow: 0 12px 32px rgba(198, 241, 53, 0.3);
        }

        .candidate-card.selected {
            border-color: #c6f135;
            background: #f7fce9;
        }

        .candidate-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 16px;
            border: 4px solid #c6f135;
            display: block;
        }

        .candidate-photo-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #c6f135;
            color: #1a2e1a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            margin: 0 auto 16px;
        }

        .candidate-no {
            position: absolute;
            top: 12px;
            left: 12px;
            background: #1a2e1a;
            color: #c6f135;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .candidate-name {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .candidate-class {
            opacity: 0.6;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }

        .candidate-visi {
            font-size: 0.85rem;
            opacity: 0.8;
            max-height: 80px;
            overflow: hidden;
            text-align: left;
        }

        /* THANKS */
        .thanks-wrapper {
            min-height: 100vh;
            display: none;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .thanks-wrapper .icon {
            font-size: 8rem;
            color: #c6f135;
        }

        .thanks-wrapper .text {
            font-size: 2rem;
            font-weight: 700;
            color: #c6f135;
            margin-top: 20px;
        }

        .thanks-wrapper .sub {
            opacity: 0.7;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    @if (!$election || !$device)
        {{-- WAITING --}}
        <div class="waiting-wrapper">
            <div class="waiting-card">
                <i class="bi bi-hourglass-split waiting-icon"></i>
                <h2 class="mt-4 mb-2">Menunggu Pemilihan...</h2>
                <p class="text-muted mb-4">Belum ada pemilihan yang sedang berlangsung.</p>
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle"></i>
                    Layar ini akan otomatis menampilkan QR saat pemilihan dimulai.
                </div>
                <div class="text-muted small">
                    <i class="bi bi-arrow-clockwise"></i>
                    Refresh otomatis dalam <span id="waitCounter">5</span>s
                </div>
            </div>
        </div>
        <script>
            let counter = 5;
            const el = document.getElementById('waitCounter');
            setInterval(() => {
                counter--;
                if (el) el.textContent = counter;
                if (counter <= 0) window.location.reload();
            }, 1000);
        </script>
    @else
        {{-- IDLE (QR) --}}
        <div class="idle-wrapper" id="idleScreen">
            <div class="kiosk-card">
                <h2 class="mb-1">{{ $election->title }}</h2>
                <p class="text-muted mb-0">Bilik Suara — {{ $election->tahun_ajaran }}</p>

                <div class="qr-box" id="qrcode"></div>

                <p class="mb-3">
                    <i class="bi bi-phone"></i>
                    Scan QR ini dari HP untuk memilih
                </p>

                <div class="progress-thin mb-2">
                    <div class="progress-bar" id="timer-bar" style="width: 100%"></div>
                </div>
                <small class="text-muted">
                    Refresh dalam <span id="timer-text">30</span>s
                </small>

                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-around">
                        <div>
                            <div class="fs-3 fw-bold" id="count-voted">0</div>
                            <small class="text-muted">Sudah Memilih</small>
                        </div>
                        <div>
                            <div class="fs-3 fw-bold" id="count-total">0</div>
                            <small class="text-muted">Total Pemilih</small>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshQR()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh QR
                    </button>
                    <form action="{{ route('device.voting.close') }}" method="POST" class="d-inline" onsubmit="return confirm('Tutup device voting?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-x-circle"></i> Tutup Device
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- BALLOT --}}
        <div class="ballot-wrapper" id="ballotScreen">
            <div class="ballot-header">
                <div class="voter-name" id="ballotVoterName">-</div>
                <div class="voter-info">
                    <span id="ballotVoterNis">-</span> · <span id="ballotVoterKelas">-</span>
                </div>
                <p class="mt-3 mb-0" style="opacity: 0.8;">Silakan pilih kandidat:</p>
            </div>

            <div class="candidate-grid" id="candidateGrid"></div>

            <div class="text-center mt-4">
                <button type="button" class="btn btn-lg" style="background: #c6f135; color: #1a2e1a; font-weight: 700; padding: 14px 60px;" id="submitBtn" onclick="confirmVote()" disabled>
                    <i class="bi bi-check-circle"></i> Konfirmasi Pilihan
                </button>
            </div>
        </div>

        {{-- THANKS --}}
        <div class="thanks-wrapper" id="thanksScreen">
            <div>
                <i class="bi bi-check-circle-fill icon"></i>
                <div class="text">Terima Kasih!</div>
                <div class="sub">Suara Anda sudah tercatat</div>
                <div class="sub mt-4" style="font-size: 0.9rem;">
                    Kembali ke layar awal dalam <span id="thanksTimer">5</span>s
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
        <script>
            const SCAN_URL = '{{ url('/scan/voting') }}';
            const DEVICE_ID = {{ $device->id }};
            const CSRF_TOKEN = '{{ csrf_token() }}';
            const SUBMIT_URL = '{{ route('device.voting.submit') }}';
            const STATUS_URL = '{{ route('device.voting.status') }}';

            let currentToken = '{{ $device->device_token }}';
            let currentExpires = {{ max(0, now()->diffInSeconds($device->token_expired_at, false)) }};
            let qrCode = null;
            let pollTimer = null;
            let countdownTimer = null;
            let thanksTimer = null;
            let assignedVoter = null;
            let selectedCandidateId = null;
            let currentDeviceState = 'idle';

            function renderQR(token) {
                const container = document.getElementById('qrcode');
                container.innerHTML = '';

                qrCode = new QRCode(container, {
                    text: `${SCAN_URL}/${token}`,
                    width: 280,
                    height: 280,
                    colorDark: '#1a2e1a',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M,
                });
            }

            function startCountdown(seconds) {
                clearInterval(countdownTimer);
                let remaining = seconds;
                updateCountdown(remaining);

                countdownTimer = setInterval(() => {
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(countdownTimer);
                        refreshQR();
                    } else {
                        updateCountdown(remaining);
                    }
                }, 1000);
            }

            function updateCountdown(seconds) {
                const el = document.getElementById('timer-text');
                const bar = document.getElementById('timer-bar');
                if (el) el.textContent = seconds;
                if (bar) bar.style.width = Math.max(0, Math.min(100, (seconds / 30) * 100)) + '%';
            }

            function pollStatus() {
                fetch(STATUS_URL, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.action === 'reload') {
                            clearInterval(pollTimer);
                            clearInterval(countdownTimer);
                            if (data.message) alert(data.message);
                            window.location.reload();
                            return;
                        }

                        if (data.total_voted !== undefined) {
                            document.getElementById('count-voted').textContent = data.total_voted;
                        }
                        if (data.total_voters !== undefined) {
                            document.getElementById('count-total').textContent = data.total_voters;
                        }

                        if (data.status === 'assigned' && currentDeviceState !== 'assigned') {
                            currentDeviceState = 'assigned';
                            showBallot(data.voter, data.candidates);
                        } else if (data.status === 'idle' && currentDeviceState !== 'idle') {
                            currentDeviceState = 'idle';
                            showIdle();
                        } else if (data.status === 'idle' && data.token && data.token !== currentToken) {
                            currentToken = data.token;
                            renderQR(currentToken);
                            startCountdown(30);
                        }
                    })
                    .catch(err => console.error('Poll error:', err));
            }

            function showIdle() {
                document.getElementById('idleScreen').style.display = 'flex';
                document.getElementById('ballotScreen').style.display = 'none';
                document.getElementById('thanksScreen').style.display = 'none';

                selectedCandidateId = null;
                assignedVoter = null;
                currentDeviceState = 'idle';

                renderQR(currentToken);
                startCountdown(30);
            }

            function showBallot(voter, candidates) {
                assignedVoter = voter;
                selectedCandidateId = null;

                document.getElementById('idleScreen').style.display = 'none';
                document.getElementById('ballotScreen').style.display = 'block';
                document.getElementById('thanksScreen').style.display = 'none';

                document.getElementById('ballotVoterName').textContent = voter.nama;
                document.getElementById('ballotVoterNis').textContent = 'NIS: ' + voter.nis;
                document.getElementById('ballotVoterKelas').textContent = voter.kelas;

                const grid = document.getElementById('candidateGrid');
                grid.innerHTML = '';

                candidates.forEach(c => {
                    const photoHtml = c.foto ?
                        `<img src="${c.foto}" alt="${c.nama}" class="candidate-photo">` :
                        `<div class="candidate-photo-placeholder">${c.nama.charAt(0).toUpperCase()}</div>`;

                    const card = document.createElement('div');
                    card.className = 'candidate-card';
                    card.dataset.candidateId = c.id;
                    card.onclick = () => selectCandidate(c.id);

                    card.innerHTML = `
                    <div class="candidate-no">${c.no_urut}</div>
                    ${photoHtml}
                    <div class="candidate-name">${c.nama}</div>
                    <div class="candidate-class"><i class="bi bi-mortarboard"></i> ${c.kelas}</div>
                    <div class="candidate-visi">${c.visi || ''}</div>
                `;

                    grid.appendChild(card);
                });

                document.getElementById('submitBtn').disabled = true;
            }

            function showThanks() {
                document.getElementById('idleScreen').style.display = 'none';
                document.getElementById('ballotScreen').style.display = 'none';
                document.getElementById('thanksScreen').style.display = 'flex';

                let remaining = 5;
                document.getElementById('thanksTimer').textContent = remaining;

                clearInterval(thanksTimer);
                thanksTimer = setInterval(() => {
                    remaining--;
                    document.getElementById('thanksTimer').textContent = remaining;

                    if (remaining <= 0) {
                        clearInterval(thanksTimer);
                        currentDeviceState = 'idle';
                        showIdle();
                    }
                }, 1000);
            }

            function selectCandidate(candidateId) {
                selectedCandidateId = candidateId;

                document.querySelectorAll('.candidate-card').forEach(card => {
                    card.classList.toggle('selected', parseInt(card.dataset.candidateId) === candidateId);
                });

                document.getElementById('submitBtn').disabled = false;
            }

            async function confirmVote() {
                if (!selectedCandidateId) return;

                const ok = confirm('Yakin dengan pilihan Anda? Pilihan tidak dapat diubah.');
                if (!ok) return;

                try {
                    const res = await fetch(SUBMIT_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            device_id: DEVICE_ID,
                            candidate_id: selectedCandidateId,
                        }),
                    });

                    const data = await res.json();

                    if (data.status === 'ok') {
                        showThanks();
                    } else {
                        alert('Error: ' + (data.message || 'Gagal menyimpan vote'));
                    }
                } catch (err) {
                    alert('Error koneksi: ' + err.message);
                }
            }

            function refreshQR() {
                fetch(STATUS_URL + '?refresh=1', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.token) {
                            currentToken = data.token;
                            renderQR(currentToken);
                            startCountdown(30);
                        }
                    });
            }

            showIdle();
            pollTimer = setInterval(pollStatus, 2000);
            pollStatus();
        </script>
    @endif

</body>

</html>
