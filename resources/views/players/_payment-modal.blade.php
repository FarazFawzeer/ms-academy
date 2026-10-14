{{-- Receive-payment popup. Open with: openPayment(playerId, 'Player name') --}}
@include('partials.js-helpers')

<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
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

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Amount (LKR)</label>
                        <input type="number" name="amount" step="0.01" min="0" class="form-control"
                            value="{{ config('academy.fee') }}" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Sessions</label>
                        <input type="number" name="sessions" min="1" max="64" class="form-control"
                            value="{{ config('academy.sessions_per_package') }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment method</label>
                    <select name="method" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank transfer</option>
                        <option value="card">Card</option>
                        <option value="online">Online</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Note (optional)</label>
                    <input type="text" name="note" maxlength="150" class="form-control" placeholder="Ex: paid by mother">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save Payment</button>
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
        var current = null;

        window.openPayment = function(id, name) {
            current = id;
            form.setAttribute('action', tpl.replace('__ID__', id));
            document.getElementById('payName').textContent = name;
            box.innerHTML = '';
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        };

        AC.form(form, {
            box: box,
            onSuccess: function(d) {
                AC.toast(d.message);
                // refresh every "sessions left" badge for this player on the page
                document.querySelectorAll('[data-balance="' + current + '"]').forEach(function(el) {
                    el.innerHTML = d.badge;
                });
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                document.dispatchEvent(new CustomEvent('payment:saved', {
                    detail: {
                        id: current,
                        data: d
                    }
                }));
            }
        });
    })();
</script>
