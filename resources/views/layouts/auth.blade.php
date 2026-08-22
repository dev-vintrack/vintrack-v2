<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'VINTRACK')</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="48x48" href="images/favicon-48x48.png">
    <link href="{{ asset('vendor/vintrack/bootstrap-5.3.2.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/vintrack/bootstrap-icons-1.11.2.min.css') }}">
    @stack('styles')
    <style>
        body {
            background: #0f172a url('/images/vintrack_login.jpeg') top center / cover no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        @media (max-width: 768px) {
            body.auth-login-page {
                align-items: flex-start;
                padding: 0;
                background-size: 100% auto;
                background-attachment: scroll;
            }

            .auth-login-page .auth-container {
                margin-top: 66.6667vw;
            }
        }

        .auth-container {
            width: 100%;
            max-width: 380px;
            background: rgba(0, 0, 0, 0.65);
            border-radius: 12px;
            padding: 2rem;
            color: #fff;
        }

        .otp-input {
            width: 45px;
            height: 55px;
            text-align: center;
            font-size: 24px;
            margin: 4px;
            border: none;
            border-radius: 5px;
            color: #000;
        }
    </style>
</head>
<body class="@yield('body_class')">
    <div class="auth-container">
        @if(session('session_expired'))
            <div class="alert alert-warning" role="alert" aria-live="assertive">
                {{ session('session_expired') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @yield('content')
    </div>

    @stack('scripts')
    <script src="{{ asset('js/session-expiry-handler.js') }}"></script>
    <script src="{{ asset('vendor/vintrack/bootstrap-5.3.2.bundle.min.js') }}"></script>
</body>
</html>
