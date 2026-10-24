<?php $__env->startSection('body-attribuet'); ?>
    class="authentication-bg"
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('auth.partials.auth-style', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="lg-page">
        <div class="lg-wrap">
            <div class="lg-card">

                
                <div class="lg-head">
                    <img src="/images/ms-logo.png" alt="" class="lg-logo">
                    <p class="name"><?php echo e(config('academy.name', 'MS Football Academy')); ?></p>
                    <p class="tag">Academy Management</p>
                </div>

                <div class="lg-intro">
                    <h1 class="lg-title">Welcome back</h1>
                    <p class="lg-sub">Sign in to your account</p>
                </div>

                <?php if(session('status')): ?>
                    <div class="lg-alert ok"><?php echo e(session('status')); ?></div>
                <?php endif; ?>

                <?php if($errors->any()): ?>
                    <div class="lg-alert err" role="alert">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9.5" />
                            <path d="M12 7.5v5.5M12 16.5h.01" />
                        </svg>
                        <span><?php echo e($errors->first()); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo e(route('login.post')); ?>" id="loginForm" novalidate>
                    <?php echo csrf_field(); ?>

                    <div class="lg-field">
                        <label for="email">Email address</label>
                        <div class="lg-input <?php echo e($errors->has('email') ? 'bad' : ''); ?>">
                            <svg class="lead" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect x="3" y="5" width="18" height="14" rx="2.5" />
                                <path d="m3.5 7 8.5 6 8.5-6" />
                            </svg>
                            <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>"
                                placeholder="you@example.com" autocomplete="username" autocapitalize="none"
                                spellcheck="false" inputmode="email" required autofocus>
                        </div>
                    </div>

                    <div class="lg-field">
                        <label for="password">Password</label>
                        <div class="lg-input <?php echo e($errors->has('password') ? 'bad' : ''); ?>">
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
                            <input type="checkbox" id="remember" name="remember" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                            Keep me signed in
                        </label>
                    </div>

                    <button class="lg-btn" type="submit" id="loginBtn">Sign in</button>
                </form>
            </div>

            <p class="lg-foot">&copy; <?php echo e(date('Y')); ?> <?php echo e(config('academy.name', 'MS Football Academy')); ?></p>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', ['subtitle' => 'Login'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Nexas\ms-academy\resources\views/auth/signin.blade.php ENDPATH**/ ?>