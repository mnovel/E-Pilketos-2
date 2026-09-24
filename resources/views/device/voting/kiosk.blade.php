<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Voting - Pilketos</title>

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
                    },
                    animation: {
                        'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
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

        #qrcode img,
        #qrcode canvas {
            display: block;
            width: 280px !important;
            height: 280px !important;
        }
    </style>
</head>

<body class="bg-brand-dark text-white min-h-screen m-0 font-sans overflow-x-hidden">

    @if (!$election || !$device)
        {{-- ==========================================
             WAITING SCREEN
             ========================================== --}}
        <div x-data="waitingScreen()" x-init="start()" class="min-h-screen flex items-center justify-center">
            <div class="bg-white text-brand-dark rounded-3xl px-8 py-14 md:px-10 md:py-16 text-center max-w-lg w-[90%] shadow-2xl">

                <i class="bi bi-hourglass-split text-brand-lime text-8xl block animate-pulse-soft"></i>

                <h2 class="mt-6 mb-2 text-2xl font-bold">Menunggu Pemilihan...</h2>
                <p class="text-slate-500 mb-6">Belum ada pemilihan yang sedang berlangsung.</p>

                <div class="bg-blue-50 text-blue-800 border border-blue-200 rounded-xl px-4 py-3 text-sm mb-5 inline-flex items-start gap-2 text-left">
                    <i class="bi bi-info-circle mt-0.5"></i>
                    <span>Layar ini akan otomatis menampilkan QR saat pemilihan dimulai.</span>
                </div>

                <div class="text-slate-500 text-sm">
                    <i class="bi bi-arrow-clockwise"></i>
                    Refresh otomatis dalam <span class="font-bold" x-text="counter">5</span>s
                </div>
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
             MAIN KIOSK (IDLE / BALLOT / THANKS)
             ========================================== --}}
        <div x-data="votingKiosk()" x-init="init()" x-cloak>

            {{-- ==========================================
                 SCREEN: IDLE (QR)
                 ========================================== --}}
            <div x-show="screen === 'idle'" class="min-h-screen flex items-center justify-center p-4">
                <div class="bg-white text-brand-dark rounded-3xl p-6 md:p-10 text-center max-w-2xl w-full shadow-2xl">

                    <h2 class="text-2xl md:text-3xl font-bold mb-1">{{ $election->title }}</h2>
                    <p class="text-slate-500 mb-0">Bilik Suara — {{ $election->tahun_ajaran }}</p>

                    <div class="inline-block bg-white p-5 rounded-2xl border-4 border-brand-lime my-5">
                        <div id="qrcode" class="w-[280px] h-[280px]"></div>
                    </div>

                    <p class="mb-4 text-slate-700">
                        <i class="bi bi-phone"></i> Scan QR ini dari HP untuk memilih
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
                                <div class="text-3xl md:text-4xl font-extrabold text-brand-dark" x-text="totalVoted">0</div>
                                <small class="text-slate-500">Sudah Memilih</small>
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

                        <form action="{{ route('device.voting.close') }}" method="POST" onsubmit="return confirm('Tutup device voting?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 border border-rose-300 hover:bg-rose-50 text-rose-600 text-sm font-medium px-4 py-2 rounded-lg transition">
                                <i class="bi bi-x-circle"></i> Tutup Device
                            </button>
                        </form>
                    </div>

                </div>
            </div>

            {{-- ==========================================
                 SCREEN: BALLOT
                 ========================================== --}}
            <div x-show="screen === 'ballot'" class="min-h-screen p-6 md:p-8">

                {{-- Header --}}
                <div class="text-center mb-8">
                    <div class="text-2xl md:text-3xl font-bold text-brand-lime" x-text="voter.nama">-</div>
                    <div class="text-white/70 text-sm md:text-base mt-1">
                        <span x-text="'NIS: ' + voter.nis">-</span>
                        ·
                        <span x-text="voter.kelas">-</span>
                    </div>
                    <p class="mt-3 text-white/80">Silakan pilih kandidat:</p>
                </div>

                {{-- Candidate grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 max-w-6xl mx-auto">
                    <template x-for="c in candidates" :key="c.id">
                        <div @click="selectCandidate(c.id)"
                            :class="selectedCandidateId === c.id ?
                                'border-brand-lime bg-lime-50 -translate-y-1 shadow-2xl shadow-brand-lime/40' :
                                'border-transparent hover:border-brand-lime hover:-translate-y-1 hover:shadow-2xl hover:shadow-brand-lime/30'"
                            class="relative bg-white text-brand-dark rounded-3xl p-6 text-center cursor-pointer transition-all border-4">

                            {{-- No urut --}}
                            <div class="absolute top-3 left-3 w-11 h-11 rounded-full bg-brand-dark text-brand-lime flex items-center justify-center font-bold text-lg" x-text="c.no_urut"></div>

                            {{-- Foto --}}
                            <template x-if="c.foto">
                                <img :src="c.foto" :alt="c.nama" class="w-[120px] h-[120px] rounded-full object-cover mx-auto mb-4 border-4 border-brand-lime block">
                            </template>
                            <template x-if="!c.foto">
                                <div class="w-[120px] h-[120px] rounded-full bg-brand-lime text-brand-dark flex items-center justify-center text-5xl font-bold mx-auto mb-4"
                                    x-text="c.nama.charAt(0).toUpperCase()"></div>
                            </template>

                            {{-- Nama & kelas --}}
                            <div class="text-xl font-bold mb-1" x-text="c.nama"></div>
                            <div class="text-slate-500 text-sm mb-3">
                                <i class="bi bi-mortarboard"></i>
                                <span x-text="c.kelas"></span>
                            </div>

                            {{-- Visi --}}
                            <div class="text-sm text-slate-600 text-left max-h-20 overflow-hidden leading-relaxed" x-text="c.visi || ''"></div>
                        </div>
                    </template>
                </div>

                {{-- Submit button --}}
                <div class="text-center mt-8">
                    <button type="button" @click="confirmVote()" :disabled="!selectedCandidateId"
                        :class="selectedCandidateId
                            ?
                            'bg-brand-lime hover:bg-brand-limeHover text-brand-dark hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-lime/30 cursor-pointer' :
                            'bg-brand-lime/40 text-brand-dark/40 cursor-not-allowed'"
                        class="font-bold text-lg rounded-xl px-14 py-4 inline-flex items-center gap-2 transition">
                        <i class="bi bi-check-circle"></i> Konfirmasi Pilihan
                    </button>
                </div>
            </div>

            {{-- ==========================================
                 SCREEN: THANKS
                 ========================================== --}}
            <div x-show="screen === 'thanks'" class="min-h-screen flex items-center justify-center text-center p-4">
                <div>
                    <i class="bi bi-check-circle-fill text-brand-lime text-[8rem] block"></i>
                    <div class="text-3xl md:text-4xl font-bold text-brand-lime mt-5">Terima Kasih!</div>
                    <div class="text-white/70 mt-2">Suara Anda sudah tercatat</div>
                    <div class="text-white/60 text-sm mt-5">
                        Kembali ke layar awal dalam <span class="font-bold" x-text="thanksTimer">5</span>s
                    </div>
                </div>
            </div>

        </div>

        <script>
            function votingKiosk() {
                return {
                    // ==== Config dari Blade ====
                    SCAN_URL: @json(url('/scan/voting')),
                    DEVICE_ID: @json($device->id),
                    CSRF_TOKEN: @json(csrf_token()),
                    SUBMIT_URL: @json(route('device.voting.submit')),
                    STATUS_URL: @json(route('device.voting.status')),

                    // ==== State ====
                    screen: 'idle', // 'idle' | 'ballot' | 'thanks'
                    currentToken: @json($device->device_token),
                    timer: {{ max(0, now()->diffInSeconds($device->token_expired_at, false)) }},
                    totalVoted: 0,
                    totalVoters: 0,
                    voter: {
                        nama: '-',
                        nis: '-',
                        kelas: '-'
                    },
                    candidates: [],
                    selectedCandidateId: null,
                    thanksTimer: 5,

                    // ==== Runtime ====
                    qrCode: null,
                    pollId: null,
                    timerId: null,
                    thanksId: null,

                    init() {
                        this.renderQR(this.currentToken);
                        this.startCountdown(this.timer);

                        this.pollStatus();
                        this.pollId = setInterval(() => this.pollStatus(), 2000);
                    },

                    // ==== QR ====
                    renderQR(token) {
                        const container = document.getElementById('qrcode');
                        if (!container) return;
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

                    // ==== Timer ====
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

                    // ==== Polling ====
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

                                if (data.total_voted !== undefined) this.totalVoted = data.total_voted;
                                if (data.total_voters !== undefined) this.totalVoters = data.total_voters;

                                if (data.status === 'assigned' && this.screen !== 'ballot') {
                                    this.showBallot(data.voter, data.candidates);
                                } else if (data.status === 'idle' && this.screen === 'ballot') {
                                    this.showIdle();
                                } else if (data.status === 'idle' && data.token && data.token !== this.currentToken) {
                                    this.currentToken = data.token;
                                    this.renderQR(this.currentToken);
                                    this.startCountdown(30);
                                }
                            })
                            .catch(err => console.error('Poll error:', err));
                    },

                    // ==== Screen transition ====
                    showIdle() {
                        this.screen = 'idle';
                        this.selectedCandidateId = null;
                        this.voter = {
                            nama: '-',
                            nis: '-',
                            kelas: '-'
                        };
                        this.candidates = [];

                        // Tunggu DOM selesai render sebelum render QR
                        this.$nextTick(() => {
                            this.renderQR(this.currentToken);
                            this.startCountdown(30);
                        });
                    },

                    showBallot(voter, candidates) {
                        this.voter = voter;
                        this.candidates = candidates;
                        this.selectedCandidateId = null;
                        this.screen = 'ballot';
                    },

                    showThanks() {
                        this.screen = 'thanks';
                        this.thanksTimer = 5;

                        clearInterval(this.thanksId);
                        this.thanksId = setInterval(() => {
                            this.thanksTimer--;
                            if (this.thanksTimer <= 0) {
                                clearInterval(this.thanksId);
                                this.showIdle();
                            }
                        }, 1000);
                    },

                    // ==== Selection ====
                    selectCandidate(candidateId) {
                        this.selectedCandidateId = candidateId;
                    },

                    // ==== Submit ====
                    async confirmVote() {
                        if (!this.selectedCandidateId) return;

                        const ok = confirm('Yakin dengan pilihan Anda? Pilihan tidak dapat diubah.');
                        if (!ok) return;

                        try {
                            const res = await fetch(this.SUBMIT_URL, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.CSRF_TOKEN,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({
                                    device_id: this.DEVICE_ID,
                                    candidate_id: this.selectedCandidateId,
                                }),
                            });

                            const data = await res.json();

                            if (data.status === 'ok') {
                                this.showThanks();
                            } else {
                                alert('Error: ' + (data.message || 'Gagal menyimpan vote'));
                            }
                        } catch (err) {
                            alert('Error koneksi: ' + err.message);
                        }
                    },

                    // ==== Refresh QR ====
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
