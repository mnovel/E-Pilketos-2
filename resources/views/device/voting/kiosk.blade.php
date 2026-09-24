<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Voting - {{ config('app.name', 'Pilketos') }}</title>

    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">

    <style>
        :root {
            --bg: #1a2e1a;
            --primary: #c6f135;
            --primary-soft: #f7fce9;
            --text-dark: #1a2e1a;
            --white: #ffffff;
            --card-radius: 24px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--bg);
            color: white;
            min-height: 100vh;
            min-height: 100dvh;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow-x: hidden;
        }

        /* =========================================================
           WAITING
        ========================================================= */

        .waiting-wrapper {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .waiting-card {
            background: var(--white);
            color: var(--text-dark);
            border-radius: var(--card-radius);
            padding: clamp(30px, 6vw, 60px) clamp(20px, 5vw, 40px);
            text-align: center;
            width: min(500px, 100%);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .waiting-icon {
            font-size: clamp(3.5rem, 12vw, 5rem);
            color: var(--primary);
            animation: pulse 2s ease-in-out infinite;
        }

        .waiting-card h2 {
            font-size: clamp(1.35rem, 5vw, 2rem);
        }

        .waiting-card p {
            font-size: clamp(0.9rem, 3.5vw, 1rem);
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

        /* =========================================================
           IDLE / QR
        ========================================================= */

        .idle-wrapper {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(15px, 4vw, 30px);
        }

        .kiosk-card {
            background: var(--white);
            color: var(--text-dark);
            border-radius: var(--card-radius);
            padding: clamp(20px, 5vw, 40px);
            text-align: center;
            width: min(600px, 100%);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .kiosk-card h2 {
            font-size: clamp(1.25rem, 5vw, 2rem);
            line-height: 1.25;
            word-break: break-word;
        }

        .kiosk-card .qr-box {
            background: #fff;
            padding: clamp(8px, 2vw, 20px);
            border-radius: 16px;
            display: inline-block;
            border: 4px solid var(--primary);
            margin: clamp(12px, 3vw, 20px) 0;
            max-width: 100%;
        }

        #qrcode {
            max-width: 100%;
        }

        #qrcode img,
        #qrcode canvas {
            display: block;
            width: min(280px, 65vw) !important;
            height: auto !important;
            aspect-ratio: 1 / 1;
            max-width: 100%;
        }

        .progress-thin {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-thin .progress-bar {
            background: var(--primary);
            transition: width 1s linear;
        }

        .kiosk-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .kiosk-actions form {
            margin: 0;
        }

        /* =========================================================
           BALLOT
        ========================================================= */

        .ballot-wrapper {
            min-height: 100vh;
            min-height: 100dvh;
            padding:
                clamp(20px, 4vw, 40px) clamp(12px, 4vw, 30px) clamp(30px, 5vw, 50px);
            display: none;
        }

        .ballot-header {
            text-align: center;
            margin: 0 auto clamp(20px, 4vw, 30px);
            max-width: 900px;
        }

        .ballot-header .voter-name {
            font-size: clamp(1.35rem, 4vw, 1.8rem);
            font-weight: 700;
            color: var(--primary);
            line-height: 1.25;
            word-break: break-word;
        }

        .ballot-header .voter-info {
            opacity: 0.7;
            font-size: clamp(0.8rem, 2.5vw, 0.95rem);
            margin-top: 4px;
        }

        .ballot-header p {
            font-size: clamp(0.9rem, 3vw, 1rem);
        }

        .candidate-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: clamp(12px, 2vw, 20px);
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .candidate-card {
            background: var(--white);
            color: var(--text-dark);
            border-radius: 20px;
            padding: clamp(16px, 2.5vw, 24px);
            text-align: center;
            cursor: pointer;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease,
                background 0.2s ease;
            border: 4px solid transparent;
            position: relative;
            min-width: 0;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .candidate-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: 0 12px 32px rgba(198, 241, 53, 0.3);
        }

        .candidate-card.selected {
            border-color: var(--primary);
            background: var(--primary-soft);
            box-shadow: 0 8px 24px rgba(198, 241, 53, 0.2);
        }

        .candidate-photo,
        .candidate-photo-placeholder {
            width: clamp(90px, 10vw, 120px);
            height: clamp(90px, 10vw, 120px);
            border-radius: 50%;
            margin: 0 auto 14px;
        }

        .candidate-photo {
            object-fit: cover;
            border: 4px solid var(--primary);
            display: block;
        }

        .candidate-photo-placeholder {
            background: var(--primary);
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(2.2rem, 5vw, 3rem);
            font-weight: 700;
        }

        .candidate-no {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--text-dark);
            color: var(--primary);
            width: clamp(34px, 4vw, 42px);
            height: clamp(34px, 4vw, 42px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: clamp(0.9rem, 2vw, 1.1rem);
            z-index: 2;
        }

        .candidate-name {
            font-size: clamp(1rem, 2vw, 1.3rem);
            font-weight: 700;
            margin-bottom: 4px;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .candidate-class {
            opacity: 0.6;
            margin-bottom: 12px;
            font-size: clamp(0.78rem, 1.5vw, 0.9rem);
        }

        .candidate-visi {
            font-size: clamp(0.78rem, 1.5vw, 0.85rem);
            opacity: 0.8;
            max-height: 80px;
            overflow: hidden;
            text-align: left;
            line-height: 1.5;
        }

        .ballot-submit {
            width: min(100%, 420px);
            min-height: 54px;
            padding: 12px 30px;
            background: var(--primary);
            color: var(--text-dark);
            font-weight: 700;
            border: 0;
            border-radius: 10px;
            font-size: clamp(0.95rem, 3vw, 1.1rem);
        }

        .ballot-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* =========================================================
           THANKS
        ========================================================= */

        .thanks-wrapper {
            min-height: 100vh;
            min-height: 100dvh;
            padding: 20px;
            display: none;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .thanks-wrapper .icon {
            font-size: clamp(5rem, 20vw, 8rem);
            color: var(--primary);
        }

        .thanks-wrapper .text {
            font-size: clamp(1.5rem, 6vw, 2rem);
            font-weight: 700;
            color: var(--primary);
            margin-top: 20px;
        }

        .thanks-wrapper .sub {
            opacity: 0.7;
            margin-top: 10px;
            font-size: clamp(0.85rem, 3vw, 1rem);
        }

        /* =========================================================
           TABLET
        ========================================================= */

        @media (max-width: 1100px) {
            .candidate-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        /* =========================================================
           SMALL TABLET / LARGE PHONE
        ========================================================= */

        @media (max-width: 768px) {
            .candidate-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .candidate-card {
                border-radius: 16px;
                border-width: 3px;
                padding: 16px 12px;
            }

            .candidate-no {
                top: 8px;
                left: 8px;
            }

            .candidate-photo,
            .candidate-photo-placeholder {
                width: 90px;
                height: 90px;
            }

            .ballot-submit {
                width: 100%;
            }
        }

        /* =========================================================
           PHONE
        ========================================================= */

        @media (max-width: 576px) {
            .waiting-wrapper {
                padding: 12px;
            }

            .waiting-card {
                border-radius: 18px;
            }

            .idle-wrapper {
                padding: 12px;
            }

            .kiosk-card {
                border-radius: 18px;
                padding: 18px 14px;
            }

            .kiosk-card .qr-box {
                border-width: 3px;
                border-radius: 12px;
            }

            #qrcode img,
            #qrcode canvas {
                width: min(260px, 68vw) !important;
            }

            .kiosk-actions {
                flex-direction: column;
                width: 100%;
            }

            .kiosk-actions button,
            .kiosk-actions form,
            .kiosk-actions form button {
                width: 100%;
            }

            .ballot-wrapper {
                padding:
                    18px 10px 30px;
            }

            .ballot-header {
                margin-bottom: 18px;
            }

            .candidate-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .candidate-card {
                padding: 18px 14px;
            }

            .candidate-photo,
            .candidate-photo-placeholder {
                width: 100px;
                height: 100px;
            }

            .candidate-visi {
                max-height: 100px;
            }

            .candidate-no {
                width: 36px;
                height: 36px;
                font-size: 0.9rem;
            }

            .ballot-submit {
                min-height: 52px;
            }

            .thanks-wrapper {
                padding: 15px;
            }
        }

        /* =========================================================
           VERY SMALL PHONE
        ========================================================= */

        @media (max-width: 380px) {
            .kiosk-card {
                padding: 16px 10px;
            }

            #qrcode img,
            #qrcode canvas {
                width: min(220px, 65vw) !important;
            }

            .candidate-card {
                padding: 16px 10px;
            }

            .candidate-photo,
            .candidate-photo-placeholder {
                width: 85px;
                height: 85px;
            }
        }

        /* =========================================================
           LANDSCAPE PHONE
        ========================================================= */

        @media (max-height: 600px) and (orientation: landscape) {
            .idle-wrapper {
                align-items: flex-start;
                padding-top: 15px;
                padding-bottom: 15px;
            }

            .kiosk-card {
                padding: 16px 20px;
            }

            .kiosk-card .qr-box {
                margin: 8px 0;
            }

            #qrcode img,
            #qrcode canvas {
                width: min(180px, 35vh) !important;
            }

            .waiting-wrapper,
            .thanks-wrapper {
                align-items: flex-start;
                padding-top: 20px;
            }

            .ballot-wrapper {
                padding-top: 15px;
            }
        }

        /* =========================================================
           TOUCH DEVICES
        ========================================================= */

        @media (hover: none) and (pointer: coarse) {
            .candidate-card:hover {
                transform: none;
                box-shadow: none;
            }

            .candidate-card:active {
                transform: scale(0.99);
            }

            .candidate-card.selected {
                box-shadow: 0 8px 24px rgba(198, 241, 53, 0.25);
            }

            button,
            .candidate-card {
                touch-action: manipulation;
            }
        }

        /* =========================================================
           REDUCED MOTION
        ========================================================= */

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>

    @if (!$election || !$device)
        {{-- WAITING --}}
        <div class="waiting-wrapper">
            <div class="waiting-card">

                <i class="bi bi-hourglass-split waiting-icon"></i>

                <h2 class="mt-4 mb-2">
                    Menunggu Pemilihan...
                </h2>

                <p class="text-muted mb-4">
                    Belum ada pemilihan yang sedang berlangsung.
                </p>

                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle"></i>
                    Layar ini akan otomatis menampilkan QR saat pemilihan dimulai.
                </div>

                <div class="text-muted small">
                    <i class="bi bi-arrow-clockwise"></i>
                    Refresh otomatis dalam
                    <span id="waitCounter">5</span>s
                </div>

            </div>
        </div>

        <script>
            let counter = 5;
            const el = document.getElementById('waitCounter');

            setInterval(() => {
                counter--;

                if (el) {
                    el.textContent = counter;
                }

                if (counter <= 0) {
                    window.location.reload();
                }
            }, 1000);
        </script>
    @else
        {{-- IDLE --}}
        <div class="idle-wrapper" id="idleScreen">

            <div class="kiosk-card">

                <h2 class="mb-1">
                    {{ $election->title }}
                </h2>

                <p class="text-muted mb-0">
                    Bilik Suara — {{ $election->tahun_ajaran }}
                </p>

                <div class="qr-box" id="qrcode"></div>

                <p class="mb-3">
                    <i class="bi bi-phone"></i>
                    Scan QR ini dari HP untuk memilih
                </p>

                <div class="progress-thin mb-2">
                    <div class="progress-bar" id="timer-bar" style="width: 100%">
                    </div>
                </div>

                <small class="text-muted">
                    Refresh dalam
                    <span id="timer-text">30</span>s
                </small>

                <div class="mt-4 pt-3 border-top">

                    <div class="row g-3">

                        <div class="col-6">
                            <div class="fs-3 fw-bold" id="count-voted">
                                0
                            </div>
                            <small class="text-muted">
                                Sudah Memilih
                            </small>
                        </div>

                        <div class="col-6">
                            <div class="fs-3 fw-bold" id="count-total">
                                0
                            </div>
                            <small class="text-muted">
                                Total Pemilih
                            </small>
                        </div>

                    </div>

                </div>

                <div class="kiosk-actions mt-4 pt-3 border-top">

                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshQR()">

                        <i class="bi bi-arrow-clockwise"></i>
                        Refresh QR

                    </button>

                    <form action="{{ route('device.voting.close') }}" method="POST" onsubmit="return confirm('Tutup device voting?')">

                        @csrf

                        <button type="submit" class="btn btn-sm btn-outline-danger">

                            <i class="bi bi-x-circle"></i>
                            Tutup Device

                        </button>

                    </form>

                </div>

            </div>

        </div>


        {{-- BALLOT --}}
        <div class="ballot-wrapper" id="ballotScreen">

            <div class="ballot-header">

                <div class="voter-name" id="ballotVoterName">
                    -
                </div>

                <div class="voter-info">

                    <span id="ballotVoterNis">
                        -
                    </span>

                    ·

                    <span id="ballotVoterKelas">
                        -
                    </span>

                </div>

                <p class="mt-3 mb-0" style="opacity: 0.8;">
                    Silakan pilih kandidat:
                </p>

            </div>

            <div class="candidate-grid" id="candidateGrid">
            </div>

            <div class="text-center mt-4">

                <button type="button" class="ballot-submit" id="submitBtn" onclick="confirmVote()" disabled>

                    <i class="bi bi-check-circle"></i>
                    Konfirmasi Pilihan

                </button>

            </div>

        </div>


        {{-- THANKS --}}
        <div class="thanks-wrapper" id="thanksScreen">

            <div>

                <i class="bi bi-check-circle-fill icon"></i>

                <div class="text">
                    Terima Kasih!
                </div>

                <div class="sub">
                    Suara Anda sudah tercatat
                </div>

                <div class="sub mt-4" style="font-size: 0.9rem;">

                    Kembali ke layar awal dalam
                    <span id="thanksTimer">5</span>s

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

                if (el) {
                    el.textContent = seconds;
                }

                if (bar) {

                    bar.style.width =
                        Math.max(
                            0,
                            Math.min(
                                100,
                                (seconds / 30) * 100
                            )
                        ) + '%';

                }
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

                            if (data.message) {
                                alert(data.message);
                            }

                            window.location.reload();

                            return;
                        }


                        if (data.total_voted !== undefined) {

                            document.getElementById('count-voted').textContent =
                                data.total_voted;

                        }


                        if (data.total_voters !== undefined) {

                            document.getElementById('count-total').textContent =
                                data.total_voters;

                        }


                        if (
                            data.status === 'assigned' &&
                            currentDeviceState !== 'assigned'
                        ) {

                            currentDeviceState = 'assigned';

                            showBallot(
                                data.voter,
                                data.candidates
                            );

                        } else if (
                            data.status === 'idle' &&
                            currentDeviceState !== 'idle'
                        ) {

                            currentDeviceState = 'idle';

                            showIdle();

                        } else if (
                            data.status === 'idle' &&
                            data.token &&
                            data.token !== currentToken
                        ) {

                            currentToken = data.token;

                            renderQR(currentToken);

                            startCountdown(30);

                        }

                    })

                    .catch(err => {
                        console.error('Poll error:', err);
                    });
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


                document.getElementById('ballotVoterName').textContent =
                    voter.nama;

                document.getElementById('ballotVoterNis').textContent =
                    'NIS: ' + voter.nis;

                document.getElementById('ballotVoterKelas').textContent =
                    voter.kelas;


                const grid =
                    document.getElementById('candidateGrid');

                grid.innerHTML = '';


                candidates.forEach(c => {

                    const photoHtml = c.foto

                        ?
                        `
                            <img
                                src="${c.foto}"
                                alt="${c.nama}"
                                class="candidate-photo">
                        `

                        :
                        `
                            <div class="candidate-photo-placeholder">
                                ${c.nama.charAt(0).toUpperCase()}
                            </div>
                        `;


                    const card =
                        document.createElement('div');

                    card.className =
                        'candidate-card';

                    card.dataset.candidateId =
                        c.id;

                    card.onclick = () =>
                        selectCandidate(c.id);


                    card.innerHTML = `

                        <div class="candidate-no">
                            ${c.no_urut}
                        </div>

                        ${photoHtml}

                        <div class="candidate-name">
                            ${c.nama}
                        </div>

                        <div class="candidate-class">
                            <i class="bi bi-mortarboard"></i>
                            ${c.kelas}
                        </div>

                        <div class="candidate-visi">
                            ${c.visi || ''}
                        </div>

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

                document.getElementById('thanksTimer').textContent =
                    remaining;


                clearInterval(thanksTimer);


                thanksTimer = setInterval(() => {

                    remaining--;

                    document.getElementById('thanksTimer').textContent =
                        remaining;


                    if (remaining <= 0) {

                        clearInterval(thanksTimer);

                        currentDeviceState = 'idle';

                        showIdle();

                    }

                }, 1000);
            }


            function selectCandidate(candidateId) {

                selectedCandidateId = candidateId;


                document
                    .querySelectorAll('.candidate-card')
                    .forEach(card => {

                        card.classList.toggle(
                            'selected',
                            parseInt(card.dataset.candidateId) === candidateId
                        );

                    });


                document.getElementById('submitBtn').disabled = false;
            }


            async function confirmVote() {

                if (!selectedCandidateId) {
                    return;
                }


                const ok =
                    confirm(
                        'Yakin dengan pilihan Anda? Pilihan tidak dapat diubah.'
                    );


                if (!ok) {
                    return;
                }


                try {

                    const res =
                        await fetch(SUBMIT_URL, {

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


                    const data =
                        await res.json();


                    if (data.status === 'ok') {

                        showThanks();

                    } else {

                        alert(
                            'Error: ' +
                            (
                                data.message ||
                                'Gagal menyimpan vote'
                            )
                        );

                    }

                } catch (err) {

                    alert(
                        'Error koneksi: ' +
                        err.message
                    );

                }
            }


            function refreshQR() {

                fetch(
                        STATUS_URL + '?refresh=1', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }
                    )

                    .then(r => r.json())

                    .then(data => {

                        if (data.token) {

                            currentToken =
                                data.token;

                            renderQR(
                                currentToken
                            );

                            startCountdown(30);

                        }

                    });

            }


            showIdle();

            pollTimer =
                setInterval(
                    pollStatus,
                    2000
                );

            pollStatus();
        </script>
    @endif

</body>

</html>
