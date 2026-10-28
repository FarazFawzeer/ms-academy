@extends('layouts.vertical', ['subtitle' => $player->name])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Players', 'subtitle' => 'Profile'])
    @include('partials.js-helpers')

    @php
        $isAdmin = in_array(auth()->user()->type, ['Super Admin', 'Admin']);
        $plan = $player->plan();
        $methods = ['cash' => 'Cash', 'bank' => 'Bank transfer', 'card' => 'Card', 'online' => 'Online', 'extra' => 'Extra days (free)'];
    @endphp

    <style>
        .pf-photo {
            width: 112px;
            height: 112px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(15, 23, 42, .12);
        }

        .pf-dl {
            margin: 0;
        }

        .pf-dl > div {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px solid rgba(100, 116, 139, .12);
            font-size: 14px;
        }

        .pf-dl > div:last-child {
            border-bottom: 0;
        }

        .pf-dl dt {
            font-weight: 400;
            color: #64748b;
            white-space: nowrap;
        }

        .pf-dl dd {
            margin: 0;
            text-align: right;
            font-weight: 500;
        }

        .pf-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .pf-stat {
            border: 1px solid rgba(100, 116, 139, .18);
            border-radius: 12px;
            padding: 12px 14px;
        }

        .pf-stat small {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .pf-stat b {
            font-size: 22px;
            color: #1e293b;
            line-height: 1;
        }

        .pf-stat .pf-sub {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
        }

        @media (max-width: 767.98px) {
            .pf-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .day-info {
            padding: 10px 12px;
            border-radius: 8px;
            background: rgba(100, 116, 139, .08);
            font-size: 13.5px;
            line-height: 1.5;
        }

        .day-pick {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .day-pick input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .day-pick label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            margin: 0;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
        }

        .day-pick label iconify-icon {
            font-size: 20px;
        }

        .day-pick input:checked + label.ok {
            border-color: #15803d;
            background: #15803d14;
            color: #15803d;
        }

        .day-pick input:checked + label.bad {
            border-color: #b91c1c;
            background: #b91c1c14;
            color: #b91c1c;
        }

        .day-pick input:focus-visible + label {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }

        .st-pill {
            display: inline-block;
            min-width: 92px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
        }
    </style>

    <div class="row g-3">
        {{-- Profile --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-center">
                        <img src="{{ $player->photo_url }}" alt="{{ $player->name }}" class="pf-photo mb-3">
                        <h4 class="mb-1">{{ $player->name }}</h4>
                        <p class="text-muted mb-2">{{ $player->player_no }}</p>
                        <div class="d-flex justify-content-center flex-wrap gap-2 mb-1">
                            {{ $player->ageGroupBadge() }} {{ $player->daysBadge() }}
                            @unless ($player->is_active)
                                {{ \App\Models\Player::softBadge('#4b5563', 'Inactive', 80) }}
                            @endunless
                        </div>
                    </div>

                    <dl class="pf-dl mt-3">
                        <div>
                            <dt>Date of birth</dt>
                            <dd>{{ $player->dob->format('d M Y') }} ({{ $player->age }} yrs)
                                @if ($nextMove)
                                    <br><small class="text-muted fw-normal">Moves up on {{ $nextMove->format('d M Y') }}</small>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Parent</dt>
                            <dd>{{ $player->guardian_name }}</dd>
                        </div>
                        <div>
                            <dt>Parent number</dt>
                            <dd><a href="tel:{{ $player->guardian_phone }}">{{ $player->guardian_phone }}</a></dd>
                        </div>
                        @if ($player->phone)
                            <div>
                                <dt>Player phone</dt>
                                <dd>{{ $player->phone }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt>Joined</dt>
                            <dd>{{ $player->joined_at?->format('d M Y') ?? '-' }}</dd>
                        </div>
                    </dl>

                    <div class="d-grid gap-2 mt-3">
                        <button type="button" class="btn btn-success ac-btn"
                            onclick="openPayment({{ $player->id }}, @js($player->name), @js($plan))">
                            <iconify-icon icon="solar:wallet-money-outline"></iconify-icon> Receive Payment
                        </button>
                        @if ($player->whatsapp_number)
                            <a href="https://wa.me/{{ $player->whatsapp_number }}" target="_blank" rel="noopener"
                                class="btn btn-soft-success ac-btn">
                                <iconify-icon icon="ic:baseline-whatsapp"></iconify-icon> WhatsApp Parent
                            </a>
                        @endif
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.players.edit', $player) }}" class="btn btn-soft-primary ac-btn flex-fill">
                                <iconify-icon icon="solar:pen-outline"></iconify-icon> Edit
                            </a>
                            <a href="{{ route('admin.players.cards', ['ids' => $player->id]) }}" target="_blank"
                                class="btn btn-soft-secondary ac-btn flex-fill">
                                <iconify-icon icon="solar:printer-outline"></iconify-icon> ID Card
                            </a>
                        </div>
                        @if ($isAdmin)
                            <button type="button" class="btn btn-soft-info ac-btn" id="extraBtn">
                                <iconify-icon icon="solar:gift-outline"></iconify-icon> Give Extra Days
                            </button>
                        @endif
                        <div class="d-flex justify-content-center flex-wrap gap-3">
                            <button type="button" class="btn btn-link btn-sm text-{{ $player->is_active ? 'warning' : 'success' }}"
                                id="toggleBtn">{{ $player->is_active ? 'Deactivate player' : 'Activate player' }}</button>
                            @if ($isAdmin)
                                <button type="button" class="btn btn-link btn-sm text-danger" id="deleteBtn">Delete player</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sessions, payments, attendance --}}
        <div class="col-lg-8">
            <div class="pf-stats mb-3">
                <div class="pf-stat">
                    <small>Sessions left</small>
                    <div data-balance="{{ $player->id }}">{{ $player->balanceBadge() }}</div>
                </div>
                <div class="pf-stat">
                    <small>Plan</small>
                    <b style="font-size:16px">{{ $plan['label'] }}</b>
                    <div class="pf-sub">LKR {{ number_format($plan['fee']) }} / {{ $plan['sessions'] }} sessions</div>
                </div>
                <div class="pf-stat">
                    <small>Attended</small>
                    <b>{{ $totalAttended }}</b>
                    <div class="pf-sub">practice days</div>
                </div>
                <div class="pf-stat">
                    <small>Missed</small>
                    <b>{{ $totalMissed }}</b>
                    <div class="pf-sub">practice days</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h5 class="card-title mb-0">Attendance</h5>
                        <p class="card-subtitle mb-0">Last 30 practice days. A missed day uses a session too, until the package
                            is used up.</p>
                    </div>
                    @if ($isAdmin)
                        <button type="button" class="btn btn-sm btn-soft-primary ac-btn btn-mark-day" data-date="">
                            <iconify-icon icon="solar:calendar-add-outline"></iconify-icon> Mark attendance
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="d-none d-sm-table-cell">Group</th>
                                    <th class="d-none d-sm-table-cell">Time</th>
                                    @if ($isAdmin)
                                        <th class="text-end">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($attendance as $a)
                                    @php
                                        [$c, $txt] = $a->present
                                            ? ($a->extra ? ['#2563eb', 'Present (extra)'] : ['#15803d', 'Present'])
                                            : ($a->counted ? ['#b91c1c', 'Absent'] : ['#6b7280', 'Absent (free)']);
                                    @endphp
                                    <tr>
                                        <td class="text-nowrap">{{ $a->date->format('D, d M') }}<span class="d-none d-sm-inline"> {{ $a->date->format('Y') }}</span></td>
                                        <td><span class="st-pill" style="background:{{ $c }}1a;color:{{ $c }}"
                                                @unless ($a->present || $a->counted) title="Not counted - the package was already used up" @endunless>{{ $txt }}</span>
                                        </td>
                                        <td class="d-none d-sm-table-cell">{{ $a->group ?? '-' }}</td>
                                        <td class="text-muted d-none d-sm-table-cell">
                                            @if ($a->present)
                                                {{ $a->time?->format('h:i A') }}
                                                <small class="d-none d-md-inline">&middot; {{ $a->method === 'qr' ? 'QR scan' : 'Manual' }}</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @if ($isAdmin)
                                            <td>
                                                <div class="ac-actions">
                                                    <button type="button" class="btn btn-sm btn-soft-secondary btn-mark-day"
                                                        data-date="{{ $a->date->toDateString() }}" title="Change this day">
                                                        <iconify-icon icon="solar:pen-outline"></iconify-icon><span class="ac-hide-xs">Change</span>
                                                    </button>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isAdmin ? 5 : 4 }}" class="text-center text-muted py-3">No attendance yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payments</h5>
                    <p class="card-subtitle mb-0">Last 10 payments and extra days.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Sessions</th>
                                    <th class="d-none d-sm-table-cell">Method</th>
                                    <th class="d-none d-md-table-cell">Note</th>
                                    @if ($payments->contains(fn($x) => $x->canBeUndoneBy(auth()->user())))
                                        <th class="text-end">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments as $pay)
                                    <tr>
                                        <td class="text-nowrap">{{ $pay->paid_at->format('d M') }}<span class="d-none d-sm-inline"> {{ $pay->paid_at->format('Y') }}</span></td>
                                        <td>{{ $pay->method === 'extra' ? 'Free' : 'LKR ' . number_format($pay->amount) }}</td>
                                        <td>+{{ $pay->sessions }}</td>
                                        <td class="d-none d-sm-table-cell">{{ $methods[$pay->method] ?? ucfirst($pay->method) }}</td>
                                        <td class="d-none d-md-table-cell text-muted">{{ $pay->note ?? '-' }}</td>
                                        @if ($payments->contains(fn($x) => $x->canBeUndoneBy(auth()->user())))
                                            <td>
                                                <div class="ac-actions">
                                                    @if ($pay->canBeUndoneBy(auth()->user()))
                                                        <button type="button" class="btn btn-sm btn-soft-danger btn-undo-pay"
                                                        data-url="{{ route('admin.payments.undo', $pay) }}" title="Undo this payment (entered by mistake)">
                                                        <iconify-icon icon="solar:undo-left-round-outline"></iconify-icon><span class="ac-hide-xs">Undo</span>
                                            </button>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No payments yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($isAdmin)
        {{-- Admin: mark one practice day by hand --}}
        <div class="modal fade" id="dayModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" id="dayForm">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0">Mark attendance</h5>
                            <small class="text-muted">{{ $player->name }} &middot; {{ $player->days_text }}</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="dayMessage"></div>
                        <div class="mb-2">
                            <label class="form-label" for="dayDate">Date</label>
                            <input type="date" class="form-control" id="dayDate" name="date" max="{{ today()->toDateString() }}"
                                value="{{ today()->toDateString() }}" required>
                        </div>
                        <div class="day-info mb-3" id="dayInfo" aria-live="polite">Choose a date.</div>

                        <label class="form-label d-block">Mark as</label>
                        <div class="day-pick">
                            <input type="radio" name="status" id="st_present" value="present">
                            <label for="st_present" class="ok"><iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                                <span id="lblPresent">Present</span></label>
                            <input type="radio" name="status" id="st_absent" value="absent">
                            <label for="st_absent" class="bad"><iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                                <span>Absent</span></label>
                        </div>
                        <div class="form-text mt-2">Present uses one session. Absent uses one only while sessions are left.
                            Changing a day puts its session back first.</div>
                        <button type="button" class="btn btn-link btn-sm text-danger px-0 mt-1 d-none" id="dayClear">
                            Clear this day (no record)</button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @include('players._payment-modal')

    <script>
        // new payment: refresh once the "Undo" chance in the message is over; after an undo straight away
        document.addEventListener('payment:settled', function() {
            location.reload();
        });
        document.addEventListener('payment:undone', function() {
            setTimeout(function() {
                location.reload();
            }, 700);
        });

        document.querySelectorAll('.btn-undo-pay').forEach(function(b) {
            b.addEventListener('click', function() {
                undoPayment(b.dataset.url);
            });
        });

        document.getElementById('toggleBtn').addEventListener('click', function() {
            var active = {{ $player->is_active ? 'true' : 'false' }};
            AC.act("{{ route('admin.players.toggle', $player) }}", {
                confirm: active ? {
                    title: 'Deactivate this player?',
                    text: 'They will be hidden from the scanner and no sessions are counted. Their history is kept.',
                    button: 'Yes, deactivate'
                } : null,
                onSuccess: function() {
                    setTimeout(function() {
                        location.reload();
                    }, 700);
                }
            });
        });

        @if ($isAdmin)
            (function() {
                var modalEl = document.getElementById('dayModal');
                var form = document.getElementById('dayForm');
                var input = document.getElementById('dayDate');
                var msg = document.getElementById('dayMessage');
                var info = document.getElementById('dayInfo');
                var clearBtn = document.getElementById('dayClear');
                var today = "{{ today()->toDateString() }}";
                var infoUrl = "{{ route('admin.players.day-info', $player) }}";
                var saveUrl = "{{ route('admin.players.day', $player) }}";
                var seq = 0;

                // what is recorded on the chosen date (practice, their day or not, present / absent)
                async function load() {
                    var date = input.value;
                    clearBtn.classList.add('d-none');
                    form.querySelectorAll('input[name=status]').forEach(function(r) {
                        r.checked = false;
                    });
                    document.getElementById('lblPresent').textContent = 'Present';
                    if (!date) {
                        info.textContent = 'Choose a date.';
                        return;
                    }
                    var mine = ++seq;
                    info.textContent = 'Checking...';
                    var d = await AC.call(infoUrl + '?date=' + encodeURIComponent(date), {
                        method: 'GET'
                    });
                    if (mine !== seq) return;
                    if (!d.success) {
                        info.textContent = d.message || 'Could not check this date.';
                        return;
                    }
                    var st = d.status === 'present' ? '<span class="text-success fw-semibold">Present</span>' :
                        d.status === 'absent' ? '<span class="text-danger fw-semibold">Absent</span>' :
                        '<span class="text-muted">Not marked</span>';
                    info.innerHTML = '<strong>' + AC.esc(d.label) + '</strong><br>' + AC.esc(d.practice) + ' &middot; ' + st +
                        (d.future ? '<br><span class="text-danger">Future days cannot be marked.</span>' : '');
                    if (d.extra) document.getElementById('lblPresent').textContent = 'Present (extra day)';
                    if (d.status) {
                        document.getElementById(d.status === 'present' ? 'st_present' : 'st_absent').checked = true;
                        clearBtn.classList.remove('d-none');
                    }
                }
                input.addEventListener('change', load);

                document.querySelectorAll('.btn-mark-day').forEach(function(b) {
                    b.addEventListener('click', function() {
                        msg.innerHTML = '';
                        var date = b.dataset.date || today;
                        if (window.ACDate) ACDate.set(input, date, {
                            max: today
                        });
                        else input.value = date;
                        load();
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    });
                });

                async function save(status) {
                    if (!input.value) {
                        msg.innerHTML = '<div class="alert alert-warning">Choose a date.</div>';
                        return;
                    }
                    if (input.value > today) {
                        msg.innerHTML = '<div class="alert alert-warning">Future days cannot be marked.</div>';
                        return;
                    }
                    var btn = form.querySelector('[type=submit]');
                    btn.disabled = true;
                    var d = await AC.call(saveUrl, {
                        body: {
                            date: input.value,
                            status: status
                        }
                    });
                    btn.disabled = false;
                    if (d.success) {
                        AC.toast(d.message);
                        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                        setTimeout(function() {
                            location.reload();
                        }, 800);
                    } else {
                        msg.innerHTML = '<div class="alert alert-danger">' + AC.errors(d) + '</div>';
                    }
                }

                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var st = form.querySelector('input[name=status]:checked');
                    if (!st) {
                        msg.innerHTML = '<div class="alert alert-warning">Choose Present or Absent.</div>';
                        return;
                    }
                    save(st.value);
                });

                clearBtn.addEventListener('click', async function() {
                    if (await AC.ask('Clear this day?', 'The record for this day is removed and its session is given back.', 'Yes, clear')) {
                        save('clear');
                    }
                });
            })();

            document.getElementById('deleteBtn').addEventListener('click', function() {
                deletePlayer({{ $player->id }}, @js($player->name), function() {
                    window.location = "{{ route('admin.players.index') }}";
                });
            });

            document.getElementById('extraBtn').addEventListener('click', async function() {
                var r = await Swal.fire({
                    title: 'Give extra days',
                    html: '<p class="text-muted mb-3" style="font-size:14px">Free sessions added to ' + AC.esc(@js($player->name)) +
                        '\'s package, for example when they missed weeks for a good reason.</p>' +
                        '<label class="form-label d-block text-start" for="xDays">Number of days</label>' +
                        '<input id="xDays" type="number" min="1" max="16" value="1" class="form-control mb-3" inputmode="numeric">' +
                        '<label class="form-label d-block text-start" for="xReason">Reason</label>' +
                        '<input id="xReason" type="text" maxlength="120" class="form-control" placeholder="Ex: Missed 3 weeks - injury">',
                    showCancelButton: true,
                    confirmButtonText: 'Give days',
                    confirmButtonColor: '#0f766e',
                    focusConfirm: false,
                    preConfirm: async function() {
                        var d = await AC.call("{{ route('admin.players.extra-days', $player) }}", {
                            body: {
                                days: document.getElementById('xDays').value,
                                reason: document.getElementById('xReason').value
                            }
                        });
                        if (!d.success) {
                            Swal.showValidationMessage(AC.errors(d));
                            return false;
                        }
                        return d;
                    }
                });
                if (r.isConfirmed) {
                    AC.toast(r.value.message);
                    setTimeout(function() {
                        location.reload();
                    }, 900);
                }
            });
        @endif
    </script>
@endsection
