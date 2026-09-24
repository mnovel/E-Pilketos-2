<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Pilketos') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('storage/assets/images/favicon.ico') }}">

    {{-- Libraries --}}
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/apexcharts/apexcharts.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/flatpickr/flatpickr.min.css') }}">

    {{-- Main CSS --}}
    <link rel="stylesheet" href="{{ asset('storage/assets/css/main.css') }}">

    @stack('styles')
</head>

<body>

    @include('partials.sidebar')

    <div class="main-wrapper">

        @include('partials.navbar')

        {{-- Page Header --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                <p class="page-subtitle">@yield('page-subtitle', '')</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted-green">
                            Home
                        </a>
                    </li>
                    @yield('breadcrumb')
                </ol>
            </nav>
        </div>

        {{-- Content --}}
        @yield('content')

        @include('partials.footer')

    </div>

    {{-- ==========================================
         SCRIPTS
         ========================================== --}}
    <script src="{{ asset('storage/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('storage/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('storage/assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('storage/assets/js/dashboard.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ==========================================
        // SWEETALERT2 HELPERS
        // ==========================================
        const swalConfig = {
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            customClass: {
                popup: 'rounded-4 shadow-lg',
            },
        };

        /**
         * Toast — notifikasi kecil di pojok kanan atas
         */
        function swalToast(message, type = 'info') {
            const icons = {
                info: 'info',
                success: 'success',
                warning: 'warning',
                error: 'error',
            };

            return Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icons[type] || 'info',
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                },
            });
        }

        /**
         * Confirm — modal konfirmasi
         * Return: Promise<boolean>
         */
        async function swalConfirm({
            title = 'Konfirmasi',
            message = 'Yakin ingin melanjutkan?',
            icon = 'question',
            okText = 'Ya, Lanjutkan',
            cancelText = 'Batal',
            okColor = '#0d6efd',
        } = {}) {
            const result = await Swal.fire({
                ...swalConfig,
                title,
                text: message,
                icon,
                showCancelButton: true,
                confirmButtonText: okText,
                cancelButtonText: cancelText,
                confirmButtonColor: okColor,
                reverseButtons: true,
            });

            return result.isConfirmed;
        }

        /**
         * Prompt — minta input dari user
         * Return: Promise<string|null>
         */
        async function swalPrompt({
            title = 'Input',
            message = '',
            placeholder = '',
            okText = 'Submit',
            cancelText = 'Batal',
            inputType = 'textarea',
        } = {}) {
            const result = await Swal.fire({
                ...swalConfig,
                title,
                text: message,
                input: inputType,
                inputPlaceholder: placeholder,
                showCancelButton: true,
                confirmButtonText: okText,
                cancelButtonText: cancelText,
                reverseButtons: true,
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'Wajib diisi!';
                    }
                },
            });

            return result.isConfirmed ? result.value : null;
        }

        // ==========================================
        // FLASH MESSAGE → TOAST
        // ==========================================
        document.addEventListener('DOMContentLoaded', () => {
            @if (session('success'))
                swalToast(@json(session('success')), 'success');
            @endif

            @if (session('warning'))
                swalToast(@json(session('warning')), 'warning');
            @endif

            @if (session('error'))
                swalToast(@json(session('error')), 'error');
            @endif
        });
    </script>

    @stack('scripts')

</body>

</html>
