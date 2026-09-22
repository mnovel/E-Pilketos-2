<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pemilih - Pilketos</title>

    <meta name="description" content="Pendaftaran Pemilih Pilketos Digital">

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

            <p class="login-subtitle">Daftar sebagai pemilih Pilketos</p>

            {{-- Form Register --}}
            <form action="{{ route('register') }}" method="POST" enctype="multipart/form-data" id="registerForm" class="needs-validation" novalidate>
                @csrf

                {{-- NIS --}}
                <div class="login-form-group">
                    <label for="nis" class="login-form-label">NIS</label>
                    <div class="login-input-group">
                        <i class="bi bi-person-badge input-icon"></i>
                        <input type="text" id="nis" name="nis" class="login-input @error('nis') is-invalid @enderror" placeholder="Contoh: 12345" value="{{ old('nis') }}" required
                            autofocus>
                    </div>
                    @error('nis')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Nama Lengkap --}}
                <div class="login-form-group">
                    <label for="name" class="login-form-label">Nama Lengkap</label>
                    <div class="login-input-group">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" id="name" name="name" class="login-input @error('name') is-invalid @enderror" placeholder="Nama lengkap Anda" value="{{ old('name') }}"
                            required>
                    </div>
                    @error('name')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Kelas --}}
                <div class="login-form-group">
                    <label for="class_id" class="login-form-label">Kelas</label>
                    <div class="login-input-group">
                        <i class="bi bi-mortarboard input-icon"></i>
                        <select id="class_id" name="class_id" class="login-input @error('class_id') is-invalid @enderror" required style="padding-left: 3rem;">
                            <option value="">— Pilih Kelas —</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('class_id')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="login-form-group">
                    <label for="email" class="login-form-label">Email</label>
                    <div class="login-input-group">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="login-input @error('email') is-invalid @enderror" placeholder="name@example.com" value="{{ old('email') }}"
                            required>
                    </div>
                    @error('email')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Kartu Pelajar --}}
                <div class="login-form-group">
                    <label for="kartu_pelajar" class="login-form-label">Foto Kartu Pelajar</label>
                    <div class="login-input-group">
                        <i class="bi bi-card-image input-icon"></i>
                        <input type="file" id="kartu_pelajar" name="kartu_pelajar" class="login-input @error('kartu_pelajar') is-invalid @enderror" accept="image/jpeg,image/png,image/jpg"
                            style="padding-top: 0.65rem;" required>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                        Format JPG/PNG, maks 2MB
                    </small>
                    @error('kartu_pelajar')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="login-form-group">
                    <label for="password" class="login-form-label">Password</label>
                    <div class="login-input-group">
                        <i class="bi bi-shield-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="login-input login-input-password @error('password') is-invalid @enderror" placeholder="••••••••" required
                            autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" data-toggle-target="#password" aria-label="Tampilkan password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Konfirmasi Password --}}
                <div class="login-form-group">
                    <label for="password_confirmation" class="login-form-label">Konfirmasi Password</label>
                    <div class="login-input-group">
                        <i class="bi bi-shield-check input-icon"></i>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="login-input login-input-password" placeholder="••••••••" required
                            autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" data-toggle-target="#password_confirmation" aria-label="Tampilkan password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-login" id="btn-submit">
                    <span>Daftar Sekarang</span>
                    <i class="bi bi-arrow-right"></i>
                </button>

            </form>

            {{-- Info --}}
            <div class="login-divider">Info Pendaftaran</div>

            <p class="text-center text-muted mb-4" style="font-size: 0.8rem;">
                Akun akan diverifikasi oleh panitia sebelum dapat digunakan untuk memilih.
            </p>

            {{-- Footer --}}
            <p class="login-footer-text">
                Sudah punya akun? <a href="{{ route('login') }}">Login di sini</a>
            </p>

        </div>
    </div>

    {{-- Bootstrap JS --}}
    <script src="{{ asset('storage/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    {{-- Password toggle script --}}
    <script>
        document.querySelectorAll('.password-toggle-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const target = document.querySelector(this.dataset.toggleTarget);
                const icon = this.querySelector('i');

                if (target.type === 'password') {
                    target.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    target.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
    </script>

</body>

</html>
