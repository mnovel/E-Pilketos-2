<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Device Ditutup - Pilketos</title>

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

<body class="bg-brand-dark text-white min-h-screen flex items-center justify-center font-sans m-0">

    <div class="bg-white text-brand-dark rounded-3xl px-8 py-14 md:px-10 md:py-16 text-center max-w-lg w-[90%] shadow-2xl">

        {{-- Icon --}}
        <i class="bi bi-power text-rose-500 text-8xl block"></i>

        <h2 class="mt-6 mb-2 text-2xl font-bold">Device Voting Ditutup</h2>
        <p class="text-slate-500 mb-8">
            Device voting sudah ditutup. Siswa tidak bisa vote sampai device dibuka kembali.
        </p>

        {{-- Reopen button --}}
        <form action="{{ route('device.voting.reopen') }}" method="POST">
            @csrf
            <button type="submit"
                class="w-full bg-brand-lime hover:bg-brand-limeHover text-brand-dark font-bold text-lg rounded-xl py-4 inline-flex items-center justify-center gap-2 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-lime/30">
                <i class="bi bi-power"></i> Buka Device Lagi
            </button>
        </form>

        <p class="text-slate-500 text-sm mt-6 mb-0">
            <i class="bi bi-info-circle"></i> Tutup tab ini kalau sudah selesai.
        </p>
    </div>

</body>

</html>
