<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Check-in - Pilketos</title>

    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#1a2e1a',
                            mid: '#2d5a3d',
                            lime: '#c6f135',
                            limeHover: '#a8d92d',
                            cream: '#e8f5c8',
                            deep: '#0f1f0f',
                        },
                    },
                    keyframes: {
                        pulseSoft: {
                            '0%, 100%': {
                                opacity: '1',
                                transform: 'scale(1)'
                            },
                            '50%': {
                                opacity: '0.6',
                                transform: 'scale(0.95)'
                            },
                        },
                        fadeIn: {
                            from: {
                                opacity: '0'
                            },
                            to: {
                                opacity: '1'
                            },
                        },
                    },
                    animation: {
                        'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
                        'fade-in': 'fadeIn 0.3s ease',
                    },
                },
            },
        };
    </script>

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- QRCode.js --}}
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* QR code sizing supaya konsisten */
        #qrcode img,
        #qrcode canvas {
            display: block;
            width: 280px !important;
            height: 280px !important;
        }
    </style>
</head>

<body class="bg-brand-dark text-white min-h-screen flex items-center justify-center font-sans m-0">

    @if (!$election || !$device)
        {{-- ==========================================
             WAITING SCREEN
             ========================================== --}}
        <div x-data="waitingScreen()" x-init="start()" class="bg-white text-brand-dark rounded-3xl px-8 py-14 md:px-10 md:py-16 text-center max-w-lg w-[90%] shadow-2xl">

            <i class="bi bi-hourglass-split text-brand-lime text-8xl block animate-pulse-soft"></i>

            <h2 class="mt-6 mb-2 text-2xl font-bold">Menunggu Pemilihan...</h2>
            <p class="text-slate-500 mb-6">
                Belum ada pemilihan yang sedang berlangsung.
            </p>

            <div class="bg-blue-50 text-blue-800 border border-blue-200 rounded-xl px-4 py-3 text-sm mb-5 inline-flex items-center gap-2 text-left">
                <i class="bi bi-info-circle"></i>
                <span>Layar ini akan otomatis menampilkan QR saat pemilihan dimulai.</span>
            </div>

            <div class="text-slate-500 text-sm">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh otomatis dalam <span class="font-bold" x-text="counter">5</span>s
            </div>
        </div>

        <script>
            function waitingScreen() {
                return {
                    counter: 5,
                    intervalId: null,
                    start() {
                        this.intervalId = setInterval(() => {
                            this.counter--;
                            if (this.counter <= 0) {
                                clearInterval(this.intervalId);
                                window.location.reload();
                            }
                        }, 1000);
                    },
                };
            }
        </script>
    @else
        {{-- ==========================================
             KIOSK SCREEN
             ========================================== --}}
        <div x-data="kiosk()" x-init="init()" class="bg-white text-brand-dark rounded-3xl p-6 md:p-10 text-center max-w-2xl w-[90%] shadow-2xl">

            {{-- Title --}}
            <h2 class="text-2xl md:text-3xl font-bold mb-1">{{ $election->title }}</h2>
            <p class="text-slate-500 mb-0">
                Check-in Pemilih — {{ $election->tahun_ajaran }}
            </p>

            {{-- QR Code --}}
            <div class="inline-block bg-white p-5 rounded-2xl border-4 border-brand-lime my-5">
                <div id="qrcode" class="w-[280px] h-[280px]"></div>
            </div>

            <p class="mb-4 text-slate-700">
                <i class="bi bi-phone"></i>
                Scan QR ini dari HP kamu untuk check-in
            </p>

            {{-- Progress bar --}}
            <div class="h-1 bg-slate-200 rounded-full overflow-hidden mb-2 max-w-md mx-auto">
                <div class="h-full bg-brand-lime transition-[width] duration-1000 ease-linear" :style="`width: ${(timer / 30) * 100}%`"></div>
            </div>
            <small class="text-slate-500 text-sm">
                Refresh dalam <span class="font-bold" x-text="timer">30</span>s
            </small>

            {{-- Counters --}}
            <div class="mt-8 pt-6 border-t border-slate-200">
                <div class="flex justify-around">
                    <div>
                        <div class="text-3xl md:text-4xl font-extrabold text-brand-dark" x-text="checkedCount">0</div>
                        <small class="text-slate-500">Sudah Check-in</small>
                    </div>
                    <div class="w-px bg-slate-200"></div>
                    <div>
                        <div class="text-3xl md:text-4xl font-extrabold text-brand-dark" x-text="totalVoters">0</div>
                        <small class="text-slate-500">Total Pemilih</small>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="mt-8 flex gap-2 justify-center flex-wrap">
                <button type="button" @click="refreshQR()"
                    class="inline-flex items-center gap-1.5 border border-slate-300 hover:bg-slate-100 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg transition">
                    <i class="bi bi-arrow-clockwise"></i> Refresh QR
                </button>

                <form action="{{ route('device.checkin.close') }}" method="POST" onsubmit="return confirm('Tutup device check-in?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 border border-rose-300 hover:bg-rose-50 text-rose-600 text-sm font-medium px-4 py-2 rounded-lg transition">
                        <i class="bi bi-x-circle"></i> Tutup Device
                    </button>
                </form>
            </div>

            {{-- ==========================================
                 SCAN FLASH OVERLAY
                 ========================================== --}}
            <div x-show="flash.show" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-brand-lime/95">

                <i class="bi bi-check-circle-fill text-brand-dark text-[8rem]"></i>
                <div class="text-3xl font-bold text-brand-dark mt-5" x-text="flash.name">-</div>
                <div class="text-xl text-brand-dark/70" x-text="flash.kelas">-</div>
                <div class="mt-3 text-brand-dark/60">
                    Silakan masuk ke bilik suara
                </div>
            </div>

        </div>

        <script>
            function kiosk() {
                return {
                    SCAN_URL: '{{ url('/scan/checkin') }}',
                    STATUS_URL: '{{ route('device.checkin.status') }}',

                    currentToken: '{{ $device->device_token }}',
                    timer: {{ max(0, now()->diffInSeconds($device->token_expired_at, false)) }},
                    checkedCount: 0,
                    totalVoters: 0,
                    flash: {
                        show: false,
                        name: '-',
                        kelas: '-'
                    },

                    qrCode: null,
                    pollId: null,
                    timerId: null,

                    init() {
                        // Render QR pertama kali
                        this.renderQR(this.currentToken);
                        this.startCountdown(this.timer);

                        // Polling status
                        this.pollStatus();
                        this.pollId = setInterval(() => this.pollStatus(), 2000);
                    },

                    renderQR(token) {
                        const container = document.getElementById('qrcode');
                        container.innerHTML = '';
                        this.qrCode = new QRCode(container, {
                            text: `${this.SCAN_URL}/${token}`,
                            width: 280,
                            height: 280,
                            colorDark: '#1a2e1a',
                            colorLight: '#ffffff',
                            correctLevel: QRCode.CorrectLevel.M,
                        });
                    },

                    startCountdown(seconds) {
                        clearInterval(this.timerId);
                        this.timer = seconds;

                        this.timerId = setInterval(() => {
                            this.timer--;
                            if (this.timer <= 0) {
                                clearInterval(this.timerId);
                                this.refreshQR();
                            }
                        }, 1000);
                    },

                    pollStatus() {
                        fetch(this.STATUS_URL, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.action === 'reload') {
                                    clearInterval(this.pollId);
                                    clearInterval(this.timerId);
                                    if (data.message) alert(data.message);
                                    window.location.reload();
                                    return;
                                }

                                if (data.total_checked !== undefined) {
                                    this.checkedCount = data.total_checked;
                                }
                                if (data.total_voters !== undefined) {
                                    this.totalVoters = data.total_voters;
                                }

                                if (data.token && data.token !== this.currentToken) {
                                    this.currentToken = data.token;
                                    this.renderQR(this.currentToken);
                                    this.startCountdown(30);
                                }

                                if (data.status === 'scanned' && data.voter) {
                                    this.showScanFlash(data.voter);
                                }
                            })
                            .catch(err => console.error('Poll error:', err));
                    },

                    showScanFlash(voter) {
                        this.flash.name = voter.nama;
                        this.flash.kelas = voter.kelas;
                        this.flash.show = true;

                        setTimeout(() => {
                            this.flash.show = false;
                        }, 3000);
                    },

                    refreshQR() {
                        fetch(this.STATUS_URL + '?refresh=1', {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.token) {
                                    this.currentToken = data.token;
                                    this.renderQR(this.currentToken);
                                    this.startCountdown(30);
                                }
                            })
                            .catch(err => console.error('Refresh error:', err));
                    },
                };
            }
        </script>
    @endif

</body>

</html>
