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
                <div class="card-header">
                    <h5 class="card-title mb-0">Attendance</h5>
                    <p class="card-subtitle mb-0">Last 30 practice days. A missed day uses a session too, until the package is
                        used up.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="d-none d-sm-table-cell">Group</th>
                                    <th>Time</th>
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
                                        <td class="text-muted">
                                            @if ($a->present)
                                                {{ $a->time?->format('h:i A') }}
                                                <small class="d-none d-md-inline">&middot; {{ $a->method === 'qr' ? 'QR scan' : 'Manual' }}</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">No attendance yet.</td>
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

    @include('players._payment-modal')

    <script>
        // new payment -> reload so the payment table and balance are both fresh
        document.addEventListener('payment:saved', function() {
            setTimeout(function() {
                location.reload();
            }, 900);
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
