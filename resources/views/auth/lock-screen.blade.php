@extends('layouts.base', ['subtitle' => 'Lock Screen'])

@section('body-attribuet')
    class="authentication-bg"
@endsection

@section('content')
    @include('auth.partials.auth-style')

    <style>
        .lg-me {
            position: relative;
            width: 92px;
            height: 92px;
            margin: 0 0 14px;
        }

        .lg-me img {
            width: 92px;
            height: 92px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px var(--lg-line), 0 8px 20px rgba(20, 40, 75, .18);
        }

        .lg-me .badge-lock {
            position: absolute;
            right: -2px;
            bottom: 0;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: var(--lg-gold);
            border: 3px solid #fff;
        }

        .lg-me .badge-lock svg {
            width: 14px;
            height: 14px;
            stroke: var(--lg-navy);
        }

        .lg-back {
            margin: 18px 0 0;
            text-align: center;
            font-size: 13.5px;
            color: var(--lg-muted);
        }

        .lg-link {
            padding: 0;
            border: 0;
            background: none;
            color: var(--lg-blue);
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .lg-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 767.98px) {
            .lg-me {
                width: 80px;
                height: 80px;
            }

            .lg-me img {
                width: 80px;
                height: 80px;
            }
        }
    </style>

    @php
        $me = Auth::user();
        $loginError = session('error') ?: ($errors->any() ? $errors->first() : null);
    @endphp

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

            {{-- Unlock form --}}
            <div class="lg-form">
                <div class="lg-me">
                    <img src="{{ $me?->image_path ? asset($me->image_path) : asset('images/users/avatar-6.jpg') }}"
                        alt="{{ $me?->name ?? 'User' }}">
                    <span class="badge-lock">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="4.5" y="10.5" width="15" height="10" rx="2.5" />
                            <path d="M8 10.5V8a4 4 0 0 1 8 0v2.5" />
                        </svg>
                    </span>
                </div>

                <h2>Hi, {{ $me?->name ?? 'there' }}</h2>
                <p class="sub">Your session is locked. Enter your password to continue.</p>

                @if ($loginError)
                    <div class="lg-alert err" role="alert">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px">
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
                            <button type="button" class="eye" id="togglePw" aria-label="Show password" title="Show / hide password">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button class="lg-btn" type="submit" id="unlockBtn">Unlock</button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="lg-back">
                    @csrf
                    Not you? <button type="submit" class="lg-link">Sign in as someone else</button>
                </form>
                <p class="lg-foot" style="margin-top:18px">&copy; {{ date('Y') }} {{ config('academy.name', 'MS Football Academy') }}</p>
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