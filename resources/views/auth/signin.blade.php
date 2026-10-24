@extends('layouts.base', ['subtitle' => 'Login'])

@section('body-attribuet')
    class="authentication-bg"
@endsection

@section('content')
    @include('auth.partials.auth-style')

    <div class="lg-page">
        <div class="lg-wrap">
            <div class="lg-card">

                {{-- Brand --}}
                <div class="lg-head">
                    <img src="/images/ms-logo.png" alt="" class="lg-logo">
                    <p class="name">{{ config('academy.name', 'MS Football Academy') }}</p>
                    <p class="tag">Academy Management</p>
                </div>

                <div class="lg-intro">
                    <h1 class="lg-title">Welcome back</h1>
                    <p class="lg-sub">Sign in to your account</p>
                </div>

                @if (session('status'))
                    <div class="lg-alert ok">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="lg-alert err" role="alert">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

                    <div class="lg-row">
                        <label class="lg-check" for="remember">
                            <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            Keep me signed in
                        </label>
                    </div>

                    <button class="lg-btn" type="submit" id="loginBtn">Sign in</button>
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
