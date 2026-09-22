<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pilketos</title>

    <link rel="icon" type="image/png" href="{{ asset('storage/assets/images/favicon.ico') }}">

    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/css/main.css') }}">
</head>

<body>

    <div class="login-wrapper">
        <div class="login-bg-shape login-bg-shape-1"></div>
        <div class="login-bg-shape login-bg-shape-2"></div>

        <div class="login-card">

            {{-- Brand --}}
            <a href="/" class="login-brand text-decoration-none">
                <i class="bi bi-asterisk"></i>
                <span>Pilketos</span>
            </a>

            <p class="login-subtitle">Masuk untuk melanjutkan</p>

            {{-- Notifikasi sukses logout --}}
            @if (session('success'))
                <div class="alert alert-success py-2" style="font-size: 0.85rem;">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Notifikasi dari register --}}
            @if (session('info'))
                <div class="alert alert-info py-2" style="font-size: 0.85rem;">
                    {{ session('info') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" id="loginForm" class="needs-validation" novalidate>
                @csrf

                {{-- Email --}}
                <div class="login-form-group">
                    <label for="email" class="login-form-label">Email</label>
                    <div class="login-input-group">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="login-input @error('email') is-invalid @enderror" placeholder="name@example.com" value="{{ old('email') }}" required
                            autofocus>
                    </div>
                    @error('email')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="login-form-group">
                    <label for="password" class="login-form-label">Password</label>
                    <div class="login-input-group">
                        <i class="bi bi-shield-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="login-input login-input-password @error('password') is-invalid @enderror" placeholder="••••••••" required
                            autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" data-toggle-target="#password" aria-label="Tampilkan password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Options --}}
                <div class="login-options">
                    <label class="custom-control-label">
                        <input type="checkbox" class="custom-checkbox-input" name="remember" id="rememberMe" {{ old('remember') ? 'checked' : '' }}>
                        <span>Ingat Saya</span>
                    </label>
                    <a href="#" class="forgot-password-link">Lupa Password?</a>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-login" id="btn-submit">
                    <span>Masuk</span>
                    <i class="bi bi-arrow-right"></i>
                </button>

            </form>

            {{-- Footer --}}
            <p class="login-footer-text">
                Belum punya akun? <a href="{{ route('register') }}">Daftar Sekarang</a>
            </p>
            <p class="text-center text-sm mt-3" style="font-size: 0.85rem;">
                Belum verifikasi?
                <a href="{{ route('cek-status.index') }}" class="fw-medium">
                    Cek status di sini
                </a>
            </p>

        </div>
    </div>

    <script src="{{ asset('storage/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <script>
        // Toggle password
        document.querySelectorAll('.password-toggle-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const target = document.querySelector(this.dataset.toggleTarget);
                const icon = this.querySelector('i');

                if (target.type === 'password') {
                    target.type = 'text';
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    target.type = 'password';
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        });
    </script>

</body>

</html>
