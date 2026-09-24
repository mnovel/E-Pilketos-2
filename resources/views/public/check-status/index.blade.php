<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status - Pilketos</title>

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
                },
            },
        };
    </script>
</head>

<body class="bg-slate-100 min-h-screen flex flex-col font-sans m-0">

    {{-- ==========================================
         HEADER
         ========================================== --}}
    <header class="bg-brand-dark text-white py-5">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center flex-wrap gap-3">
            <a href="{{ url('/') }}" class="no-underline">
                <h3 class="mb-0 text-2xl font-extrabold text-brand-lime flex items-center gap-2">
                    <i class="bi bi-asterisk"></i> Pilketos
                </h3>
            </a>

            <div class="flex gap-2">
                @auth
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isOperator() ? route('operator.dashboard') : route('voter.dashboard')) }}"
                        class="bg-brand-lime hover:bg-brand-limeHover text-brand-dark font-semibold text-sm px-4 py-2 rounded-lg inline-flex items-center gap-1.5 transition hover:-translate-y-0.5">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="border border-white/40 hover:bg-white/10 text-white text-sm px-4 py-2 rounded-lg inline-flex items-center gap-1.5 transition">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                    <a href="{{ route('register') }}"
                        class="bg-brand-lime hover:bg-brand-limeHover text-brand-dark font-semibold text-sm px-4 py-2 rounded-lg inline-flex items-center gap-1.5 transition hover:-translate-y-0.5">
                        <i class="bi bi-person-plus"></i> Daftar
                    </a>
                @endauth
            </div>
        </div>
    </header>

    {{-- ==========================================
         CONTENT
         ========================================== --}}
    <main class="flex-1 py-16 px-4">
        <div class="max-w-xl mx-auto">

            {{-- CARD --}}
            <div class="bg-white rounded-3xl shadow-lg p-8 md:p-12 text-center">

                {{-- Icon --}}
                <div class="w-20 h-20 mx-auto rounded-full bg-brand-cream flex items-center justify-center mb-5">
                    <i class="bi bi-search text-3xl text-lime-700"></i>
                </div>

                <h3 class="text-2xl md:text-3xl font-extrabold text-brand-dark mb-2">Cek Status Pendaftaran</h3>
                <p class="text-slate-500 mb-8">
                    Masukkan NIS untuk melihat status pendaftaran Anda
                </p>

                {{-- Form --}}
                <form action="{{ route('cek-status.check') }}" method="POST">
                    @csrf

                    <div class="mb-5">
                        <input type="text" name="nis" value="{{ old('nis') }}" placeholder="Masukkan NIS" autofocus required
                            class="w-full text-center text-xl font-semibold tracking-widest border-2 rounded-xl px-4 py-4 bg-slate-50 text-brand-dark outline-none transition
                                      {{ $errors->has('nis') ? 'border-rose-400 focus:border-rose-500 focus:ring-4 focus:ring-rose-100' : 'border-slate-200 focus:border-brand-lime focus:ring-4 focus:ring-brand-lime/20' }}">

                        @error('nis')
                            <div class="text-rose-500 text-sm mt-2 text-left">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit"
                        class="w-full bg-brand-lime hover:bg-brand-limeHover text-brand-dark font-bold text-base rounded-xl py-4 inline-flex items-center justify-center gap-2 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-lime/30 cursor-pointer border-0">
                        <i class="bi bi-search"></i> Cek Status
                    </button>
                </form>

                {{-- Footer card --}}
                <div class="mt-8 pt-6 border-t border-slate-200">
                    <small class="text-slate-500">
                        Belum daftar?
                        <a href="{{ route('register') }}" class="font-semibold text-brand-mid hover:text-brand-dark hover:underline transition">
                            Daftar sekarang
                        </a>
                    </small>
                </div>
            </div>

            {{-- Info --}}
            <div class="bg-blue-50 text-blue-800 border border-blue-200 rounded-xl px-4 py-3 mt-6 text-sm inline-flex items-start gap-2 text-left w-full">
                <i class="bi bi-info-circle mt-0.5"></i>
                <span>
                    <strong>Info:</strong> Status akan <strong>Menunggu</strong> sampai
                    panitia memverifikasi akun Anda. Biasanya 1×24 jam kerja.
                </span>
            </div>

        </div>
    </main>

    {{-- ==========================================
         FOOTER
         ========================================== --}}
    <footer class="text-center py-4 text-slate-500 text-sm">
        &copy; {{ date('Y') }} Pilketos Digital
    </footer>

</body>

</html>
