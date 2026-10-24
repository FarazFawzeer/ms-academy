@extends('layouts.base', ['subtitle' => 'Lock Screen'])

@section('body-attribuet')
    class="authentication-bg"
@endsection

@section('content')
    @include('auth.partials.auth-style')

    <style>
        .lg-me {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
            margin-bottom: 22px;
        }

        .lg-me .pic {
            position: relative;
            flex-shrink: 0;
        }

        .lg-me img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--lg-line-strong);
        }

        .lg-me .badge-lock {
            position: absolute;
            right: -3px;
            bottom: -3px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: var(--lg-gold);
            border: 2px solid var(--lg-card);
        }

        .lg-me .badge-lock svg {
            width: 12px;
            height: 12px;
            stroke: #1e293b;
        }

        .lg-me .lg-title {
            margin: 0;
        }

        .lg-me .lg-sub {
            margin: 2px 0 0;
        }

        /* the user's photo leads here: a smaller academy logo */
        .lg-head .lg-logo {
            width: 56px;
            height: 56px;
            padding: 5px;
            margin-bottom: 10px;
        }

        .lg-head {
            padding-bottom: 20px;
            margin-bottom: 22px;
            border-bottom: 1px solid var(--lg-line);
        }

        .lg-back {
            margin: 18px 0 0;
            text-align: center;
            font-size: 13.5px;
            color: var(--lg-muted);
        }
    </style>

    @php
        $me = Auth::user();
        $loginError = session('error') ?: ($errors->any() ? $errors->first() : null);
    @endphp

    <div class="lg-page">
        <div class="lg-wrap">
            <div class="lg-card">

                {{-- Brand --}}
                <div class="lg-head">
                    <img src="/images/ms-logo.png" alt="" class="lg-logo">
                    <p class="name">{{ config('academy.name', 'MS Football Academy') }}</p>
                    <p class="tag">Academy Management</p>
                </div>

                {{-- Who is locked --}}
                <div class="lg-me">
                    <span class="pic">
                        <img src="{{ $me?->image_path ? asset($me->image_path) : asset('images/users/avatar-6.jpg') }}"
                            alt="">
                        <span class="badge-lock">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect x="4.5" y="10.5" width="15" height="10" rx="2.5" />
                                <path d="M8 10.5V8a4 4 0 0 1 8 0v2.5" />
                            </svg>
                        </span>
                    </span>
                    <div class="min-w-0">
                        <h1 class="lg-title">Hi, {{ $me?->name ?? 'there' }}</h1>
                        <p class="lg-sub">Screen locked. Enter your password to continue.</p>
                    </div>
                </div>

                @if ($loginError)
                    <div class="lg-alert err" role="alert">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9.5" />
                            <path d="M12 7.5v5.5M12 16.5h.01" />
                        </svg>
                        <span>{{ $loginError }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" id="lockForm" novalidate>
                    @csrf
                    <input type="hidden" name="email" value="{{ $me?->email }}">

                    <div class="lg-field">
                        <label for="password">Password</label>
                        <div class="lg-input {{ $errors->has('password') ? 'bad' : '' }}">
                            <svg class="lead" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect x="4.5" y="10.5" width="15" height="10" rx="2.5" />
                                <path d="M8 10.5V8a4 4 0 0 1 8 0v2.5" />
                            </svg>
                            <input type="password" id="password" name="password" placeholder="Enter your password"
                                autocomplete="current-password" required autofocus>
                            <button type="button" class="eye" id="togglePw" aria-label="Show password"
                                aria-pressed="false" title="Show / hide password">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button class="lg-btn" type="submit" id="unlockBtn" style="margin-top:6px">Unlock</button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="lg-back">
                    @csrf
                    Not you? <button type="submit" class="lg-link">Sign in as someone else</button>
                </form>
            </div>

            <p class="lg-foot">&copy; {{ date('Y') }} {{ config('academy.name', 'MS Football Academy') }}</p>
        </div>
    </div>

    <script>
        (function() {
            var pw = document.getElementById('password');
            var eye = document.getElementById('togglePw');
            eye.addEventListener('click', function() {
                var show = pw.type === 'password';
                pw.type = show ? 'text' : 'password';
                eye.classList.toggle('on', show);
                eye.setAttribute('aria-pressed', show ? 'true' : 'false');
                eye.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                pw.focus();
            });

            var form = document.getElementById('lockForm');
            var btn = document.getElementById('unlockBtn');
            form.addEventListener('submit', function(e) {
                if (!pw.value) {
                    e.preventDefault();
                    pw.focus();
                    return;
                }
                btn.disabled = true;
                btn.innerHTML = '<span class="lg-spin"></span> Unlocking...';
            });
        })();
    </script>
@endsection
