{{-- Receive-payment popup. Open with: openPayment(playerId, 'Player name', {label, fee, sessions}) - the plan is optional --}}
@include('partials.js-helpers')

@php
    $defaultPlan = \App\Models\Player::planFor(null);
    $payAdmin = in_array(auth()->user()?->type, ['Super Admin', 'Admin'], true);
@endphp

<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <form class="modal-content" id="paymentForm" method="POST" action="">
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Receive Payment</h5>
                    <small class="text-muted" id="payName"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div id="payMessage"></div>

                <div class="alert alert-light border d-flex align-items-center gap-2 py-2 mb-3" id="payPlan">
                    <iconify-icon icon="solar:tag-price-outline" class="fs-20 text-primary"></iconify-icon>
                    <span></span>
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label" for="payAmount">Amount (LKR)</label>
                        <input type="number" name="amount" id="payAmount" step="0.01" min="0" class="form-control form-control-lg"
                            value="{{ $defaultPlan['fee'] }}" inputmode="decimal" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label" for="paySessions">Sessions</label>
                        <input type="number" name="sessions" id="paySessions" min="1" max="64" class="form-control form-control-lg"
                            value="{{ $defaultPlan['sessions'] }}" inputmode="numeric" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="payMethod">Payment method</label>
                    <select name="method" id="payMethod" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank transfer</option>
                        <option value="card">Card</option>
                        <option value="online">Online</option>
                    </select>
                </div>

                @if ($payAdmin)
                    <div class="mb-3">
                        <label class="form-label" for="payDate">Paid on</label>
                        <input type="date" name="paid_on" id="payDate" class="form-control" max="{{ today()->toDateString() }}"
                            value="{{ today()->toDateString() }}">
                        <div class="form-text">Change only when entering an older payment.</div>
                    </div>
                @endif

                <div>
                    <label class="form-label" for="payNote">Note <span class="text-muted">(optional)</span></label>
                    <input type="text" name="note" id="payNote" maxlength="150" class="form-control" placeholder="Ex: paid by mother">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <iconify-icon icon="solar:check-circle-outline" class="me-1 align-middle"></iconify-icon> Save Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        var form = document.getElementById('paymentForm');
        var modalEl = document.getElementById('paymentModal');
        var box = document.getElementById('payMessage');
        var tpl = "{{ route('admin.players.payments.store', ['player' => '__ID__']) }}";
        var fallback = @json($defaultPlan);
        var current = null;

        window.openPayment = function(id, name, plan) {
            plan = plan || fallback;
            current = id;
            form.reset();
            form.setAttribute('action', tpl.replace('__ID__', id));
            document.getElementById('payName').textContent = name;
            document.getElementById('payAmount').value = plan.fee;
            document.getElementById('paySessions').value = plan.sessions;
            document.querySelector('#payPlan span').textContent =
                (plan.label ? plan.label + ': ' : '') + 'LKR ' + AC.fmt(plan.fee) + ' for ' + plan.sessions + ' sessions';
            box.innerHTML = '';
            var pd = document.getElementById('payDate');
            if (pd) {
                var t = "{{ today()->toDateString() }}";
                if (window.ACDate) ACDate.set(pd, t, {
                    max: t
                });
                else pd.value = t;
            }
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        };

        AC.form(form, {
            box: box,
            onSuccess: function(d) {
                var id = current;
                // refresh every "sessions left" badge for this player on the page
                document.querySelectorAll('[data-balance="' + id + '"]').forEach(function(el) {
                    el.innerHTML = d.badge;
                });
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                document.dispatchEvent(new CustomEvent('payment:saved', {
                    detail: {
                        id: id,
                        data: d
                    }
                }));

                // success message with an Undo button (stays 12 s, pauses while the mouse is on it)
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: d.message,
                    showConfirmButton: true,
                    confirmButtonText: 'Undo',
                    confirmButtonColor: '#475569',
                    showDenyButton: !!d.receipt_url,
                    denyButtonText: 'Receipt',
                    denyButtonColor: '#0f766e',
                    timer: 12000,
                    timerProgressBar: true,
                    didOpen: function(t) {
                        t.addEventListener('mouseenter', Swal.stopTimer);
                        t.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                }).then(async function(r) {
                    if (r.isDenied && d.receipt_url) window.open(d.receipt_url, '_blank');
                    if (r.isConfirmed && d.undo_url) await undoPayment(d.undo_url, {
                        confirm: false
                    });
                    // the page may refresh now (profile) - after the Undo chance is over
                    document.dispatchEvent(new CustomEvent('payment:settled', {
                        detail: {
                            id: id,
                            undone: !!r.isConfirmed
                        }
                    }));
                });
            }
        });
    })();
</script>
