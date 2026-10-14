@extends('layouts.base', ['subtitle' => 'Login'])

@section('body-attribuet')
    class="authentication-bg"
@endsection

@section('content')
    <style>
        :root {
            --lg-navy: #14284b;
            --lg-blue: #214b7d;
            --lg-gold: #f2b705;
            --lg-text: #1b2437;
            --lg-muted: #6b768b;
            --lg-line: #d9dfea;
        }

        body.authentication-bg,
        .authentication-bg {
            background: var(--lg-blue) !important;
        }

        .lg-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background:
                radial-gradient(circle at 15% 20%, rgba(242, 183, 5, .14), transparent 38%),
                radial-gradient(circle at 85% 85%, rgba(255, 255, 255, .10), transparent 42%),
                linear-gradient(135deg, var(--lg-navy), var(--lg-blue));
            font-family: Inter, "Segoe UI", Roboto, Arial, sans-serif;
        }

        .lg-card {
            width: 100%;
            max-width: 940px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 70px rgba(5, 15, 40, .45);
        }

        /* ---- brand side ---- */
        .lg-brand {
            position: relative;
            padding: 48px 40px;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: linear-gradient(160deg, var(--lg-navy), var(--lg-blue));
            overflow: hidden;
        }

        .lg-brand::before,
        .lg-brand::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, .12);
        }

        .lg-brand::before {
            width: 320px;
            height: 320px;
            right: -120px;
            top: -120px;
        }

        .lg-brand::after {
            width: 240px;
            height: 240px;
            left: -90px;
            bottom: -90px;
            border-color: rgba(242, 183, 5, .35);
        }

        .lg-brand>* {
            position: relative;
            z-index: 1;
        }

        .lg-logo {
            width: 120px;
            height: 120px;
            padding: 8px;
            border-radius: 50%;
            background: #fff;
            object-fit: contain;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .25);
            margin-bottom: 22px;
        }

        .lg-brand h1 {
            margin: 0 0 6px;
            font-size: 26px;
            line-height: 1.2;
            font-weight: 700;
            letter-spacing: .3px;
            color: #fff;
        }

        .lg-brand .tag {
            margin: 0;
            color: var(--lg-gold);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }


        /* ---- form side ---- */
        .lg-form {
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .lg-form h2 {
            margin: 0 0 4px;
            font-size: 24px;
            font-weight: 700;
            color: var(--lg-text);
        }

        .lg-form .sub {
            margin: 0 0 26px;
            font-size: 14px;
            color: var(--lg-muted);
        }

        .lg-alert {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 11px 14px;
            margin-bottom: 18px;
            border-radius: 10px;
            font-size: 13.5px;
            line-height: 1.4;
        }

        .lg-alert.err {
            background: #fdecec;
            color: #9b1c1c;
            border: 1px solid #f5c2c2;
        }

        .lg-alert.ok {
            background: #e8f6ee;
            color: #166534;
            border: 1px solid #bfe5cc;
        }

        .lg-field {
            margin-bottom: 18px;
        }

        .lg-field label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 600;
            color: var(--lg-text);
        }

        .lg-input {
            position: relative;
        }

        .lg-input input {
            width: 100%;
            height: 48px;
            padding: 0 44px 0 44px;
            font-size: 15px;
            color: var(--lg-text);
            background: #f6f8fb;
            border: 1.5px solid var(--lg-line);
            border-radius: 12px;
            outline: none;
            transition: border-color .15s, box-shadow .15s, background .15s;
            -webkit-appearance: none;
        }

        .lg-input input::placeholder {
            color: #98a2b5;
        }

        .lg-input input:focus {
            background: #fff;
            border-color: var(--lg-blue);
            box-shadow: 0 0 0 4px rgba(33, 75, 125, .14);
        }

        .lg-input.bad input {
            border-color: #e05757;
            background: #fff7f7;
        }

        .lg-input .lead {
            position: absolute;
            left: 14px;
            top: 50%;
            width: 19px;
            height: 19px;
            transform: translateY(-50%);
            stroke: #8793a8;
            pointer-events: none;
        }

        .lg-input .eye {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            width: 38px;
            height: 38px;
            border: 0;
            background: transparent;
            border-radius: 9px;
            display: grid;
            place-items: center;
            cursor: pointer;
            color: #8793a8;
        }

        .lg-input .eye:hover {
            background: #e9edf4;
            color: var(--lg-blue);
        }

        .lg-input .eye svg {
            width: 19px;
            height: 19px;
            stroke: currentColor;
        }

        .lg-err {
            margin-top: 6px;
            font-size: 12.5px;
            color: #c53030;
        }

        .lg-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 2px 0 22px;
        }

        .lg-check {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-size: 13.5px;
            color: var(--lg-text);
            cursor: pointer;
            user-select: none;
        }

        .lg-check input {
            width: 18px;
            height: 18px;
            accent-color: var(--lg-blue);
            cursor: pointer;
        }

        .lg-btn {
            width: 100%;
            height: 50px;
            border: 0;
            border-radius: 12px;
            font-size: 15.5px;
            font-weight: 600;
            letter-spacing: .2px;
            color: #fff;
            cursor: pointer;
            background: linear-gradient(135deg, var(--lg-blue), var(--lg-navy));
            box-shadow: 0 10px 22px rgba(20, 40, 75, .30);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            transition: transform .1s, box-shadow .15s, opacity .15s;
        }

        .lg-btn:hover {
            box-shadow: 0 14px 28px rgba(20, 40, 75, .38);
        }

        .lg-btn:active {
            transform: translateY(1px);
        }

        .lg-btn:disabled {
            opacity: .75;
            cursor: wait;
        }

        .lg-spin {
            width: 17px;
            height: 17px;
            border: 2px solid rgba(255, 255, 255, .45);
            border-top-color: #fff;
            border-radius: 50%;
            animation: lgspin .7s linear infinite;
        }

        @keyframes lgspin {
            to {
                transform: rotate(360deg);
            }
        }

        .lg-foot {
            margin: 26px 0 0;
            text-align: center;
            font-size: 12.5px;
            color: var(--lg-muted);
        }

        /* ---- tablet / phone ---- */
        @media (max-width: 767.98px) {
            .lg-page {
                align-items: flex-start;
                padding: 16px 14px 28px;
            }

            .lg-card {
                grid-template-columns: 1fr;
                max-width: 460px;
                margin-top: 4vh;
                border-radius: 18px;
            }

            .lg-brand {
                flex-direction: row;
                align-items: center;
                gap: 14px;
                padding: 20px 22px;
                flex-wrap: wrap;
                text-align: left;
            }

            .lg-logo {
                width: 62px;
                height: 62px;
                padding: 6px;
                margin: 0;
            }

            .lg-brand .txt {
                flex: 1;
                min-width: 0;
            }

            .lg-brand h1 {
                font-size: 19px;
                margin-bottom: 3px;
            }

            .lg-brand .tag {
                margin: 0;
                font-size: 10.5px;
                letter-spacing: 1.6px;
            }


            .lg-form {
                padding: 26px 22px 24px;
            }

            .lg-form h2 {
                font-size: 21px;
            }

            .lg-form .sub {
                margin-bottom: 20px;
            }

            /* 16px stops iPhone Safari zooming into the field */
            .lg-input input {
                font-size: 16px;
            }
        }

        @media (max-width: 360px) {
            .lg-brand .tag {
                display: none;
            }
        }
    </style>

    <div class="lg-page">
        <div class="lg-card">

            {{-- Brand --}}
            <div class="lg-brand">
                <img src="/images/ms-logo.png" alt="{{ config('academy.name') }}" class="lg-logo">
                <div class="txt">
                    <h1>{{ config('academy.name', 'MS Football Academy') }}</h1>
                    <p class="tag">Academy Management</p>
                </div>
            </div>

            {{-- Form --}}
            <div class="lg-form">
                <h2>Welcome back</h2>
                <p class="sub">Sign in to continue to your dashboard.</p>

                @if (session('status'))
                    <div class="lg-alert ok">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="lg-alert err" role="alert">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px">
                            <circle cx="12" cy="12" r="9.5" />
                            <path d="M12 7.5v5.5M12 16.5h.01" />
                        </svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" id="loginForm" novalidate>
                    @csrf

                    <div class="lg-field">
                        <label for="email">Email address</label>
                        <div class="lg-input {{ $errors->has('email') ? 'bad' : '' }}">
                            <svg class="lead" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect x="3" y="5" width="18" height="14" rx="2.5" />
                                <path d="m3.5 7 8.5 6 8.5-6" />
                            </svg>
                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                placeholder="you@example.com" autocomplete="username" autocapitalize="none"
                                spellcheck="false" inputmode="email" required autofocus>
                        </div>
                    </div>

                    <div class="lg-field">
                        <label for="password">Password</label>
                        <div class="lg-input {{ $errors->has('password') ? 'bad' : '' }}">
                            <svg class="lead" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect x="4.5" y="10.5" width="15" height="10" rx="2.5" />
                                <path d="M8 10.5V8a4 4 0 0 1 8 0v2.5" />
                            </svg>
                            <input type="password" id="password" name="password" placeholder="Enter your password"
                                autocomplete="current-password" required>
                            <button type="button" class="eye" id="togglePw" aria-label="Show password" title="Show / hide password">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="lg-row">
                        <label class="lg-check" for="remember">
                            <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            Remember me
                        </label>
                    </div>

                    <button class="lg-btn" type="submit" id="loginBtn">Sign In</button>
                </form>

                <p class="lg-foot">&copy; {{ date('Y') }} {{ config('academy.name', 'MS Football Academy') }}</p>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var pw = document.getElementById('password');
            var eye = document.getElementById('togglePw');
            eye.addEventListener('click', function() {
                var show = pw.type === 'password';
                pw.type = show ? 'text' : 'password';
                eye.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                eye.style.color = show ? '#214b7d' : '';
                pw.focus();
            });

            var form = document.getElementById('loginForm');
            var btn = document.getElementById('loginBtn');
            form.addEventListener('submit', function(e) {
                if (!form.email.value.trim() || !pw.value) {
                    e.preventDefault();
                    (form.email.value.trim() ? pw : form.email).focus();
                    return;
                }
                btn.disabled = true; // stops double submits on slow connections
                btn.innerHTML = '<span class="lg-spin"></span> Signing in...';
            });
        })();
    </script>
@endsection