
<?php if (! $__env->hasRenderedOnce('4fc2371c-786e-4dd6-8967-bcbd39680191')): $__env->markAsRenderedOnce('4fc2371c-786e-4dd6-8967-bcbd39680191'); ?>
    <script>
        window.AC = (function() {
            var csrf = '<?php echo e(csrf_token()); ?>';

            function esc(s) {
                var d = document.createElement('div');
                d.textContent = s == null ? '' : s;
                return d.innerHTML;
            }

            // fetch wrapper: always returns an object with success/message, never throws
            async function call(url, opts) {
                opts = opts || {};
                var init = {
                    method: opts.method || 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                };
                if (opts.body instanceof FormData) {
                    init.body = opts.body;
                } else if (opts.body) {
                    init.body = JSON.stringify(opts.body);
                    init.headers['Content-Type'] = 'application/json';
                }

                var res, data;
                try {
                    res = await fetch(url, init);
                } catch (e) {
                    return {
                        success: false,
                        message: 'No connection. Please check the internet and try again.'
                    };
                }
                try {
                    data = await res.json();
                } catch (e) {
                    data = {
                        success: false,
                        message: 'Unexpected server response (' + res.status + ').'
                    };
                }
                if (res.status === 419) data.message = 'Your session expired. Please refresh the page.';
                if (res.status === 403 && !data.message) data.message = 'You do not have permission to do this.';
                data.status_code = res.status;
                return data;
            }

            function errors(d) {
                if (d && d.errors) {
                    return Object.keys(d.errors).map(function(k) {
                        return [].concat(d.errors[k]).map(esc).join('<br>');
                    }).join('<br>');
                }
                return esc((d && d.message) || 'Something went wrong.');
            }

            // AJAX form: shows the message box, spinner on the button, redirects when the server says so
            function form(formEl, opts) {
                opts = opts || {};
                formEl.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    var box = opts.box || document.getElementById('message');
                    var btn = formEl.querySelector('[type=submit]');
                    var label = btn ? btn.innerHTML : '';
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
                    }

                    var data = await call(formEl.getAttribute('action'), {
                        body: new FormData(formEl)
                    });

                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = label;
                    }

                    if (data.success) {
                        if (box) box.innerHTML = '<div class="alert alert-success">' + esc(data.message) + '</div>';
                        if (opts.onSuccess) opts.onSuccess(data);
                        else if (data.redirect) setTimeout(function() {
                            window.location = data.redirect;
                        }, 600);
                    } else if (box) {
                        box.innerHTML = '<div class="alert alert-danger">' + errors(data) + '</div>';
                        box.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                    }
                });
            }

            function toast(message, icon) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: icon || 'success',
                    title: message,
                    showConfirmButton: false,
                    timer: 2200,
                    timerProgressBar: true
                });
            }

            async function ask(title, text, confirmText) {
                var r = await Swal.fire({
                    title: title,
                    text: text || '',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: confirmText || 'Yes'
                });
                return r.isConfirmed;
            }

            // confirm (optional) -> request -> toast -> callback
            async function act(url, o) {
                o = o || {};
                if (o.confirm && !(await ask(o.confirm.title, o.confirm.text, o.confirm.button))) return;
                var data = await call(url, {
                    method: o.method || 'POST'
                });
                if (data.success) {
                    toast(data.message);
                    if (o.onSuccess) o.onSuccess(data);
                } else {
                    Swal.fire('Error', data.message || 'Something went wrong.', 'error');
                }
            }

            function debounce(fn, ms) {
                var t;
                return function() {
                    var a = arguments,
                        self = this;
                    clearTimeout(t);
                    t = setTimeout(function() {
                        fn.apply(self, a);
                    }, ms);
                };
            }

            function fmt(n) {
                return Number(n || 0).toLocaleString('en-US');
            }

            return {
                call: call,
                form: form,
                esc: esc,
                errors: errors,
                toast: toast,
                ask: ask,
                act: act,
                debounce: debounce,
                fmt: fmt
            };
        })();
    </script>
<?php endif; ?>
<?php /**PATH F:\Nexas\ms-academy\resources\views/partials/js-helpers.blade.php ENDPATH**/ ?>