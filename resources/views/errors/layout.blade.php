<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Error') - {{ config('app.name', 'Pilketos') }}</title>
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/assets/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1a2e1a 0%, #2d5a3d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-family: system-ui, -apple-system, sans-serif;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Dekorasi background */
        .bg-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            z-index: 0;
        }

        .bg-shape-1 {
            width: 400px;
            height: 400px;
            background: #c6f135;
            top: -100px;
            left: -100px;
        }

        .bg-shape-2 {
            width: 500px;
            height: 500px;
            background: #a8d92d;
            bottom: -150px;
            right: -150px;
        }

        .error-wrapper {
            position: relative;
            z-index: 1;
            max-width: 560px;
            width: 100%;
            text-align: center;
        }

        .error-card {
            background: white;
            color: #1a2e1a;
            border-radius: 28px;
            padding: 60px 40px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-icon-wrap {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 28px;
            position: relative;
            animation: pulse 2.5s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .error-icon-wrap i {
            font-size: 4rem;
        }

        .error-code {
            font-size: 5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 12px;
            letter-spacing: -2px;
        }

        .error-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .error-message {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .error-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-error {
            padding: 12px 28px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-primary-error {
            background: #c6f135;
            color: #1a2e1a;
        }

        .btn-primary-error:hover {
            background: #a8d92d;
            color: #1a2e1a;
            transform: translateY(-2px);
        }

        .btn-outline-error {
            background: transparent;
            color: #1a2e1a;
            border: 2px solid #e9ecef;
        }

        .btn-outline-error:hover {
            background: #f8f9fa;
            border-color: #c6f135;
            color: #1a2e1a;
        }

        .error-footer {
            margin-top: 40px;
            opacity: 0.7;
            font-size: 0.85rem;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 576px) {
            .error-card {
                padding: 40px 24px;
            }

            .error-code {
                font-size: 4rem;
            }

            .error-icon-wrap {
                width: 100px;
                height: 100px;
            }

            .error-icon-wrap i {
                font-size: 3rem;
            }

            .error-actions {
                flex-direction: column;
            }

            .btn-error {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    {{-- Background shapes --}}
    <div class="bg-shape bg-shape-1"></div>
    <div class="bg-shape bg-shape-2"></div>

    <div class="error-wrapper">

        <div class="error-card">

            {{-- Icon --}}
            <div class="error-icon-wrap" style="background: @yield('icon-bg', '#fff3cd');">
                <i class="bi @yield('icon', 'bi-exclamation-triangle-fill')" style="color: @yield('icon-color', '#cc9a06');"></i>
            </div>

            {{-- Code --}}
            <div class="error-code" style="color: @yield('icon-color', '#cc9a06');">
                @yield('code', '500')
            </div>

            {{-- Title --}}
            <h1 class="error-title">@yield('title', 'Terjadi Kesalahan')</h1>

            {{-- Message --}}
            <p class="error-message">@yield('message', 'Maaf, terjadi kesalahan. Silakan coba lagi.')</p>

            {{-- Actions --}}
            <div class="error-actions">
                @yield('actions')
            </div>

        </div>

        <div class="error-footer">
            <i class="bi bi-asterisk"></i> &copy; {{ date('Y') }} {{ config('app.name', 'Pilketos') }}
        </div>

    </div>

</body>

</html>
